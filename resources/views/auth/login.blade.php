@section('title')
    {{ $title ?? 'Login' }}
@endsection
<x-guest-layout>
    <section class="application-login-section">
        <div class="login-screen-top-img">
            <h2>{{ $heading ?? 'Login' }}</h2>
        </div>
        <div class="container container-max-cs">
            <div class="row justify-content-center">

                <div class="col-lg-6">
                    <div class="login-field-box login-box-latest login-mtop">
                        <img src="{{ asset('assets/img/logo.png') }}" alt="">
                        <form class="row g-3 lg-form" method="POST" action="{{ route('login') }}">
                            @csrf
                            <input type="hidden" name="portal" value="{{ old('portal', $portal ?? 'general') }}">
                            <!-- Session Status -->
                            @if (session('status'))
                                <div class="col-12">
                                    <div class="alert alert-success mb-4">
                                        {{ session('status') }}
                                    </div>
                                </div>
                            @endif
                            <!-- Email -->
                            <div class="col-12">
                                <div class="input-group has-validation form-login">
                                    <input type="text" name="email" class="form-control shadow-none" id="yourEmail"
                                        placeholder="Enter your email" value="{{ old('email') }}" required>
                                </div>
                                @error('email')
                                    <div class="text-danger small mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <!-- Password -->
                            <div class="col-12 form-login">
                                <input type="password" name="password" class="form-control shadow-none"
                                    id="yourPassword" placeholder="Enter your password" required>
                                @error('password')
                                    <div class="text-danger small mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <!-- Remember -->
                            <div class="col-12 my-4">
                                <div class="form-check">
                                    <input class="form-check-input shadow-none" type="checkbox" name="remember"
                                        id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">
                                        Remember me
                                    </label>
                                </div>
                            </div>
                            <!-- Submit -->
                            <div class="col-12">
                                <button type="submit" class="btn--form btn--form-login w-100">
                                    {{ $submitLabel ?? 'Login' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>