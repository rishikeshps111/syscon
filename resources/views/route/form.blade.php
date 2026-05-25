@section('title')
    {{ isset($record) ? 'Edit Route' : 'Add Route' }}
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Route Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('routes.index') }}">Route Management</a></li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit Route' : 'Add Route' }}</li>
                </ol>
            </nav>
        </div>

        <form id="routeForm" method="POST" action="{{ isset($record) ? route('routes.update', $record->id) : route('routes.store') }}">
            @csrf
            @if(isset($record))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-xl-12">
                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Basic Information</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="route_code">Route Code <span class="text-danger">*</span></label>
                                <input type="text" id="route_code" class="form-control shadow-none"
                                    value="{{ $record->route_code ?? $generatedCode ?? '' }}" disabled>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="route_name">Route Name <span class="text-danger">*</span></label>
                                <input type="text" id="route_name" name="route_name"
                                    class="form-control shadow-none @error('route_name') is-invalid @enderror"
                                    value="{{ old('route_name', $record->route_name ?? '') }}">
                                @error('route_name')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="state_id">State <span class="text-danger">*</span></label>
                                <select id="state_id" name="state_id" class="form-select shadow-none select2 @error('state_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}" @selected((int) old('state_id', $record->state_id ?? 0) === $state->id)>{{ $state->name }}</option>
                                    @endforeach
                                </select>
                                @error('state_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="district_id">District <span class="text-danger">*</span></label>
                                <select id="district_id" name="district_id" class="form-select shadow-none select2 @error('district_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($districts as $district)
                                        <option value="{{ $district->id }}" data-state-id="{{ $district->state_id }}" @selected((int) old('district_id', $record->district_id ?? 0) === $district->id)>{{ $district->name }}</option>
                                    @endforeach
                                </select>
                                @error('district_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="route_type">Route Type <span class="text-danger">*</span></label>
                                <select id="route_type" name="route_type" class="form-select shadow-none @error('route_type') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($routeTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('route_type', $record->route_type ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('route_type')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="route_category">Category <span class="text-danger">*</span></label>
                                <select id="route_category" name="route_category" class="form-select shadow-none @error('route_category') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($routeCategories as $value => $label)
                                        <option value="{{ $value }}" @selected(old('route_category', $record->route_category ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('route_category')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Start &amp; End Points</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="start_point_id">Start Point <span class="text-danger">*</span></label>
                                <select id="start_point_id" name="start_point_id" class="form-select shadow-none select2 @error('start_point_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}" data-state-id="{{ $location->state_id }}" data-district-id="{{ $location->district_id }}" @selected((int) old('start_point_id', $record->start_point_id ?? 0) === $location->id)>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                                @error('start_point_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="end_point_id">End Point <span class="text-danger">*</span></label>
                                <select id="end_point_id" name="end_point_id" class="form-select shadow-none select2 @error('end_point_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}" data-state-id="{{ $location->state_id }}" data-district-id="{{ $location->district_id }}" @selected((int) old('end_point_id', $record->end_point_id ?? 0) === $location->id)>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                                @error('end_point_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="total_distance_km">Distance <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" id="total_distance_km" name="total_distance_km"
                                    class="form-control shadow-none @error('total_distance_km') is-invalid @enderror"
                                    value="{{ old('total_distance_km', $record->total_distance_km ?? '') }}">
                                @error('total_distance_km')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Route Details</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="estimated_duration">Estimated Duration <span class="text-danger">*</span></label>
                                <input type="time" id="estimated_duration" name="estimated_duration"
                                    class="form-control shadow-none @error('estimated_duration') is-invalid @enderror"
                                    value="{{ old('estimated_duration', isset($record) && $record->estimated_duration ? substr($record->estimated_duration, 0, 5) : '') }}">
                                @error('estimated_duration')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Additional</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select id="status" name="status" class="form-select shadow-none @error('status') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $record->status ?? 'Active') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-12 o-f-inp mb-3">
                                <label for="remarks">Remarks</label>
                                <textarea id="remarks" name="remarks" class="form-control shadow-none @error('remarks') is-invalid @enderror">{{ old('remarks', $record->remarks ?? '') }}</textarea>
                                @error('remarks')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12 d-flex justify-content-center align-items-center">
                        <div class="btn-flex">
                            <a href="{{ route('routes.index') }}" class="reset-btn">Cancel</a>
                            <button type="submit" class="submit-btn js-loading-submit" data-loading-text="Saving...">
                                {{ isset($record) ? 'Update' : 'Submit' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>

    @php
        $districtOptions = $districts->map(function ($district) {
            return [
                'id' => $district->id,
                'name' => $district->name,
                'state_id' => $district->state_id,
            ];
        })->values();

        $locationOptions = $locations->map(function ($location) {
            return [
                'id' => $location->id,
                'name' => $location->name,
                'state_id' => $location->state_id,
                'district_id' => $location->district_id,
            ];
        })->values();
    @endphp

    @section('scripts')
        <script>
            $(function () {
                $('.select2').select2({ width: '100%', placeholder: '---Select---', allowClear: true });

                var districts = @json($districtOptions);
                var locations = @json($locationOptions);

                var selectedDistrictId = "{{ old('district_id', $record->district_id ?? '') }}";
                var selectedStartPointId = "{{ old('start_point_id', $record->start_point_id ?? '') }}";
                var selectedEndPointId = "{{ old('end_point_id', $record->end_point_id ?? '') }}";

                function setOptions(selector, items, selectedValue, labelKey) {
                    var select = $(selector);
                    select.empty().append(new Option('---Select---', ''));

                    items.forEach(function (item) {
                        var option = new Option(item[labelKey], item.id, false, item.id.toString() === selectedValue.toString());
                        select.append(option);
                    });

                    select.val(selectedValue || '').trigger('change.select2');
                }

                function renderDistricts(selectedValue) {
                    var stateId = $('#state_id').val();
                    var filteredDistricts = stateId
                        ? districts.filter(function (district) { return district.state_id.toString() === stateId; })
                        : districts;

                    var exists = filteredDistricts.some(function (district) {
                        return district.id.toString() === (selectedValue || '').toString();
                    });

                    setOptions('#district_id', filteredDistricts, exists ? selectedValue : '', 'name');
                }

                function renderLocations(startValue, endValue) {
                    var stateId = $('#state_id').val();
                    var districtId = $('#district_id').val();
                    var filteredLocations = locations.filter(function (location) {
                        return (!stateId || location.state_id.toString() === stateId)
                            && (!districtId || location.district_id.toString() === districtId);
                    });

                    var startExists = filteredLocations.some(function (location) {
                        return location.id.toString() === (startValue || '').toString();
                    });
                    var endExists = filteredLocations.some(function (location) {
                        return location.id.toString() === (endValue || '').toString();
                    });

                    setOptions('#start_point_id', filteredLocations, startExists ? startValue : '', 'name');
                    setOptions('#end_point_id', filteredLocations, endExists ? endValue : '', 'name');
                }

                $('#state_id').on('change', function () {
                    renderDistricts('');
                    renderLocations('', '');
                });

                $('#district_id').on('change', function () {
                    renderLocations('', '');
                });

                renderDistricts(selectedDistrictId);
                renderLocations(selectedStartPointId, selectedEndPointId);

                $('#routeForm').on('submit', function () {
                    var button = $(this).find('.js-loading-submit');
                    button.prop('disabled', true).html(button.data('loading-text') || 'Loading...');
                });
            });
        </script>
    @endsection
</x-app-layout>
