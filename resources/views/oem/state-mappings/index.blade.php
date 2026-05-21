@section('title')
    OEM State Mappings
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>OEM State Mappings</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('oems.index') }}">OEM</a></li>
                    <li class="breadcrumb-item active">State Mappings</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec mb-3">Basic Details</h5>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="oem-detail-card">
                        <span>OEM Code</span>
                        <strong>{{ $oem->oem_code }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="oem-detail-card">
                        <span>OEM Name</span>
                        <strong>{{ $oem->oem_name }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="oem-detail-card">
                        <span>Type</span>
                        <strong>{{ $oem->oem_type ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="oem-detail-card">
                        <span>Registered State</span>
                        <strong>{{ $oem->state?->name ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="oem-detail-card">
                        <span>GST Number</span>
                        <strong>{{ $oem->gst_number ?: '-' }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="oem-detail-card">
                        <span>Primary Contact</span>
                        <strong>{{ $oem->primaryContact?->contact_person ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        @can('oems.edit')
            <div class="modal fade" id="stateMappingModal" tabindex="-1" aria-labelledby="stateMappingLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="stateMappingLabel">Add State Mapping</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('oems.state-mappings.store', $oem->id) }}" method="POST" id="stateMappingForm">
                                @csrf
                                <input type="hidden" id="formMethod" name="_method" value="POST" disabled>
                                <div class="row">
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="stateId">State <span class="text-danger">*</span></label>
                                        <select name="state_id" id="stateId" class="form-select shadow-none" required>
                                            <option value="">--- Select ---</option>
                                            @foreach ($states as $state)
                                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="gstNumber">GST Number <span class="text-danger">*</span></label>
                                        <input type="text" name="gst_number" id="gstNumber" class="form-control shadow-none" maxlength="30" required>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="mappingStatus">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="mappingStatus" class="form-select shadow-none" required>
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
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
                        <a href="{{ route('oems.index') }}" class="add-btn bg-filter">Back</a>
                        @can('oems.edit')
                            <a class="add-btn" data-bs-toggle="modal" href="#stateMappingModal" role="button" id="addStateMapping">
                                Add State Mapping
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
                            <th class="text-center nowrap">State</th>
                            <th class="text-center nowrap">GST Number</th>
                            <th class="text-center nowrap">Primary</th>
                            <th class="text-center nowrap">Status</th>
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
                var createUrl = "{{ route('oems.state-mappings.store', $oem->id) }}";

                $('#stateId').select2({
                    placeholder: '--- Select ---',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#stateMappingModal')
                });

                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('oems.state-mappings.index', $oem->id) }}"
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'state', name: 'state.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'gst_number', name: 'gst_number', className: 'text-center' },
                        { data: 'is_primary', name: 'is_primary', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[0, 'desc']]
                });

                $('#addStateMapping').on('click', resetForm);

                $(document).on('click', '.edit-state-mapping', function () {
                    var mapping = $(this).data('mapping');
                    $('#stateMappingLabel').text('Edit State Mapping');
                    $('#stateMappingForm').attr('action', '/oem-state-mappings/' + mapping.id);
                    $('#formMethod').prop('disabled', false).val('PUT');
                    $('#stateId').val(mapping.state_id).trigger('change');
                    $('#gstNumber').val(mapping.gst_number);
                    $('#mappingStatus').val(mapping.status ? '1' : '0');
                    $('#stateMappingModal').modal('show');
                });

                $('#stateMappingModal').on('hidden.bs.modal', resetForm);

                $('#stateMappingForm').on('submit', function (e) {
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
                            $('#stateMappingModal').modal('hide');
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

                $(document).on('click', '.togglePrimary', function () {
                    var checkbox = $(this);
                    var id = checkbox.data('id');
                    var wasPrimary = checkbox.data('status') == 1;

                    if (wasPrimary) {
                        checkbox.prop('checked', true);
                        return;
                    }

                    postAction('/oem-state-mappings/' + id + '/make-primary');
                });

                function postAction(url) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (res) {
                            table.ajax.reload();
                            showToast('success', res.message);
                        },
                        error: function (xhr) {
                            table.ajax.reload();
                            showToast('error', xhr.responseJSON?.message || 'An error occurred. Please try again.');
                        }
                    });
                }

                function resetForm() {
                    $('#stateMappingLabel').text('Add State Mapping');
                    $('#stateMappingForm').attr('action', createUrl)[0].reset();
                    $('#formMethod').prop('disabled', true).val('POST');
                    $('#stateId').val('').trigger('change');
                    $('#mappingStatus').val('1');
                }
            });

            function deleteStateMapping(id) {
                deleteRecord('/oem-state-mappings/' + id, 'table', 'Do you really want to delete this state mapping?');
            }
        </script>
    @endsection
</x-app-layout>
