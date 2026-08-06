<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalaryTemplateRequest;
use App\Http\Requests\UpdateSalaryTemplateRequest;
use App\Models\Designation;
use App\Models\SalaryComponent;
use App\Models\SalaryTemplate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class SalaryTemplateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('salary-templates.view'), ['index', 'components']),
            new Middleware(PermissionMiddleware::using('salary-templates.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('salary-templates.edit'), ['edit', 'update']),
            new Middleware(PermissionMiddleware::using('salary-templates.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of(SalaryTemplate::with(['role', 'designation', 'items'])->latest())
                ->addIndexColumn()
                ->addColumn('role_name', fn ($row) => $row->role?->name ?? '-')
                ->addColumn('designation_name', fn ($row) => $row->designation?->name ?? '-')
                ->addColumn('components_count', fn ($row) => $row->items->count())
                ->addColumn('action', fn ($row) => view('salary-template.partials.action', compact('row'))->render())
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('salary-template.index');
    }

    public function create()
    {
        return view('salary-template.form', $this->formData() + [
            'generatedCode' => generate_code('Salary Template Module', ((int) SalaryTemplate::max('id')) + 1, 3, 'ST'),
        ]);
    }

    public function store(StoreSalaryTemplateRequest $request)
    {
        DB::transaction(function () use ($request) {
            $template = SalaryTemplate::create([
                'role_id' => $request->integer('role_id'),
                'designation_id' => $request->integer('designation_id') ?: null,
            ]);
            $template->update(['code' => generate_code('Salary Template Module', $template->id, 3, 'ST')]);
            $this->syncItems($template, $request->validated('components'));
        });

        return redirect()->route('salary-templates.index')->with('success', 'Salary template created successfully.');
    }

    public function edit(SalaryTemplate $salaryTemplate)
    {
        $salaryTemplate->load('items');

        return view('salary-template.form', $this->formData() + ['record' => $salaryTemplate]);
    }

    public function update(UpdateSalaryTemplateRequest $request, SalaryTemplate $salaryTemplate)
    {
        DB::transaction(function () use ($request, $salaryTemplate) {
            $salaryTemplate->update([
                'role_id' => $request->integer('role_id'),
                'designation_id' => $request->integer('designation_id') ?: null,
            ]);
            $this->syncItems($salaryTemplate, $request->validated('components'));
        });

        return redirect()->route('salary-templates.index')->with('success', 'Salary template updated successfully.');
    }

    public function destroy(SalaryTemplate $salaryTemplate)
    {
        $salaryTemplate->delete();

        return response()->json(['success' => true, 'message' => 'Salary template deleted successfully.']);
    }

    public function components(Request $request)
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'designation_id' => ['nullable', 'integer', 'exists:designations,id'],
            'template_id' => ['nullable', 'integer', 'exists:salary_templates,id'],
        ]);
        $role = Role::findOrFail($data['role_id']);

        $components = SalaryComponent::query()
            ->whereHas('assignments', function ($query) use ($role, $data) {
                $query->where('role_id', $role->id);
                if ($role->name === 'Staff') {
                    $query->where('designation_id', $data['designation_id'] ?? 0);
                }
            })
            ->orderByRaw("CASE type WHEN 'earning' THEN 1 ELSE 2 END")
            ->orderBy('component_name')
            ->get(['id', 'component_name', 'type']);

        $selected = ! empty($data['template_id'])
            ? SalaryTemplate::find($data['template_id'])?->items()->pluck('amount', 'salary_component_id') ?? collect()
            : collect();

        return response()->json($components->map(fn ($component) => [
            'id' => $component->id,
            'name' => $component->component_name,
            'type' => $component->type,
            'selected' => $selected->has($component->id),
            'amount' => $selected[$component->id] ?? 0,
        ]));
    }

    private function formData(): array
    {
        return [
            'roles' => Role::whereIn('name', ['Staff', 'Driver', 'Controller', 'Supervisor', 'Housekeeping'])
                ->orderByRaw("CASE name WHEN 'Staff' THEN 1 WHEN 'Driver' THEN 2 WHEN 'Controller' THEN 3 ELSE 4 END")
                ->get(['id', 'name']),
            'designations' => Designation::orderBy('name')->get(['id', 'name']),
        ];
    }

    private function syncItems(SalaryTemplate $template, array $components): void
    {
        $template->items()->delete();
        $template->items()->createMany(
            collect($components)->map(fn ($amount, $componentId) => [
                'salary_component_id' => (int) $componentId,
                'amount' => (float) $amount,
            ])->values()->all()
        );
    }
}
