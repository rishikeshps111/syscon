@section('title')
    Salary Processing
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Salary Processing</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Payroll</li>
                    <li class="breadcrumb-item active">Salary Processing</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="row mb-4">
                        <div class="col-lg-3 o-f-inp mb-2">
                            <label for="yearFilter">Year</label>
                            <select id="yearFilter" class="form-select shadow-none">
                                <option value="">--- Select ---</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 o-f-inp mb-2">
                            <label for="monthFilter">Month</label>
                            <select id="monthFilter" class="form-select shadow-none">
                                <option value="">--- Select ---</option>
                                @foreach ($months as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 o-f-inp mb-2">
                            <label for="depotFilter">Depo</label>
                            <select id="depotFilter" class="form-select shadow-none">
                                <option value="">--- Select ---</option>
                                @foreach ($depots as $depot)
                                    <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 o-f-inp mb-2">
                            <label for="roleFilter">Role</label>
                            <select id="roleFilter" class="form-select shadow-none">
                                <option value="">--- Select ---</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-12 d-flex align-items-end mb-2">
                            <button type="button" id="resetFilters" class="btn btn-secondary mb-1">Reset</button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 ms-auto justify-content-end d-flex">
                            @can('salary-processing.create')
                                <a href="{{ route('salary-processing.create') }}"
                                    class="add-btn form-btn text-decoration-none">Add</a>
                            @endcan
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mt-3 table-container" style="overflow-x: auto;">
                                <table id="table" class="table align-middle mb-0 table tble-cstm mt-3"
                                    style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center"><input type="checkbox" id="checkAll"></th>
                                            <th class="text-center">SL No</th>
                                            <th class="text-center">Month</th>
                                            <th class="text-center">Year</th>
                                            <th class="text-center">Depo</th>
                                            <th class="text-center">Role</th>
                                            <th class="text-center">Payment Method</th>
                                            <th class="text-center">Created By</th>
                                            <th class="text-center">Date and Time</th>
                                            <th class="text-center">Verified and Approved By</th>
                                            <th class="text-center">Approval Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        @include('salary-processing.partials.js')
    @endsection
</x-app-layout>
