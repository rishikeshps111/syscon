<?php

namespace App\Http\Controllers;

use App\Exports\ShiftSettingExport;
use App\Http\Requests\StoreShiftSettingRequest;
use App\Http\Requests\UpdateShiftSettingRequest;
use App\Models\ShiftSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class ShiftSettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('shift-settings.view'), ['index', 'export']),
            new Middleware(PermissionMiddleware::using('shift-settings.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('shift-settings.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('shift-settings.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = ShiftSetting::select([
                'id',
                'code',
                'shift_name',
                'start_time',
                'end_time',
                'total_working_hours',
                'is_active',
                'created_at',
            ])->orderBy('created_at', 'desc');

            if (request()->filled('shift_timing')) {
                $query->where('shift_name', request('shift_timing'));
            }

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->editColumn('start_time', function ($row) {
                    return $row->formatted_start_time;
                })
                ->editColumn('end_time', function ($row) {
                    return $row->formatted_end_time;
                })
                ->addColumn('hours', function ($row) {
                    return number_format((float) $row->total_working_hours, 2);
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('shift-setting.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        $shiftNames = ShiftSetting::SHIFT_NAMES;

        return view('shift-setting.index', compact('shiftNames'));
    }

    public function create()
    {
        $generatedCode = $this->generateShiftCode(((int) ShiftSetting::max('id')) + 1);
        $shiftNames = ShiftSetting::SHIFT_NAMES;

        return view('shift-setting.form', compact('generatedCode', 'shiftNames'));
    }

    public function store(StoreShiftSettingRequest $request)
    {
        $data = $this->prepareShiftData($request->validated());
        $shiftSetting = ShiftSetting::create($data);
        $shiftSetting->code = $this->generateShiftCode($shiftSetting->id);
        $shiftSetting->save();

        return redirect()
            ->route('shift-settings.index')
            ->with('success', 'Shift setting created successfully.');
    }

    public function edit(ShiftSetting $shiftSetting)
    {
        $record = $shiftSetting;
        $shiftNames = ShiftSetting::SHIFT_NAMES;

        return view('shift-setting.form', compact('record', 'shiftNames'));
    }

    public function update(UpdateShiftSettingRequest $request, ShiftSetting $shiftSetting)
    {
        $shiftSetting->update($this->prepareShiftData($request->validated()));

        return redirect()
            ->route('shift-settings.index')
            ->with('success', 'Shift setting updated successfully.');
    }

    public function destroy(ShiftSetting $shiftSetting)
    {
        $shiftSetting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shift setting deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = ShiftSetting::select(
            'code',
            'shift_name',
            'number_of_shifts_per_day',
            'start_time',
            'end_time',
            'break_duration_minutes',
            'total_working_hours',
            'grace_time_minutes',
            'minimum_working_hours',
            'check_in_window_start',
            'check_in_window_end',
            'check_out_flexibility',
            'enable_overtime',
            'is_active',
            'created_at'
        );

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new ShiftSettingExport($query), 'shift-settings.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:shift_settings,id'],
            'status' => ['required', 'boolean'],
        ]);

        $shiftSetting = ShiftSetting::findOrFail($request->id);
        $shiftSetting->is_active = $request->status;
        $shiftSetting->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function prepareShiftData(array $data): array
    {
        $data['number_of_shifts_per_day'] = 2;
        $data['total_working_hours'] = $data['total_working_hours'] ?: $this->calculateWorkingHours(
            $data['start_time'],
            $data['end_time'],
            (int) $data['break_duration_minutes']
        );

        return $data;
    }

    private function calculateWorkingHours(string $startTime, string $endTime, int $breakMinutes): float
    {
        $start = strtotime('2000-01-01 ' . $startTime);
        $end = strtotime('2000-01-01 ' . $endTime);

        if ($end <= $start) {
            $end = strtotime('2000-01-02 ' . $endTime);
        }

        $workedMinutes = max(0, (($end - $start) / 60) - $breakMinutes);

        return round($workedMinutes / 60, 2);
    }

    private function generateShiftCode(int $id): string
    {
        return 'SH' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }
}
