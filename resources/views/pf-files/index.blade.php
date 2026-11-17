@section('title', 'PF File')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>PF File</h3>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item">Payroll</li><li class="breadcrumb-item active">PF File</li></ol></nav>
        </div>
        <div class="main-table-container mb-3">
            @if ($errors->any())<div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form id="pfFileForm" method="GET" action="{{ route('pf-files.index') }}" data-default-year="{{ now()->year }}">
                <input type="hidden" name="generate" value="1">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="pf_year">Year <span class="text-danger">*</span></label>
                        <select id="pf_year" name="year" class="form-select shadow-none" required>
                            @foreach ($years as $year)<option value="{{ $year }}" @selected((int) $filters['year'] === $year)>{{ $year }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="pf_month">Month <span class="text-danger">*</span></label>
                        <select id="pf_month" name="month" class="form-select shadow-none" required>
                            <option value="">--- Select ---</option>
                            @foreach ($months as $value => $label)<option value="{{ $value }}" @selected((int) $filters['month'] === $value)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="pf_depot">Depo <span class="text-danger">*</span></label>
                        <select id="pf_depot" name="depot_id" class="form-select shadow-none" required>
                            <option value="">--- Select ---</option>
                            @foreach ($depots as $depot)<option value="{{ $depot->id }}" @selected((int) $filters['depot_id'] === $depot->id)>{{ $depot->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="pf_role">Role <span class="text-danger">*</span></label>
                        <select id="pf_role" name="role_id" class="form-select shadow-none" required>
                            <option value="">--- Select ---</option><option value="all" @selected($filters['role_id'] === 'all')>All</option>
                            @foreach ($roles as $role)<option value="{{ $role->id }}" @selected((string) $filters['role_id'] === (string) $role->id)>{{ $role->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-lg-12 mt-2 btns-group-container">
                        <button type="button" id="resetPfFilters" class="fil-btn">Reset</button>
                        <button type="submit" class="exp-btn m-0">Generate Report</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal fade" id="pfFileModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl"><div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generated PF File</h5>
                    <a id="downloadPfExcel" class="modal-btn-2 ms-auto me-3 {{ $report && count($report['rows']) ? '' : 'disabled' }}" href="{{ $report && count($report['rows']) ? route('pf-files.export', $filters) : '#' }}" aria-disabled="{{ $report && count($report['rows']) ? 'false' : 'true' }}">Download Excel</a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="pfFileBody" data-generated="{{ $report ? '1' : '0' }}">@include('pf-files.partials.report')</div>
            </div></div>
        </div>
    </section>
    @section('scripts')
        <script src="{{ asset('assets/js/pf-file-report.js') }}"></script>
    @endsection
</x-app-layout>
