@section('title')
    Salary Components
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Manage Salary Components</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Payroll</li>
                    <li class="breadcrumb-item active">Salary Components</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="row mb-4">
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="roleFilter">Filter by Role</label>
                                <select name="role_id" id="roleFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" data-role-name="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 d-none" id="designationFilterWrapper">
                            <div class="o-f-inp">
                                <label for="designationFilter">Filter by Designation</label>
                                <select name="designation_id" id="designationFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    @foreach ($designations as $designation)
                                        <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 d-flex align-items-end">
                            <button type="button" id="resetFilters" class="btn btn-secondary mb-1">
                                Reset
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 ms-auto justify-content-end d-flex">
                            @can('salary-components.create')
                                <a href="{{ route('salary-components.create') }}" class="add-btn form-btn text-decoration-none">Add Salary Component</a>
                            @endcan
                            @can('salary-components.view')
                                <button id="exportSelected" class="exp-btn ms-1">Export</button>
                            @endcan
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mt-3 table-container">
                                <table id="table" class="table align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                <input type="checkbox" id="checkAll">
                                            </th>
                                            <th class="text-center">Sl No</th>
                                            <th class="text-center">Code</th>
                                            <th class="text-center">Roles</th>
                                            <th class="text-center">Designations</th>
                                            <th class="text-center">Component Name</th>
                                            <th class="text-center">Type</th>
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
        @include('salary-component.partials.js')
    @endsection
</x-app-layout>
