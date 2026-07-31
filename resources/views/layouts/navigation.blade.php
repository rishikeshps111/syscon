<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
    <div class="sidebar-blur">
        <div class="sidebar-cont">
            <div class="app-logo">
                <a href="{{ route('dashboard') }}" class="logo d-flex align-items-center">
                    <img src="{{ asset('assets/img/logo.png') }}" alt="">
                    <!-- <span class="d-none d-lg-block">LOGO</span> -->
                </a>
            </div>

            <ul class="sidebar-nav" id="sidebar-nav">

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? '' : 'collapsed' }}"
                        href="{{ route('dashboard') }}">
                        <i class="bi bi-grid"></i>
                        <span>Dashboard</span>
                    </a>
                </li><!-- End Dashboard Nav -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('profile.edit') ? '' : 'collapsed' }}"
                        href="{{ route('profile.edit') }}">
                        <i class="fa-solid fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
                @canany(['prefixes.view', 'service-types.view', 'oem-types.view', 'vehicle-classifications.view',
                    'document-types.view', 'complaint-categories.view', 'dor-account-responsibles.view',
                    'dor-kilometer-loss-reasons.view', 'depots.view', 'countries.view',
                    'nationalities.view', 'states.view', 'districts.view', 'locations.view', 'degree-levels.view',
                    'field-of-studies.view', 'study-modes.view', 'currencies.view', 'universities.view',
                    'university-types.view', 'intakes.view', 'program-types.view', 'program-levels.view',
                    'study-areas.view', 'discipline-areas.view', 'requirements.view', 'highest-qualifications.view',
                    'grading-systems.view', 'document-types.view', 'application-statuses.view', 'biller-profiles.view',
                    'payment-types.view', 'payment-methods.view', 'lead-sources.view', 'lead-types.view',
                    'lead-statuses.view', 'action-plans.view', 'relations.view'])
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('prefixes.*', 'states.*', 'districts.*', 'locations.*', 'service-types.*', 'oem-types.*', 'vehicle-classifications.*', 'document-types.*', 'complaint-categories.*', 'dor-account-responsibles.*', 'dor-kilometer-loss-reasons.*', 'depots.*') ? '' : 'collapsed' }}"
                            data-bs-target="#sidebarNav0" data-bs-toggle="collapse" href="#">

                            <i class="fa-solid fa-align-center"></i>
                            <span>Masters</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="sidebarNav0"
                            class="nav-content collapse sub-menu {{ request()->routeIs('prefixes.*', 'states.*', 'districts.*', 'locations.*', 'service-types.*', 'oem-types.*', 'vehicle-classifications.*', 'document-types.*', 'complaint-categories.*', 'dor-account-responsibles.*', 'dor-kilometer-loss-reasons.*', 'depots.*') ? 'show' : '' }}"
                            data-bs-parent="#sidebar-nav">
                            @can('prefixes.view')
                                <li>
                                    <a href="{{ route('prefixes.index') }}"
                                        class="{{ request()->routeIs('prefixes.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> Prefix Management </span>
                                    </a>
                                </li>
                            @endcan
                            @can('states.view')
                                <li>
                                    <a href="{{ route('states.index') }}"
                                        class="{{ request()->routeIs('states.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> State Management </span>
                                    </a>
                                </li>
                            @endcan
                            @can('districts.view')
                                <li>
                                    <a href="{{ route('districts.index') }}"
                                        class="{{ request()->routeIs('districts.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> District Management </span>
                                    </a>
                                </li>
                            @endcan
                            @can('locations.view')
                                <li>
                                    <a href="{{ route('locations.index') }}"
                                        class="{{ request()->routeIs('locations.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> Location Management </span>
                                    </a>
                                </li>
                            @endcan
                            @can('service-types.view')
                                <li>
                                    <a href="{{ route('service-types.index') }}"
                                        class="{{ request()->routeIs('service-types.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> Service Type Master </span>
                                    </a>
                                </li>
                            @endcan
                            @can('oem-types.view')
                                <li>
                                    <a href="{{ route('oem-types.index') }}"
                                        class="{{ request()->routeIs('oem-types.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> OEM Type Master </span>
                                    </a>
                                </li>
                            @endcan
                            @can('depots.view')
                                <li>
                                    <a href="{{ route('depots.index') }}"
                                        class="{{ request()->routeIs('depots.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> Depot Management</span>
                                    </a>
                                </li>
                            @endcan
                            @can('vehicle-classifications.view')
                                <li>
                                    <a href="{{ route('vehicle-classifications.index') }}"
                                        class="{{ request()->routeIs('vehicle-classifications.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> Vehicle Classification </span>
                                    </a>
                                </li>
                            @endcan
                            @can('document-types.view')
                                <li>
                                    <a href="{{ route('document-types.index') }}"
                                        class="{{ request()->routeIs('document-types.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> Document Types </span>
                                    </a>
                                </li>
                            @endcan
                            @can('complaint-categories.view')
                                <li>
                                    <a href="{{ route('complaint-categories.index') }}"
                                        class="{{ request()->routeIs('complaint-categories.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> Complaint Categories </span>
                                    </a>
                                </li>
                            @endcan
                            @can('dor-account-responsibles.view')
                                <li>
                                    <a href="{{ route('dor-account-responsibles.index') }}"
                                        class="{{ request()->routeIs('dor-account-responsibles.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> DOR Account Responsible </span>
                                    </a>
                                </li>
                            @endcan
                            @can('dor-kilometer-loss-reasons.view')
                                <li>
                                    <a href="{{ route('dor-kilometer-loss-reasons.index') }}"
                                        class="{{ request()->routeIs('dor-kilometer-loss-reasons.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> DOR KM Loss Reasons </span>
                                    </a>
                                </li>
                            @endcan

                        </ul>
                    </li>
                @endcanany
                @can('oems.view')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('oems.*') ? '' : 'collapsed' }}"
                            data-bs-target="#sidebarNav1" data-bs-toggle="collapse" href="#">
                            <i class="fa-solid fa-handshake"></i><span>OEM/Vendor Management </span><i
                                class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="sidebarNav1"
                            class="nav-content collapse sub-menu {{ request()->routeIs('oems.*') ? 'show' : '' }}"
                            data-bs-parent="#sidebar-nav">
                            <li>
                                <a href="{{ route('oems.index') }}"
                                    class="{{ request()->routeIs('oems.*') ? 'sub-active' : '' }}">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>OEM</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan
                @php
                    $bulkImportModule = request()->routeIs('bulk-import.*') ? request()->route('module') : null;
                    $bulkImportIsDesignation = $bulkImportModule === 'designations';
                    $bulkImportIsHrms = $bulkImportIsDesignation
                        || in_array($bulkImportModule, ['staff', 'drivers', 'controllers', 'supervisors'], true);
                    $letterUser = request()->routeIs('hr-letters.*') ? request()->route('user') : null;
                    if (! $letterUser && request()->routeIs('hr-letters.*') && request()->route('hrLetter')) {
                        $letterUser = request()->route('hrLetter')->user;
                    }
                    $letterUserRole = $letterUser?->roles?->pluck('name')->first(fn ($role) => in_array($role, ['Staff', 'Driver', 'Controller', 'Supervisor'], true));
                    $letterUserModule = match ($letterUserRole) {
                        'Staff' => 'staff',
                        'Driver' => 'drivers',
                        'Controller' => 'controllers',
                        'Supervisor' => 'supervisors',
                        default => null,
                    };
                @endphp
                @canany(['branch-locations.view', 'departments.view', 'levels.view', 'designations.view',
                    'hrms-document-types.view', 'leave-types.view', 'shift-settings.view', 'holidays.view',
                    'staff-management.view', 'driver-management.view', 'controller-management.view',
                    'supervisor-management.view', 'attendance-management.view', 'hr-letter-templates.view',
                    'salary-components.view', 'salary-templates.view', 'salary-processing.view', 'salary-reports.view', 'salary-archives.view', 'salary-files.view', 'salary-slips.view', 'role-permissions.view', 'settings.view'])
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('branch-locations.*', 'departments.*', 'levels.*', 'designations.*', 'role-permissions.*', 'hrms-document-types.*', 'leave-types.*', 'shift-settings.*', 'leaves.*', 'attendance-management.*', 'holidays.*', 'staff-management.*', 'controller-management.*', 'supervisor-management.*', 'driver-management.*', 'salary-components.*', 'salary-templates.*', 'salary-processing.*', 'salary-reports.*', 'salary-archives.*', 'salary-files.*', 'salary-slips.*', 'hr-letter-templates.*', 'hr-letters.*') || $bulkImportIsHrms ? '' : 'collapsed' }}"
                            data-bs-target="#sidebarNav6" data-bs-toggle="collapse" href="#">
                            <i class="fa-solid fa-id-badge"></i><span>HRMS</span><i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="sidebarNav6"
                            class="nav-content collapse sub-menu {{ request()->routeIs('branch-locations.*', 'departments.*', 'levels.*', 'designations.*', 'role-permissions.*', 'hrms-document-types.*', 'leave-types.*', 'shift-settings.*', 'leaves.*', 'attendance-management.*', 'holidays.*', 'staff-management.*', 'controller-management.*', 'supervisor-management.*', 'driver-management.*', 'salary-components.*', 'salary-templates.*', 'salary-processing.*', 'salary-reports.*', 'salary-archives.*', 'salary-files.*', 'salary-slips.*', 'hr-letter-templates.*', 'hr-letters.*') || $bulkImportIsHrms ? 'show' : '' }}"
                            data-bs-parent="#sidebar-nav">
                            @can('branch-locations.view')
                                <li>
                                    <a class="nav-link {{ request()->routeIs('branch-locations.*') ? '' : 'collapsed' }}"
                                        data-bs-target="#sidebarNavInner" data-bs-toggle="collapse" href="#">
                                        <i class="fa-solid fa-gear mn-0"></i><span>Branch Management</span><i
                                            class="bi bi-chevron-down ms-auto"></i>
                                    </a>
                                    <ul id="sidebarNavInner"
                                        class="nav-content collapse sub-menu {{ request()->routeIs('branch-locations.*') ? 'show' : '' }}"
                                        data-bs-parent="#sidebar-nav1">
                                        <li>
                                            <a href="{{ route('branch-locations.index') }}"
                                                class="{{ request()->routeIs('branch-locations.*') ? 'sub-active' : '' }}">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Define Branch
                                                    Locations</span>
                                            </a>
                                        </li>

                                    </ul>
                                </li>
                            @endcan
                            @canany(['departments.view', 'levels.view', 'designations.view', 'role-permissions.view',
                                'hrms-document-types.view', 'leave-types.view', 'shift-settings.view', 'holidays.view', 'hr-letter-templates.view'])
                                <li>
                                    <a class="nav-link {{ request()->routeIs('departments.*', 'levels.*', 'designations.*', 'role-permissions.*', 'hrms-document-types.*', 'leave-types.*', 'shift-settings.*', 'holidays.*', 'hr-letter-templates.*') || $bulkImportIsDesignation ? '' : 'collapsed' }}"
                                        data-bs-target="#sidebarNavInner2" data-bs-toggle="collapse" href="#">
                                        <i class="fa-solid fa-gear mn-0"></i><span>Settings</span><i
                                            class="bi bi-chevron-down ms-auto"></i>
                                    </a>
                                    <ul id="sidebarNavInner2"
                                        class="nav-content collapse sub-menu {{ request()->routeIs('departments.*', 'levels.*', 'designations.*', 'role-permissions.*', 'hrms-document-types.*', 'leave-types.*', 'shift-settings.*', 'holidays.*', 'hr-letter-templates.*') || $bulkImportIsDesignation ? 'show' : '' }}"
                                        data-bs-parent="#sidebar-nav1">

                                        @can('departments.view')
                                            <li>
                                                <a href="{{ route('departments.index') }}"
                                                    class="{{ request()->routeIs('departments.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Department</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('levels.view')
                                            <li>
                                                <a href="{{ route('levels.index') }}"
                                                    class="{{ request()->routeIs('levels.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Level</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('designations.view')
                                            <li>
                                                <a href="{{ route('designations.index') }}"
                                                    class="{{ request()->routeIs('designations.*') || $bulkImportIsDesignation ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Designation</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('hrms-document-types.view')
                                            <li>
                                                <a href="{{ route('hrms-document-types.index') }}"
                                                    class="{{ request()->routeIs('hrms-document-types.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Document
                                                        Type</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('leave-types.view')
                                            <li>
                                                <a href="{{ route('leave-types.index') }}"
                                                    class="{{ request()->routeIs('leave-types.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Leave Type</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('shift-settings.view')
                                            <li>
                                                <a href="{{ route('shift-settings.index') }}"
                                                    class="{{ request()->routeIs('shift-settings.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Shift Setting
                                                    </span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('holidays.view')
                                            <li>
                                                <a href="{{ route('holidays.index') }}"
                                                    class="{{ request()->routeIs('holidays.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Holiday </span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('role-permissions.view')
                                            <li>
                                                <a href="{{ route('role-permissions.index') }}"
                                                    class="{{ request()->routeIs('role-permissions.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Role And
                                                        Permissions</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('hr-letter-templates.view')
                                            <li>
                                                <a href="{{ route('hr-letter-templates.index') }}"
                                                    class="{{ request()->routeIs('hr-letter-templates.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Letter Templates</span>
                                                </a>
                                            </li>
                                        @endcan

                                    </ul>
                                </li>
                            @endcanany
                            @can('staff-management.view')
                                <li>
                                    <a href="{{ route('staff-management.index') }}"
                                        class="{{ request()->routeIs('staff-management.*') || $bulkImportModule === 'staff' || $letterUserModule === 'staff' ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Staff Management</span>
                                    </a>
                                </li>
                            @endcan
                            @can('driver-management.view')
                                <li>
                                    <a href="{{ route('driver-management.index') }}"
                                        class="{{ request()->routeIs('driver-management.*') || $bulkImportModule === 'drivers' || $letterUserModule === 'drivers' ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Driver Management</span>
                                    </a>
                                </li>
                            @endcan
                            @can('controller-management.view')
                                <li>
                                    <a href="{{ route('controller-management.index') }}"
                                        class="{{ request()->routeIs('controller-management.*') || $bulkImportModule === 'controllers' || $letterUserModule === 'controllers' ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Controller
                                            Management</span>
                                    </a>
                                </li>
                            @endcan
                            @can('supervisor-management.view')
                                <li>
                                    <a href="{{ route('supervisor-management.index') }}"
                                        class="{{ request()->routeIs('supervisor-management.*') || $bulkImportModule === 'supervisors' || $letterUserModule === 'supervisors' ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Supervisor
                                            Management</span>
                                    </a>
                                </li>
                            @endcan
                            @can('leaves.view')
                                <li>
                                    <a href="{{ route('leaves.index') }}"
                                        class="{{ request()->routeIs('leaves.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Leave Management</span>
                                    </a>
                                </li>
                            @endcan
                            @can('attendance-management.view')
                                <li>
                                    <a href="{{ route('attendance-management.index') }}"
                                        class="{{ request()->routeIs('attendance-management.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Attendance
                                            Management</span>
                                    </a>
                                </li>
                            @endcan
                            @canany(['salary-components.view', 'salary-templates.view', 'salary-processing.view', 'salary-reports.view', 'salary-archives.view', 'salary-files.view', 'salary-slips.view'])
                                <li>
                                    <a class="nav-link {{ request()->routeIs('salary-components.*', 'salary-templates.*', 'salary-processing.*', 'salary-reports.*', 'salary-archives.*', 'salary-files.*', 'salary-slips.*') ? '' : 'collapsed' }}"
                                        data-bs-target="#sidebarNavPayroll" data-bs-toggle="collapse" href="#">
                                        <i class="fa-solid fa-money-check-dollar mn-0"></i><span>Payroll</span><i
                                            class="bi bi-chevron-down ms-auto"></i>
                                    </a>
                                    <ul id="sidebarNavPayroll"
                                        class="nav-content collapse sub-menu {{ request()->routeIs('salary-components.*', 'salary-templates.*', 'salary-processing.*', 'salary-reports.*', 'salary-archives.*', 'salary-files.*', 'salary-slips.*') ? 'show' : '' }}"
                                        data-bs-parent="#sidebar-nav1">
                                        @can('salary-components.view')
                                            <li>
                                                <a href="{{ route('salary-components.index') }}"
                                                    class="{{ request()->routeIs('salary-components.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Salary
                                                        Components</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('salary-templates.view')
                                            <li>
                                                <a href="{{ route('salary-templates.index') }}"
                                                    class="{{ request()->routeIs('salary-templates.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Salary
                                                        Templates</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('salary-processing.view')
                                            <li>
                                                <a href="{{ route('salary-processing.index') }}"
                                                    class="{{ request()->routeIs('salary-processing.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Salary
                                                        Processing</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('salary-reports.view')
                                            <li>
                                                <a href="{{ route('salary-reports.index') }}"
                                                    class="{{ request()->routeIs('salary-reports.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Salary
                                                        Report</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('salary-archives.view')
                                            <li>
                                                <a href="{{ route('salary-archives.index') }}"
                                                    class="{{ request()->routeIs('salary-archives.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Salary
                                                        Archive</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('salary-files.view')
                                            <li>
                                                <a href="{{ route('salary-files.index') }}"
                                                    class="{{ request()->routeIs('salary-files.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Salary
                                                        Files</span>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('salary-slips.view')
                                            <li>
                                                <a href="{{ route('salary-slips.index') }}"
                                                    class="{{ request()->routeIs('salary-slips.*') ? 'sub-active' : '' }}">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Generate
                                                        Pay Slip</span>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany
                @can('routes.view')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('routes.*') ? '' : 'collapsed' }}"
                            href="{{ route('routes.index') }}">
                            <i class="fa-solid fa-route"></i>
                            <span>Route Management</span>
                        </a>
                    </li>
                @endcan
                @can('vehicles.view')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('vehicles.*') || $bulkImportModule === 'vehicles' ? '' : 'collapsed' }}"
                            href="{{ route('vehicles.index') }}">
                            <i class="fa-solid fa-bus"></i>
                            <span>Vehicle Management</span>
                        </a>
                    </li>
                @endcan
                @can('trips.view')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('trips.*') ? '' : 'collapsed' }}"
                            data-bs-target="#sidebarTripManagement" data-bs-toggle="collapse" href="#">
                            <i class="fa-solid fa-cogs"></i>
                            <span>Trip Management</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="sidebarTripManagement"
                            class="nav-content collapse sub-menu {{ request()->routeIs('trips.*') ? 'show' : '' }}"
                            data-bs-parent="#sidebar-nav">
                            <li>
                                <a href="{{ route('trips.index') }}"
                                    class="{{ request()->routeIs('trips.index') ? 'sub-active' : '' }}">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    <span>Manage Trips</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('trips.completed.index') }}"
                                    class="{{ request()->routeIs('trips.completed.*') ? 'sub-active' : '' }}">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    <span>Completed Trips</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('trips.report.index') }}"
                                    class="{{ request()->routeIs('trips.report.*') ? 'sub-active' : '' }}">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    <span>Trip Report</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan
                @canany(['dor-reports.view', 'license-expiry-reports.view', 'salary-reports.view'])
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reports.*', 'salary-reports.*') ? '' : 'collapsed' }}"
                            data-bs-target="#sidebarReports" data-bs-toggle="collapse" href="#">
                            <i class="fa-solid fa-chart-column"></i>
                            <span>Reports</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="sidebarReports"
                            class="nav-content collapse sub-menu {{ request()->routeIs('reports.*', 'salary-reports.*') ? 'show' : '' }}"
                            data-bs-parent="#sidebar-nav">
                            @can('dor-reports.view')
                                <li>
                                    <a href="{{ route('reports.dor.index') }}"
                                        class="{{ request()->routeIs('reports.dor.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span>DOR Report</span>
                                    </a>
                                </li>
                            @endcan
                            @can('license-expiry-reports.view')
                                <li>
                                    <a href="{{ route('reports.license-expiry.index') }}"
                                        class="{{ request()->routeIs('reports.license-expiry.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span>License Expiry Report</span>
                                    </a>
                                </li>
                            @endcan
                            @can('salary-reports.view')
                                <li>
                                    <a href="{{ route('salary-reports.index') }}"
                                        class="{{ request()->routeIs('salary-reports.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span>Salary Report</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany
                @can('rosters.view')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('rosters.*') ? '' : 'collapsed' }}"
                            data-bs-target="#sidebarRoasterManagement" data-bs-toggle="collapse" href="#">
                            <i class="fa-regular fa-user-clock"></i>
                            <span>Roaster</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="sidebarRoasterManagement"
                            class="nav-content collapse sub-menu {{ request()->routeIs('rosters.*') ? 'show' : '' }}"
                            data-bs-parent="#sidebar-nav">
                            @can('rosters.create')
                                <li>
                                    <a href="{{ route('rosters.create') }}"
                                        class="{{ request()->routeIs('rosters.create') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span>Create Roaster</span>
                                    </a>
                                </li>
                            @endcan
                            <li>
                                <a href="{{ route('rosters.index') }}"
                                    class="{{ request()->routeIs('rosters.index', 'rosters.edit', 'rosters.show') ? 'sub-active' : '' }}">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    <span>Manage Roaster</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan
                @can('complaints.view')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('complaints.*') ? '' : 'collapsed' }}"
                            href="{{ route('complaints.index') }}">
                            <i class="fa-solid fa-ticket"></i>
                            <span>Complaint Management</span>
                        </a>
                    </li>
                @endcan
                @can('settings.view')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('financial-year-settings.*', 'free-no-settings.*') ? '' : 'collapsed' }}"
                            data-bs-target="#sidebarSettings" data-bs-toggle="collapse" href="#">
                            <i class="fa-solid fa-gear"></i>
                            <span>Settings</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="sidebarSettings"
                            class="nav-content collapse sub-menu {{ request()->routeIs('financial-year-settings.*', 'free-no-settings.*') ? 'show' : '' }}"
                            data-bs-parent="#sidebar-nav">
                            <li>
                                <a href="{{ route('financial-year-settings.index') }}"
                                    class="{{ request()->routeIs('financial-year-settings.*') ? 'sub-active' : '' }}">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    <span>Financial Year Settings</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('free-no-settings.index') }}"
                                    class="{{ request()->routeIs('free-no-settings.*') ? 'sub-active' : '' }}">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    <span>Toll Free No Settings</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan
                @canany(['user-logs.view', 'activity-logs.view'])
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('user-logs.*', 'activity-logs.*') ? '' : 'collapsed' }}"
                            data-bs-target="#sidebarLogs" data-bs-toggle="collapse" href="#">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>Logs</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="sidebarLogs"
                            class="nav-content collapse sub-menu {{ request()->routeIs('user-logs.*', 'activity-logs.*') ? 'show' : '' }}"
                            data-bs-parent="#sidebar-nav">
                            @can('user-logs.view')
                                <li>
                                    <a href="{{ route('user-logs.index') }}"
                                        class="{{ request()->routeIs('user-logs.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span>User Logs</span>
                                    </a>
                                </li>
                            @endcan
                            @can('activity-logs.view')
                                <li>
                                    <a href="{{ route('activity-logs.index') }}"
                                        class="{{ request()->routeIs('activity-logs.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span>Activity Logs</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany
                 @if(auth()->user()?->hasAnyRole(['Super Admin', 'Staff']))
                    @php
                        $sidebarChatUnreadCount = app(\App\Http\Controllers\ChatController::class)->unreadCountFor(auth()->user());
                    @endphp
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('chat.*') ? '' : 'collapsed' }}"
                            href="{{ route('chat.index') }}">
                            <i class="fa-solid fa-comments"></i>
                            <span>{{ auth()->user()->hasRole('Staff') ? 'Chat with Admin' : 'Chat' }}</span>
                            <span id="chatSidebarBadge" class="chat-sidebar-badge ms-auto {{ $sidebarChatUnreadCount ? '' : 'd-none' }}">{{ $sidebarChatUnreadCount }}</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</aside>
