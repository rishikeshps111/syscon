@section('title')
    Vehicle Maintenance Logs
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Vehicle Maintenance Logs</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicle Management</a></li>
                    <li class="breadcrumb-item active">Maintenance Logs</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec mb-3">Basic Details</h5>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card-2">
                        <span>Vehicle Code</span>
                        <strong>{{ $vehicle->vehicle_code }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card-2">
                        <span>Vehicle No</span>
                        <strong>{{ $vehicle->vehicle_no }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card-2">
                        <span>OEM</span>
                        <strong>{{ $vehicle->oem?->oem_name ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card-2">
                        <span>Depot</span>
                        <strong>{{ $vehicle->depot?->name ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        @can('vehicles.edit')
            <div class="modal fade" id="maintenanceModal" tabindex="-1" aria-labelledby="maintenanceModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="maintenanceModalLabel">Add Maintenance Log</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('vehicles.maintenance-logs.store', $vehicle->id) }}" method="POST"
                                id="maintenanceForm"
                                data-store-url="{{ route('vehicles.maintenance-logs.store', $vehicle->id) }}">
                                @csrf
                                <input type="hidden" name="_method" id="maintenanceMethod" value="POST">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="maintenanceType">Maintenance Type <span
                                                class="text-danger">*</span></label>
                                        <select name="maintenance_type" id="maintenanceType" class="form-select shadow-none"
                                            required>
                                            <option value="">--- Select ---</option>
                                            @foreach ($types as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="tripId">Trip</label>
                                        <select name="trip_id" id="tripId" class="form-select shadow-none">
                                            <option value="">--- Select ---</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="serviceDate">Service Date <span class="text-danger">*</span></label>
                                        <input type="date" name="service_date" id="serviceDate"
                                            class="form-control shadow-none" required>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="nextServiceDue">Next Service Due</label>
                                        <input type="date" name="next_service_due" id="nextServiceDue"
                                            class="form-control shadow-none">
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="cost">Cost</label>
                                        <input type="number" min="0" step="0.01" name="cost" id="cost"
                                            class="form-control shadow-none">
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="vendorName">Vendor Name</label>
                                        <input type="text" name="vendor_name" id="vendorName"
                                            class="form-control shadow-none">
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="maintenanceStatus">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="maintenanceStatus" class="form-select shadow-none"
                                            required>
                                            @foreach ($statuses as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-12 o-f-inp mb-3">
                                        <label for="description">Description</label>
                                        <textarea name="description" id="description"
                                            class="form-control shadow-none"></textarea>
                                    </div>
                                    <div class="col-lg-12 ">
                                        <div class="modal-btns-last">
                                            <button type="button" class="modal-btn-1"
                                                data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="modal-btn-2"
                                                id="maintenanceSubmitBtn">Submit</button>
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
                <div class="col-lg-12">
                    <div class="btns-group-container" style="margin-bottom:-20px;">
                        <a href="{{ route('vehicles.index') }}" class="bk-btn">Back</a>
                        @can('vehicles.edit')
                            <a class="add-btn m-0" data-bs-toggle="modal" href="#maintenanceModal" role="button">
                                Add Maintenance Log
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
                            <th class="text-center nowrap">Description</th>
                            <th class="text-center nowrap">Cost</th>
                            <th class="text-center nowrap">Vendor</th>
                            <th class="text-center nowrap">Service Date</th>
                            <th class="text-center nowrap">Next Service Due</th>
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
                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('vehicles.maintenance-logs.index', $vehicle->id) }}"
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'maintenance_type', name: 'maintenance_type', className: 'text-center' },
                        { data: 'description', name: 'description', defaultContent: '-', className: 'text-center' },
                        { data: 'cost_display', name: 'cost', className: 'text-center' },
                        { data: 'vendor_name', name: 'vendor_name', defaultContent: '-', className: 'text-center' },
                        { data: 'service_date_display', name: 'service_date', className: 'text-center' },
                        { data: 'next_service_due_display', name: 'next_service_due', className: 'text-center' },
                        { data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[5, 'desc']]
                });

                $('#maintenanceModal').on('show.bs.modal', function (event) {
                    if ($(event.relatedTarget).hasClass('edit-maintenance')) {
                        return;
                    }

                    if ($('#maintenanceMethod').val() === 'PUT') {
                        return;
                    }

                    resetMaintenanceForm();
                });

                $(document).on('click', '.edit-maintenance', function () {
                    var button = $(this);
                    $('#maintenanceModalLabel').text('Edit Maintenance Log');
                    $('#maintenanceForm').attr('action', button.data('url'));
                    $('#maintenanceMethod').val('PUT');
                    $('#maintenanceType').val(button.data('maintenance-type'));
                    $('#description').val(button.data('description'));
                    $('#cost').val(button.data('cost'));
                    $('#vendorName').val(button.data('vendor-name'));
                    $('#serviceDate').val(button.data('service-date'));
                    $('#nextServiceDue').val(button.data('next-service-due'));
                    $('#maintenanceStatus').val(button.data('status'));
                    $('#maintenanceSubmitBtn').text('Update');
                });

                $('#maintenanceForm').on('submit', function (e) {
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
                            $('#maintenanceModal').modal('hide');
                            resetMaintenanceForm();
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

                function resetMaintenanceForm() {
                    var form = $('#maintenanceForm');
                    form[0].reset();
                    form.attr('action', form.data('store-url'));
                    $('#maintenanceMethod').val('POST');
                    $('#maintenanceModalLabel').text('Add Maintenance Log');
                    $('#maintenanceStatus').val('Open');
                    $('#maintenanceSubmitBtn').text('Submit');
                }
            });

            function deleteMaintenance(id) {
                deleteRecord('/vehicle-maintenance-logs/' + id, 'table', 'Do you really want to delete this maintenance log?');
            }
        </script>
    @endsection
</x-app-layout>