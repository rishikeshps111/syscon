@section('title')
    Create Trip
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Create Trip</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trip Management</a></li>
                    <li class="breadcrumb-item active">Create Trip</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container">
            @include('trip.form', ['pageForm' => true])
        </div>
    </section>

    @section('scripts')
        @include('trip.partials.form-scripts')
    @endsection
</x-app-layout>
