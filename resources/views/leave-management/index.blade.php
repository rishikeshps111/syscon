@section('title')
    Leave Management
@endsection
<style>
   @media screen and (max-width:991px){
       div.dataTables_wrapper{
             padding-top:20px !important;
       }
       div.btn-flex {
           padding:0 !important;
       }
       div button.fil-btn{
           padding:7px 15px !important;
       }
   }
   
</style>
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Leave Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Leave Management</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                   <ul class="nav nav-tabs-custom pb-3" id="leaveTabs" role="tablist">
    <li class="nav-item ps-0 ms-0" role="presentation">
        <button class="nav-link ms-0 active mb-0"
            data-table-type="all"
            data-bs-toggle="tab"
            data-bs-target="#lv0"
            type="button">
            <i class="fa-solid fa-layer-group"></i>
            All
        </button>
    </li>

    <li class="nav-item" role="presentation">
        <button class="nav-link mb-0"
            data-table-type="general"
            data-bs-toggle="tab"
            data-bs-target="#lv1"
            type="button">
            <i class="fa-solid fa-calendar-days"></i>
            General Leave System
        </button>
    </li>

    <li class="nav-item" role="presentation">
        <button class="nav-link mb-0"
            data-table-type="driver"
            data-bs-toggle="tab"
            data-bs-target="#lv2"
            type="button">
            <i class="fa-solid fa-clock"></i>
            Shift-Based Leave System
        </button>
    </li>

    <li class="nav-item" role="presentation">
        <button class="nav-link mb-0"
            data-table-type="consolidated"
            data-bs-toggle="tab"
            data-bs-target="#lv3"
            type="button">
            <i class="fa-solid fa-chart-column"></i>
            Consolidated Leave Report
        </button>
    </li>
</ul>
                    </ul>

                    <div class="tab-content pt-2">
                        <div class="tab-pane fade show active lv0" id="lv0">
                            @include('leave-management.partials.table', ['type' => 'all'])
                        </div>
                        <div class="tab-pane fade lv1" id="lv1">
                            @include('leave-management.partials.table', ['type' => 'general'])
                        </div>
                        <div class="tab-pane fade lv2" id="lv2">
                            @include('leave-management.partials.table', ['type' => 'driver'])
                        </div>
                        <div class="tab-pane fade lv3" id="lv3">
                            @include('leave-management.partials.consolidated-report')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="changeStatusForm" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Change Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12 o-f-inp mb-3">
                            <label for="modalStatus">Status <span class="text-danger">*</span></label>
                            <select id="modalStatus" name="status" class="form-select shadow-none">
                                <option value="">---Select---</option>
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger error-text status_error"></span>
                        </div>
                        <div class="col-lg-12 o-f-inp">
                            <label for="modalRemarks">Remarks</label>
                            <textarea id="modalRemarks" name="remarks" class="form-control shadow-none"></textarea>
                            <span class="text-danger error-text remarks_error"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-btns-last">
                    <button type="button" class="modal-btn-1" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="modal-btn-2">Submit</button>
                </div>
            </form>
        </div>
    </div>

    @section('scripts')
        @include('leave-management.partials.js')
    @endsection
</x-app-layout>
