@section('title')
    Trip Report
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Trip Report</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Trip Report</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="main-table-container">
                    <form id="tripReportForm" action="{{ route('trips.report.download') }}" method="GET">
                        <div class="row align-items-end">
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="date_from">From Date</label>
                                    <input type="date" id="date_from" name="date_from" class="form-control shadow-none"
                                        value="{{ old('date_from', $filters['date_from'] ?? '') }}">
                                    @error('date_from')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="date_to">To Date</label>
                                    <input type="date" id="date_to" name="date_to" class="form-control shadow-none"
                                        value="{{ old('date_to', $filters['date_to'] ?? '') }}">
                                    @error('date_to')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="depot_id">Depot</label>
                                    <select id="depot_id" name="depot_id" class="form-select shadow-none select2">
                                        <option value="">---Select---</option>
                                        @foreach ($depots as $depot)
                                            <option value="{{ $depot->id }}" @selected((string) old('depot_id', $filters['depot_id'] ?? '') === (string) $depot->id)>
                                                {{ $depot->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('depot_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="filter-btns-top justify-content-start">
                                    <button type="button" class="reset-btn border-0" id="resetTripReport">Reset</button>
                                    <button type="submit" class="search-btn" style="font-size: 11px;">Download
                                        Report</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                $('#depot_id').select2({
                    placeholder: '---Select---',
                    allowClear: true,
                    width: '100%'
                });

                $('#resetTripReport').on('click', function () {
                    $('#date_from, #date_to').val('');
                    $('#depot_id').val('').trigger('change');
                });
            });
        </script>
    @endsection
</x-app-layout>
