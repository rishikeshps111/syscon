@section('title')
    Service Codes
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Manage Service Codes</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Master</li>
                    <li class="breadcrumb-item active">Manage Service Codes</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="row">
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="statusFilter">Filter by Status</label>
                                <select name="status" id="statusFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 d-flex align-items-end">
                            <button type="button" id="resetFilters" class="">
                                Reset
                            </button>
                        </div>
                        <div class="col-lg-6 ms-auto btns-group-container">
                            @can('service-codes.create')
                                <button type="button" class="add-btn form-btn">Add Service Code</button>
                            @endcan
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
                                            <th class="text-center">Title</th>
                                            {{-- <th class="text-center">Description</th> --}}
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
    </section>
    @section('scripts')
        @include('service-code.partials-js')
    @endsection
</x-app-layout>


