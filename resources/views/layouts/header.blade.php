<header id="header" class="header fixed-top header-blur ">
    <div class="header-top-cs">
        <div class="header-section-right" style="justify-content: flex-start;">
            <div class="digital-time-container">
                <div class="display-date">
                    <span id="day">day</span>,
                    <span id="daynum">00</span>
                    <span id="month">month</span>
                    <span id="year">0000</span>
                </div>
                <div class="display-time"></div>
            </div>
        </div>
        <div class="header-section-right">
            <a href="#"><i class="fa-regular fa-bell"></i></a>
            <div class="headertogle">
                <i class="bi bi-list toggle-sidebar-btn"></i>
            </div>
            <div class="headertogle">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="border:none;background:none;">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <nav class="header-nav ms-auto">
        <ul class="d-flex align-items-center">
            <li class="nav-item d-block d-lg-none">
                <a class="nav-link nav-icon search-bar-toggle " href="#">
                    <i class="bi bi-search"></i>
                </a>
            </li>
        </ul>
    </nav>
</header>