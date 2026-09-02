@section('title')
    Import Trip Sheet
@endsection
<style>
    .font-para p{
        font-size:13.5px;
    }
    .font-para h5{
        font-size:18px;
            color: #025187;
    }
</style>
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Import Trip Sheet</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trip Management</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.sheet', $record->id) }}">Trip Sheet</a></li>
                    <li class="breadcrumb-item active">Import</li>
                </ol>
            </nav>
        </div>

        @if(session('import_summary'))
            @php
                $summary = session('import_summary');
            @endphp
            <div class="alert alert-success mb-3" role="alert">
                <h5 class="alert-heading mb-3">
                    <i class="fa-solid fa-circle-check me-1"></i> Trip sheet imported successfully
                </h5>
                <div class="row g-3">
                    <div class="col-md-4 col-lg-2">
                        <small class="d-block text-muted">Date Range</small>
                        <strong>{{ $summary['from_date'] }} - {{ $summary['to_date'] }}</strong>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <small class="d-block text-muted">Dates Added</small>
                        <strong>{{ $summary['date_count'] }}</strong>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <small class="d-block text-muted">SER Codes</small>
                        <strong>{{ $summary['service_count'] }}</strong>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <small class="d-block text-muted">Rounds / SER</small>
                        <strong>{{ $summary['rounds_per_service'] }}</strong>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <small class="d-block text-muted">Entries / Date</small>
                        <strong>{{ $summary['entries_per_date'] }}</strong>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <small class="d-block text-muted">Total Entries</small>
                        <strong>{{ $summary['total_entries'] }}</strong>
                    </div>
                </div>
                <hr>
                <a href="{{ route('trips.sheet', $record->id) }}" class="btn btn-sm btn-success">
                    View Trip Sheet Entries
                </a>
            </div>
        @endif

        <div class="main-table-container mb-3">
            <div class="row">
                <div class="col-lg-4 o-f-inp mb-3">
                    <label>Trip No</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->code }}" disabled>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label>Trip</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->trip_title }}" disabled>
                </div>
                {{-- <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip Side</label>
                    <input type="text" class="form-control shadow-none"
                        value="{{ \App\Models\Trip::TRIP_SIDES[$record->trip_side] ?? '-' }}" disabled>
                </div> --}}
                @if($record->trip_side === 'both')
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label>Depot</label>
                        <input type="text" class="form-control shadow-none" value="{{ $record->depot?->name ?? '-' }}"
                            disabled>
                    </div>
                @else
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label>From Depot</label>
                        <input type="text" class="form-control shadow-none" value="{{ $record->fromDepot?->name ?? '-' }}"
                            disabled>
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label>To Depot</label>
                        <input type="text" class="form-control shadow-none" value="{{ $record->toDepot?->name ?? '-' }}"
                            disabled>
                    </div>
                @endif
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Date Range</label>
                    <input type="text" class="form-control shadow-none"
                        value="{{ $record->from_date?->format('d M Y') }} - {{ $record->to_date?->format('d M Y') }}"
                        disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>NAT</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->tripNature->title ?? '-' }}"
                        disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>KMS</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->schedule_km }}" disabled>
                </div>
                 <div class="col-lg-3 o-f-inp mb-3">
                    <label>Rounds per Round</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->rounds_per_trip }}" disabled>
                </div>
                <div class="col-lg-12 o-f-inp">
                    <label>Route Stops</label>
                    <div class="route-stops-horizontal mt-2">
                        @php
                            $routePoints = collect();
                            if ($record->route?->startPoint) {
                                $routePoints->push([
                                    'name' => $record->route->startPoint->name,
                                    'short_name' => $record->route->startPoint->short_name,
                                    'label' => 'Starting Depot',
                                ]);
                            }
                            foreach ($record->route?->stops ?? [] as $stop) {
                                $routePoints->push([
                                    'name' => $stop->location?->name ?? $stop->name,
                                    'short_name' => $stop->location?->short_name,
                                    'label' => 'Stop',
                                ]);
                            }
                            if ($record->route?->endPoint) {
                                $routePoints->push([
                                    'name' => $record->route->endPoint->name,
                                    'short_name' => $record->route->endPoint->short_name,
                                    'label' => 'Ending Depot',
                                ]);
                            }
                        @endphp

                        @forelse($routePoints as $point)
                            @if(!$loop->first)
                                <span class="route-stop-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                            @endif
                            <div class="route-stop-item">
                                <div class="fw-semibold">
                                    {{ $point['name'] }}{{ $point['short_name'] ? ' (' . $point['short_name'] . ')' : '' }}
                                </div>
                                <small class="text-muted">{{ $point['label'] }}</small>
                            </div>
                        @empty
                            <span class="text-muted">No route stops available.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="main-table-container h-100  font-para">
                    <h5>1. Download the configured workbook</h5>
                    <p class="text-muted">
                        Trip metadata, ED service codes, nature, kilometres per round, and round locations are generated
                        automatically.
                        Only the time cells are left blank.
                    </p>
                    <div class="btns-group-container">
                        <a href="{{ route('trips.sheet.sample-excel', $record->id) }}" class="exp-btn">
                    Download Sample Excel
                    </a>
                    </div>
                    
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="main-table-container h-100 font-para ">
                    <h5>2. Fill times and import</h5>
                    <p class="text-muted">Do not change the title, code, metadata rows, service codes, columns, or route
                        locations.</p>
                    <form class="js-loading-form" method="POST" action="{{ route('trips.sheet.import', $record->id) }}"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="overwrite_confirmed" id="overwriteConfirmed" value="">
                        <div class="o-f-inp mb-3 file-input">
                            <label for="sheetFile">Excel File <span class="text-danger">*</span></label>
                            <input type="file" id="sheetFile" name="sheet_file" class="form-control shadow-none"
                                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                            @error('sheet_file')
                                @foreach($errors->get('sheet_file') as $message)
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @endforeach
                            @enderror
                        </div>
                        <div class="btns-group-container">
                            <a href="{{ route('trips.sheet', $record->id) }}" class="bk-btn">Back</a>
                            <button type="submit" class="imp-btn js-loading-submit">Import Excel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <style>
        .route-stops-horizontal {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;

    width: 100% !important;

    /*padding: 16px !important;*/

    /*background: #f8fafc !important;*/

    /*border: 1px solid #e2e8f0 !important;*/
    /*border-radius: 12px !important;*/

    overflow-x: auto !important;
    overflow-y: hidden !important;

    scrollbar-width: thin !important;
}



