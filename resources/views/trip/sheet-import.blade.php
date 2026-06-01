@section('title')
    Import Trip Sheet CSV
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Import Trip Sheet CSV</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trip Management</a></li>
                    <li class="breadcrumb-item active">Import Trip Sheet</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <div class="row">
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip No</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->code }}" disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->trip_title }}" disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>From Date</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->from_date?->format('d/m/Y') }}" disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>To Date</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->to_date?->format('d/m/Y') }}" disabled>
                </div>
                <div class="col-lg-12 o-f-inp">
                    <label>Stops</label>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        @forelse($record->route?->stops ?? [] as $stop)
                            @if(! $loop->first)
                                <span class="d-inline-flex align-items-center text-muted">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </span>
                            @endif
                            <span class="btn btn-sm btn-outline-secondary disabled">{{ $stop->name }}</span>
                        @empty
                            <span class="btn btn-sm btn-light text-muted disabled">No stops selected</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-5 mb-3">
                <div class="main-table-container h-100">
                    <form class="js-loading-form" method="POST" action="{{ route('trips.sheet.import', $record->id) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="o-f-inp mb-3">
                            <label for="csvFile">CSV File <span class="text-danger">*</span></label>
                            <input type="file" id="csvFile" name="csv_file" class="form-control shadow-none" accept=".csv,text/csv">
                            @error('csv_file')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($errors->get('csv_file'))
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->get('csv_file') as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('trips.index') }}" class="btn btn-secondary">Back</a>
                            <button type="submit" class="btn btn-primary js-loading-submit">Import</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-7 mb-3">
                <div class="main-table-container h-100">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h5 class="mb-0">CSV Instructions</h5>
                        <a href="{{ route('trips.sheet.sample-csv', $record->id) }}" class="btn btn-outline-primary">Download Sample CSV</a>
                    </div>

                    <p class="mb-2">Create a CSV file with this exact header row:</p>
                    <code class="d-block mb-3">{{ implode(',', $headers) }}</code>

                    <div class="table-over mb-3">
                        <table class="align-middle mb-0 table tble-cstm" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Required</th>
                                    <th>Instruction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>trip_date</td>
                                    <td>Yes</td>
                                    <td>Use DD-MM-YYYY within the trip date range.</td>
                                </tr>
                                <tr>
                                    <td>status</td>
                                    <td>No</td>
                                    <td>Use pending, partial, completed, or cancelled. Blank defaults to pending.</td>
                                </tr>
                                <tr>
                                    <td>side</td>
                                    <td>Yes</td>
                                    <td>Use up/down. Both-side trips can import one row for each side per date.</td>
                                </tr>
                                <tr>
                                    <td>departure_time / arrival_time</td>
                                    <td>No</td>
                                    <td>Use HH:MM in 24-hour format.</td>
                                </tr>
                                <tr>
                                    <td>actual_start_time / actual_reach_time</td>
                                    <td>No</td>
                                    <td>Use HH:MM. Blank actual_start_time defaults from trip start time for up; blank actual_reach_time defaults from trip end time for down.</td>
                                </tr>
                                <tr>
                                    <td>starting_km / starting_electric_charge</td>
                                    <td>No</td>
                                    <td>Use whole numbers. Electric charge must be 0 to 100.</td>
                                </tr>
                                <tr>
                                    <td>vehicle_condition</td>
                                    <td>No</td>
                                    <td>Optional vehicle condition notes.</td>
                                </tr>
                                <tr>
                                    <td>is_* fields</td>
                                    <td>No</td>
                                    <td>Use yes/no for verification flags.</td>
                                </tr>
                                <tr>
                                    <td>*_verified_by / verified_by_*</td>
                                    <td>No</td>
                                    <td>Use a supervisor or controller name.</td>
                                </tr>
                                <tr>
                                    <td>*_verified_at / verified_by_*_at</td>
                                    <td>No</td>
                                    <td>Use DD-MM-YYYY HH:MM.</td>
                                </tr>
                                <tr>
                                    <td>notes</td>
                                    <td>No</td>
                                    <td>Optional notes for the trip sheet row.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="mb-0">
                        The file is checked completely before saving. Existing trip sheets for imported dates are replaced,
                        and other dates remain unchanged.
                    </p>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                $('.js-loading-form').on('submit', function () {
                    $(this).find('.js-loading-submit').prop('disabled', true).html('Loading...');
                });
            });
        </script>
    @endsection
</x-app-layout>
