@section('title')
    {{ isset($record) ? 'Edit OEM' : 'Add OEM' }}
@endsection


<style>
   .contact-row .select2-container{
       width:70px !important;
   }
   .contact-row  .select2-container--default .select2-selection--single .select2-selection__rendered{
       padding-left:0 !important;
   }
  .select2-container--open .select2-dropdown{
       min-width:300px !important;
       width:300px !important;
   }
   .select2-container--open .select2-dropdown--below{
        min-width:300px !important;
        width:300px !important;
   }
</style>
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>OEM Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('oems.index') }}">OEM/Vendor Management</a></li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit OEM' : 'Add OEM' }}</li>
                </ol>
            </nav>
        </div>

        <form id="oemForm" method="POST" action="{{ isset($record) ? route('oems.update', $record->id) : route('oems.store') }}">
            @csrf
            @if(isset($record))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-xl-12">
                    <div class="main-table-container">
                        <ul class="nav nav-tabs nav-tabs-custom" id="oemWizardTabs">

    <li class="nav-item">
        <button class="nav-link active"
                type="button"
                data-bs-toggle="tab"
                data-bs-target="#oemtb1">
            <i class="fa-solid fa-id-card"></i>
            Basic Information
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                type="button"
                data-bs-toggle="tab"
                data-bs-target="#oemtb2">
            <i class="fa-solid fa-user"></i>
            Contact Details
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                type="button"
                data-bs-toggle="tab"
                data-bs-target="#oemtb3">
            <i class="fa-solid fa-location-dot"></i>
            Address
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                type="button"
                data-bs-toggle="tab"
                data-bs-target="#oemtb4">
            <i class="fa-solid fa-building"></i>
            Business Details
        </button>
    </li>

