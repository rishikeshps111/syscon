@section('title')
    Completed Trip Details
@endsection
<x-app-layout>
    @php
        $trip = $entry->sheet?->trip;
        $date = fn ($value) => $value ? $value->format('d-m-Y') : '-';
        $dateTime = fn ($value) => $value ? $value->format('d-m-Y H:i') : '-';
        $time = fn ($value) => $value ? substr($value, 0, 5) : '-';
    @endphp

    <div class="page-title">
        <h3>Completed Trip Details</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('trips.completed.index') }}">Completed Trips</a></li>
                <li class="breadcrumb-item active">View Details</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="row">
            <div class="col-lg-10 mb-3">
                <div class="main-table-container mt-3 bg-white">
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <div class="btn-flex justify-content-end">
                                <a href="{{ route('trips.completed.index') }}" class="btn btn-secondary">Back</a>
                                <a href="{{ route('trips.completed.download-pdf', $entry->id) }}" class="btn btn-primary">Download PDF</a>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-preview">
                                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo">
                                <span><i class="fa-solid fa-circle-check"></i> Completed</span>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-preview-widget s-preview-widget">
                                <h3>{{ $entry->sheet?->code ?: '-' }}</h3>
                                <ul>
                                    <li>Trip Title : <span>{{ $trip?->trip_title ?: '-' }}</span></li>
                                    <li>Date : <span>{{ $date($entry->sheet?->date) }}</span></li>
                                    <li>Side : <span>{{ ucfirst((string) $entry->side) }}</span></li>
                                    <li>Depot : <span>{{ $trip?->depot?->name ?: '-' }}</span></li>
                                    <li>Driver Name : <span>{{ $entry->driverProfile?->user?->name ?: $assignment?->driverProfile?->user?->name ?: '-' }}</span></li>
                                    <li>Vehicle No : <span>{{ $entry->vehicle?->vehicle_no ?: $assignment?->vehicle?->vehicle_no ?: '-' }}</span></li>
                                    <li>Status : <span>Completed</span></li>
                                    <li>Starting KM : <span>{{ $entry->starting_km ?? '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Route Details</h6>
                                <ul>
                                    <li><label>From :</label> <span>{{ $trip?->route?->startPoint?->name ?: '-' }}</span></li>
                                    <li><label>To :</label> <span>{{ $trip?->route?->endPoint?->name ?: '-' }}</span></li>
                                    <li><label>Departure Time :</label> <span>{{ $time($entry->departure_time) }}</span></li>
                                    <li><label>Arrival Time :</label> <span>{{ $time($entry->arrival_time) }}</span></li>
                                    <li><label>Actual Start Time :</label> <span>{{ $time($entry->actual_start_time) }}</span></li>
                                    <li><label>Actual Reach Time :</label> <span>{{ $time($entry->actual_reach_time) }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Vehicle Details</h6>
                                <ul>
                                    <li><label>Vehicle No :</label> <span>{{ $entry->vehicle?->vehicle_no ?: $assignment?->vehicle?->vehicle_no ?: '-' }}</span></li>
                                    <li><label>Starting KM :</label> <span>{{ $entry->starting_km ?? '-' }}</span></li>
                                    <li><label>Starting Charge :</label> <span>{{ $entry->starting_electric_charge !== null ? $entry->starting_electric_charge . '%' : '-' }}</span></li>
                                    <li><label>Vehicle Condition :</label> <span>{{ $entry->vehicle_condition ?: '-' }}</span></li>
                                    <li><label>Vehicle Verified :</label> <span>{{ $entry->is_vehicle_verified ? 'Yes' : 'No' }}</span></li>
                                    <li><label>Verified By :</label> <span>{{ $entry->vehicle_verified_by ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Driver Verification</h6>
                                <ul>
                                    <li><label>Driver :</label> <span>{{ $entry->driverProfile?->user?->name ?: $assignment?->driverProfile?->user?->name ?: '-' }}</span></li>
                                    <li><label>Driver Verified :</label> <span>{{ $entry->is_driver_verified ? 'Yes' : 'No' }}</span></li>
                                    <li><label>Verified By :</label> <span>{{ $entry->driver_verified_by ?: '-' }}</span></li>
                                    <li><label>Verified At :</label> <span>{{ $dateTime($entry->driver_verified_at) }}</span></li>
                                    <li><label>Final Verified :</label> <span>{{ $entry->is_verified_by_driver ? 'Yes' : 'No' }}</span></li>
                                    <li><label>Final Verified By :</label> <span>{{ $entry->verified_by_driver ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Supervisor Verification</h6>
                                <ul>
                                    <li><label>Supervisor Verified :</label> <span>{{ $entry->is_verified_by_supervisor ? 'Yes' : 'No' }}</span></li>
                                    <li><label>Verified By :</label> <span>{{ $entry->verified_by_supervisor ?: '-' }}</span></li>
                                    <li><label>Verified At :</label> <span>{{ $dateTime($entry->verified_by_supervisor_at) }}</span></li>
                                    <li><label>Vehicle Verified At :</label> <span>{{ $dateTime($entry->vehicle_verified_at) }}</span></li>
                                    <li><label>Notes :</label> <span>{{ $entry->notes ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-preview-widget s-preview-address" style="height: unset;">
                                <h6>Stops</h6>
                                <ul>
                                    @forelse($trip?->route?->stops ?? [] as $stop)
                                        <li><label>{{ $stop->position }}. {{ $stop->location?->name ?? $stop->name ?? '-' }} :</label> <span>{{ $stop->expected_reach_time ?: '-' }}</span></li>
                                    @empty
                                        <li><label>Stops :</label> <span>No records found.</span></li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
