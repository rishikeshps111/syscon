@section('title')
    Login
@endsection
<x-guest-layout>
    <section class="syscon-entry-shell">

    <!-- Decorative Background -->
    <div class="syscon-entry-orb syscon-entry-orb-one"></div>
    <div class="syscon-entry-orb syscon-entry-orb-two"></div>
    <div class="syscon-entry-grid"></div>

    <div class="syscon-entry-container">

        <!-- Top Welcome Area -->
        <div class="syscon-entry-heading">

            <div class="syscon-entry-clock-box">
                <span class="syscon-entry-clock-icon">
                    <i class="fa-regular fa-clock"></i>
                </span>

                <div>
                    <strong id="sysconLiveClock">00:00:00</strong>
                    <small id="sysconLiveDate">Loading...</small>
                </div>
            </div>

            <span class="syscon-entry-eyebrow">
                <i class="fa-solid fa-circle"></i>
                Secure Access Portal
            </span>

            <h1>Welcome to <strong>SYSCON</strong></h1>

            <p>
                Streamline your workflow, manage with ease,
                <br class="syscon-desktop-break">
                and stay connected — all in one place.
            </p>
        </div>


        <!-- Main Login Card -->
        <div class="syscon-entry-card">

            <div class="syscon-entry-card-glow"></div>

            <!-- Logo -->
            <div class="syscon-entry-brand">
                <!--<div class="syscon-entry-logo-frame">-->
                <!--    <img src="{{ asset('assets/img/logo.png') }}" alt="SYSCON Logo">-->
                <!--</div>-->

                <span>Choose your access</span>
            </div>


            <!-- Login Widgets -->
            <div class="syscon-entry-options">

                <!-- Admin Login -->
                <a href="{{ route('login') }}"
                   class="syscon-access-widget syscon-admin-widget">

                    <span class="syscon-widget-shape syscon-admin-shape-one"></span>
                    <span class="syscon-widget-shape syscon-admin-shape-two"></span>

                    <span class="syscon-widget-icon">
                        <i class="fa-solid fa-user-shield"></i>
                    </span>

                    <span class="syscon-widget-content">
                        <small>
                            <i class="fa-solid fa-shield-halved"></i>
                            Secure Portal
                        </small>

                        <strong>Login as Admin</strong>

                        <em>
                            Manage system, users & operations
                        </em>
                    </span>

                    <span class="syscon-widget-arrow">
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>

                </a>


                <!-- Staff Login -->
                <a href="{{ route('login.staff') }}"
                   class="syscon-access-widget syscon-staff-widget">

                    <span class="syscon-widget-shape syscon-staff-shape-one"></span>
                    <span class="syscon-widget-shape syscon-staff-shape-two"></span>

                    <span class="syscon-widget-icon">
                        <i class="fa-solid fa-user-tie"></i>
                    </span>

                    <span class="syscon-widget-content">
                        <small>
                            <i class="fa-solid fa-briefcase"></i>
                            Staff Portal
                        </small>

                        <strong>Login as Staff</strong>

                        <em>
                            Access your workspace & daily tasks
                        </em>
                    </span>

                    <span class="syscon-widget-arrow">
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>

                </a>

            </div>


            <!-- Route Line -->
            <div class="syscon-route-track">
                <span class="syscon-route-line"></span>

                <span class="syscon-route-stop">
                    <i class="fa-solid fa-location-dot"></i>
                </span>

                <span class="syscon-route-bus">
                    <i class="fa-solid fa-bus"></i>
                </span>

                <span class="syscon-route-stop">
                    <i class="fa-solid fa-location-dot"></i>
                </span>

                <span class="syscon-route-line"></span>
            </div>


            <div class="syscon-entry-footer">
                <span>
                    <i class="fa-solid fa-lock"></i>
                    Secure & protected access
                </span>

                <span class="syscon-footer-divider"></span>

                <span>
                    <i class="fa-solid fa-circle-check"></i>
                    SYSCON Management System
                </span>
            </div>

        </div>

    </div>
</section>

<script>
    function updateSysconClock() {
        const clock = document.getElementById('sysconLiveClock');
        const date = document.getElementById('sysconLiveDate');

        if (!clock || !date) {
            return;
        }

        const now = new Date();

        clock.textContent = now.toLocaleTimeString('en-IN', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });

        date.textContent = now.toLocaleDateString('en-IN', {
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    updateSysconClock();
    setInterval(updateSysconClock, 1000);
</script>
</x-guest-layout>