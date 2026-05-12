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
                    @canany(['prefixes.view', 'designations.view', 'countries.view', 'nationalities.view', 'states.view',
                    'locations.view', 'degree-levels.view', 'field-of-studies.view', 'study-modes.view', 'currencies.view',
                    'universities.view', 'university-types.view', 'intakes.view', 'program-types.view',
                    'program-levels.view', 'study-areas.view', 'discipline-areas.view', 'requirements.view',
                    'highest-qualifications.view', 'grading-systems.view', 'document-types.view',
                    'application-statuses.view', 'biller-profiles.view', 'payment-types.view', 'payment-methods.view', 'lead-sources.view',
                    'lead-types.view', 'lead-statuses.view', 'action-plans.view', 'relations.view'])
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('prefixes.*') ? '': 'collapsed' }}"
                            data-bs-target="#sidebarNav0" data-bs-toggle="collapse" href="#">

                            <i class="fa-solid fa-align-center"></i>
                            <span>Masters</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="sidebarNav0"
                            class="nav-content collapse sub-menu {{ request()->routeIs('prefixes.*') ? 'show': '' }}"
                            data-bs-parent="#sidebar-nav">
                            @can('prefixes.view')
                                <li>
                                    <a href="{{ route('prefixes.index') }}"
                                        class="{{ request()->routeIs('prefixes.*') ? 'sub-active' : '' }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span> Prefix Management    </span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany
            </ul>
        </div>
    </div>
</aside>