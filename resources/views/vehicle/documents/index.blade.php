@section('title')
    Vehicle Documents
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Vehicle Documents</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicle Management</a></li>
                    <li class="breadcrumb-item active">Documents</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec mb-3">Basic Details</h5>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card">
                        <span>Vehicle Code</span>
                        <strong>{{ $vehicle->vehicle_code }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card">
                        <span>Vehicle No</span>
                        <strong>{{ $vehicle->vehicle_no }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card">
                        <span>Type</span>
                        <strong>{{ $vehicle->vehicle_type }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card">
                        <span>Fuel Type</span>
                        <strong>{{ $vehicle->fuel_type }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card">
                        <span>OEM</span>
                        <strong>{{ $vehicle->oem?->oem_name ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card">
                        <span>Depot</span>
                        <strong>{{ $vehicle->depot?->name ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        @can('vehicles.edit')
            <div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addDocumentLabel">Add Document</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('vehicles.documents.store', $vehicle->id) }}" method="POST"
                                enctype="multipart/form-data" id="documentForm">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="documentType">Document Type <span class="text-danger">*</span></label>
                                        <select name="document_type_id" id="documentType" class="form-select shadow-none" required>
                                            <option value="">--- Select ---</option>
                                            @foreach ($documentTypes as $type)
                                                <option value="{{ $type->id }}" data-expiry="{{ $type->is_expiry_required ? 1 : 0 }}">
                                                    {{ $type->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3" id="expiryDateWrap">
                                        <label for="expiryDate">Expiry Date <span id="expiryRequiredMark" class="text-danger d-none">*</span></label>
                                        <input type="date" name="expiry_date" id="expiryDate" class="form-control shadow-none">
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="documentFile">Document File <span class="text-danger">*</span></label>
                                        <input type="file" name="document_file" id="documentFile" class="form-control shadow-none"
                                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="isVerified" class="flex-check">
                                            <input type="checkbox" name="is_verified" id="isVerified" value="1">
                                            Is Verified
                                        </label>
                                    </div>
                                    <div class="col-lg-12 d-flex justify-content-center align-items-center">
                                        <div class="btn-flex">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="submit-btn">Submit</button>
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
                    <div class="btn-flex">
                        <a href="{{ route('vehicles.index') }}" class="add-btn bg-filter">Back</a>
                        @can('vehicles.edit')
                            <a class="add-btn" data-bs-toggle="modal" href="#addDocumentModal" role="button">
                                Add Document
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
                            <th class="text-center nowrap">Type</th>
                            <th class="text-center nowrap">Expiry Date</th>
                            <th class="text-center nowrap">Status</th>
                            <th class="text-center nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="modal fade" id="viewDoc" tabindex="-1" aria-labelledby="viewDocLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body pb-0">
                    <iframe id="documentPreviewFrame" src="" width="100%" height="600px"></iframe>
                </div>
                <div class="lock-modal-footer">
                    <button type="button" data-bs-dismiss="modal">Close</button>
                    <a href="#!" id="documentDownloadLink">Download</a>
                </div>
            </div>
        </div>
    </div>

    @section('scripts')
        <style>
            .vehicle-detail-card {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                min-height: 78px;
                padding: 14px 16px;
            }

            .vehicle-detail-card span {
                color: #6b7280;
                display: block;
                font-size: 13px;
                margin-bottom: 8px;
            }

            .vehicle-detail-card strong {
                color: #111827;
                display: block;
                font-size: 15px;
                font-weight: 600;
                word-break: break-word;
            }
        </style>
        <script>
            $(function () {
                $('#documentType').select2({
                    placeholder: '--- Select ---',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#addDocumentModal')
                });

                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('vehicles.documents.index', $vehicle->id) }}"
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'type', name: 'documentType.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'expiry_date', name: 'expiry_date', className: 'text-center' },
                        { data: 'status', name: 'is_verified', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[2, 'asc']]
                });

                $('#documentType').on('change', function () {
                    var needsExpiry = $(this).find(':selected').data('expiry') == 1;

                    $('#expiryDateWrap').toggleClass('d-none', !needsExpiry);
                    $('#expiryRequiredMark').toggleClass('d-none', !needsExpiry);
                    $('#expiryDate').prop('required', needsExpiry);
                    if (!needsExpiry) {
                        $('#expiryDate').val('');
                    }
                }).trigger('change');

                $(document).on('click', '.view-document', function () {
                    $('#documentPreviewFrame').attr('src', $(this).data('preview'));
                    $('#documentDownloadLink').attr('href', $(this).data('download'));
                });

                $('#viewDoc').on('hidden.bs.modal', function () {
                    $('#documentPreviewFrame').attr('src', '');
                });

                $('#documentForm').on('submit', function (e) {
                    e.preventDefault();

                    var form = $(this);
                    var button = form.find('button[type="submit"]');
                    var originalText = button.data('original-text') || button.text();
                    button.data('original-text', originalText).prop('disabled', true).text('Please wait...');

                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: new FormData(this),
                        processData: false,
                        contentType: false,
                        success: function (res) {
                            $('#addDocumentModal').modal('hide');
                            form[0].reset();
                            $('#documentType').val('').trigger('change');
                            $('#isVerified').prop('checked', false);
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
            });

            function deleteDocument(id) {
                deleteRecord('/vehicle-documents/' + id, 'table', 'Do you really want to delete this document?');
            }
        </script>
    @endsection
</x-app-layout>
