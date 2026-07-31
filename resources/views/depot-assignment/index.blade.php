@section('title')
    {{ $title }}
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ $title }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $backRoute }}">Management</a></li>
                    <li class="breadcrumb-item active">Assign Depot</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec mb-3">Basic Details</h5>
            <div class="row">
                @foreach($details as $label => $value)
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="assignment-detail-card">
                            <span>{{ $label }}</span>
                            <strong>{{ $value ?: '-' }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($canEdit)
            <div class="modal fade" id="depotAssignmentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="depotAssignmentModalTitle">Add Depot Assignment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="depotAssignmentForm" method="POST" action="{{ $storeUrl }}" data-store-url="{{ $storeUrl }}">
                                @csrf
                                <input type="hidden" name="_method" id="assignmentMethod" value="POST">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="depotId">Depot <span class="text-danger">*</span></label>
                                        <select id="depotId" name="depot_id" class="form-select shadow-none">
                                            <option value="">--- Select ---</option>
                                            @foreach($depots as $depot)
                                                <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                                            @endforeach
                                        </select>
                                        <span class="text-danger error-text depot_id_error"></span>
                                    </div>
                                    @if($requiresReportingManager)
                                        <div class="col-lg-4 o-f-inp mb-3">
                                            <label for="reportingTo">Reporting To <span class="text-danger">*</span></label>
                                            <select id="reportingTo" name="reporting_to" class="form-select shadow-none">
                                                <option value="">--- Select ---</option>
                                            </select>
                                            <span class="text-danger error-text reporting_to_error"></span>
                                        </div>
                                    @endif
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="fromDate">From Date <span class="text-danger">*</span></label>
                                        <input type="date" id="fromDate" name="from_date" class="form-control shadow-none">
                                        <span class="text-danger error-text from_date_error"></span>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="toDate">To Date <span class="text-danger">*</span></label>
                                        <input type="date" id="toDate" name="to_date" class="form-control shadow-none">
                                        <span class="text-danger error-text to_date_error"></span>
                                    </div>
                                    <div class="col-lg-12 d-flex justify-content-center align-items-center">
                                        <div class="btn-flex">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="submit-btn" id="assignmentSubmitBtn">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="main-table-container">
            <div class="row mb-3">
                <div class="col-lg-12 d-flex justify-content-end align-items-end">
                    <div class="btn-flex">
                        <a href="{{ $backRoute }}" class="add-btn bg-filter">Back</a>
                        @if($canEdit)
                            <a class="add-btn" data-bs-toggle="modal" href="#depotAssignmentModal" role="button">
                                Add Assignment
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="table-over">
                <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center nowrap">SL No</th>
                            <th class="text-center nowrap">Depot</th>
                            @if($requiresReportingManager)
                                <th class="text-center nowrap">Reporting To</th>
                            @endif
                            <th class="text-center nowrap">From Date</th>
                            <th class="text-center nowrap">To Date</th>
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
            .assignment-detail-card {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                min-height: 78px;
                padding: 14px 16px;
            }

            .assignment-detail-card span {
                color: #6b7280;
                display: block;
                font-size: 13px;
                margin-bottom: 8px;
            }

            .assignment-detail-card strong {
                color: #111827;
                display: block;
                font-size: 15px;
                font-weight: 600;
                word-break: break-word;
            }
        </style>
        <script>
            $(function () {
                $('#depotId, #reportingTo').select2({
                    placeholder: '--- Select ---',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#depotAssignmentModal')
                });

                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ url()->current() }}"
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'depot_name', name: 'depot.name', orderable: false, searchable: false, className: 'text-center' },
                        @if($requiresReportingManager)
                        { data: 'reporting_to_name', name: 'reporting_to_name', orderable: false, searchable: false, className: 'text-center' },
                        @endif
                        { data: 'from_date_display', name: 'from_date', className: 'text-center' },
                        { data: 'to_date_display', name: 'to_date', className: 'text-center' },
                        { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[{{ $requiresReportingManager ? 3 : 2 }}, 'desc']]
                });

                @if($requiresReportingManager)
                var pendingReportingTo = '';

                function loadReportingManagers() {
                    var depotId = $('#depotId').val();

                    if (!depotId) {
                        $('#reportingTo').html('<option value="">--- Select ---</option>').val('').trigger('change.select2');
                        return;
                    }

                    $('#reportingTo').html('<option value="">Loading...</option>').prop('disabled', true).trigger('change.select2');

                    $.ajax({
                        url: "{{ route('depot-assignments.reporting-managers') }}",
                        type: 'GET',
                        data: {
                            module: @json($module),
                            depot_id: depotId
                        },
                        success: function (managers) {
                            var options = '<option value="">--- Select ---</option>';
                            managers.forEach(function (manager) {
                                var code = manager.code ? ` (${manager.code})` : '';
                                var role = manager.role ? ` - ${manager.role}` : '';
                                options += `<option value="${manager.id}">${manager.name}${code}${role}</option>`;
                            });
                            $('#reportingTo').html(options).prop('disabled', false).val(pendingReportingTo).trigger('change.select2');
                            pendingReportingTo = '';
                        },
                        error: function () {
                            $('#reportingTo').html('<option value="">--- Select ---</option>').prop('disabled', false).trigger('change.select2');
                            showToast('error', 'Unable to load reporting managers.');
                        }
                    });
                }

                $('#depotId').on('change', loadReportingManagers);
                @endif

                $('#depotAssignmentModal').on('show.bs.modal', function (event) {
                    if ($(event.relatedTarget).hasClass('edit-assignment')) {
                        return;
                    }

                    resetAssignmentForm();
                });

                $(document).on('click', '.edit-assignment', function () {
                    var button = $(this);
                    $('#depotAssignmentModalTitle').text('Edit Depot Assignment');
                    $('#depotAssignmentForm').attr('action', button.data('url'));
                    $('#assignmentMethod').val('PUT');
                    @if($requiresReportingManager)
                    pendingReportingTo = String(button.data('reporting-to') || '');
                    @endif
                    $('#depotId').val(button.data('depot-id')).trigger('change');
                    $('#fromDate').val(button.data('from-date'));
                    $('#toDate').val(button.data('to-date'));
                    $('#assignmentSubmitBtn').text('Update');
                });

                $('#depotAssignmentForm').on('submit', function (e) {
                    e.preventDefault();

                    var form = $(this);
                    var button = form.find('button[type="submit"]');
                    var originalText = button.data('original-text') || button.text();
                    form.find('.error-text').text('');
                    form.find('.form-control, .form-select').removeClass('is-invalid');
                    button.data('original-text', originalText).prop('disabled', true).text('Please wait...');

                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        success: function (res) {
                            $('#depotAssignmentModal').modal('hide');
                            resetAssignmentForm();
                            table.ajax.reload();
                            showToast('success', res.message);
                        },
                        error: function (xhr) {
                            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                                $.each(xhr.responseJSON.errors, function (field, messages) {
                                    form.find('.' + field + '_error').text(messages[0]);
                                    form.find('[name="' + field + '"]').addClass('is-invalid');
                                });
                                return;
                            }

                            showToast('error', xhr.responseJSON?.message || 'An error occurred. Please try again.');
                        },
                        complete: function () {
                            button.prop('disabled', false).text(originalText);
                        }
                    });
                });

                function resetAssignmentForm() {
                    var form = $('#depotAssignmentForm');
                    form[0]?.reset();
                    form.attr('action', form.data('store-url'));
                    $('#assignmentMethod').val('POST');
                    $('#depotAssignmentModalTitle').text('Add Depot Assignment');
                    $('#depotId').val('').trigger('change');
                    @if($requiresReportingManager)
                    pendingReportingTo = '';
                    $('#reportingTo').html('<option value="">--- Select ---</option>').prop('disabled', false).val('').trigger('change.select2');
                    @endif
                    $('#assignmentSubmitBtn').text('Submit');
                    form.find('.error-text').text('');
                    form.find('.form-control, .form-select').removeClass('is-invalid');
                }
            });

            function deleteDepotAssignment(id) {
                deleteRecord('/depot-assignments/' + id, 'table', 'Do you really want to delete this depot assignment?');
            }
        </script>
    @endsection
</x-app-layout>
