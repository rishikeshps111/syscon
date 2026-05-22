@section('title')
    Vehicle Assignments
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Vehicle Assignments</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicle Management</a></li>
                    <li class="breadcrumb-item active">Assignments</li>
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
            <div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-labelledby="addAssignmentLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addAssignmentLabel">Add Assignment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('vehicles.assignments.store', $vehicle->id) }}" method="POST"
                                id="assignmentForm"
                                data-store-url="{{ route('vehicles.assignments.store', $vehicle->id) }}">
                                @csrf
                                <input type="hidden" name="_method" id="assignmentMethod" value="POST">
                                <div class="row">
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="driverId">Driver <span class="text-danger">*</span></label>
                                        <select name="driver_id" id="driverId" class="form-select shadow-none" required>
                                            <option value="">--- Select ---</option>
                                            @foreach ($drivers as $driver)
                                                <option value="{{ $driver->id }}">
                                                    {{ trim(($driver->code ? $driver->code . ' - ' : '') . $driver->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="routeId">Route <span class="text-danger">*</span></label>
                                        <select name="route_id" id="routeId" class="form-select shadow-none" required>
                                            <option value="">--- Select ---</option>
                                            @foreach ($routes as $route)
                                                <option value="{{ $route->id }}">
                                                    {{ trim(($route->code ? $route->code . ' - ' : '') . $route->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="tripId">Trip</label>
                                        <select name="trip_id" id="tripId" class="form-select shadow-none">
                                            <option value="">--- Select ---</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="assignedFrom">Assigned From <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="assigned_from" id="assignedFrom"
                                            class="form-control shadow-none" required>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="assignedTo">Assigned To</label>
                                        <input type="datetime-local" name="assigned_to" id="assignedTo"
                                            class="form-control shadow-none">
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="assignmentStatus">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="assignmentStatus" class="form-select shadow-none"
                                            required>
                                            @foreach ($statuses as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-12 d-flex justify-content-center align-items-center">
                                        <div class="btn-flex">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="submit-btn"
                                                id="assignmentSubmitBtn">Submit</button>
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
                            <a class="add-btn" data-bs-toggle="modal" href="#addAssignmentModal" role="button">
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
                            <th class="text-center nowrap">SL NO</th>
                            <th class="text-center nowrap">Driver</th>
                            <th class="text-center nowrap">Route</th>
                            <th class="text-center nowrap">Assigned From</th>
                            <th class="text-center nowrap">Assigned To</th>
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
                $('#driverId, #routeId').select2({
                    placeholder: '--- Select ---',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#addAssignmentModal')
                });

                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('vehicles.assignments.index', $vehicle->id) }}"
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'driver_name', name: 'driver.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'route_name', name: 'route.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'assigned_from_display', name: 'assigned_from', className: 'text-center' },
                        { data: 'assigned_to_display', name: 'assigned_to', className: 'text-center' },
                        { data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[3, 'desc']]
                });

                $('#addAssignmentModal').on('show.bs.modal', function (event) {
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
                    $('#addAssignmentLabel').text('Edit Assignment');
                    $('#assignmentForm').attr('action', button.data('url'));
                    $('#assignmentMethod').val('PUT');
                    $('#driverId').val(button.data('driver-id')).trigger('change');
                    $('#routeId').val(button.data('route-id')).trigger('change');
                    $('#assignedFrom').val(button.data('assigned-from'));
                    $('#assignedTo').val(button.data('assigned-to'));
                    $('#assignmentStatus').val(button.data('status'));
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
                            $('#addAssignmentModal').modal('hide');
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
                    $('#addAssignmentLabel').text('Add Assignment');
                    $('#driverId, #routeId').val('').trigger('change');
                    $('#assignmentStatus').val('Active');
                    $('#assignmentSubmitBtn').text('Submit');
                }
            });

            function deleteAssignment(id) {
                deleteRecord('/vehicle-assignments/' + id, 'table', 'Do you really want to delete this assignment?');
            }
        </script>
    @endsection
</x-app-layout>