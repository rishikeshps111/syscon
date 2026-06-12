@section('title')
    {{ isset($record) ? 'Edit Driver' : 'Add Driver' }}
@endsection
<x-app-layout>
    @php
        $profile = $record->driverProfile ?? null;
        $countryCodes = [
            '+91' => '+91',
            '+1' => '+1',
            '+44' => '+44',
            '+61' => '+61',
            '+971' => '+971',
            '+65' => '+65',
            '+60' => '+60',
            '+81' => '+81',
            '+49' => '+49',
            '+33' => '+33',
        ];
        $selectedCountryCode = old('country_code', $record->country_code ?? '+91');
        $selectedAlternateCountryCode = old('alternate_country_code', $profile->alternate_country_code ?? '+91');
        $selectedEmergencyCountryCode = old('emergency_country_code', $profile->emergency_country_code ?? '+91');
        $avatarUrl = isset($record) ? $record->avatar_url : asset('assets/img/user.png');
    @endphp
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ isset($record) ? 'Edit Driver' : 'Add Driver' }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item"><a href="{{ route('driver-management.index') }}">Driver Management</a>
                    </li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit' : 'Add' }}</li>
                </ol>
            </nav>
        </div>
    </section>

    <section class="section dashboard">
        <div class="row">
            <div class="col-xl-12">
                <div class="main-table-container">
                    <form class="js-loading-form" method="POST" enctype="multipart/form-data" novalidate
                        action="{{ isset($record) ? route('driver-management.update', $record->id) : route('driver-management.store') }}">
                        @csrf
                        @if (isset($record))
                            @method('PUT')
                        @endif

                        <ul class="nav nav-tabs nav-tabs-bordered justify-content-start" role="tablist">
                            @foreach ([
        'drv1' => 'Basic Information',
        'drv2' => 'Identity Details',
        'drv3' => 'License Details',
        'drv4' => 'Employment Details',
        'drv5' => 'Bank Details',
        'drv6' => 'Emergency & Medical',
        'drv7' => 'Status & Verification',
    ] as $target => $label)
                                <li class="nav-item {{ $loop->first ? 'ps-0 ms-0' : '' }}" role="presentation">
                                    <button type="button"
                                        class="nav-link {{ $loop->first ? 'ms-0 active' : '' }} wizard-tab-button"
                                        data-bs-toggle="tab" data-bs-target="#{{ $target }}">{{ $label }}</button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content pt-2">
                            <div class="tab-pane fade show active" id="drv1" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="code">Driver Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control shadow-none" id="code"
                                            value="{{ $record->code ?? ($generatedCode ?? '') }}" disabled>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="name">Name <span class="text-danger">*</span></label>
                                        <input type="text" id="name" name="name"
                                            class="form-control shadow-none @error('name') is-invalid @enderror"
                                            value="{{ old('name', $record->name ?? '') }}" required>
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="phone">Phone <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select name="country_code" id="country_code"
                                                class="form-select shadow-none" style="max-width: 112px;" required>
                                                @foreach ($countryCodes as $code => $label)
                                                    <option value="{{ $code }}" @selected($selectedCountryCode === $code)>
                                                        {{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" id="phone" name="phone"
                                                class="form-control shadow-none @error('phone') is-invalid @enderror"
                                                value="{{ old('phone', $record->phone ?? '') }}" required>
                                        </div>
                                        @error('country_code')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                        @error('phone')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="alternate_phone">Alternate Phone</label>
                                        <div class="input-group">
                                            <select name="alternate_country_code" id="alternate_country_code"
                                                class="form-select shadow-none" style="max-width: 112px;">
                                                @foreach ($countryCodes as $code => $label)
                                                    <option value="{{ $code }}" @selected($selectedAlternateCountryCode === $code)>
                                                        {{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" id="alternate_phone" name="alternate_phone"
                                                class="form-control shadow-none @error('alternate_phone') is-invalid @enderror"
                                                value="{{ old('alternate_phone', $profile->alternate_phone ?? '') }}">
                                        </div>
                                        @error('alternate_phone')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="email">Email <span class="text-danger">*</span></label>
                                        <input type="email" id="email" name="email"
                                            class="form-control shadow-none @error('email') is-invalid @enderror"
                                            value="{{ old('email', $record->email ?? '') }}" required>
                                        @error('email')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="passcode">Passcode @if (!isset($record))
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <div class="input-group">
                                            <input type="password" id="passcode" name="passcode"
                                                class="form-control shadow-none @error('passcode') is-invalid @enderror"
                                                inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                                {{ isset($record) ? '' : 'required' }}>
                                            <button type="button" class="btn btn-outline-secondary" id="togglePasscode"
                                                title="Show passcode">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                        @error('passcode')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="avatar">Driver Image</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <img id="driverAvatarPreview" src="{{ $avatarUrl }}"
                                                alt="Driver image preview" width="70" height="70"
                                                class="rounded object-fit-cover border">
                                            <input type="file"
                                                class="form-control shadow-none @error('avatar') is-invalid @enderror"
                                                id="avatar" name="avatar" accept="image/*">
                                        </div>
                                        @error('avatar')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="drv2" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="aadhaar_number">Aadhaar Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="aadhaar_number" name="aadhaar_number"
                                            class="form-control shadow-none"
                                            value="{{ old('aadhaar_number', $profile->aadhaar_number ?? '') }}"
                                            required>
                                        @error('aadhaar_number')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="country">Country <span class="text-danger">*</span></label>
                                        <select name="country" id="country" class="form-select shadow-none"
                                            required>
                                            <option value="">---Select---</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country }}"
                                                    {{ old('country', $profile->country ?? 'India') === $country ? 'selected' : '' }}>
                                                    {{ $country }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="state_id">State <span class="text-danger">*</span></label>
                                        <select name="state_id" id="state_id"
                                            class="form-select shadow-none select2" required>
                                            <option value="">---Select---</option>
                                            @foreach ($states as $state)
                                                <option value="{{ $state->id }}"
                                                    {{ old('state_id', $profile->state_id ?? '') == $state->id ? 'selected' : '' }}>
                                                    {{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('state_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="district_id">District <span class="text-danger">*</span></label>
                                        <select name="district_id" id="district_id"
                                            class="form-select shadow-none select2" required>
                                            <option value="">---Select---</option>
                                            @foreach ($districts as $district)
                                                <option value="{{ $district->id }}"
                                                    {{ old('district_id', $profile->district_id ?? '') == $district->id ? 'selected' : '' }}>
                                                    {{ $district->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('district_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="location_id">City <span class="text-danger">*</span></label>
                                        <select name="location_id" id="location_id"
                                            class="form-select shadow-none select2" required>
                                            <option value="">---Select---</option>
                                            @foreach ($locations as $location)
                                                <option value="{{ $location->id }}"
                                                    data-pincode="{{ $location->pincode }}"
                                                    {{ old('location_id', $profile->location_id ?? '') == $location->id ? 'selected' : '' }}>
                                                    {{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('location_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="pincode">Pincode <span class="text-danger">*</span></label>
                                        <input type="text" id="pincode" name="pincode"
                                            class="form-control shadow-none"
                                            value="{{ old('pincode', $profile->pincode ?? '') }}" required>
                                        @error('pincode')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-12 o-f-inp mb-3">
                                        <label for="address">Address <span class="text-danger">*</span></label>
                                        <textarea id="address" name="address" class="form-control shadow-none" rows="3" required>{{ old('address', $profile->address ?? '') }}</textarea>
                                        @error('address')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="drv3" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="license_number">License Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="license_number" name="license_number"
                                            class="form-control shadow-none"
                                            value="{{ old('license_number', $profile->license_number ?? '') }}"
                                            required>
                                        @error('license_number')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="license_type">License Type <span
                                                class="text-danger">*</span></label>
                                        <select name="license_type" id="license_type" class="form-select shadow-none"
                                            required>
                                            <option value="">---Select---</option>
                                            @foreach ($licenseTypes as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ old('license_type', $profile->license_type ?? '') === $value ? 'selected' : '' }}>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('license_type')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="issue_date">Issue Date <span class="text-danger">*</span></label>
                                        <input type="date" id="issue_date" name="issue_date"
                                            class="form-control shadow-none"
                                            value="{{ old('issue_date', $profile?->issue_date?->format('Y-m-d')) }}"
                                            required>
                                        @error('issue_date')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="expiry_date">Expiry Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" id="expiry_date" name="expiry_date"
                                            class="form-control shadow-none"
                                            value="{{ old('expiry_date', $profile?->expiry_date?->format('Y-m-d')) }}"
                                            required>
                                        @error('expiry_date')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="badge_number">Badge Number</label>
                                        <input type="text" id="badge_number" name="badge_number"
                                            class="form-control shadow-none"
                                            value="{{ old('badge_number', $profile->badge_number ?? '') }}">
                                        @error('badge_number')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="badge_expiry_date">Badge Expiry Date</label>
                                        <input type="date" id="badge_expiry_date" name="badge_expiry_date"
                                            class="form-control shadow-none"
                                            value="{{ old('badge_expiry_date', $profile?->badge_expiry_date?->format('Y-m-d')) }}">
                                        @error('badge_expiry_date')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="drv4" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="employment_type">Employment Type <span
                                                class="text-danger">*</span></label>
                                        <select name="employment_type" id="employment_type"
                                            class="form-select shadow-none" required>
                                            <option value="">---Select---</option>
                                            @foreach ($employmentTypes as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ old('employment_type', $profile->employment_type ?? '') === $value ? 'selected' : '' }}>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('employment_type')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="joining_date">Joining Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" id="joining_date" name="joining_date"
                                            class="form-control shadow-none"
                                            value="{{ old('joining_date', $profile?->joining_date?->format('Y-m-d')) }}"
                                            required>
                                        @error('joining_date')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="salary">Salary <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" id="salary"
                                            name="salary" class="form-control shadow-none"
                                            value="{{ old('salary', $profile->salary ?? '') }}" required>
                                        @error('salary')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="depot_id">Depot <span class="text-danger">*</span></label>
                                        <select name="depot_id" id="depot_id"
                                            class="form-select shadow-none select2" required>
                                            <option value="">---Select---</option>
                                            @foreach ($depots as $depot)
                                                <option value="{{ $depot->id }}"
                                                    {{ old('depot_id', $profile->depot_id ?? '') == $depot->id ? 'selected' : '' }}>
                                                    {{ $depot->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('depot_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="branch_location_id">Branch <span
                                                class="text-danger">*</span></label>
                                        <select name="branch_location_id" id="branch_location_id"
                                            class="form-select shadow-none select2" required>
                                            <option value="">---Select---</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}"
                                                    {{ old('branch_location_id', $profile->branch_location_id ?? '') == $branch->id ? 'selected' : '' }}>
                                                    {{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('branch_location_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="drv5" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="account_number">Account Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="account_number" name="account_number"
                                            class="form-control shadow-none"
                                            value="{{ old('account_number', $profile->account_number ?? '') }}"
                                            required>
                                        @error('account_number')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="ifsc_code">IFSC Code <span class="text-danger">*</span></label>
                                        <input type="text" id="ifsc_code" name="ifsc_code"
                                            class="form-control shadow-none"
                                            value="{{ old('ifsc_code', $profile->ifsc_code ?? '') }}" required>
                                        @error('ifsc_code')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="drv6" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="emergency_contact_name">Emergency Contact Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="emergency_contact_name"
                                            name="emergency_contact_name" class="form-control shadow-none"
                                            value="{{ old('emergency_contact_name', $profile->emergency_contact_name ?? '') }}"
                                            required>
                                        @error('emergency_contact_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="emergency_contact_no">Emergency Contact No <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select name="emergency_country_code" id="emergency_country_code"
                                                class="form-select shadow-none" style="max-width: 112px;" required>
                                                @foreach ($countryCodes as $code => $label)
                                                    <option value="{{ $code }}" @selected($selectedEmergencyCountryCode === $code)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" id="emergency_contact_no" name="emergency_contact_no"
                                                class="form-control shadow-none"
                                                value="{{ old('emergency_contact_no', $profile->emergency_contact_no ?? '') }}"
                                                required>
                                        </div>
                                        @error('emergency_country_code')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                        @error('emergency_contact_no')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="medical_fitness_expiry">Medical Fitness Expiry <span
                                                class="text-danger">*</span></label>
                                        <input type="date" id="medical_fitness_expiry"
                                            name="medical_fitness_expiry" class="form-control shadow-none"
                                            value="{{ old('medical_fitness_expiry', $profile?->medical_fitness_expiry?->format('Y-m-d')) }}"
                                            required>
                                        @error('medical_fitness_expiry')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="drv7" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="is_active">Status <span class="text-danger">*</span></label>
                                        <select name="is_active" id="is_active"
                                            class="form-select shadow-none @error('is_active') is-invalid @enderror"
                                            required>
                                            <option value="1"
                                                {{ old('is_active', $record->is_active ?? 1) == 1 ? 'selected' : '' }}>
                                                Active</option>
                                            <option value="0"
                                                {{ old('is_active', $record->is_active ?? 1) == 0 ? 'selected' : '' }}>
                                                Inactive</option>
                                        </select>
                                        @error('is_active')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="police_verification_status">Police Verification Status <span
                                                class="text-danger">*</span></label>
                                        <select name="police_verification_status" id="police_verification_status"
                                            class="form-select shadow-none" required>
                                            @foreach ($verificationStatuses as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ old('police_verification_status', $profile->police_verification_status ?? 'pending') === $value ? 'selected' : '' }}>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('police_verification_status')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="verification_status">Verification Status <span
                                                class="text-danger">*</span></label>
                                        <select name="verification_status" id="verification_status"
                                            class="form-select shadow-none" required>
                                            @foreach ($verificationStatuses as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ old('verification_status', $profile->verification_status ?? 'pending') === $value ? 'selected' : '' }}>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('verification_status')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 mt-3">
                            <div class="btn-flex">
                                <a href="{{ route('driver-management.index') }}"
                                    class="btn btn-secondary me-2">Cancel</a>
                                <button type="button" class="btn btn-secondary me-2"
                                    id="wizardPrev">Previous</button>
                                <button type="button" class="submit-btn" id="wizardNext">Next</button>
                                <button type="submit" class="submit-btn js-loading-submit" id="wizardSubmit"
                                    data-loading-text="Loading...">{{ isset($record) ? 'Update' : 'Submit' }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $('.select2').select2({
                placeholder: '---Select---',
                allowClear: true,
                width: '100%'
            });

            var wizardTabs = Array.from(document.querySelectorAll('[data-bs-toggle="tab"][data-bs-target^="#drv"]'));
            var prevButton = document.getElementById('wizardPrev');
            var nextButton = document.getElementById('wizardNext');
            var submitButton = document.getElementById('wizardSubmit');

            function activeWizardIndex() {
                return wizardTabs.findIndex(function(tab) {
                    return tab.classList.contains('active');
                });
            }

            function updateWizardButtons() {
                var index = activeWizardIndex();
                prevButton.classList.toggle('d-none', index <= 0);
                nextButton.classList.toggle('d-none', index >= wizardTabs.length - 1);
                submitButton.classList.toggle('d-none', index < wizardTabs.length - 1);
            }

            nextButton.addEventListener('click', function() {
                var index = activeWizardIndex();
                if (index < wizardTabs.length - 1) {
                    bootstrap.Tab.getOrCreateInstance(wizardTabs[index + 1]).show();
                }
            });

            prevButton.addEventListener('click', function() {
                var index = activeWizardIndex();
                if (index > 0) {
                    bootstrap.Tab.getOrCreateInstance(wizardTabs[index - 1]).show();
                }
            });

            wizardTabs.forEach(function(tab) {
                tab.addEventListener('shown.bs.tab', updateWizardButtons);
            });
            updateWizardButtons();

            document.getElementById('togglePasscode').addEventListener('click', function() {
                var passcodeInput = document.getElementById('passcode');
                var icon = this.querySelector('i');
                var isHidden = passcodeInput.type === 'password';

                passcodeInput.type = isHidden ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !isHidden);
                icon.classList.toggle('fa-eye-slash', isHidden);
                this.title = isHidden ? 'Hide passcode' : 'Show passcode';
            });

            var avatarInput = document.getElementById('avatar');
            var avatarPreview = document.getElementById('driverAvatarPreview');

            if (avatarInput && avatarPreview) {
                avatarInput.addEventListener('change', function() {
                    var file = this.files && this.files[0] ? this.files[0] : null;

                    if (!file) {
                        return;
                    }

                    avatarPreview.src = URL.createObjectURL(file);
                    avatarPreview.onload = function() {
                        URL.revokeObjectURL(avatarPreview.src);
                    };
                });
            }

            $('#state_id').on('change', function() {
                var stateId = $(this).val();
                resetSelect('#district_id', 'Loading...');
                resetSelect('#location_id', '---Select---');
                $('#pincode').val('');

                if (!stateId) {
                    resetSelect('#district_id', '---Select---');
                    return;
                }

                $.ajax({
                    url: "{{ route('driver-management.districts-by-state') }}",
                    type: 'GET',
                    data: {
                        state_id: stateId
                    },
                    success: function(districts) {
                        var options = '<option value="">---Select---</option>';
                        districts.forEach(function(district) {
                            options += `<option value="${district.id}">${district.name}</option>`;
                        });
                        $('#district_id').html(options).prop('disabled', false).val('').trigger(
                            'change.select2');
                    },
                    error: function() {
                        resetSelect('#district_id', '---Select---');
                        showToast('error', 'Unable to load districts.');
                    }
                });
            });

            $('#district_id').on('change', function() {
                var stateId = $('#state_id').val();
                var districtId = $(this).val();
                resetSelect('#location_id', 'Loading...');
                $('#pincode').val('');

                if (!stateId || !districtId) {
                    resetSelect('#location_id', '---Select---');
                    return;
                }

                $.ajax({
                    url: "{{ route('driver-management.locations-by-district') }}",
                    type: 'GET',
                    data: {
                        state_id: stateId,
                        district_id: districtId
                    },
                    success: function(locations) {
                        var options = '<option value="">---Select---</option>';
                        locations.forEach(function(location) {
                            options +=
                                `<option value="${location.id}" data-pincode="${location.pincode || ''}">${location.name}</option>`;
                        });
                        $('#location_id').html(options).prop('disabled', false).val('').trigger(
                            'change.select2');
                    },
                    error: function() {
                        resetSelect('#location_id', '---Select---');
                        showToast('error', 'Unable to load cities.');
                    }
                });
            });

            $('#location_id').on('change', function() {
                var pincode = $(this).find(':selected').data('pincode') || '';
                $('#pincode').val(pincode);
            });

            function resetSelect(selector, label) {
                $(selector)
                    .html(`<option value="">${label}</option>`)
                    .prop('disabled', label === 'Loading...')
                    .val('')
                    .trigger('change.select2');
            }

            document.querySelectorAll('.js-loading-form').forEach(function(form) {
                form.addEventListener('submit', function() {
                    var loadingButton = form.querySelector('.js-loading-submit');
                    if (!loadingButton || loadingButton.disabled) {
                        return;
                    }
                    loadingButton.disabled = true;
                    loadingButton.innerHTML = loadingButton.dataset.loadingText || 'Loading...';
                });
            });
        </script>
    @endsection
</x-app-layout>
