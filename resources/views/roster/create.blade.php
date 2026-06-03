@section('title')
    Create Roaster
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Create Roaster</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('rosters.index') }}">Roaster</a></li>
                    <li class="breadcrumb-item active">Create Roaster</li>
                </ol>
            </nav>
        </div>

        @include('roster.form', ['pageForm' => true])
    </section>

    @include('roster.partials.trip-modal')

    @section('scripts')
        @include('roster.partials.form-scripts')
    @endsection
</x-app-layout>
