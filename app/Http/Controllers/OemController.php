<?php

namespace App\Http\Controllers;

use App\Exports\OemExport;
use App\Http\Requests\StoreOemRequest;
use App\Http\Requests\UpdateOemRequest;
use App\Models\District;
use App\Models\Location;
use App\Models\Oem;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class OemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('oems.view'), ['index', 'export']),
            new Middleware(PermissionMiddleware::using('oems.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('oems.edit'), ['edit', 'update']),
            new Middleware(PermissionMiddleware::using('oems.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of($this->filteredQuery())
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('type', fn ($row) => $row->oem_type)
                ->addColumn('state', fn ($row) => $row->state?->name ?? '-')
                ->addColumn('verification_status', fn ($row) => $row->is_verified
                    ? '<span class="status-green">Verified</span>'
                    : '<span class="status-orange">Pending</span>')
                ->addColumn('last_updated', fn ($row) => $row->updated_at?->format('d-m-Y') ?? '-')
                ->addColumn('status', fn ($row) => $this->statusBadge($row->status))
                ->addColumn('action', fn ($row) => view('oem.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'verification_status', 'status', 'action'])
                ->make(true);
        }

        return view('oem.index', [
            'states' => State::orderBy('name')->get(['id', 'name']),
            'statuses' => Oem::STATUSES,
        ]);
    }

    public function create()
    {
        return view('oem.form', array_merge($this->formData(), [
            'generatedCode' => $this->generateOemCode(((int) Oem::max('id')) + 1),
        ]));
    }

    public function store(StoreOemRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $oem = Oem::create($this->oemData($data) + [
                'oem_code' => null,
                'status' => 'Active',
                'is_verified' => false,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $oem->oem_code = $this->generateOemCode($oem->id);
            $oem->save();

            $this->syncContacts($oem, $data['contacts']);
            $this->syncAddresses($oem, $data['addresses']);
        });

        return redirect()->route('oems.index')->with('success', 'OEM created successfully.');
    }

    public function edit(Oem $oem)
    {
        return view('oem.form', array_merge($this->formData(), [
            'record' => $oem->load(['contacts', 'addresses']),
        ]));
    }

    public function update(UpdateOemRequest $request, Oem $oem)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $oem) {
            $oem->update($this->oemData($data) + [
                'updated_by' => auth()->id(),
            ]);

            $this->syncContacts($oem, $data['contacts']);
            $this->syncAddresses($oem, $data['addresses']);
        });

        return redirect()->route('oems.index')->with('success', 'OEM updated successfully.');
    }

    public function destroy(Oem $oem)
    {
        $oem->delete();

        return response()->json([
            'success' => true,
            'message' => 'OEM deleted successfully.',
        ]);
    }

    private function formData(): array
    {
        return [
            'states' => State::orderBy('name')->get(['id', 'name']),
            'districts' => District::orderBy('name')->get(['id', 'state_id', 'name']),
            'locations' => Location::orderBy('name')->get(['id', 'state_id', 'district_id', 'name', 'pincode']),
            'oemTypes' => Oem::OEM_TYPES,
            'registrationTypes' => Oem::REGISTRATION_TYPES,
            'addressTypes' => \App\Models\OemAddress::ADDRESS_TYPES,
        ];
    }

    private function oemData(array $data): array
    {
        return collect($data)->only([
            'state_id',
            'oem_name',
            'short_name',
            'oem_type',
            'registration_type',
            'gst_number',
            'pan_number',
            'cin_number',
            'remarks',
        ])->all();
    }

    private function syncContacts(Oem $oem, array $contacts): void
    {
        $oem->contacts()->delete();

        foreach ($contacts as $index => $contact) {
            $oem->contacts()->create($contact + [
                'is_primary' => $index === 0 && ! collect($contacts)->contains('is_primary', true),
            ]);
        }
    }

    private function syncAddresses(Oem $oem, array $addresses): void
    {
        $oem->addresses()->delete();

        foreach ($addresses as $address) {
            $oem->addresses()->create($address);
        }
    }

    private function generateOemCode(int $id): string
    {
        return generate_code('OEM Module', $id, 3, 'OEM');
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = $this->filteredQuery();

        if (! empty($ids)) {
            $query->whereIn('oems.id', $ids);
        }

        return Excel::download(new OemExport($query), 'oems.xlsx');
    }

    private function filteredQuery()
    {
        $query = Oem::with('state')->select('oems.*');

        if (request()->filled('search_text')) {
            $search = request('search_text');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('oems.oem_code', 'like', '%' . $search . '%')
                    ->orWhere('oems.oem_name', 'like', '%' . $search . '%')
                    ->orWhere('oems.gst_number', 'like', '%' . $search . '%')
                    ->orWhere('oems.pan_number', 'like', '%' . $search . '%');
            });
        }

        if (request()->filled('state_id')) {
            $query->where('state_id', request('state_id'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        if (request()->filled('date_from')) {
            $query->whereDate('updated_at', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $query->whereDate('updated_at', '<=', request('date_to'));
        }

        return $query->orderBy('updated_at', 'desc');
    }

    private function statusBadge(?string $status): string
    {
        return match ($status) {
            'Active' => '<span class="status-green">Active</span>',
            'Blocked' => '<span class="status-red">Blocked</span>',
            default => '<span class="status-orange">Inactive</span>',
        };
    }
}
