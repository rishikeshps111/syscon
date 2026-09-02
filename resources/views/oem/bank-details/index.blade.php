@section('title')
    OEM Bank Details
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>OEM Bank Details</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('oems.index') }}">OEM</a></li>
                    <li class="breadcrumb-item active">Bank Details</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec mb-3">Basic Details</h5>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget oem-widget-blue">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-barcode"></i>
        </div>
        <div class="oem-widget-content">
            <span>OEM Code</span>
                        <strong>{{ $oem->oem_code }}</strong>
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget oem-widget-purple">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-building"></i>
        </div>
        <div class="oem-widget-content">
            <span>OEM Name</span>
                        <strong>{{ $oem->oem_name }}</strong>
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget oem-widget-green">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-layer-group"></i>
        </div>
        <div class="oem-widget-content">
              <span>Type</span>
                        <strong>{{ $oem->oem_type ?? '-' }}</strong>
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget oem-widget-orange">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-location-dot"></i>
        </div>
        <div class="oem-widget-content">
            <span>State</span>
                        <strong>{{ $oem->state?->name ?? '-' }}</strong>
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget oem-widget-red">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div class="oem-widget-content">
            <span>GST Number</span>
                        <strong>{{ $oem->gst_number ?: '-' }}</strong>
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget oem-widget-cyan">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-user-tie"></i>
        </div>
        <div class="oem-widget-content">
             <span>Primary Contact</span>
                        <strong>{{ $oem->primaryContact?->contact_person ?? '-' }}</strong>
        </div>
    </div>
</div>
            </div>
        </div>

        @can('oems.edit')
            <div class="modal fade" id="bankDetailModal" tabindex="-1" aria-labelledby="bankDetailLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="bankDetailLabel">Add Bank Detail</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('oems.bank-details.store', $oem->id) }}" method="POST" id="bankDetailForm">
                                @csrf
                                <input type="hidden" id="formMethod" name="_method" value="POST" disabled>
                                <div class="row">
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="accountName">Account Name <span class="text-danger">*</span></label>
                                        <input type="text" name="account_name" id="accountName" class="form-control shadow-none" required>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="accountNumber">Account Number <span class="text-danger">*</span></label>
                                        <input type="text" name="account_number" id="accountNumber" class="form-control shadow-none" required>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="bankName">Bank Name <span class="text-danger">*</span></label>
                                        <input type="text" name="bank_name" id="bankName" class="form-control shadow-none" required>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="branch">Branch <span class="text-danger">*</span></label>
                                        <input type="text" name="branch" id="branch" class="form-control shadow-none" required>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="ifscCode">IFSC Code <span class="text-danger">*</span></label>
                                        <input type="text" name="ifsc_code" id="ifscCode" class="form-control shadow-none" maxlength="20" required>
                                    </div>
                                    <div class="col-lg-12 d-flex justify-content-center align-items-center">
                                        <div class="modal-btns-last">
                                            <button type="button" class="modal-btn-1" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="modal-btn-2">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        <div class="main-table-container">
            <div class="row mb-3">
                <div class="col-lg-12 d-flex justify-content-end align-items-end">
                    <div class="btns-group-container">
                        <a href="{{ route('oems.index') }}" class="btn-back-cs"><i class="fa-solid fa-arrow-left"></i>Back</a>
                        @can('oems.edit')
                            <a class="add-btn" data-bs-toggle="modal" href="#bankDetailModal" role="button" id="addBankDetail">
                                Add Bank Detail
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="table-over">
                <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center nowrap">SL NO</th>
                            <th class="text-center nowrap">Account Name</th>
                            <th class="text-center nowrap">Account Number</th>
                            <th class="text-center nowrap">Bank Name</th>
                            <th class="text-center nowrap">Branch</th>
                            <th class="text-center nowrap">IFSC Code</th>
                            <th class="text-center nowrap">Primary</th>
                            <th class="text-center nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

    @section('scripts')
        <style>
            .oem-detail-card {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 14px 16px;
                min-height: 78px;
                background: #fff;
            }

            .oem-detail-card span {
                display: block;
                color: #6b7280;
                font-size: 13px;
                margin-bottom: 8px;
            }

            .oem-detail-card strong {
                display: block;
                color: #111827;
                font-size: 15px;
                font-weight: 600;
                word-break: break-word;
            }
        </style>
        <script>
            $(function () {
                var createUrl = "{{ route('oems.bank-details.store', $oem->id) }}";

                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('oems.bank-details.index', $oem->id) }}"
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'account_name', name: 'account_name', className: 'text-center' },
                        { data: 'account_number', name: 'account_number', className: 'text-center' },
                        { data: 'bank_name', name: 'bank_name', className: 'text-center' },
                        { data: 'branch', name: 'branch', className: 'text-center' },
                        { data: 'ifsc_code', name: 'ifsc_code', className: 'text-center' },
                        { data: 'is_primary', name: 'is_primary', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[0, 'desc']]
                });

                $('#addBankDetail').on('click', resetForm);

                $(document).on('click', '.edit-bank-detail', function () {
                    var detail = $(this).data('detail');
                    $('#bankDetailLabel').text('Edit Bank Detail');
                    $('#bankDetailForm').attr('action', '/oem-bank-details/' + detail.id);
                    $('#formMethod').prop('disabled', false).val('PUT');
                    $('#accountName').val(detail.account_name);
                    $('#accountNumber').val(detail.account_number);
                    $('#bankName').val(detail.bank_name);
                    $('#branch').val(detail.branch);
                    $('#ifscCode').val(detail.ifsc_code);
                    $('#bankDetailModal').modal('show');
                });

                $('#bankDetailModal').on('hidden.bs.modal', resetForm);

                $('#bankDetailForm').on('submit', function (e) {
                    e.preventDefault();

                    var form = $(this);
                    var button = form.find('button[type="submit"]');
                    var originalText = button.data('original-text') || button.text();
                    button.data('original-text', originalText).prop('disabled', true).text('Please wait...');

                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        success: function (res) {
                            $('#bankDetailModal').modal('hide');
                            table.ajax.reload();
                            showToast('success', res.message);
                        },
                        error: function (xhr) {
                            let message = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                                message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            }
                            showToast('error', message);
                        },
                        complete: function () {
                            button.prop('disabled', false).text(originalText);
                        }
                    });
                });

                $(document).on('click', '.toggleStatus', function () {
                    var checkbox = $(this);
                    var id = checkbox.data('id');
                    var wasPrimary = checkbox.data('status') == 1;

                    if (wasPrimary) {
                        checkbox.prop('checked', true);
                        return;
                    }

                    makePrimary(id);
                });

                function resetForm() {
                    $('#bankDetailLabel').text('Add Bank Detail');
                    $('#bankDetailForm').attr('action', createUrl)[0].reset();
                    $('#formMethod').prop('disabled', true).val('POST');
                }
            });

            function deleteBankDetail(id) {
                deleteRecord('/oem-bank-details/' + id, 'table', 'Do you really want to delete this bank detail?');
            }

            function makePrimary(id) {
                $.ajax({
                    url: '/oem-bank-details/' + id + '/make-primary',
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (res) {
                        $('#table').DataTable().ajax.reload();
                        showToast('success', res.message);
                    },
                    error: function (xhr) {
                        showToast('error', xhr.responseJSON?.message || 'An error occurred. Please try again.');
                    }
                });
            }
        </script>
    @endsection
</x-app-layout>
