<?php

namespace App\Http\Controllers;

use App\Exports\HolidayExport;
use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Models\BranchLocation;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Holiday;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class HolidayController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('holidays.view'), ['index', 'show', 'calendarView', 'calendar', 'export']),
            new Middleware(PermissionMiddleware::using('holidays.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('holidays.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('holidays.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = $this->filteredQuery();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->editColumn('holiday_date', function ($row) {
                    return $row->holiday_date->format('d M Y');
                })
                ->addColumn('type', function ($row) {
                    return $row->holiday_type_label;
                })
                ->addColumn('location', function ($row) {
                    return $row->applicable_location_label;
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('holiday.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('holiday.index', $this->formData());
    }

    public function create()
    {
        return view('holiday.form', array_merge($this->formData(), [
            'generatedCode' => $this->generateHolidayCode(((int) Holiday::max('id')) + 1),
        ]));
    }

    public function store(StoreHolidayRequest $request)
    {
        $holiday = Holiday::create($request->validated());
        $holiday->code = $this->generateHolidayCode($holiday->id);
        $holiday->save();

        return redirect()
            ->route('holidays.index')
            ->with('success', 'Holiday created successfully.');
    }

    public function show(Holiday $holiday)
    {
        $holiday->load(['state', 'branchLocation']);

        return view('holiday.show', compact('holiday'));
    }

    public function calendarView()
    {
        return view('holiday.calendar');
    }

    public function edit(Holiday $holiday)
    {
        $record = $holiday;

        return view('holiday.form', array_merge($this->formData(), compact('record')));
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday)
    {
        $holiday->update($request->validated());

        return redirect()
            ->route('holidays.index')
            ->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return response()->json([
            'success' => true,
            'message' => 'Holiday deleted successfully.',
        ]);
    }

    public function calendar(Request $request)
    {
        return response()->json(
            $this->filteredQuery()
                ->whereYear('holiday_date', $request->input('year', now()->year))
                ->get()
                ->map(fn($holiday) => [
                    'date' => $holiday->holiday_date->format('Y-m-d'),
                    'name' => $holiday->holiday_name,
                    'type' => $holiday->holiday_type_label,
                ])
        );
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = Holiday::query();

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new HolidayExport($query), 'holidays.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:holidays,id'],
            'status' => ['required', 'boolean'],
        ]);

        $holiday = Holiday::findOrFail($request->id);
        $holiday->is_active = $request->status;
        $holiday->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function filteredQuery()
    {
        $query = Holiday::query()
            ->with(['state', 'branchLocation'])
            ->select('holidays.*');

        if (request()->filled('search_text')) {
            $search = request('search_text');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('code', 'like', '%' . $search . '%')
                    ->orWhere('holiday_name', 'like', '%' . $search . '%');
            });
        }

        if (request()->filled('year')) {
            $query->whereYear('holiday_date', request('year'));
        }

        if (request()->filled('holiday_type')) {
            $query->where('holiday_type', request('holiday_type'));
        }

        if (request()->filled('location')) {
            $location = request('location');
            $query->where(function ($subQuery) use ($location) {
                $subQuery->where('applicable_location', $location)
                    ->orWhereHas('state', fn($stateQuery) => $stateQuery->where('name', 'like', '%' . $location . '%'))
                    ->orWhereHas('branchLocation', fn($branchQuery) => $branchQuery->where('name', 'like', '%' . $location . '%'));
            });
        }

        if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
            $query->where('is_active', request('status'));
        }

        return $query->orderBy('created_at', 'desc');
    }

    private function formData(): array
    {
        return [
            'holidayTypes' => Holiday::TYPES,
            'locationTypes' => Holiday::LOCATIONS,
            'applicableForOptions' => Holiday::APPLICABLE_FOR,
            'durationOptions' => Holiday::DURATIONS,
            'states' => State::orderBy('name')->get(['id', 'name']),
            'branches' => BranchLocation::orderBy('name')->get(['id', 'name']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'designations' => Designation::orderBy('name')->get(['id', 'name']),
            'years' => range((int) now()->year - 2, (int) now()->year + 2),
        ];
    }

    private function generateHolidayCode(int $id): string
    {
        return generate_code('Holiday Module', $id, 3, 'HOL');
    }
}
