@section('title')
    Roaster Details
@endsection
<x-app-layout>
    <div class="page-title">
        <h3>Roaster Details</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('rosters.index') }}">Roaster</a></li>
                <li class="breadcrumb-item active">View Details</li>
            </ol>
        </nav>
    </div>

    @php
        $time = fn($value) => $value ? substr($value, 0, 5) : '-';
        $tripEntries = $record->tripSheetEntries;
    @endphp

    <section class="section dashboard">
        <div class="row">
            <div class="col-lg-10 mb-3">
                <div class="main-table-container mt-3 bg-white">
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <div class="btn-flex justify-content-end">
                                <a href="{{ route('rosters.index') }}" class="btn btn-secondary">Back</a>
                                <a href="{{ route('rosters.download-pdf', $record->id) }}"
                                    class="btn btn-primary">Download PDF</a>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-preview">
                                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo">
                                {{-- <span>{{ \App\Models\Roster::STATUSES[$record->status] ?? $record->status }}</span> --}}
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-preview-widget s-preview-widget">
                                <h3>{{ $record->code ?: '-' }}</h3>
                                <ul>
                                    <li>Date : <span>{{ $record->duty_date?->format('d-m-Y') ?: '-' }}</span></li>
                                    <li>Shift Type :
                                        <span>{{ \App\Models\Roster::SHIFT_TYPES[$record->shift_type] ?? '-' }}</span>
                                    </li>
                                    <li>Shift Time : <span>{{ $time($record->shift_start_time) }} -
                                            {{ $time($record->shift_end_time) }}</span></li>
                                    <li>Reporting To Time : <span>{{ $time($record->reporting_time) }}</span></li>
                                    {{-- <li>Second Reporting To Time : <span>{{ $time($record->reporting_to_time) }}</span>
                                    </li> --}}
                                    <li>Attendance :
                                        <span>{{ $record->attendance_status ? (\App\Models\Roster::ATTENDANCE_STATUSES[$record->attendance_status] ?? $record->attendance_status) : 'Not Marked' }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Basic Info</h6>
                                <ul>
                                    <li><label>State :</label> <span>{{ $record->state?->name ?: '-' }}</span></li>
                                    <li><label>Vendor :</label> <span>{{ $record->oem?->oem_name ?: '-' }}</span></li>
                                    <li><label>Depot :</label> <span>{{ $record->depot?->name ?: '-' }}</span></li>
                                    {{-- <li><label>Status :</label>
                                        <span>{{ \App\Models\Roster::STATUSES[$record->status] ?? '-' }}</span>
                                    </li> --}}
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Trip Details</h6>
                                @forelse($tripEntries as $entry)
                                    <ul>
                                        <li><label>Trip Sheet Code :</label> <span>{{ $entry->sheet?->code ?: '-' }}</span>
                                        </li>
                                        <li><label>Trip Code :</label> <span>{{ $entry->sheet?->trip?->code ?: '-' }}</span>
                                        </li>
                                        <li><label>Trip Title :</label>
                                            <span>{{ $entry->sheet?->trip?->trip_title ?: '-' }}</span>
                                        </li>
                                        {{-- <li><label>Side :</label> <span>{{ ucfirst((string) $entry->side) ?: '-' }}</span>
                                        </li> --}}
                                    </ul>
                                    @unless($loop->last)
                                        <hr>
                                    @endunless
                                @empty
                                    <ul>
                                        <li><label>Trip Sheet Code :</label> <span>-</span></li>
                                    </ul>
                                @endforelse
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Assignment</h6>
                                <ul>
                                    <li><label>Driver :</label>
                                        <span>{{ $record->driverProfile?->user?->name ?: '-' }}</span>
                                    </li>
                                    <li><label>Vehicle :</label> <span>{{ $record->vehicle?->vehicle_no ?: '-' }}</span>
                                    </li>
                                    <li class="d-none"><label>Supervisor :</label>
                                        <span>{{ $record->supervisorProfile?->user?->name ?: '-' }}</span>
                                    </li>
                                    <li class="d-none"><label>Controller :</label>
                                        <span>{{ $record->controllerProfile?->user?->name ?: '-' }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Additional</h6>
                                <ul>
                                    <li><label>Remarks :</label> <span>{{ $record->remarks ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>