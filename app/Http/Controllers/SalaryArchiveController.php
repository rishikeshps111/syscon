<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Depot;
use App\Models\SalaryProcessing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class SalaryArchiveController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('salary-archives.view'), ['index', 'show']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalaryProcessing::query()
                ->with(['depot', 'role', 'creator', 'approver'])
                ->withCount('items')
                ->where('status', 'Approved')
                ->latest('approved_at');

            foreach (['year', 'month', 'depot_id', 'role_id'] as $field) {
                if ($request->filled($field)) {
                    $query->where($field, $request->input($field));
                }
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('month_name', fn ($row) => Carbon::create($row->year, $row->month, 1)->format('F'))
                ->addColumn('depot_name', fn ($row) => $row->depot?->name ?? '-')
                ->addColumn('role_name', fn ($row) => $row->role?->name ?? '-')
                ->addColumn('approved_by_name', fn ($row) => $row->approver?->name ?? '-')
                ->addColumn('approved_date_time', fn ($row) => $row->approved_at?->format('d-m-Y h:i A') ?? '-')
                ->addColumn('action', fn ($row) => '<a class="btn-view" href="'
                    . e(route('salary-archives.show', $row)) . '" title="View"><i class="fa-solid fa-eye"></i></a>')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('salary-archive.index', [
            'years' => range((int) date('Y') + 1, (int) date('Y') - 5),
            'months' => collect(range(1, 12))->mapWithKeys(fn ($month) => [
                $month => Carbon::create(null, $month, 1)->format('F'),
            ]),
            'depots' => Depot::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => Role::whereIn('name', array_keys(Attendance::ROLES))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(SalaryProcessing $salaryProcessing)
    {
        abort_unless($salaryProcessing->status === 'Approved', 404);

        $salaryProcessing->load([
            'depot',
            'role',
            'creator',
            'approver',
            'items.user.staffProfile.designation',
        ]);

        return view('salary-archive.show', [
            'processing' => $salaryProcessing,
            'items' => $salaryProcessing->items->sortBy(fn ($item) => $item->user?->name ?? '')->values(),
        ]);
    }
}
