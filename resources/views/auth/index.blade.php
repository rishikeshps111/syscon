@section('title')
    Login
@endsection
<x-guest-layout>
    <section class="application-login-section">
        <div class="login-screen-top-img">
            <h2>Welcome to SYSCON</h2>
            <p>Streamline your workflow, manage with ease, <br> and stay connected—all in one place.</p>
        </div>
         <div class="main-index-box">
    <div class="welcome-shapes shape-1"></div>
    <div class="welcome-shapes shape-2"></div>
    <div class="welcome-shapes shape-3"></div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="welcome-content">
        <div class="logo-wrap">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Logo">
        </div>

       

        <div class="main-index-box-btns">
            <a href="{{ route('login') }}" class="login-btn">
                <span class="btn-icon">
                    <i class="fa-solid fa-user-shield"></i>
                </span>
                <span>
                    <small>Access Portal</small>
                    Login as Admin
                </span>
                <i class="fa-solid fa-arrow-right arrow"></i>
            </a>

            <a href="{{ route('login.staff') }}" class="login-btn staff-btn">
                <span class="btn-icon">
                    <i class="fa-solid fa-user-tie"></i>
                </span>
                <span>
                    <small>Staff Access</small>
                    Login as Staff
                </span>
                <i class="fa-solid fa-arrow-right arrow"></i>
            </a>
        </div>
    </div>
        </div>
    </div>

    <div class="route-line">
        <span></span>
        <i class="fa-solid fa-bus"></i>
        <span></span>
    </div>
</div>
    </section>
</x-guest-layout>