</ul>

                        @php
                            $contacts = old('contacts', isset($record) ? $record->contacts->map(fn ($contact) => $contact->only([
                                'contact_person',
                                'designation',
                                'phone_country_code',
                                'phone',
                                'alternate_phone_country_code',
                                'alternate_phone',
                                'email',
                                'is_primary',
                            ]))->values()->all() : [[
                                'contact_person' => '',
                                'designation' => '',
                                'phone_country_code' => '+91',
                                'phone' => '',
                                'alternate_phone_country_code' => '+91',
                                'alternate_phone' => '',
                                'email' => '',
                                'is_primary' => true,
                            ]]);
                            $primaryContactIndex = old('primary_contact_index', collect($contacts)->search(fn ($contact) => ! empty($contact['is_primary'])));
                            $primaryContactIndex = $primaryContactIndex === false ? 0 : $primaryContactIndex;
                            $addresses = old('addresses', isset($record) ? $record->addresses->map(fn ($address) => $address->only([
                                'address_type',
                                'state_id',
                                'district_id',
                                'city_id',
                                'address_line1',
                                'address_line2',
                                'pincode',
                                'latitude',
                                'longitude',
                            ]))->values()->all() : [[
                                'address_type' => '',
                                'state_id' => $defaultStateId ?? '',
                                'district_id' => $defaultDistrictId ?? '',
                                'city_id' => $defaultLocationId ?? '',
                                'address_line1' => '',
                                'address_line2' => '',
                                'pincode' => '',
                                'latitude' => '',
                                'longitude' => '',
                            ]]);
                            $countryCodes = [
                                '+91' => '+91',
                                '+1' => '+1',
                                '+44' => '+44',
                                '+971' => '+971',
                                '+966' => '+966',
                                '+974' => '+974',
                                '+965' => '+965',
                                '+61' => '+61',
                                '+65' => '+65',
                                '+33' => '+33',
                            ];
                            $googleMapsKey = config('services.google_maps.key');
                        @endphp

                        <div class="tab-content pt-3">
                            <div class="tab-pane fade show active" id="oemtb1">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="oem_code">OEM Code <span class="text-danger">*</span></label>
                                        <input type="text" id="oem_code" class="form-control shadow-none"
                                            value="{{ $record->oem_code ?? $generatedCode ?? '' }}" disabled>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="oem_name">OEM Name <span class="text-danger">*</span></label>
                                        <input type="text" id="oem_name" name="oem_name"
                                            class="form-control shadow-none @error('oem_name') is-invalid @enderror"
                                            value="{{ old('oem_name', $record->oem_name ?? '') }}">
                                        @error('oem_name')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="short_name">Short Name</label>
                                        <input type="text" id="short_name" name="short_name"
                                            class="form-control shadow-none @error('short_name') is-invalid @enderror"
                                            value="{{ old('short_name', $record->short_name ?? '') }}">
                                        @error('short_name')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="oem_type">OEM Type <span class="text-danger">*</span></label>
                                        <select id="oem_type" name="oem_type"
                                            class="form-select shadow-none select2 @error('oem_type') is-invalid @enderror">
                                            <option value="">---Select---</option>
                                            @foreach ($oemTypes as $type)
                                                <option value="{{ $type->name }}" {{ old('oem_type', $record->oem_type ?? '') === $type->name ? 'selected' : '' }}>{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('oem_type')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="registration_type">Registration Type <span class="text-danger">*</span></label>
                                        <select id="registration_type" name="registration_type"
                                            class="form-select shadow-none @error('registration_type') is-invalid @enderror">
                                            <option value="">---Select---</option>
                                            @foreach ($registrationTypes as $value => $label)
                                                <option value="{{ $value }}" {{ old('registration_type', $record->registration_type ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('registration_type')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="state_id">State <span class="text-danger">*</span></label>
                                        <select id="state_id" name="state_id"
                                            class="form-select shadow-none select2 @error('state_id') is-invalid @enderror">
                                            <option value="">---Select---</option>
                                            @foreach ($states as $state)
                                                <option value="{{ $state->id }}" {{ (int) old('state_id', $record->state_id ?? $defaultStateId ?? 0) === $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('state_id')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="oemtb2">
                                <input type="hidden" id="primary_contact_index" name="primary_contact_index" value="{{ $primaryContactIndex }}">
                                <div id="contactRows">
                                    @foreach ($contacts as $index => $contact)
                                        <div class="oem-repeat-card contact-row" data-index="{{ $index }}">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0">Contact {{ $index + 1 }}</h6>
                                                <button type="button" class="btn btn-danger btn-sm remove-contact {{ $loop->first ? 'd-none' : '' }}">
                                                    <i class="fa-solid fa-minus"></i>
                                                </button>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-4 o-f-inp mb-3">
                                                    <label>Contact Person<span class="text-danger">*</span></label>
                                                    <input type="text" name="contacts[{{ $index }}][contact_person]" class="form-control shadow-none" value="{{ $contact['contact_person'] ?? '' }}">
                                                </div>
                                                <div class="col-lg-4 o-f-inp mb-3">
                                                    <label>Designation</label>
                                                    <input type="text" name="contacts[{{ $index }}][designation]" class="form-control shadow-none" value="{{ $contact['designation'] ?? '' }}">
                                                </div>
                                                <div class="col-lg-4 o-f-inp mb-3">
                                                    <label>Phone<span class="text-danger">*</span></label>
                                                    <div class="d-flex gap-2">
                                                        <select name="contacts[{{ $index }}][phone_country_code]" class="form-select shadow-none oem-country-code-select">
                                                            @foreach ($countryCodes as $code => $label)
                                                                <option value="{{ $code }}" @selected(($contact['phone_country_code'] ?? '+91') === $code)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="text" name="contacts[{{ $index }}][phone]" class="form-control shadow-none" value="{{ $contact['phone'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 o-f-inp mb-3">
                                                    <label>Alternative Phone</label>
                                                    <div class="d-flex gap-2">
                                                        <select name="contacts[{{ $index }}][alternate_phone_country_code]" class="form-select shadow-none oem-country-code-select">
                                                            @foreach ($countryCodes as $code => $label)
                                                                <option value="{{ $code }}" @selected(($contact['alternate_phone_country_code'] ?? '+91') === $code)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="text" name="contacts[{{ $index }}][alternate_phone]" class="form-control shadow-none" value="{{ $contact['alternate_phone'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 o-f-inp mb-3">
                                                    <label>Email</label>
                                                    <input type="email" name="contacts[{{ $index }}][email]" class="form-control shadow-none" value="{{ $contact['email'] ?? '' }}">
                                                </div>
                                                <div class="col-lg-4 o-f-inp mb-3 d-flex align-items-end">
                                                    <label class="flex-check mb-2">
                                                        <input type="radio" name="primary_contact_radio" class="primary-contact-radio" value="{{ $index }}" {{ (int) $primaryContactIndex === $index ? 'checked' : '' }}>
                                                        Is Primary
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" id="addContact" class="add-btn mt-2">
                                    <i class="fa-solid fa-plus"></i> Add Contact
                                </button>
                                @error('contacts')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                            </div>

                            <div class="tab-pane fade o-f-inp" id="oemtb3">
                                <div id="addressRows">
                                    @foreach ($addresses as $index => $address)
                                        <div class="oem-repeat-card address-row" data-index="{{ $index }}">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0">Address {{ $index + 1 }}</h6>
                                                <button type="button" class="btn btn-danger btn-sm remove-address {{ $loop->first ? 'd-none' : '' }}">
                                                    <i class="fa-solid fa-minus"></i>
                                                </button>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-3 o-f-inp mb-3">
                                                    <label>Address Type<span class="text-danger">*</span></label>
                                                    <select name="addresses[{{ $index }}][address_type]" class="form-select shadow-none">
                                                        <option value="">---Select---</option>
                                                        @foreach ($addressTypes as $value => $label)
                                                            <option value="{{ $value }}" {{ ($address['address_type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-lg-3 o-f-inp mb-3">
                                                    <label>State</label>
                                                    <select name="addresses[{{ $index }}][state_id]" class="form-select shadow-none address-state oem-address-select">
                                                        <option value="">---Select---</option>
                                                        @foreach ($states as $state)
                                                            <option value="{{ $state->id }}" {{ (int) ($address['state_id'] ?? 0) === $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-lg-3 o-f-inp mb-3">
                                                    <label>District</label>
                                                    <select name="addresses[{{ $index }}][district_id]" class="form-select shadow-none address-district oem-address-select" data-selected="{{ $address['district_id'] ?? '' }}"></select>
                                                </div>
                                                <div class="col-lg-3 o-f-inp mb-3">
                                                    <label>City</label>
                                                    <select name="addresses[{{ $index }}][city_id]" class="form-select shadow-none address-city oem-address-select" data-selected="{{ $address['city_id'] ?? '' }}"></select>
                                                </div>
                                                <div class="col-lg-3 o-f-inp mb-3">
                                                    <label>Pincode</label>
                                                    <input type="text" name="addresses[{{ $index }}][pincode]" class="form-control shadow-none address-pincode" value="{{ $address['pincode'] ?? '' }}">
                                                </div>
                                                <div class="col-lg-3 o-f-inp mb-3">
                                                    <label>Latitude</label>
                                                    <input type="text" name="addresses[{{ $index }}][latitude]" class="form-control shadow-none address-latitude" value="{{ $address['latitude'] ?? '' }}" readonly>
                                                </div>
                                                <div class="col-lg-3 o-f-inp mb-3">
                                                    <label>Longitude</label>
                                                    <input type="text" name="addresses[{{ $index }}][longitude]" class="form-control shadow-none address-longitude" value="{{ $address['longitude'] ?? '' }}" readonly>
                                                </div>
                                                <div class="col-lg-12 mb-3">
                                                    <div class="oem-map-shell">
                                                        <input type="text" class="form-control shadow-none oem-map-search mb-2" placeholder="Search location">
                                                        <div class="oem-map-picker" data-map-index="{{ $index }}">
                                                            @unless($googleMapsKey)
                                                                <div class="oem-map-fallback">Google Maps API key is not configured. Add GOOGLE_MAPS_API_KEY to enable map location picking.</div>
                                                            @endunless
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 o-f-inp mb-3">
                                                    <label>Address Line 1<span class="text-danger">*</span></label>
                                                    <textarea name="addresses[{{ $index }}][address_line1]" class="form-control shadow-none">{{ $address['address_line1'] ?? '' }}</textarea>
                                                </div>
                                                <div class="col-lg-6 o-f-inp mb-3">
                                                    <label>Address Line 2</label>
                                                    <textarea name="addresses[{{ $index }}][address_line2]" class="form-control shadow-none">{{ $address['address_line2'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" id="addAddress" class="add-btn mt-2">
                                    <i class="fa-solid fa-plus"></i> Add Address
                                </button>
                                @error('addresses')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                            </div>

                            <div class="tab-pane fade" id="oemtb4">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="gst_number">GST Number<span class="text-danger">*</span></label>
                                        <input type="text" id="gst_number" name="gst_number"
                                            class="form-control shadow-none @error('gst_number') is-invalid @enderror"
                                            value="{{ old('gst_number', $record->gst_number ?? '') }}">
                                        @error('gst_number')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="pan_number">PAN Number<span class="text-danger">*</span></label>
                                        <input type="text" id="pan_number" name="pan_number"
                                            class="form-control shadow-none @error('pan_number') is-invalid @enderror"
                                            value="{{ old('pan_number', $record->pan_number ?? '') }}">
                                        @error('pan_number')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="cin_number">CIN Number</label>
                                        <input type="text" id="cin_number" name="cin_number"
                                            class="form-control shadow-none @error('cin_number') is-invalid @enderror"
                                            value="{{ old('cin_number', $record->cin_number ?? '') }}">
                                        @error('cin_number')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-12 o-f-inp mb-3">
                                        <label for="remarks">Remarks</label>
                                        <textarea id="remarks" name="remarks" class="form-control shadow-none">{{ old('remarks', $record->remarks ?? '') }}</textarea>
                                        @error('remarks')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            <div class="btn-flex-cs">
    <a href="{{ route('oems.index') }}" class="btn-cancel-cs">Cancel</a>

    <button type="button" id="wizardPrev" class="btn-prev-cs d-none">
        Previous
    </button>

    <button type="button" id="wizardNext" class="btn-next-cs">
        Next
    </button>

    <button type="submit"
            id="wizardSubmit"
            class="btn-submit-cs js-loading-submit d-none"
            data-loading-text="Loading...">
        {{ isset($record) ? 'Update' : 'Submit' }}
    </button>
</div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var districts = @json($districts);
                var locations = @json($locations);
                var states = @json($states);
                var addressTypes = @json($addressTypes);
                var countryCodes = @json($countryCodes);
                var hasGoogleMapsKey = @json((bool) $googleMapsKey);
                var contactIndex = $('.contact-row').length;
                var addressIndex = $('.address-row').length;
                var oemMaps = [];

                $('.select2').select2({ width: '100%', placeholder: '---Select---', allowClear: true });
                initializeSelect2(document);

                window.initOemGoogleMaps = function () {
                    $('.address-row').each(function () {
                        initializeMap($(this));
                    });
                };

                if (!hasGoogleMapsKey) {
                    $('.oem-map-picker').each(function () {
                        if (!$(this).children().length) {
                            $(this).html('<div class="oem-map-fallback">Google Maps API key is not configured. Add GOOGLE_MAPS_API_KEY to enable map location picking.</div>');
                        }
                    });
                }

                function initializeSelect2(context) {
                    $(context).find('.oem-address-select, .oem-country-code-select').each(function () {
                        var select = $(this);
                        if (select.data('select2')) {
                            select.select2('destroy');
                        }
                        select.select2({
                            width: '100%',
                            placeholder: '---Select---',
                            allowClear: !select.hasClass('oem-country-code-select')
                        });
                    });
                }

                function updateWizardButtons() {
                    var activeIndex = $('#oemWizardTabs .nav-link').index($('#oemWizardTabs .nav-link.active'));
                    var lastIndex = $('#oemWizardTabs .nav-link').length - 1;
                    $('#wizardPrev').toggleClass('d-none', activeIndex === 0);
                    $('#wizardNext').toggleClass('d-none', activeIndex === lastIndex);
                    $('#wizardSubmit').toggleClass('d-none', activeIndex !== lastIndex);
                }

                $('#wizardNext').on('click', function () {
                    var active = $('#oemWizardTabs .nav-link.active');
                    active.closest('li').next().find('.nav-link').trigger('click');
                });

                $('#wizardPrev').on('click', function () {
                    var active = $('#oemWizardTabs .nav-link.active');
                    active.closest('li').prev().find('.nav-link').trigger('click');
                });

                $('#oemWizardTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', updateWizardButtons);
                updateWizardButtons();

                function stateOptions(selected) {
                    var html = '<option value="">---Select---</option>';
                    states.forEach(function (state) {
                        html += `<option value="${state.id}" ${String(selected) === String(state.id) ? 'selected' : ''}>${state.name}</option>`;
                    });
                    return html;
                }

                function addressTypeOptions(selected) {
                    var html = '<option value="">---Select---</option>';
                    Object.entries(addressTypes).forEach(function ([value, label]) {
                        html += `<option value="${value}" ${String(selected) === String(value) ? 'selected' : ''}>${label}</option>`;
                    });
                    return html;
                }

                function populateDistricts(row) {
                    var stateId = row.find('.address-state').val();
                    var selectedDistrict = row.find('.address-district').data('selected') || '';
                    var districtSelect = row.find('.address-district');
                    var html = '<option value="">---Select---</option>';
                    districts.filter(item => String(item.state_id) === String(stateId)).forEach(function (district) {
                        html += `<option value="${district.id}" ${String(selectedDistrict) === String(district.id) ? 'selected' : ''}>${district.name}</option>`;
                    });
                    districtSelect.html(html).trigger('change.select2');
                    populateCities(row);
                    districtSelect.data('selected', '');
                }

                function populateCities(row) {
                    var districtId = row.find('.address-district').val();
                    var selectedCity = row.find('.address-city').data('selected') || '';
                    var citySelect = row.find('.address-city');
                    var html = '<option value="">---Select---</option>';
                    locations.filter(item => String(item.district_id) === String(districtId)).forEach(function (location) {
                        html += `<option value="${location.id}" data-pincode="${location.pincode || ''}" ${String(selectedCity) === String(location.id) ? 'selected' : ''}>${location.name}</option>`;
                    });
                    citySelect.html(html).trigger('change.select2');
                    citySelect.data('selected', '');
                }

                $('.address-row').each(function () {
                    populateDistricts($(this));
                    initializeMap($(this));
                });

                $(document).on('change', '.address-state', function () {
                    var row = $(this).closest('.address-row');
                    row.find('.address-district').data('selected', '');
                    row.find('.address-city').data('selected', '');
                    populateDistricts(row);
                });

                $(document).on('change', '.address-district', function () {
                    var row = $(this).closest('.address-row');
                    row.find('.address-city').data('selected', '');
                    populateCities(row);
                });

                $(document).on('change', '.address-city', function () {
                    var pincode = $(this).find('option:selected').data('pincode') || '';
                    var row = $(this).closest('.address-row');
                    row.find('.address-pincode').val(pincode);
                    updateMapFromAddress(row);
                });

                function setLatLng(row, lat, lng) {
                    row.find('.address-latitude').val(Number(lat).toFixed(7));
                    row.find('.address-longitude').val(Number(lng).toFixed(7));
                }

                function initializeMap(row) {
                    if (!hasGoogleMapsKey || !window.google || !google.maps) {
                        return;
                    }

                    var mapElement = row.find('.oem-map-picker')[0];
                    if (!mapElement || mapElement.dataset.initialized === '1') {
                        return;
                    }

                    var lat = parseFloat(row.find('.address-latitude').val()) || 20.5937;
                    var lng = parseFloat(row.find('.address-longitude').val()) || 78.9629;
                    var center = { lat: lat, lng: lng };
                    var map = new google.maps.Map(mapElement, {
                        center: center,
                        zoom: row.find('.address-latitude').val() ? 14 : 5,
                    });
                    var marker = new google.maps.Marker({
                        map: map,
                        position: center,
                        draggable: true,
                    });

                    map.addListener('click', function (event) {
                        marker.setPosition(event.latLng);
                        setLatLng(row, event.latLng.lat(), event.latLng.lng());
                    });

                    marker.addListener('dragend', function (event) {
                        setLatLng(row, event.latLng.lat(), event.latLng.lng());
                    });

                    initializeLocationSearch(row, map, marker);

                    mapElement.dataset.initialized = '1';
                    oemMaps.push({ row: row, map: map, marker: marker });
                }

                function initializeLocationSearch(row, map, marker) {
                    if (!google.maps.places) {
                        return;
                    }

                    var searchInput = row.find('.oem-map-search')[0];
                    if (!searchInput || searchInput.dataset.initialized === '1') {
                        return;
                    }

                    var autocomplete = new google.maps.places.Autocomplete(searchInput, {
                        fields: ['geometry', 'name', 'formatted_address'],
                    });
                    autocomplete.bindTo('bounds', map);

                    autocomplete.addListener('place_changed', function () {
                        var place = autocomplete.getPlace();

                        if (!place.geometry || !place.geometry.location) {
                            return;
                        }

                        map.setCenter(place.geometry.location);
                        map.setZoom(15);
                        marker.setPosition(place.geometry.location);
                        setLatLng(row, place.geometry.location.lat(), place.geometry.location.lng());
                    });

                    searchInput.dataset.initialized = '1';
                }

                function updateMapFromAddress(row) {
                    if (!hasGoogleMapsKey || !window.google || !google.maps) {
                        return;
                    }

                    initializeMap(row);

                    var mapItem = oemMaps.find(item => item.row[0] === row[0]);
                    if (!mapItem || !google.maps.Geocoder) {
                        return;
                    }

                    var parts = [
                        row.find('.address-city option:selected').text(),
                        row.find('.address-district option:selected').text(),
                        row.find('.address-state option:selected').text(),
                        row.find('.address-pincode').val(),
                    ].filter(function (value) {
                        return value && value !== '---Select---';
                    });

                    if (!parts.length) {
                        return;
                    }

                    new google.maps.Geocoder().geocode({ address: parts.join(', ') }, function (results, status) {
                        if (status !== 'OK' || !results[0]) {
                            return;
                        }

                        var location = results[0].geometry.location;
                        mapItem.map.setCenter(location);
                        mapItem.map.setZoom(14);
                        mapItem.marker.setPosition(location);
                        setLatLng(row, location.lat(), location.lng());
                    });
                }

                $('#addContact').on('click', function () {
                    $('#contactRows').append(`
                        <div class="oem-repeat-card contact-row" data-index="${contactIndex}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Contact ${contactIndex + 1}</h6>
                                <button type="button" class="btn btn-danger btn-sm remove-contact"><i class="fa-solid fa-minus"></i></button>
                            </div>
                            <div class="row">
                                <div class="col-lg-4 o-f-inp mb-3"><label>Contact Person<span class="text-danger">*</span></label><input type="text" name="contacts[${contactIndex}][contact_person]" class="form-control shadow-none"></div>
                                <div class="col-lg-4 o-f-inp mb-3"><label>Designation</label><input type="text" name="contacts[${contactIndex}][designation]" class="form-control shadow-none"></div>
                                <div class="col-lg-4 o-f-inp mb-3"><label>Phone<span class="text-danger">*</span></label><div class="d-flex gap-2"><select name="contacts[${contactIndex}][phone_country_code]" class="form-select shadow-none oem-country-code-select">${countryCodeOptions('+91')}</select><input type="text" name="contacts[${contactIndex}][phone]" class="form-control shadow-none"></div></div>
                                <div class="col-lg-4 o-f-inp mb-3"><label>Alternative Phone</label><div class="d-flex gap-2"><select name="contacts[${contactIndex}][alternate_phone_country_code]" class="form-select shadow-none oem-country-code-select">${countryCodeOptions('+91')}</select><input type="text" name="contacts[${contactIndex}][alternate_phone]" class="form-control shadow-none"></div></div>
                                <div class="col-lg-4 o-f-inp mb-3"><label>Email</label><input type="email" name="contacts[${contactIndex}][email]" class="form-control shadow-none"></div>
                                <div class="col-lg-4 o-f-inp mb-3 d-flex align-items-end"><label class="flex-check mb-2"><input type="radio" name="primary_contact_radio" class="primary-contact-radio" value="${contactIndex}"> Is Primary</label></div>
                            </div>
                        </div>
                    `);
                    initializeSelect2($('.contact-row').last());
                    contactIndex++;
                });

                function countryCodeOptions(selected) {
                    var html = '';
                    Object.entries(countryCodes).forEach(function ([value, label]) {
                        html += `<option value="${value}" ${String(selected) === String(value) ? 'selected' : ''}>${label}</option>`;
                    });
                    return html;
                }

                $(document).on('click', '.remove-contact', function () {
                    $(this).closest('.contact-row').remove();
                    if (!$('.primary-contact-radio:checked').length) {
                        $('.primary-contact-radio:first').prop('checked', true).trigger('change');
                    }
                });

                $(document).on('change', '.primary-contact-radio', function () {
                    $('#primary_contact_index').val($(this).val());
                });

                $('#addAddress').on('click', function () {
                    var html = `
                        <div class="oem-repeat-card address-row" data-index="${addressIndex}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Address ${addressIndex + 1}</h6>
                                <button type="button" class="btn btn-danger btn-sm remove-address"><i class="fa-solid fa-minus"></i></button>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 o-f-inp mb-3"><label>Address Type<span class="text-danger">*</span></label><select name="addresses[${addressIndex}][address_type]" class="form-select shadow-none">${addressTypeOptions('')}</select></div>
                                <div class="col-lg-3 o-f-inp mb-3"><label>State</label><select name="addresses[${addressIndex}][state_id]" class="form-select shadow-none address-state oem-address-select">${stateOptions($('#state_id').val())}</select></div>
                                <div class="col-lg-3 o-f-inp mb-3"><label>District</label><select name="addresses[${addressIndex}][district_id]" class="form-select shadow-none address-district oem-address-select"></select></div>
                                <div class="col-lg-3 o-f-inp mb-3"><label>City</label><select name="addresses[${addressIndex}][city_id]" class="form-select shadow-none address-city oem-address-select"></select></div>
                                <div class="col-lg-3 o-f-inp mb-3"><label>Pincode</label><input type="text" name="addresses[${addressIndex}][pincode]" class="form-control shadow-none address-pincode"></div>
                                <div class="col-lg-3 o-f-inp mb-3"><label>Latitude</label><input type="text" name="addresses[${addressIndex}][latitude]" class="form-control shadow-none address-latitude" readonly></div>
                                <div class="col-lg-3 o-f-inp mb-3"><label>Longitude</label><input type="text" name="addresses[${addressIndex}][longitude]" class="form-control shadow-none address-longitude" readonly></div>
                                <div class="col-lg-12 mb-3"><div class="oem-map-shell"><input type="text" class="form-control shadow-none oem-map-search mb-2" placeholder="Search location"><div class="oem-map-picker" data-map-index="${addressIndex}">${hasGoogleMapsKey ? '' : '<div class="oem-map-fallback">Google Maps API key is not configured. Add GOOGLE_MAPS_API_KEY to enable map location picking.</div>'}</div></div></div>
                                <div class="col-lg-6 o-f-inp mb-3"><label>Address Line 1<span class="text-danger">*</span></label><textarea name="addresses[${addressIndex}][address_line1]" class="form-control shadow-none"></textarea></div>
                                <div class="col-lg-6 o-f-inp mb-3"><label>Address Line 2</label><textarea name="addresses[${addressIndex}][address_line2]" class="form-control shadow-none"></textarea></div>
                            </div>
                        </div>
                    `;
                    $('#addressRows').append(html);
                    var row = $('.address-row').last();
                    initializeSelect2(row);
                    populateDistricts(row);
                    initializeMap(row);
                    addressIndex++;
                });

                $(document).on('click', '.remove-address', function () {
                    $(this).closest('.address-row').remove();
                });

                $('#oemForm').on('submit', function () {
                    var button = $(this).find('.js-loading-submit');
                    button.prop('disabled', true).html(button.data('loading-text') || 'Loading...');
                });
            });
        </script>
        <style>
            .oem-repeat-card {
                border: 1px solid #e2e6ea;
                border-radius: 8px;
                margin-bottom: 12px;
                padding: 14px;
            }

            .oem-country-code {
                max-width: 82px;
            }

            .oem-country-code-select {
                max-width: 82px;
            }

            .oem-map-picker {
                align-items: center;
                background: #f6f8fb;
                border: 1px solid #e2e6ea;
                border-radius: 8px;
                display: flex;
                justify-content: center;
                min-height: 260px;
                overflow: hidden;
                width: 100%;
            }

            .oem-map-shell {
                width: 100%;
            }

            .oem-map-fallback {
                color: #697386;
                padding: 18px;
                text-align: center;
            }
        </style>
        @if($googleMapsKey)
            <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=places&callback=initOemGoogleMaps"></script>
        @endif
    @endsection
</x-app-layout>
