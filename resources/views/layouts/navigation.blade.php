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
                @canany(['prefixes.view', 'service-types.view', 'vehicle-classifications.view', 'document-types.view',
                    'depots.view', 'routes.view', 'trip-setups.view', 'countries.view',
                    'nationalities.view', 'states.view', 'districts.view', 'locations.view', 'degree-levels.view',
                    'field-of-studies.view', 'study-modes.view', 'currencies.view', 'universities.view',
                    'university-types.view', 'intakes.view', 'program-types.view', 'program-levels.view',
                    'study-areas.view', 'discipline-areas.view', 'requirements.view', 'highest-qualifications.view',
                    'grading-systems.view', 'document-types.view', 'application-statuses.view', 'biller-profiles.view',
                    'payment-types.view', 'payment-methods.view', 'lead-sources.view', 'lead-types.view',
                    'lead-statuses.view', 'action-plans.view', 'relations.view'])
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('prefixes.*', 'states.*', 'districts.*', 'locations.*', 'service-types.*', 'vehicle-classifications.*', 'document-types.*', 'depots.*', 'routes.*', 'trip-setups.*') ? '' : 'collapsed' }}"
                            data-bs-target="#sidebarNav0" data-bs-toggle="collapse" href="#">

                            <i class="fa-solid fa-align-center"></i>
                            <span>Masters</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="sidebarNav0"
                            class="nav-content collapse sub-menu {{ request()->routeIs('prefixes.*', 'states.*', 'districts.*', 'locations.*', 'service-types.*', 'vehicle-classifications.*', 'document-types.*', 'depots.*', 'routes.*', 'trip-setups.*') ? 'show' : '' }}"
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
                            @can('depots.view')
                                <li>
                                    <a href="{{ route('depots.index') }}"
                                        class="{{ request()->routeIs('depots.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> Depot Management</span>
                                    </a>
                                </li>
                            @endcan
                            @can('routes.view')
                                <li>
                                    <a href="{{ route('routes.index') }}"
                                        class="{{ request()->routeIs('routes.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> Route Master</span>
                                    </a>
                                </li>
                            @endcan
                            @can('trip-setups.view')
                                <li>
                                    <a href="{{ route('trip-setups.index') }}"
                                        class="{{ request()->routeIs('trip-setups.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> Trip Setup Master</span>
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

                        </ul>
                    </li>
                @endcanany
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('branch-locations.*', 'departments.*', 'levels.*', 'designations.*', 'hrms-document-types.*', 'leave-types.*', 'shift-settings.*', 'holidays.*', 'staff-management.*') ? '' : 'collapsed' }}" data-bs-target="#sidebarNav6" data-bs-toggle="collapse"
                        href="#">
                        <i class="fa-solid fa-id-badge"></i><span>HRMS</span><i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="sidebarNav6" class="nav-content collapse sub-menu {{ request()->routeIs('branch-locations.*', 'departments.*', 'levels.*', 'designations.*', 'hrms-document-types.*', 'leave-types.*', 'shift-settings.*', 'holidays.*', 'staff-management.*') ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
                        @can('branch-locations.view')
                        <li>
                            <a class="nav-link {{ request()->routeIs('branch-locations.*') ? '' : 'collapsed' }}" data-bs-target="#sidebarNavInner" data-bs-toggle="collapse"
                                href="#">
                                <i class="fa-solid fa-gear mn-0"></i><span>Branch Management</span><i
                                    class="bi bi-chevron-down ms-auto"></i>
                            </a>
                            <ul id="sidebarNavInner" class="nav-content collapse sub-menu {{ request()->routeIs('branch-locations.*') ? 'show' : '' }}"
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
                        @canany(['departments.view', 'levels.view', 'designations.view', 'hrms-document-types.view', 'leave-types.view', 'shift-settings.view', 'holidays.view'])
                        <li>
                            <a class="nav-link {{ request()->routeIs('departments.*', 'levels.*', 'designations.*', 'hrms-document-types.*', 'leave-types.*', 'shift-settings.*', 'holidays.*') ? '' : 'collapsed' }}" data-bs-target="#sidebarNavInner2" data-bs-toggle="collapse"
                                href="#">
                                <i class="fa-solid fa-gear mn-0"></i><span>Settings</span><i
                                    class="bi bi-chevron-down ms-auto"></i>
                            </a>
                            <ul id="sidebarNavInner2" class="nav-content collapse sub-menu {{ request()->routeIs('departments.*', 'levels.*', 'designations.*', 'hrms-document-types.*', 'leave-types.*', 'shift-settings.*', 'holidays.*') ? 'show' : '' }}"
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
                                        class="{{ request()->routeIs('designations.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Designation</span>
                                    </a>
                                </li>
                                @endcan
                                @can('hrms-document-types.view')
                                <li>
                                    <a href="{{ route('hrms-document-types.index') }}"
                                        class="{{ request()->routeIs('hrms-document-types.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Document Type</span>
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

                            </ul>
                        </li>
                        @endcanany
                        @can('staff-management.view')
                        <li>
                            <a href="{{ route('staff-management.index') }}"
                                class="{{ request()->routeIs('staff-management.*') ? 'sub-active' : '' }}">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Staff Management</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</aside>
