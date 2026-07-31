<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\DepotAssignment;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\DepotAssignmentReportingManagers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class DepotAssignmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    public function index(Request $request)
    {
        $module = (string) $request->route('module');
        $subject = (int) $request->route('subject');
        $context = $this->context($module, $subject, true);
        abort_unless(auth()->user()->can($context['view_permission']), 403);

        if ($request->ajax()) {
            $query = DepotAssignment::with(['depot', 'reportingManager.roles'])
                ->where('assignable_type', $context['type'])
                ->where('assignable_id', $context['model']->getKey())
                ->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('depot_name', fn ($row) => $row->depot?->name ?? '-')
                ->addColumn('reporting_to_name', fn ($row) => $this->reportingManagerLabel($row))
                ->addColumn('from_date_display', fn ($row) => $row->from_date?->format('d-m-Y') ?? '-')
                ->addColumn('to_date_display', fn ($row) => $row->to_date?->format('d-m-Y') ?? '-')
                ->addColumn('status_badge', fn ($row) => $this->statusBadge($row->date_status))
                ->addColumn('action', function ($row) use ($context) {
                    if (! auth()->user()->can($context['edit_permission'])) {
                        return '<span class="text-muted">No Access</span>';
                    }

                    $editButton = '<button type="button" class="btn-edit edit-assignment" title="Edit" data-bs-toggle="modal" data-bs-target="#depotAssignmentModal"'
                        . ' data-url="' . e(route('depot-assignments.update', $row->id)) . '"'
                        . ' data-depot-id="' . e($row->depot_id) . '"'
                        . ' data-reporting-to="' . e($row->reporting_to) . '"'
                        . ' data-from-date="' . e($row->from_date?->format('Y-m-d')) . '"'
                        . ' data-to-date="' . e($row->to_date?->format('Y-m-d')) . '">'
                        . '<i class="fa-solid fa-pen-to-square"></i></button>';

                    $deleteButton = '<button type="button" class="btn-delete" onclick="deleteDepotAssignment(' . $row->id . ')" title="Delete">'
                        . '<i class="fa-solid fa-trash"></i></button>';

                    return '<div class="action-btns justify-content-center">' . $editButton . $deleteButton . '</div>';
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('depot-assignment.index', [
            'module' => $module,
            'subject' => $context['model'],
            'title' => $context['title'],
            'backRoute' => route($context['back_route']),
            'storeUrl' => route($context['store_route'], $context['model']->getKey()),
            'canEdit' => auth()->user()->can($context['edit_permission']),
            'depots' => Depot::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'details' => $this->details($context),
            'requiresReportingManager' => in_array($context['type'], ['driver', 'controller'], true),
        ]);
    }

    public function reportingManagers(Request $request)
    {
        $data = $request->validate([
            'module' => ['required', 'string', 'in:driver,controller'],
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
        ]);

        abort_unless(auth()->user()->can($data['module'] . '-management.view'), 403);

        return response()->json(
            DepotAssignmentReportingManagers::query($data['module'], (int) $data['depot_id'])
                ->with('roles:id,name')
                ->orderBy('users.name')
                ->get(['users.id', 'users.code', 'users.name'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'code' => $user->code,
                    'name' => $user->name,
                    'role' => $user->roles->pluck('name')->first(fn ($role) => in_array($role, ['Supervisor', 'Controller'], true)),
                ])
        );
    }

    public function depotIndex(Request $request, Depot $depot)
    {
        abort_unless(auth()->user()->can('depots.view'), 403);

        if ($request->input('export') === 'csv') {
            return $this->depotAssignmentsCsv($depot, $request);
        }

        if ($request->ajax()) {
            return DataTables::of($this->depotAssignmentsQuery($depot, $request))
                ->addIndexColumn()
                ->addColumn('module_name', fn ($row) => $this->moduleLabel($row->assignable_type))
                ->addColumn('assigned_to', fn ($row) => $this->assignedToLabel($row))
                ->addColumn('from_date_display', fn ($row) => $row->from_date?->format('d-m-Y') ?? '-')
                ->addColumn('to_date_display', fn ($row) => $row->to_date?->format('d-m-Y') ?? '-')
                ->addColumn('status_badge', fn ($row) => $this->statusBadge($row->date_status))
                ->rawColumns(['status_badge'])
                ->make(true);
        }

        return view('depot.assignments', [
            'depot' => $depot->load(['state', 'district', 'location']),
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    public function store(Request $request)
    {
        $module = (string) $request->route('module');
        $subject = (int) $request->route('subject');
        $context = $this->context($module, $subject, true);
        abort_unless(auth()->user()->can($context['edit_permission']), 403);

        $data = $this->validatedData($request, $context['type']);
        $this->ensureDateRangeIsAvailable($context['type'], $context['model']->getKey(), $data);

        $assignment = DepotAssignment::create($data + [
            'assignable_type' => $context['type'],
            'assignable_id' => $context['model']->getKey(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->syncCurrentDepot($context['type'], $context['model']);

        return response()->json([
            'success' => true,
            'message' => 'Depot assignment saved successfully.',
            'data' => $assignment,
        ], 201);
    }

    public function update(Request $request, DepotAssignment $depotAssignment)
    {
        $context = $this->context($depotAssignment->assignable_type, $depotAssignment->assignable_id);
        abort_unless(auth()->user()->can($context['edit_permission']), 403);

        $data = $this->validatedData($request, $context['type']);
        $this->ensureDateRangeIsAvailable(
            $context['type'],
            $context['model']->getKey(),
            $data,
            $depotAssignment
        );

        $depotAssignment->update($data + [
            'assignable_type' => $context['type'],
            'assignable_id' => $context['model']->getKey(),
            'updated_by' => auth()->id(),
        ]);
        $this->syncCurrentDepot($context['type'], $context['model']);

        return response()->json([
            'success' => true,
            'message' => 'Depot assignment updated successfully.',
            'data' => $depotAssignment->fresh(),
        ]);
    }

    public function destroy(DepotAssignment $depotAssignment)
    {
        $context = $this->context($depotAssignment->assignable_type, $depotAssignment->assignable_id);
        abort_unless(auth()->user()->can($context['edit_permission']), 403);

        $type = $context['type'];
        $model = $context['model'];
        $depotAssignment->delete();
        $this->syncCurrentDepot($type, $model);

        return response()->json([
            'success' => true,
            'message' => 'Depot assignment deleted successfully.',
        ]);
    }

    private function validatedData(Request $request, string $module): array
    {
        $rules = [
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reporting_to' => in_array($module, ['driver', 'controller'], true)
                ? [
                    'required',
                    'integer',
                    'exists:users,id',
                    function (string $attribute, mixed $value, \Closure $fail) use ($request, $module) {
                        $eligible = DepotAssignmentReportingManagers::query(
                            $module,
                            (int) $request->input('depot_id')
                        )->whereKey($value)->exists();

                        if (! $eligible) {
                            $fail('The selected reporting manager is not eligible for this depot.');
                        }
                    },
                ]
                : ['nullable'],
        ];

        return $request->validate($rules);
    }

    private function ensureDateRangeIsAvailable(string $type, int $id, array $data, ?DepotAssignment $current = null): void
    {
        $exists = DepotAssignment::query()
            ->where('assignable_type', $type)
            ->where('assignable_id', $id)
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->whereDate('from_date', '<=', $data['to_date'])
            ->whereDate('to_date', '>=', $data['from_date'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'from_date' => 'A depot assignment already exists for the selected date range.',
            ]);
        }
    }

    private function context(string $module, int $id, bool $strictRole = false): array
    {
        return match ($this->normalizedType($module, $id)) {
            'driver' => $this->userContext('driver', $id, 'Driver', 'driverProfile', 'driver-management', $strictRole),
            'controller' => $this->userContext('controller', $id, 'Controller', 'controllerProfile', 'controller-management', $strictRole),
            'supervisor' => $this->userContext('supervisor', $id, 'Supervisor', 'supervisorProfile', 'supervisor-management', $strictRole),
            'vehicle' => $this->vehicleContext($id),
            default => abort(404),
        };
    }

    private function normalizedType(string $type, int $id): string
    {
        if ($type === User::class || $type === 'App\\Models\\User') {
            $user = User::with('roles')->findOrFail($id);

            if ($user->hasRole('Driver')) {
                return 'driver';
            }

            if ($user->hasRole('Controller')) {
                return 'controller';
            }

            if ($user->hasRole('Supervisor')) {
                return 'supervisor';
            }
        }

        if ($type === Vehicle::class || $type === 'App\\Models\\Vehicle') {
            return 'vehicle';
        }

        return $type;
    }

    private function userContext(string $type, int $id, string $role, string $profileRelation, string $routePrefix, bool $strictRole = false): array
    {
        $user = User::with(['roles', $profileRelation . '.depot'])->findOrFail($id);
        abort_if($strictRole && ! $user->hasRole($role), 404);

        return [
            'type' => $type,
            'model' => $user,
            'title' => $role . ' Depot Assignments',
            'profile_relation' => $profileRelation,
            'view_permission' => $routePrefix . '.view',
            'edit_permission' => $routePrefix . '.edit',
            'back_route' => $routePrefix . '.index',
            'store_route' => $routePrefix . '.depot-assignments.store',
        ];
    }

    private function vehicleContext(int $id): array
    {
        return [
            'type' => 'vehicle',
            'model' => Vehicle::with('depot')->findOrFail($id),
            'title' => 'Vehicle Depot Assignments',
            'profile_relation' => null,
            'view_permission' => 'vehicles.view',
            'edit_permission' => 'vehicles.edit',
            'back_route' => 'vehicles.index',
            'store_route' => 'vehicles.depot-assignments.store',
        ];
    }

    private function details(array $context): array
    {
        /** @var User|Vehicle $model */
        $model = $context['model'];

        if ($context['type'] === 'vehicle') {
            return [
                'Code' => $model->vehicle_code,
                'Name' => $model->vehicle_no,
                'Current Depot' => $model->depot?->name ?: '-',
                'Status' => $model->status ?: '-',
            ];
        }

        $profile = $model->{$context['profile_relation']};

        return [
            'Code' => $model->code,
            'Name' => $model->name,
            'Current Depot' => $profile?->depot?->name ?: '-',
            'Phone' => $model->full_phone ?: '-',
        ];
    }

    private function syncCurrentDepot(string $type, Model $model): void
    {
        $assignment = DepotAssignment::where('assignable_type', $type)
            ->where('assignable_id', $model->getKey())
            ->whereDate('from_date', '<=', today())
            ->whereDate('to_date', '>=', today())
            ->latest('from_date')
            ->first();

        if (! $assignment) {
            return;
        }

        match ($type) {
            'driver' => $model->driverProfile?->update(['depot_id' => $assignment->depot_id]),
            'controller' => $model->controllerProfile?->update(['depot_id' => $assignment->depot_id]),
            'supervisor' => $model->supervisorProfile?->update(['depot_id' => $assignment->depot_id]),
            'vehicle' => $model->update(['depot_id' => $assignment->depot_id]),
            default => null,
        };
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'active' => '<span class="status-green">Active</span>',
            'upcoming' => '<span class="status-orange">Upcoming</span>',
            default => '<span class="status-red">Expired</span>',
        };
    }

    private function depotAssignmentsQuery(Depot $depot, Request $request)
    {
        return DepotAssignment::query()
            ->with('depot')
            ->where('depot_id', $depot->id)
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('from_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('to_date', '<=', $request->date_to))
            ->orderByDesc('from_date');
    }

    private function depotAssignmentsCsv(Depot $depot, Request $request)
    {
        $fileName = Str::slug($depot->name ?: 'depot') . '-assignments.csv';
        $assignments = $this->depotAssignmentsQuery($depot, $request)->get();

        return response()->streamDownload(function () use ($assignments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SL No', 'Module', 'Assigned To', 'From Date', 'To Date', 'Status']);

            foreach ($assignments as $index => $assignment) {
                fputcsv($handle, [
                    $index + 1,
                    $this->moduleLabel($assignment->assignable_type),
                    $this->assignedToLabel($assignment),
                    $assignment->from_date?->format('d-m-Y'),
                    $assignment->to_date?->format('d-m-Y'),
                    Str::title($assignment->date_status),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    private function moduleLabel(string $type): string
    {
        return match ($this->displayType($type)) {
            'driver' => 'Driver',
            'controller' => 'Controller',
            'supervisor' => 'Supervisor',
            'vehicle' => 'Vehicle',
            default => Str::title($type),
        };
    }

    private function displayType(string $type): string
    {
        return match ($type) {
            Vehicle::class, 'App\\Models\\Vehicle' => 'vehicle',
            User::class, 'App\\Models\\User' => 'user',
            default => $type,
        };
    }

    private function assignedToLabel(DepotAssignment $assignment): string
    {
        $type = $this->normalizedType($assignment->assignable_type, $assignment->assignable_id);

        if ($type === 'vehicle') {
            $vehicle = Vehicle::find($assignment->assignable_id);

            return trim(($vehicle?->vehicle_code ? $vehicle->vehicle_code . ' - ' : '') . ($vehicle?->vehicle_no ?? '')) ?: '-';
        }

        $user = User::find($assignment->assignable_id);

        return trim(($user?->code ? $user->code . ' - ' : '') . ($user?->name ?? '')) ?: '-';
    }

    private function reportingManagerLabel(DepotAssignment $assignment): string
    {
        $manager = $assignment->reportingManager;

        if (! $manager) {
            return '-';
        }

        $role = $manager->roles->pluck('name')->first(fn ($name) => in_array($name, ['Supervisor', 'Controller'], true));

        return $manager->name
            . ($manager->code ? ' (' . $manager->code . ')' : '')
            . ($role ? ' - ' . $role : '');
    }
}
