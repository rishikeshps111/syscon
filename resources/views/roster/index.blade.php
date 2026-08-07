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
                <div class="col-lg-2 o-f-inp mb-3">
                    <label for="dateFromFilter">From Date <span class="text-danger">*</span></label>
                    <input type="date" id="dateFromFilter" class="form-control shadow-none">
                </div>
                <div class="col-lg-2 o-f-inp mb-3">
                    <label for="dateToFilter">To Date <span class="text-danger">*</span></label>
                    <input type="date" id="dateToFilter" class="form-control shadow-none">
                </div>
                <div class="col-lg-5 mb-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" id="generateRoster">Generate Roaster</button>
                        @can('rosters.view')<button type="button" class="btn btn-success" id="generateExport">Generate and Export</button>@endcan
                        <button type="button" class="btn btn-secondary" id="resetFilters">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-table-container">
            <div class="table-over">
                <table id="table" class="align-middle mb-0 table tble-cstm" style="width:100%">
                    <thead><tr>
                        {{-- <th class="text-center nowrap"><input type="checkbox" id="checkAll"></th> --}}
                        <th class="text-center nowrap">SL No</th>
                        <th class="text-center nowrap">Roster Code</th>
                        <th class="text-center nowrap">Date</th>
                        <th class="text-center nowrap">Depot</th>
                        <th class="text-center nowrap">Shift Type</th>
                        <th class="text-center nowrap">Driver Name</th>
                        <th class="text-center nowrap">Vehicle</th>
                        <th class="text-center nowrap">Trip Code</th>
                        <th class="text-center nowrap">Reporting To Time</th>
                        {{-- <th class="text-center nowrap">Status</th>
                        <th class="text-center nowrap">Attendance Status</th> --}}
                        <th class="text-center nowrap">Action</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

    @include('roster.partials.modals')
    @section('scripts')
        @include('roster.partials.js')
    @endsection
</x-app-layout>
