@section('title')
    DOR Kilometer Loss Reasons
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Manage Reason for Kilometer Loss</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Masters</li>
                    <li class="breadcrumb-item active">Manage Reason for Kilometer Loss</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="o-f-inp">
                        <label for="accountFilter">Filter by Account Responsible</label>
                        <select id="accountFilter" class="form-select shadow-none">
                            <option value="">--- Select ---</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
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
                    <button type="button" id="resetFilters" class="btn btn-secondary mb-1">Reset</button>
                </div>
                 <div class="col-lg-4 ms-auto btns-group-container">
                    @can('dor-kilometer-loss-reasons.create')
                        <button type="button" class="add-btn form-btn">Add Reason</button>
                    @endcan
                    @can('dor-kilometer-loss-reasons.view')
                        <button id="exportSelected" class="exp-btn ms-1">Export</button>
                    @endcan
                </div>
            </div>
            <div class="row">
               
            </div>
            <div class="table-container mt-3">
               <div class="table-over-cs">
                    <table id="table" class="table align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center"><input type="checkbox" id="checkAll"></th>
                            <th class="text-center">Sl No</th>
                            <th class="text-center">Code</th>
                            <th class="text-center">Account Responsible</th>
                            <th class="text-center">Reason</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
               </div>
            </div>
        </div>
    </section>
    @section('scripts')
        @include('dor-kilometer-loss-reason.partials.js')
    @endsection
</x-app-layout>
