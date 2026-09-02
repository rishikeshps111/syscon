@section('title')
    Profile
@endsection
<x-app-layout>
    <div class="page-title">
        <h3>Profile</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Profile</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="row">
            <div class="col-xl-8">
                <div class="">
                    <div class="card-body pt-3 profile-container">
                        <h3 class="title-spa">{{ Auth::user()->roles->first()->name ?? 'User' }}</h3>
                        <!-- Bordered Tabs -->
                       <ul class="nav nav-tabs nav-tabs-custom">
    <li class="nav-item ps-0 ms-0">
        <button class="nav-link ms-0 active"
                data-bs-toggle="tab"
                data-bs-target="#profile-overview">
            <i class="fa-solid fa-user-pen me-2"></i>
            Edit Profile
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#profile-change-password">
            <i class="fa-solid fa-key me-2"></i>
            Change Password
        </button>
    </li>
</ul>
                        <div class="tab-content pt-2">
                            @include('profile.partials.update-profile-information-form')
                            @include('profile.partials.update-password-form')
                        </div><!-- End Bordered Tabs -->
                    </div>
                </div>
            </div>
    </section>
    @section('scripts')
        @include('profile.js')
    @endsection
</x-app-layout>