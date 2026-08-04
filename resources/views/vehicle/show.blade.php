@section('title')
    Vehicle Details
@endsection
<x-app-layout>
    <div class="page-title">
        <h3>Vehicle Details</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicle Management</a></li>
                <li class="breadcrumb-item active">View Details</li>
            </ol>
        </nav>
    </div>

    @php
        $date = fn ($value) => $value ? $value->format('d-m-Y') : '-';
        $money = fn ($value) => filled($value) ? number_format((float) $value, 2) : '-';
        $vehicleType = \App\Models\Vehicle::TYPES[$record->vehicle_type] ?? $record->vehicle_type;
        $fuelType = \App\Models\Vehicle::FUEL_TYPES[$record->fuel_type] ?? $record->fuel_type;
    @endphp

    <section class="section dashboard">
        <div class="row">
            <div class="col-lg-10 mb-3">
                <div class="main-table-container mt-3 bg-white">
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <div class="btn-flex justify-content-end">
                                <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Back</a>
                                <a href="{{ route('vehicles.download-pdf', $record->id) }}" class="btn btn-primary">Download PDF</a>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-preview">
                                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo">
                                @if ($record->status === 'Active')
                                    <span><i class="fa-solid fa-circle-check"></i> Active</span>
                                @else
                                    <span class="{{ $record->status === 'Under Maintenance' ? 'status-orange' : 'status-red' }}">{{ $record->status }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-preview-widget s-preview-widget">
                                <h3>{{ $record->vehicle_no }}</h3>
                                <ul>
                                    <li>Vehicle Code : <span>{{ $record->vehicle_code ?: '-' }}</span></li>
                                    <li>Type : <span>{{ $vehicleType ?: '-' }}</span></li>
                                    <li>Fuel Type : <span>{{ $fuelType ?: '-' }}</span></li>
                                    <li>Vehicle Classification : <span>{{ $record->vehicleClassification?->title ?: '-' }}</span></li>
                                    <li>OEM : <span>{{ $record->oem?->oem_name ?: '-' }}</span></li>
                                    <li>State : <span>{{ $record->state?->name ?: '-' }}</span></li>
                                    <li>Status : <span>{{ $record->status ?: '-' }}</span></li>
                                    <li>Verified : <span>{{ $record->is_verified ? 'Yes' : 'No' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Vehicle Information</h6>
                                <ul>
                                    <li><label>Make :</label> <span>{{ $record->make ?: '-' }}</span></li>
                                    <li><label>Model :</label> <span>{{ $record->model ?: '-' }}</span></li>
                                    <li><label>Variant :</label> <span>{{ $record->variant ?: '-' }}</span></li>
                                    <li><label>Seating Capacity :</label> <span>{{ $record->capacity_seating ?? '-' }}</span></li>
                                    <li><label>Load Capacity :</label> <span>{{ $record->capacity_load ?? '-' }}</span></li>
                                    <li><label>Battery Capacity :</label> <span>{{ $record->battery_capacity ?? '-' }}</span></li>
                                    <li><label>Range KM :</label> <span>{{ $record->range_km ?? '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Identification & GPS</h6>
                                <ul>
                                    <li><label>Engine No :</label> <span>{{ $record->engine_no ?: '-' }}</span></li>
                                    <li><label>Chassis No :</label> <span>{{ $record->chassis_no ?: '-' }}</span></li>
                                    <li><label>GPS Enabled :</label> <span>{{ $record->gps_enabled ? 'Yes' : 'No' }}</span></li>
                                    <li><label>GPS IMEI :</label> <span>{{ $record->gps_imei ?: '-' }}</span></li>
                                    <li><label>Depot :</label> <span>{{ $record->depot?->name ?: '-' }}</span></li>
                                    <li><label>Branch :</label> <span>{{ $record->branch?->name ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Registration & Compliance</h6>
                                <ul>
                                    <li><label>Registration Date :</label> <span>{{ $date($record->registration_date) }}</span></li>
                                    <li><label>RC Validity :</label> <span>{{ $date($record->registration_valid_upto) }}</span></li>
                                    <li><label>Fitness Expiry :</label> <span>{{ $date($record->fitness_expiry) }}</span></li>
                                    <li><label>Permit Expiry :</label> <span>{{ $date($record->permit_expiry) }}</span></li>
                                    <li><label>Insurance Expiry :</label> <span>{{ $date($record->insurance_expiry) }}</span></li>
                                    <li><label>Pollution Expiry :</label> <span>{{ $date($record->pollution_expiry) }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Latest Summary</h6>
                                <ul>
                                    <li><label>Assignments :</label> <span>{{ $record->assignments->count() }}</span></li>
                                    <li><label>Maintenance Logs :</label> <span>{{ $record->maintenanceLogs->count() }}</span></li>
                                    <li><label>Fuel Logs :</label> <span>{{ $record->fuelLogs->count() }}</span></li>
                                    <li><label>Documents :</label> <span>{{ $record->documents->count() }}</span></li>
                                    <li><label>Remarks :</label> <span>{{ $record->remarks ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-document-view">
                                <h6>Documents</h6>
                                <div class="row">
                                    @forelse ($record->documents as $document)
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="v-doc-preview s-doc-preview">
                                                <p>{{ $document->documentType?->name ?: 'Document' }}</p>
                                                <img src="{{ asset('assets/img/file.avif') }}" alt="Document">
                                                <a href="#!" class="mb-3 view-document"
                                                    data-bs-toggle="modal" data-bs-target="#viewDoc"
                                                    data-preview="{{ route('vehicle-documents.preview', $document->id) }}"
                                                    data-download="{{ route('vehicle-documents.download', $document->id) }}">
                                                    View
                                                </a>
                                                <small>
                                                    {{ $document->is_verified ? 'Verified' : 'Not Verified' }}
                                                    @if ($document->expiry_date)
                                                        | Expiry {{ $document->expiry_date->format('d-m-Y') }}
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-lg-12">
                                            <p class="text-muted mb-0">No documents uploaded.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-preview-widget s-preview-address" style="height: unset;">
                                <h6>Recent Assignments</h6>
                                <ul>
                                    @forelse ($record->assignments->take(5) as $assignment)
                                        <li>
                                            <label>{{ $assignment->driver?->name ?: '-' }} :</label>
                                            <span>{{ $assignment->route?->route_name ?: '-' }} | {{ $assignment->status }}</span>
                                        </li>
                                    @empty
                                        <li><label>Assignments :</label> <span>No records found.</span></li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address" style="height: unset;">
                                <h6>Recent Maintenance</h6>
                                <ul>
                                    @forelse ($record->maintenanceLogs->take(5) as $log)
                                        <li><label>{{ $log->maintenance_type }} :</label> <span>{{ $date($log->service_date) }} | {{ $log->status }}</span></li>
                                    @empty
                                        <li><label>Maintenance :</label> <span>No records found.</span></li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address" style="height: unset;">
                                <h6>Recent Fuel / Energy Logs</h6>
                                <ul>
                                    @forelse ($record->fuelLogs->take(5) as $log)
                                        <li><label>{{ $log->fuel_type }} :</label> <span>{{ $log->quantity }} | {{ $money($log->cost) }} | {{ $date($log->date) }}</span></li>
                                    @empty
                                        <li><label>Fuel Logs :</label> <span>No records found.</span></li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
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
        <script>
            $(function () {
                $(document).on('click', '.view-document', function () {
                    $('#documentPreviewFrame').attr('src', $(this).data('preview'));
                    $('#documentDownloadLink').attr('href', $(this).data('download'));
                });

                $('#viewDoc').on('hidden.bs.modal', function () {
                    $('#documentPreviewFrame').attr('src', '');
                });
            });
        </script>
    @endsection
</x-app-layout>
