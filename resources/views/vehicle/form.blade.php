@section('title')
    {{ isset($record) ? 'Edit Vehicle' : 'Add Vehicle' }}
@endsection

<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Vehicle Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicle Management</a></li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit Vehicle' : 'Add Vehicle' }}</li>
                </ol>
            </nav>
        </div>

        <form id="vehicleForm" method="POST" action="{{ isset($record) ? route('vehicles.update', $record->id) : route('vehicles.store') }}">
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
                                <label for="vehicle_code">Vehicle Code</label>
                                <input type="text" id="vehicle_code" class="form-control shadow-none"
                                    value="{{ $record->vehicle_code ?? $generatedCode ?? '' }}" disabled>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="vehicle_no">Vehicle Number <span class="text-danger">*</span></label>
                                <input type="text" id="vehicle_no" name="vehicle_no"
                                    class="form-control shadow-none @error('vehicle_no') is-invalid @enderror"
                                    value="{{ old('vehicle_no', $record->vehicle_no ?? '') }}">
                                @error('vehicle_no')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="oem_id">OEM Name <span class="text-danger">*</span></label>
                                <select id="oem_id" name="oem_id" class="form-select shadow-none select2 @error('oem_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($oems as $oem)
                                        <option value="{{ $oem->id }}" @selected((int) old('oem_id', $record->oem_id ?? 0) === $oem->id)>{{ $oem->oem_name }}</option>
                                    @endforeach
                                </select>
                                @error('oem_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="vehicle_type">Vehicle Type <span class="text-danger">*</span></label>
                                <select id="vehicle_type" name="vehicle_type" class="form-select shadow-none @error('vehicle_type') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($vehicleTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('vehicle_type', $record->vehicle_type ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('vehicle_type')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="vehicle_classification_id">Vehicle Classification <span class="text-danger">*</span></label>
                                <select id="vehicle_classification_id" name="vehicle_classification_id"
                                    class="form-select shadow-none select2 @error('vehicle_classification_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($vehicleClassifications as $classification)
                                        <option value="{{ $classification->id }}"
                                            @selected((int) old('vehicle_classification_id', $record->vehicle_classification_id ?? 0) === $classification->id)>
                                            {{ $classification->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_classification_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="fuel_type">Fuel Type <span class="text-danger">*</span></label>
                                <select id="fuel_type" name="fuel_type" class="form-select shadow-none @error('fuel_type') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($fuelTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('fuel_type', $record->fuel_type ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('fuel_type')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="vehicle_category">Category <span class="text-danger">*</span></label>
                                <select id="vehicle_category" name="vehicle_category"
                                    class="form-select shadow-none @error('vehicle_category') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($categories as $value => $label)
                                        <option value="{{ $value }}"
                                            @selected(old('vehicle_category', $record->vehicle_category ?? '') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_category')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="make">Make <span class="text-danger">*</span></label>
                                <input type="text" id="make" name="make" class="form-control shadow-none @error('make') is-invalid @enderror"
                                    value="{{ old('make', $record->make ?? '') }}">
                                @error('make')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="model">Model <span class="text-danger">*</span></label>
                                <input type="text" id="model" name="model" class="form-control shadow-none @error('model') is-invalid @enderror"
                                    value="{{ old('model', $record->model ?? '') }}">
                                @error('model')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="variant">Variant</label>
                                <input type="text" id="variant" name="variant" class="form-control shadow-none @error('variant') is-invalid @enderror"
                                    value="{{ old('variant', $record->variant ?? '') }}">
                                @error('variant')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Capacity</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="capacity_seating">Seating Capacity</label>
                                <input type="number" min="0" id="capacity_seating" name="capacity_seating" class="form-control shadow-none @error('capacity_seating') is-invalid @enderror"
                                    value="{{ old('capacity_seating', $record->capacity_seating ?? '') }}">
                                @error('capacity_seating')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="capacity_load">Load Capacity</label>
                                <input type="number" step="0.01" min="0" id="capacity_load" name="capacity_load" class="form-control shadow-none @error('capacity_load') is-invalid @enderror"
                                    value="{{ old('capacity_load', $record->capacity_load ?? '') }}">
                                @error('capacity_load')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3 ev-fields">
                                <label>EV Fields</label>
                                <div class="d-flex gap-2">
                                    <input type="number" step="0.01" min="0" name="battery_capacity" class="form-control shadow-none @error('battery_capacity') is-invalid @enderror"
                                        placeholder="Battery Capacity" value="{{ old('battery_capacity', $record->battery_capacity ?? '') }}">
                                    <input type="number" min="0" name="range_km" class="form-control shadow-none @error('range_km') is-invalid @enderror"
                                        placeholder="Range (KM)" value="{{ old('range_km', $record->range_km ?? '') }}">
                                </div>
                                @error('battery_capacity')<span class="text-danger">{{ $message }}</span>@enderror
                                @error('range_km')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Identification</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="engine_no">Engine Number</label>
                                <input type="text" id="engine_no" name="engine_no" class="form-control shadow-none @error('engine_no') is-invalid @enderror"
                                    value="{{ old('engine_no', $record->engine_no ?? '') }}">
                                @error('engine_no')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="chassis_no">Chassis Number (Unique) <span class="text-danger">*</span></label>
                                <input type="text" id="chassis_no" name="chassis_no" class="form-control shadow-none @error('chassis_no') is-invalid @enderror"
                                    value="{{ old('chassis_no', $record->chassis_no ?? '') }}">
                                @error('chassis_no')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Registration &amp; Compliance</h5>
                        <hr>
                        <div class="row">
                            @foreach ([
                                'registration_date' => 'Registration Date',
                                'registration_valid_upto' => 'RC Validity',
                                'fitness_expiry' => 'Fitness Expiry',
                                'permit_expiry' => 'Permit Expiry',
                                'insurance_expiry' => 'Insurance Expiry',
                                'pollution_expiry' => 'Pollution Expiry',
                            ] as $field => $label)
                                <div class="col-lg-4 o-f-inp mb-3">
                                    <label for="{{ $field }}">{{ $label }}</label>
                                    <input type="date" id="{{ $field }}" name="{{ $field }}" class="form-control shadow-none @error($field) is-invalid @enderror"
                                        value="{{ old($field, isset($record) && $record->{$field} ? $record->{$field}->format('Y-m-d') : '') }}">
                                    @error($field)<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Location Mapping</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="state_id">State <span class="text-danger">*</span></label>
                                <select id="state_id" name="state_id" class="form-select shadow-none select2 @error('state_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}" @selected((int) old('state_id', $record->state_id ?? $defaultStateId ?? 0) === $state->id)>{{ $state->name }}</option>
                                    @endforeach
                                </select>
                                @error('state_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="depot_id">Depot <span class="text-danger">*</span></label>
                                <select id="depot_id" name="depot_id" class="form-select shadow-none select2 @error('depot_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($depots as $depot)
                                        <option value="{{ $depot->id }}" @selected((int) old('depot_id', $record->depot_id ?? 0) === $depot->id)>{{ $depot->name }}</option>
                                    @endforeach
                                </select>
                                @error('depot_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="branch_id">Branch <span class="text-danger">*</span></label>
                                <select id="branch_id" name="branch_id" class="form-select shadow-none select2 @error('branch_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((int) old('branch_id', $record->branch_id ?? 0) === $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                @error('branch_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">GPS Details</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-3 o-f-inp mb-3">
                                <label for="gps_enabled">GPS Enabled</label>
                                <input type="checkbox" id="gps_enabled" name="gps_enabled" value="1" class="toggle-btn mt-3"
                                    @checked(old('gps_enabled', $record->gps_enabled ?? false))>
                            </div>
                            <div class="col-lg-3 o-f-inp mb-3">
                                <label for="gps_imei">GPS IMEI Number</label>
                                <input type="text" id="gps_imei" name="gps_imei" class="form-control shadow-none @error('gps_imei') is-invalid @enderror"
                                    value="{{ old('gps_imei', $record->gps_imei ?? '') }}">
                                @error('gps_imei')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Status</h5>
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
                                <label for="remarks">Remark</label>
                                <textarea id="remarks" name="remarks" class="form-control shadow-none @error('remarks') is-invalid @enderror">{{ old('remarks', $record->remarks ?? '') }}</textarea>
                                @error('remarks')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="modal-btns-last">
                            <a href="{{ route('vehicles.index') }}" class="modal-btn-1">Cancel</a>
                            <button type="submit" class="modal-btn-2 js-loading-submit" data-loading-text="Saving...">
                                {{ isset($record) ? 'Update' : 'Submit' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>

    @section('scripts')
        <script>
            $(function () {
                $('.select2').select2({ width: '100%', placeholder: '---Select---', allowClear: true });

                function toggleEvFields() {
                    var isElectric = $('#fuel_type').val() === 'ELECTRIC';
                    $('.ev-fields input').prop('disabled', !isElectric);
                    $('.ev-fields').toggleClass('opacity-50', !isElectric);
                }

                $('#fuel_type').on('change', toggleEvFields);
                toggleEvFields();

                $('#vehicleForm').on('submit', function () {
                    var button = $(this).find('.js-loading-submit');
                    button.prop('disabled', true).html(button.data('loading-text') || 'Loading...');
                });
            });
        </script>
    @endsection
</x-app-layout>
