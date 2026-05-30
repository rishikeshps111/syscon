@section('title')
    Trip Assignments
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Trip Assignments</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trip Management</a></li>
                    <li class="breadcrumb-item active">Assignments</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec mb-3">Trip Details</h5>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="trip-detail-card">
                        <span>Trip No</span>
                        <strong>{{ $trip->code }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="trip-detail-card">
                        <span>Trip Title</span>
                        <strong>{{ $trip->trip_title ?: '-' }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="trip-detail-card">
                        <span>Route</span>
                        <strong>{{ $trip->route?->route_name ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="trip-detail-card">
                        <span>Depot</span>
                        <strong>{{ $trip->depot?->name ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        @can('trips.assign')
            <div class="modal fade" id="assignmentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="assignmentModalTitle">Add Assignment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="assignmentForm" method="POST"
                                action="{{ route('trips.assignments.store', $trip->id) }}"
                                data-store-url="{{ route('trips.assignments.store', $trip->id) }}">
                                @csrf
                                <input type="hidden" name="_method" id="assignmentMethod" value="POST">
                                <div class="row">
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="assignmentFromDate">From Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control shadow-none" id="assignmentFromDate"
                                            name="from_date" value="{{ $trip->from_date?->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="assignmentToDate">To Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control shadow-none" id="assignmentToDate"
                                            name="to_date" value="{{ $trip->to_date?->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="assignmentVehicle">Choose Vehicle <span class="text-danger">*</span></label>
                                        <select class="form-select shadow-none" id="assignmentVehicle" name="vehicle_id">
                                            <option value="">--- Select ---</option>
                                            @foreach($vehicles as $vehicle)
                                                <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_no }} - {{ $vehicle->vehicle_type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="assignmentDriver">Select Driver <span class="text-danger">*</span></label>
                                        <select class="form-select shadow-none" id="assignmentDriver" name="driver_profile_id">
                                            <option value="">--- Select ---</option>
                                            @foreach($drivers as $driver)
                                                <option value="{{ $driver->id }}">{{ $driver->user?->name ?? 'Driver #' . $driver->id }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-12 o-f-inp mb-3">
                                        <label for="assignmentNotes">Notes</label>
                                        <textarea class="form-control shadow-none" id="assignmentNotes" name="notes" rows="2"></textarea>
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
        @endcan

        <div class="main-table-container">
            <div class="row mb-3">
                <div class="col-lg-12 d-flex justify-content-end align-items-end">
                    <div class="btn-flex">
                        <a href="{{ route('trips.index') }}" class="add-btn bg-filter">Back</a>
                        @can('trips.assign')
                            <a class="add-btn" data-bs-toggle="modal" href="#assignmentModal" role="button">
                                Add Assignment
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="table-over">
                <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center nowrap">SL No</th>
                            <th class="text-center nowrap">Date Range</th>
                            <th class="text-center nowrap">Vehicle</th>
                            <th class="text-center nowrap">Driver</th>
                            <th class="text-center nowrap">Notes</th>
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
            .trip-detail-card {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                min-height: 78px;
                padding: 14px 16px;
            }

            .trip-detail-card span {
                color: #6b7280;
                display: block;
                font-size: 13px;
                margin-bottom: 8px;
            }

            .trip-detail-card strong {
                color: #111827;
                display: block;
                font-size: 15px;
                font-weight: 600;
                word-break: break-word;
            }
        </style>
        <script>
            $(function () {
                $('#assignmentVehicle, #assignmentDriver').select2({
                    placeholder: '--- Select ---',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#assignmentModal')
                });

                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('trips.assignments.index', $trip->id) }}"
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'date_range', name: 'from_date', className: 'text-center' },
                        { data: 'vehicle_no', name: 'vehicle.vehicle_no', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'driver_name', name: 'driverProfile.user.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'notes_display', name: 'notes', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[1, 'desc']]
                });

                $('#assignmentModal').on('show.bs.modal', function (event) {
                    if ($(event.relatedTarget).hasClass('edit-assignment')) {
                        return;
                    }

                    if ($('#assignmentMethod').val() === 'PUT') {
                        return;
                    }

                    resetAssignmentForm();
                });

                $(document).on('click', '.edit-assignment', function () {
                    var button = $(this);
                    $('#assignmentModalTitle').text('Edit Assignment');
                    $('#assignmentForm').attr('action', button.data('url'));
                    $('#assignmentMethod').val('PUT');
                    $('#assignmentFromDate').val(button.data('from-date'));
                    $('#assignmentToDate').val(button.data('to-date'));
                    $('#assignmentVehicle').val(button.data('vehicle-id')).trigger('change');
                    $('#assignmentDriver').val(button.data('driver-profile-id')).trigger('change');
                    $('#assignmentNotes').val(button.data('notes') || '');
                    $('#assignmentSubmitBtn').text('Update');
                });

                $('#assignmentForm').on('submit', function (e) {
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
                            $('#assignmentModal').modal('hide');
                            resetAssignmentForm();
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

                function resetAssignmentForm() {
                    var form = $('#assignmentForm');
                    form[0].reset();
                    form.attr('action', form.data('store-url'));
                    $('#assignmentMethod').val('POST');
                    $('#assignmentModalTitle').text('Add Assignment');
                    $('#assignmentFromDate').val("{{ $trip->from_date?->format('Y-m-d') }}");
                    $('#assignmentToDate').val("{{ $trip->to_date?->format('Y-m-d') }}");
                    $('#assignmentVehicle, #assignmentDriver').val('').trigger('change');
                    $('#assignmentNotes').val('');
                    $('#assignmentSubmitBtn').text('Submit');
                }
            });

            function deleteAssignment(id) {
                deleteRecord('/trip-assignments/' + id, 'table', 'Do you really want to delete this assignment?');
            }
        </script>
    @endsection
</x-app-layout>
