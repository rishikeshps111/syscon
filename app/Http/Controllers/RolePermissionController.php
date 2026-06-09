<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller implements HasMiddleware
{
    private const STATIC_ROLES = ['Super Admin', 'Staff', 'Driver', 'Controller', 'Supervisor'];

    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('role-permissions.view'), ['index', 'edit']),
            new Middleware(PermissionMiddleware::using('role-permissions.edit'), ['update']),
        ];
    }

    public function index()
    {
        $roles = Role::whereNotIn('name', self::STATIC_ROLES)
            ->withCount('permissions')
            ->orderBy('name')
            ->get();

        return view('role-permission.index', compact('roles'));
    }

    public function edit(Role $role)
    {
        $this->abortForStaticRole($role);

        $role->load('permissions');

        return view('role-permission.edit', [
            'role' => $role,
            'permissionTree' => $this->permissionTree(),
            'assignedPermissions' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $this->abortForStaticRole($role);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissions = Permission::whereIn('id', $validated['permissions'] ?? [])
            ->pluck('name')
            ->all();

        $oldPermissions = $role->permissions()->pluck('name')->all();
        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('crud')
            ->event('updated')
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->withProperties([
                'module' => 'Role Permissions',
                'record_name' => $role->name,
                'status' => null,
                'attributes' => ['permissions' => $permissions],
                'old' => ['permissions' => $oldPermissions],
            ])
            ->log('Role Permissions updated');

        return redirect()
            ->route('role-permissions.index')
            ->with('success', 'Permissions assigned successfully.');
    }

    private function abortForStaticRole(Role $role): void
    {
        abort_if(in_array($role->name, self::STATIC_ROLES, true), 404);
    }

    private function permissionTree(): array
    {
        $permissions = Permission::orderBy('group_name')
            ->orderBy('name')
            ->get()
            ->keyBy('name');

        $tree = [];
        $used = [];

        foreach ($this->navigationOrder() as $section) {
            $children = [];

            foreach ($section['children'] as $child) {
                $items = collect($child['permissions'])
                    ->map(function (string $permission) use ($permissions, &$used) {
                        $record = $permissions->get($permission);

                        if ($record) {
                            $used[] = $permission;
                        }

                        return $record;
                    })
                    ->filter()
                    ->values();

                if ($items->isNotEmpty()) {
                    $children[] = [
                        'label' => $child['label'],
                        'permissions' => $items,
                    ];
                }
            }

            if ($children) {
                $tree[] = [
                    'label' => $section['label'],
                    'children' => $children,
                ];
            }
        }

        $extraPermissions = $permissions
            ->reject(fn (Permission $permission) => in_array($permission->name, $used, true))
            ->groupBy('group_name');

        foreach ($extraPermissions as $groupName => $items) {
            $tree[] = [
                'label' => 'Other Permissions',
                'children' => [[
                    'label' => $groupName,
                    'permissions' => $items->values(),
                ]],
            ];
        }

        return $tree;
    }

    private function navigationOrder(): array
    {
        return [
            [
                'label' => 'Masters',
                'children' => [
                    ['label' => 'Prefix Management', 'permissions' => $this->crud('prefixes')],
                    ['label' => 'State Management', 'permissions' => $this->crud('states')],
                    ['label' => 'District Management', 'permissions' => $this->crud('districts')],
                    ['label' => 'Location Management', 'permissions' => $this->crud('locations')],
                    ['label' => 'Service Type Master', 'permissions' => $this->crud('service-types')],
                    ['label' => 'OEM Type Master', 'permissions' => $this->crud('oem-types')],
                    ['label' => 'Depot Management', 'permissions' => $this->crud('depots')],
                    ['label' => 'Vehicle Classification', 'permissions' => $this->crud('vehicle-classifications')],
                    ['label' => 'Document Types', 'permissions' => $this->crud('document-types')],
                    ['label' => 'Complaint Categories', 'permissions' => $this->crud('complaint-categories')],
                ],
            ],
            [
                'label' => 'OEM/Vendor Management',
                'children' => [
                    ['label' => 'OEM', 'permissions' => $this->crud('oems')],
                ],
            ],
            [
                'label' => 'HRMS',
                'children' => [
                    ['label' => 'Branch Management', 'permissions' => $this->crud('branch-locations')],
                    ['label' => 'Department', 'permissions' => $this->crud('departments')],
                    ['label' => 'Level', 'permissions' => $this->crud('levels')],
                    ['label' => 'Designation', 'permissions' => $this->crud('designations')],
                    ['label' => 'Role Permissions', 'permissions' => ['role-permissions.view', 'role-permissions.edit']],
                    ['label' => 'Document Type', 'permissions' => $this->crud('hrms-document-types')],
                    ['label' => 'Leave Type', 'permissions' => $this->crud('leave-types')],
                    ['label' => 'Shift Setting', 'permissions' => $this->crud('shift-settings')],
                    ['label' => 'Holiday', 'permissions' => $this->crud('holidays')],
                    ['label' => 'Staff Management', 'permissions' => $this->crud('staff-management')],
                    ['label' => 'Driver Management', 'permissions' => $this->crud('driver-management')],
                    ['label' => 'Controller Management', 'permissions' => $this->crud('controller-management')],
                    ['label' => 'Supervisor Management', 'permissions' => $this->crud('supervisor-management')],
                    ['label' => 'Leave Management', 'permissions' => $this->crud('leaves')],
                    ['label' => 'Attendance Management', 'permissions' => $this->crud('attendance-management')],
                ],
            ],
            [
                'label' => 'Route Management',
                'children' => [
                    ['label' => 'Route Management', 'permissions' => $this->crud('routes')],
                ],
            ],
            [
                'label' => 'Vehicle Management',
                'children' => [
                    ['label' => 'Vehicle Management', 'permissions' => $this->crud('vehicles')],
                ],
            ],
            [
                'label' => 'Trip Management',
                'children' => [
                    ['label' => 'Manage Trips', 'permissions' => ['trips.view', 'trips.create', 'trips.edit', 'trips.delete', 'trips.assign', 'trips.sheet']],
                    ['label' => 'Completed Trips', 'permissions' => ['trips.view']],
                    ['label' => 'Trip Report', 'permissions' => ['trips.view']],
                ],
            ],
            [
                'label' => 'Roaster',
                'children' => [
                    ['label' => 'Create Roaster', 'permissions' => ['rosters.create']],
                    ['label' => 'Manage Roaster', 'permissions' => $this->crud('rosters')],
                ],
            ],
            [
                'label' => 'Complaint Management',
                'children' => [
                    ['label' => 'Complaint Management', 'permissions' => $this->crud('complaints')],
                ],
            ],
            [
                'label' => 'Settings',
                'children' => [
                    ['label' => 'Financial Year Settings', 'permissions' => ['settings.view', 'settings.edit']],
                    ['label' => 'Free No Settings', 'permissions' => ['settings.view', 'settings.edit']],
                ],
            ],
            [
                'label' => 'Logs',
                'children' => [
                    ['label' => 'User Logs', 'permissions' => ['user-logs.view']],
                    ['label' => 'Activity Logs', 'permissions' => ['activity-logs.view']],
                ],
            ],
        ];
    }

    private function crud(string $prefix): array
    {
        return [
            $prefix . '.view',
            $prefix . '.create',
            $prefix . '.edit',
            $prefix . '.delete',
        ];
    }
}
