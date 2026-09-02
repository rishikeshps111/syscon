@section('title', 'Roaster Management')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Roaster Management</h3>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item active">Roaster Management</li></ol></nav>
        </div>

        <div class="main-table-container mb-3">
            <div class="row align-items-end">
                <div class="col-lg-3 o-f-inp mb-3">
                    <label for="depotFilter">Depot <span class="text-danger">*</span></label>
                    <select id="depotFilter" class="form-select shadow-none select2-filter">
                        <option value="">---Select---</option>
                        @foreach ($depots as $depot)<option value="{{ $depot->id }}">{{ $depot->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label for="dateFromFilter">From Date <span class="text-danger">*</span></label>
                    <input type="date" id="dateFromFilter" class="form-control shadow-none">
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label for="dateToFilter">To Date <span class="text-danger">*</span></label>
                    <input type="date" id="dateToFilter" class="form-control shadow-none">
                </div>
                <div class="col-lg-3 mb-3">
                    <div class="btns-group-container" style="justify-content:flex-start !important;">
                        <button type="button" class="add-btn m-0" id="generateRoster">Generate Roaster</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('roster.partials.modals')
    @section('scripts')
        @include('roster.partials.js')
    @endsection
</x-app-layout>
