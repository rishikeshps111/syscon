@section('title')
    View Trip Sheet
@endsection
<x-app-layout>
    @php
        $date = fn($value) => $value ? $value->format('d-m-Y') : '-';
        $time = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('h:ia') : '-';
        $delay = function ($startTime, $actualStartTime) {
            if (!$startTime || !$actualStartTime) {
                return '-';
            }

            $start = \Carbon\Carbon::parse($startTime);
            $actual = \Carbon\Carbon::parse($actualStartTime);
            $minutes = (int) round($start->diffInMinutes($actual, false));

            return $minutes > 0
                ? $minutes . ' ' . (\abs($minutes) === 1 ? 'min' : 'mins')
                : ($minutes === 0
                    ? '0 min'
                    : \abs($minutes) . ' ' . (\abs($minutes) === 1 ? 'min' : 'mins') . ' early');
        };
        $dateRange = $record->from_date || $record->to_date
            ? trim(($record->from_date?->format('d-m-Y') ?: '-') . ' - ' . ($record->to_date?->format('d-m-Y') ?: '-'))
            : '-';
        $vehicle = $record->assignments->first()?->vehicle;
    @endphp

    <div class="page-title">
        <h3>View Trip Sheet</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Trip Management</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="main-table-container">
            <div class="col-lg-12 mb-3">
                <div class="vehicle-short-preview">
                    <h4>Trip Details</h4>
                    <ul>
                        <li>Trip Code: <span>{{ $record->code ?: '-' }}</span></li>
                        <li>Route: <span>{{ $record->route?->route_name ?: '-' }}</span></li>
                        {{-- <li>Schedule: <span>{{ $record->schedule_type ?: '-' }}</span></li> --}}
                        {{-- <li>Trip Side:
                            <span>{{ \App\Models\Trip::TRIP_SIDES[$record->trip_side] ?? '-' }}</span>
                        </li> --}}
                        <li>State:
                            <span>{{ $record->route?->startPoint?->state?->name ?: $record->depot?->state?->name ?: '-' }}</span>
                        </li>
                        @if($record->trip_side === 'both')
                            <li>Depot: <span>{{ $record->depot?->name ?: '-' }}</span></li>
                        @else
                            <li>From Depot: <span>{{ $record->fromDepot?->name ?: '-' }}</span></li>
                            <li>To Depot: <span>{{ $record->toDepot?->name ?: '-' }}</span></li>
                        @endif
                        <li>KMS: <span>{{ $record->schedule_km ?? '-' }}</span></li>
                        <li>Trip Nature: <span>{{ $record->tripNature?->title ?: '-' }}</span></li>
                        <li>Date: <span>{{ $dateRange }}</span></li>
                        {{-- <li>Start Time: <span>{{ $time($record->start_time) }}</span></li> --}}
                        {{-- <li>End Time: <span>{{ $time($record->end_time) }}</span></li> --}}
                        <li>Created By: <span>{{ $record->createdBy?->name ?: '-' }}</span></li>
                        <li>Created At: <span>{{ $date($record->created_at) }}</span></li>
                    </ul>
                </div>
            </div>

            {{-- <div class="col-lg-12">
                <div class="vehicle-short-preview">
                    <h4>Vehicle Details</h4>
                    <ul>
                        <li>Vehicle No: <span>{{ $vehicle?->vehicle_no ?: '-' }}</span></li>
                        <li>Type: <span>{{ $vehicle?->vehicle_type ?: '-' }}</span></li>
                        <li>Fuel Type: <span>{{ $vehicle?->fuel_type ?: '-' }}</span></li>
                        <li>Vendor / Branch: <span>{{ $vehicle?->oem?->oem_name ?: $vehicle?->branch?->name ?: '-'
                                }}</span></li>
                        <li>Capacity: <span>{{ $vehicle?->capacity_seating ? $vehicle->capacity_seating . ' Seats' : '-'
                                }}</span></li>
                        <li>Insurance Expiry: <span>{{ $date($vehicle?->insurance_expiry) }}</span></li>
                        <li>Fitness Expiry: <span>{{ $date($vehicle?->fitness_expiry) }}</span></li>
                    </ul>
                </div>
            </div>
        </div> --}}

        <div class="main-table-container mt-3">
            <h5 class="title-w-sec bt-ween-print">
                Trip Sheet
                <a href="{{ route('trips.sheet.view', array_merge(['trip' => $record->id, 'export' => 'csv'], $filters)) }}"
                    class="add-btn">Export</a>
            </h5>
            <hr>

            <form method="GET" action="{{ route('trips.sheet.view', $record->id) }}" id="sheetViewFilters"
                class="row align-items-end mb-3">
                <div class="col-md-4 col-lg-3 o-f-inp mb-2 mb-md-0">
                    <label for="sheetViewDate" class="form-label m-0">Date</label>
                    <input type="date" id="sheetViewDate" name="entry_date" class="form-control shadow-none"
                        value="{{ $filters['entry_date'] ?? '' }}"
                        min="{{ $record->from_date?->format('Y-m-d') }}"
                        max="{{ $record->to_date?->format('Y-m-d') }}">
                </div>
                <div class="col-md-4 col-lg-3 o-f-inp mb-2 mb-md-0">
                    <label for="sheetViewSer" class="form-label m-0">SER Code</label>
                    <input type="text" id="sheetViewSer" name="ser_search" class="form-control shadow-none"
                        value="{{ $filters['ser_search'] ?? '' }}" placeholder="Search SER code">
                </div>
                <div class="col-md-4 col-lg-2 o-f-inp mb-2 mb-md-0">
                    <label for="sheetViewPerPage" class="form-label m-0">Per Page</label>
                    <select id="sheetViewPerPage" name="per_page" class="form-select shadow-none">
                        @foreach([10, 15, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 15) === $size)>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-2 d-flex gap-2">
                    <a href="{{ route('trips.sheet.view', $record->id) }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </a>
                </div>
            </form>

            <div class="mt-3" style="overflow-x: auto;">
                <table class="align-middle mb-0 table xl-table">
                    <thead>
                        <tr>
                            <th class="text-center nowrap">Sl No</th>
                            <th class="text-center nowrap">Date</th>
                            <th class="text-center nowrap">Trip Code</th>
                            <th class="text-center">Starting From</th>
                            <th class="text-center">Destination Point</th>
                            <th class="text-center">Start Time</th>
                            <th class="text-center">Actual Start Time</th>
                            <th class="text-center">Reach Time</th>
                            <th class="text-center">Actual Reach Time</th>
                            {{-- <th class="text-center">Shift</th> --}}
                            <th class="text-center">Driver</th>
                            <th class="text-center">Vehicle</th>
                            <th class="text-center">Delay</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            @php
                                $route = $entry->sheet?->trip?->route;
                                $startingFrom = $entry->side === 'down'
                                    ? ($route?->endPoint?->name ?: '-')
                                    : ($route?->startPoint?->name ?: '-');
                                $destinationPoint = $entry->side === 'down'
                                    ? ($route?->startPoint?->name ?: '-')
                                    : ($route?->endPoint?->name ?: '-');
                                $assignment = $entry->sheet?->trip?->assignments
                                    ->first(fn($assignment) => $assignment->from_date?->lte($entry->sheet?->date) && $assignment->to_date?->gte($entry->sheet?->date));
                                $driver = $entry->driverProfile?->user?->name
                                    ?: $assignment?->driverProfile?->user?->name
                                    ?: '-';
                            @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $entries->firstItem() + $loop->index }}</td>
                                <td class="text-center text-muted nowrap">
                                    {{ $entry->sheet?->date?->format('d M Y') ?: '-' }}
                                </td>
                                <td class="text-center text-muted nowrap">{{ $entry->code ?: '-' }}</td>
                                <td class="text-center text-muted">{{ $startingFrom }}</td>
                                <td class="text-center text-muted">{{ $destinationPoint }}</td>
                                <td class="text-center text-muted">{{ $time($entry->departure_time) }}</td>
                                <td class="text-center text-muted">{{ $time($entry->actual_start_time) }}</td>
                                <td class="text-center text-muted">{{ $time($entry->arrival_time) }}</td>
                                <td class="text-center text-muted">{{ $time($entry->actual_reach_time) }}</td>
                                {{-- <td class="text-center text-muted">{{ ucfirst((string) $entry->side) ?: '-' }}</td>
                                --}}
                                <td class="text-center text-muted">{{ $driver }}</td>
                                <td class="text-center text-muted">{{ $entry->vehicle?->vehicle_no ?: '-' }}</td>
                                <td class="text-center text-muted">
                                    {{ $delay($entry->departure_time, $entry->actual_start_time) }}
                                </td>
                                <td class="text-center text-muted">
                                    <div class="action-btns">
                                        @if($entry->dor?->is_completed && !auth()->user()?->hasRole('Super Admin'))
                                            <a href="{{ route('trips.sheet.entries.dor.preview', [$record->id, $entry->id]) }}"
                                                class="btn-edit btn-nowrap btn-cstm">
                                                View DOR
                                            </a>
                                        @else
                                            <a href="{{ route('trips.sheet.entries.dor', [$record->id, $entry->id]) }}"
                                                class="btn-edit btn-nowrap btn-cstm">
                                                {{ $entry->dor ? 'Edit DOR' : 'Create DOR' }}
                                            </a>
                                            @if($entry->dor)
                                                <a href="{{ route('trips.sheet.entries.dor.preview', [$record->id, $entry->id]) }}"
                                                    class="btn-edit btn-nowrap btn-cstm" style="background-color: #b23939;">View
                                                    DOR</a>
                                            @else
                                                <a href="#!" class="btn-edit btn-nowrap btn-cstm disabled"
                                                    style="background-color: #b23939; opacity: .65; pointer-events: none;">View
                                                    DOR</a>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted">No trip sheet entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="sheet-view-pagination mt-3">
                {{ $entries->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </section>

    <style>
        .sheet-view-pagination nav > div:last-child { align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .sheet-view-pagination nav > div:last-child > div:first-child { margin: 0; color: #6c757d; font-size: 14px; }
        .sheet-view-pagination .pagination { margin: 0; flex-wrap: wrap; }
        .sheet-view-pagination .page-link { min-width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px; }
    </style>

    @section('scripts')
        <script>
            $(function () {
                var filterForm = $('#sheetViewFilters');

                $('#sheetViewDate, #sheetViewSer, #sheetViewPerPage').on('change', function () {
                    filterForm.trigger('submit');
                });
            });
        </script>
    @endsection
</x-app-layout>
