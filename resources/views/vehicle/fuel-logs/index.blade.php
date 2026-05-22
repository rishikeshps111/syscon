@section('title')
    Fuel / Energy Logs
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Fuel / Energy Logs</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicle Management</a></li>
                    <li class="breadcrumb-item active">Fuel / Energy Logs</li>
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
                        <span>Fuel Type</span>
                        <strong>{{ $fuelTypes[$vehicle->fuel_type] ?? $vehicle->fuel_type }}</strong>
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
            <div class="modal fade" id="fuelLogModal" tabindex="-1" aria-labelledby="fuelLogModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="fuelLogModalLabel">Add Fuel / Energy Log</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('vehicles.fuel-logs.store', $vehicle->id) }}" method="POST"
                                id="fuelLogForm" data-store-url="{{ route('vehicles.fuel-logs.store', $vehicle->id) }}">
                                @csrf
                                <input type="hidden" name="_method" id="fuelLogMethod" value="POST">
                                <div class="row">
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="tripId">Trip</label>
                                        <select name="trip_id" id="tripId" class="form-select shadow-none">
                                            <option value="">--- Select ---</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="fuelType">Fuel Type <span class="text-danger">*</span></label>
                                        <select name="fuel_type" id="fuelType" class="form-select shadow-none" required>
                                            <option value="">--- Select ---</option>
                                            @foreach ($fuelTypes as $value => $label)
                                                <option value="{{ $value }}" @selected($vehicle->fuel_type === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="quantity">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" min="0" step="0.01" name="quantity" id="quantity"
                                            class="form-control shadow-none" required>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="cost">Cost</label>
                                        <input type="number" min="0" step="0.01" name="cost" id="cost"
                                            class="form-control shadow-none">
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="odometerReading">Odometer Reading</label>
                                        <input type="number" min="0" name="odometer_reading" id="odometerReading"
                                            class="form-control shadow-none">
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="logDate">Date <span class="text-danger">*</span></label>
                                        <input type="date" name="date" id="logDate" class="form-control shadow-none"
                                            required>
                                    </div>
                                    <div class="col-lg-12 d-flex justify-content-center align-items-center">
                                        <div class="btn-flex">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="submit-btn" id="fuelLogSubmitBtn">Submit</button>
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
                            <a class="add-btn" data-bs-toggle="modal" href="#fuelLogModal" role="button">
                                Add Fuel / Energy Log
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
                            <th class="text-center nowrap">Fuel Type</th>
                            <th class="text-center nowrap">Quantity</th>
                            <th class="text-center nowrap">Cost</th>
                            <th class="text-center nowrap">Odometer Reading</th>
                            <th class="text-center nowrap">Date</th>
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
                var defaultFuelType = @json($vehicle->fuel_type);

                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('vehicles.fuel-logs.index', $vehicle->id) }}"
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'fuel_type_display', name: 'fuel_type', className: 'text-center' },
                        { data: 'quantity_display', name: 'quantity', className: 'text-center' },
                        { data: 'cost_display', name: 'cost', className: 'text-center' },
                        { data: 'odometer_display', name: 'odometer_reading', className: 'text-center' },
                        { data: 'date_display', name: 'date', className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[5, 'desc']]
                });

                $('#fuelLogModal').on('show.bs.modal', function (event) {
                    if ($(event.relatedTarget).hasClass('edit-fuel-log')) {
                        return;
                    }

                    if ($('#fuelLogMethod').val() === 'PUT') {
                        return;
                    }

                    resetFuelLogForm();
                });

                $(document).on('click', '.edit-fuel-log', function () {
                    var button = $(this);
                    $('#fuelLogModalLabel').text('Edit Fuel / Energy Log');
                    $('#fuelLogForm').attr('action', button.data('url'));
                    $('#fuelLogMethod').val('PUT');
                    $('#fuelType').val(button.data('fuel-type'));
                    $('#quantity').val(button.data('quantity'));
                    $('#cost').val(button.data('cost'));
                    $('#odometerReading').val(button.data('odometer-reading'));
                    $('#logDate').val(button.data('date'));
                    $('#fuelLogSubmitBtn').text('Update');
                });

                $('#fuelLogForm').on('submit', function (e) {
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
                            $('#fuelLogModal').modal('hide');
                            resetFuelLogForm();
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

                function resetFuelLogForm() {
                    var form = $('#fuelLogForm');
                    form[0].reset();
                    form.attr('action', form.data('store-url'));
                    $('#fuelLogMethod').val('POST');
                    $('#fuelLogModalLabel').text('Add Fuel / Energy Log');
                    $('#fuelType').val(defaultFuelType);
                    $('#fuelLogSubmitBtn').text('Submit');
                }
            });

            function deleteFuelLog(id) {
                deleteRecord('/vehicle-fuel-logs/' + id, 'table', 'Do you really want to delete this fuel / energy log?');
            }
        </script>
    @endsection
</x-app-layout>