.route-stop-item {
    position: relative !important;

    flex: 0 0 auto !important;

    min-width: 170px !important;

    padding: 13px 16px !important;

    background: #ffffff !important;

    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;

    box-shadow: 0 2px 7px rgba(15, 23, 42, 0.04) !important;

    transition: all 0.2s ease !important;
}


/* Hover */

.route-stop-item:hover {
    transform: translateY(-2px) !important;

    border-color: #cbd5e1 !important;

    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08) !important;
}

.route-stop-item .fw-semibold {
    display: block !important;

    margin-bottom: 4px !important;

    color: #1e293b !important;

    font-size: 13px !important;
    font-weight: 700 !important;

    line-height: 1.4 !important;

    white-space: nowrap !important;
}


.route-stop-item small {
    display: block !important;

    color: #64748b !important;

    font-size: 10px !important;
    font-weight: 500 !important;

    line-height: 1.3 !important;
}



.route-stop-arrow {
    flex: 0 0 auto !important;

    width: 32px !important;
    height: 32px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    color: #64748b !important;

    background: #ffffff !important;

    border: 1px solid #e2e8f0 !important;

    border-radius: 50% !important;

    font-size: 12px !important;

    box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04) !important;
}



/* First Stop */

.route-stops-horizontal .route-stop-item:first-child {
    background: #eff6ff !important;

    border-color: #eff6ff !important;
}

.route-stops-horizontal .route-stop-item:first-child .fw-semibold {
    color: #1d4ed8 !important;
}

.route-stops-horizontal .route-stop-item:first-child small {
    color: #2563eb !important;
}


/* Last Stop */

.route-stops-horizontal .route-stop-item:last-child {
   background: #fae9ea !important;
    border-color: #fae9ea !important;
}

.route-stops-horizontal .route-stop-item:last-child .fw-semibold {
    color: #dc3545  !important;
}

.route-stops-horizontal .route-stop-item:last-child small {
    color: #dc3545  !important;
}



.route-stop-item:not(:first-child):not(:last-child) {
    background: #f8fafc !important;

    border-color: #e2e8f0 !important;
}




.route-stops-horizontal + * {
    margin-top: 0 !important;
}



.route-stops-horizontal::-webkit-scrollbar {
    height: 5px !important;
}

.route-stops-horizontal::-webkit-scrollbar-track {
    background: #f1f5f9 !important;

    border-radius: 10px !important;
}

.route-stops-horizontal::-webkit-scrollbar-thumb {
    background: #cbd5e1 !important;

    border-radius: 10px !important;
}
    </style>

    @section('scripts')
        <script>
            $(function () {
                $('.js-loading-form').on('submit', function (event) {
                    var form = this;
                    var submitButton = $(form).find('.js-loading-submit');

                    if (@json($hasExistingTripData) && $('#overwriteConfirmed').val() !== 'yes') {
                        event.preventDefault();

                        Swal.fire({
                            icon: 'warning',
                            title: 'Are you sure?',
                            text: 'Current Trip Data will be OverRide. Please confirm',
                            showCancelButton: true,
                            confirmButtonText: 'Yes',
                            cancelButtonText: 'No',
                            reverseButtons: true
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                $('#overwriteConfirmed').val('yes');
                                submitButton.prop('disabled', true).text('Importing...');
                                form.submit();
                            }
                        });

                        return;
                    }

                    submitButton.prop('disabled', true).text('Importing...');
                });
            });
        </script>
    @endsection
</x-app-layout>
