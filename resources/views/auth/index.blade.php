@section('title')
    Login
@endsection
<x-guest-layout>
    <section class="application-login-section">
        <div class="login-screen-top-img">
        </div>
        <div class="container container-max-cs">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="login-field-box login-box-latest">
                        <img src="{{ asset('assets/img/logo.png') }}" alt="">
                        <div class="login-btns-index">
                            <a href="{{ route('login') }}">Login as Admin</a>
                            <a href="{{ route('login.staff') }}" class="bg1">Login as Staff</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>