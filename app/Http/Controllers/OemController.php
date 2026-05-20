<?php

namespace App\Http\Controllers;

use App\Exports\OemExport;
use App\Models\Oem;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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

    public function destroy(Oem $oem)
    {
        $oem->delete();

        return response()->json([
            'success' => true,
            'message' => 'OEM deleted successfully.',
        ]);
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
