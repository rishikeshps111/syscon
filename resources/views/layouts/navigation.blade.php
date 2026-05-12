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
            </ul>
        </div>
    </div>
</aside>