@section('title')
    HRMS Document Types
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Manage Document Type</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item active">Manage Document Type</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                      <div class="row mb-2">
                        <div class="col-lg-4 ms-auto btns-group-container">
                            @can('hrms-document-types.create')
                                <button type="button" id="addNewHrmsDocumentType" class="add-btn form-btn">Add Document Type</button>
                            @endcan
                            @can('hrms-document-types.view')
                                <button id="exportSelected" class="exp-btn ms-1">Export</button>
                            @endcan
                        </div>
                    </div>
                    <div class="row ">
                        <div class="col-lg-3 mb-2 ps-3">
                            <div class="o-f-inp">
                                <label for="categoryFilter">Filter by Category</label>
                                <select name="category" id="categoryFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 mb-2">
                            <div class="o-f-inp">
                                <label for="applicableForFilter">Applicable For</label>
                                <select name="applicable_for" id="applicableForFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    @foreach ($applicableFor as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 mb-2">
                            <div class="o-f-inp">
                                <label for="mandatoryFilter">Mandatory</label>
                                <select name="mandatory" id="mandatoryFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 mb-2">
                            <div class="o-f-inp">
                                <label for="statusFilter">Filter by Status</label>
                                <select name="status" id="statusFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 d-flex align-items-end">
                            <button type="button" id="resetFilters" class="btn btn-secondary mb-1">
                                Reset
                            </button>
                        </div>
                    </div>
                  
                    <div class="row">
                        <div class="col-lg-12">
                            <div class=" mt-3 table-container">
                                <div class="table-over-cs">
                                    <table id="table" class="table align-middle mb-0 table tble-cstm mt-3"
                                    style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                <input type="checkbox" id="checkAll">
                                            </th>
                                            <th class="text-center">Sl No</th>
                                            <th class="text-center">Code</th>
                                            <th class="text-center">Document Type</th>
                                            <th class="text-center">Category</th>
                                            <th class="text-center">Is Mandatory</th>
                                            <th class="text-center">Is Expiry</th>
                                            <th class="text-center">Applicable For</th>
                                            <th class="text-center">Status</th>
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
        </div>
    </section>
    @section('scripts')
        @include('hrms-document-type.partials.js')
    @endsection
</x-app-layout>
