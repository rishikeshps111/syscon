<?php

namespace App\Http\Controllers;

use App\Exports\SalaryComponentExport;
use App\Http\Requests\StoreSalaryComponentRequest;
use App\Http\Requests\UpdateSalaryComponentRequest;
use App\Models\Designation;
use App\Models\SalaryComponent;
use App\Models\SalaryComponentAssignment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class SalaryComponentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('salary-components.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('salary-components.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('salary-components.edit'), ['edit', 'update']),
            new Middleware(PermissionMiddleware::using('salary-components.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = SalaryComponent::with(['assignments.role', 'assignments.designation'])
                ->select(['id', 'code', 'component_name', 'type', 'created_at'])
                ->latest();

            if (request()->filled('role_id')) {
                $selectedRole = Role::find(request('role_id'));

                $query->whereHas('assignments', function ($assignmentQuery) use ($selectedRole) {
                    $assignmentQuery->where('role_id', request('role_id'));

                    if ($selectedRole?->name === 'Staff' && request()->filled('designation_id')) {
                        $assignmentQuery->where('designation_id', request('designation_id'));
                    }
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-check" value="' . $row->id . '">')
                ->addColumn('role_name', fn ($row) => $this->assignmentBadges($row))
                ->addColumn('designation_name', fn ($row) => $this->designationBadges($row))
                ->addColumn('type_label', fn ($row) => ucfirst($row->type))
                ->addColumn('action', fn ($row) => view('salary-component.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'role_name', 'designation_name', 'action'])
                ->make(true);
        }

        $roles = $this->componentRoles();
        $designations = $this->designations();

        return view('salary-component.index', compact('roles', 'designations'));
    }

    public function create()
    {
        return view('salary-component.form', [
            'roles' => $this->componentRoles(),
            'designations' => $this->designations(),
            'generatedCode' => generate_code(SalaryComponent::PREFIX_MODULE, ((int) SalaryComponent::max('id')) + 1, 3, 'SC'),
        ]);
    }

    public function store(StoreSalaryComponentRequest $request)
    {
        DB::transaction(function () use ($request) {
            $component = SalaryComponent::create($this->componentData($request->validated(), true));
            $component->code = generate_code(SalaryComponent::PREFIX_MODULE, $component->id, 3, 'SC');
            $component->save();
            $this->syncAssignments($component, $request->validated());
        });

        return redirect()
            ->route('salary-components.index')
            ->with('success', 'Salary component created successfully.');
    }

    public function show(SalaryComponent $salaryComponent) {}

    public function edit(SalaryComponent $salaryComponent)
    {
        $salaryComponent->load('assignments');

        return view('salary-component.form', [
            'record' => $salaryComponent,
            'roles' => $this->componentRoles(),
            'designations' => $this->designations(),
        ]);
    }

    public function update(UpdateSalaryComponentRequest $request, SalaryComponent $salaryComponent)
    {
        DB::transaction(function () use ($request, $salaryComponent) {
            $salaryComponent->update($this->componentData($request->validated()));
            $this->syncAssignments($salaryComponent, $request->validated());
        });

        return redirect()
            ->route('salary-components.index')
            ->with('success', 'Salary component updated successfully.');
    }

    public function destroy(SalaryComponent $salaryComponent)
    {
        $salaryComponent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salary component deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = SalaryComponent::with(['assignments.role', 'assignments.designation'])
            ->select([
                'id',
                'code',
                'component_name',
                'type',
                'is_applicable',
                'calculation_type',
                'default_value',
                'is_editable_in_payroll',
                'is_mandatory',
                'created_at',
            ]);

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new SalaryComponentExport($query), 'salary-components.xlsx');
    }

    private function componentRoles()
    {
        return Role::whereIn('name', ['Staff', 'Driver', 'Controller', 'Supervisor', 'Housekeeping'])
            ->orderByRaw("CASE name WHEN 'Staff' THEN 1 WHEN 'Driver' THEN 2 WHEN 'Controller' THEN 3 WHEN 'Supervisor' THEN 4 WHEN 'Housekeeping' THEN 5 ELSE 6 END")
            ->get(['id', 'name']);
    }

    private function designations()
    {
        return Designation::orderBy('name')->get(['id', 'name']);
    }

    private function componentData(array $data, bool $withDefaults = false): array
    {
        $componentData = collect($data)->except(['role_ids', 'designation_ids'])->all();

        if (! $withDefaults) {
            return $componentData;
        }

        return $componentData + [
            'is_applicable' => true,
            'calculation_type' => 'fixed',
            'default_value' => 0,
            'is_editable_in_payroll' => true,
            'is_mandatory' => false,
        ];
    }

    private function syncAssignments(SalaryComponent $component, array $data): void
    {
        $staffRole = Role::where('name', 'Staff')->where('guard_name', 'web')->first();
        $roleIds = collect($data['role_ids'] ?? [])->map(fn ($roleId) => (int) $roleId)->unique();
        $designationIds = collect($data['designation_ids'] ?? [])->map(fn ($designationId) => (int) $designationId)->unique();

        $assignments = [];

        foreach ($roleIds as $roleId) {
            if ($staffRole && $roleId === (int) $staffRole->id) {
                foreach ($designationIds as $designationId) {
                    $assignments[] = [
                        'salary_component_id' => $component->id,
                        'role_id' => $roleId,
                        'designation_id' => $designationId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                continue;
            }

            $assignments[] = [
                'salary_component_id' => $component->id,
                'role_id' => $roleId,
                'designation_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $component->assignments()->delete();
        SalaryComponentAssignment::insert($assignments);
    }

    private function assignmentLabel(SalaryComponent $component): string
    {
        return $component->assignments
            ->groupBy(fn ($assignment) => $assignment->role?->name ?? 'Role')
            ->map(function ($assignments, string $roleName) {
                $designations = $assignments
                    ->pluck('designation.name')
                    ->filter()
                    ->values();

                return $designations->isEmpty()
                    ? $roleName
                    : $roleName . ' (' . $designations->implode(', ') . ')';
            })
            ->implode(', ');
    }

    private function assignmentBadges(SalaryComponent $component): string
    {
        $classes = [
            'Staff' => 'bg-primary',
            'Driver' => 'bg-success',
            'Housekeeping' => 'bg-info',
            'Controller' => 'bg-warning text-dark',
            'Supervisor' => 'bg-info text-dark',
        ];

        $badges = $component->assignments
            ->groupBy(fn ($assignment) => $assignment->role?->name ?? 'Role')
            ->map(function ($assignments, string $roleName) use ($classes) {
                $class = $classes[$roleName] ?? 'bg-secondary';

                return '<span class="badge ' . $class . ' me-1 mb-1">' . e($roleName) . '</span>';
            })
            ->implode('');

        return $badges ?: '<span class="text-muted">-</span>';
    }

    private function designationBadges(SalaryComponent $component): string
    {
        $badges = $component->assignments
            ->pluck('designation.name')
            ->filter()
            ->unique()
            ->sort()
            ->map(fn ($designation) => '<span class="badge bg-light text-dark border me-1 mb-1">' . e($designation) . '</span>')
            ->implode('');

        return $badges ?: '<span class="text-muted">-</span>';
    }
}
