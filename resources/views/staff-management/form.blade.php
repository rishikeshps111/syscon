@section('title')
    {{ isset($record) ? 'Edit Staff' : 'Add Staff' }}
@endsection
<x-app-layout>
    @php
        $profile = $record->staffProfile ?? null;
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
        $avatarUrl = isset($record) ? $record->avatar_url : asset('assets/img/user.png');
    @endphp
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ isset($record) ? 'Edit Staff' : 'Add Staff' }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item"><a href="{{ route('staff-management.index') }}">Staff Management</a></li>
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
                        action="{{ isset($record) ? route('staff-management.update', $record->id) : route('staff-management.store') }}">
                        @csrf
                        @if(isset($record))
                            @method('PUT')
                        @endif

                        <ul class="nav nav-tabs nav-tabs-bordered justify-content-start" role="tablist">
                            <li class="nav-item ps-0 ms-0" role="presentation">
                                <button type="button" class="nav-link ms-0 active" data-bs-toggle="tab" data-bs-target="#stf1">Basic Information</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#stf2">Personal Details</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#stf3">Employment Details</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#stf4">Location Details</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#stf5">Bank Details</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#stf6">Salary Structure</button>
                            </li>
                        </ul>

                        <div class="tab-content pt-2">
                            <div class="tab-pane fade show active" id="stf1" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="code">Staff Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control shadow-none" id="code"
                                            value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="name">Staff Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control shadow-none @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $record->name ?? '') }}" required>
                                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="email">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control shadow-none @error('email') is-invalid @enderror"
                                            id="email" name="email" value="{{ old('email', $record->email ?? '') }}" required>
                                        @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="phone">Phone <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select name="country_code" id="country_code" class="form-select shadow-none @error('country_code') is-invalid @enderror"
                                                style="max-width: 112px;" required>
                                                @foreach ($countryCodes as $code => $label)
                                                    <option value="{{ $code }}" @selected($selectedCountryCode === $code)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" class="form-control shadow-none @error('phone') is-invalid @enderror"
                                                id="phone" name="phone" value="{{ old('phone', $record->phone ?? '') }}" required>
                                        </div>
                                        @error('country_code') <span class="text-danger">{{ $message }}</span> @enderror
                                        @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="password">Password @if(! isset($record)) <span class="text-danger">*</span> @endif</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control shadow-none @error('password') is-invalid @enderror"
                                                id="password" name="password" {{ isset($record) ? '' : 'required' }}>
                                            <button type="button" class="btn btn-outline-secondary" id="togglePassword" title="Show password">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                        @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="designation_id">Designation <span class="text-danger">*</span></label>
                                        <select name="designation_id" id="designation_id" class="form-select shadow-none select2 @error('designation_id') is-invalid @enderror" required>
                                            <option value="">---Select---</option>
                                            @foreach ($designations as $designation)
                                                <option value="{{ $designation->id }}" {{ old('designation_id', $profile->designation_id ?? '') == $designation->id ? 'selected' : '' }}>{{ $designation->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('designation_id') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="category">Category <span class="text-danger">*</span></label>
                                        <select name="category" id="category" class="form-select shadow-none @error('category') is-invalid @enderror" required>
                                            <option value="">---Select---</option>
                                            @foreach ($categories as $value => $label)
                                                <option value="{{ $value }}" {{ old('category', $profile->category ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('category') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-lg-4 mb-3">
                                        <div class="o-f-inp">
                                            <label for="employment_type">Employment Type <span class="text-danger">*</span></label>
                                            <select name="employment_type" id="employment_type" class="form-select shadow-none @error('employment_type') is-invalid @enderror" required>
                                                <option value="">---Select---</option>
                                                @foreach ($employmentTypes as $value => $label)
                                                    <option value="{{ $value }}" {{ old('employment_type', $profile->employment_type ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('employment_type') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 mb-3">
                                        <div class="o-f-inp">
                                            <label for="is_active">Status <span class="text-danger">*</span></label>
                                            <select name="is_active" id="is_active" class="form-select shadow-none @error('is_active') is-invalid @enderror">
                                                <option value="1" {{ old('is_active', $record->is_active ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ old('is_active', $record->is_active ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @error('is_active') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                     <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="avatar">Staff Image</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <img id="staffAvatarPreview" src="{{ $avatarUrl }}" alt="Staff image preview"
                                                width="70" height="70" class="rounded object-fit-cover border">
                                            <input type="file" class="form-control shadow-none @error('avatar') is-invalid @enderror"
                                                id="avatar" name="avatar" accept="image/*">
                                        </div>
                                        @error('avatar') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="stf2" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="father_name">Father's Name <span class="text-danger">*</span></label>
                                        <input type="text" id="father_name" name="father_name" class="form-control shadow-none" value="{{ old('father_name', $profile->father_name ?? '') }}" required>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="date_of_birth">Date of Birth <span class="text-danger">*</span></label>
                                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control shadow-none" value="{{ old('date_of_birth', $profile?->date_of_birth?->format('Y-m-d')) }}" required>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="aadhaar_number">Aadhaar Number <span class="text-danger">*</span></label>
                                        <input type="text" id="aadhaar_number" name="aadhaar_number" class="form-control shadow-none" value="{{ old('aadhaar_number', $profile->aadhaar_number ?? '') }}" required>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="pan_number">PAN Number <span class="text-danger">*</span></label>
                                        <input type="text" id="pan_number" name="pan_number" class="form-control shadow-none" value="{{ old('pan_number', $profile->pan_number ?? '') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="stf3" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="date_of_joining">Date of Joining <span class="text-danger">*</span></label>
                                        <input type="date" id="date_of_joining" name="date_of_joining" class="shadow-none form-control" value="{{ old('date_of_joining', $profile?->date_of_joining?->format('Y-m-d')) }}" required>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="uan">UAN <span class="text-danger">*</span></label>
                                        <input type="text" id="uan" name="uan" class="shadow-none form-control" value="{{ old('uan', $profile->uan ?? '') }}" required>
                                    </div>
                                    <div class="col-lg-4 o-f-inp mb-3">
                                        <label for="esic_wc">ESIC / WC <span class="text-danger">*</span></label>
                                        <input type="text" id="esic_wc" name="esic_wc" class="shadow-none form-control" value="{{ old('esic_wc', $profile->esic_wc ?? '') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="stf4" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="country">Country <span class="text-danger">*</span></label>
                                        <select name="country" id="country" class="form-select shadow-none" required>
                                            <option value="">---Select---</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country }}" {{ old('country', $profile->country ?? 'India') === $country ? 'selected' : '' }}>{{ $country }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="state_id">State <span class="text-danger">*</span></label>
                                        <select name="state_id" id="state_id" class="form-select shadow-none select2" required>
                                            <option value="">---Select---</option>
                                            @foreach ($states as $state)
                                                <option value="{{ $state->id }}" {{ old('state_id', $profile->state_id ?? '') == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="district_id">District <span class="text-danger">*</span></label>
                                        <select name="district_id" id="district_id" class="form-select shadow-none select2" required>
                                            <option value="">---Select---</option>
                                            @foreach ($districts as $district)
                                                <option value="{{ $district->id }}" {{ old('district_id', $profile->district_id ?? '') == $district->id ? 'selected' : '' }}>{{ $district->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="location_id">Location <span class="text-danger">*</span></label>
                                        <select name="location_id" id="location_id" class="form-select shadow-none select2" required>
                                            <option value="">---Select---</option>
                                            @foreach ($locations as $location)
                                                <option value="{{ $location->id }}" {{ old('location_id', $profile->location_id ?? '') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="stf5" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="bank_account_number">Bank Account Number <span class="text-danger">*</span></label>
                                        <input type="text" id="bank_account_number" name="bank_account_number" class="form-control shadow-none" value="{{ old('bank_account_number', $profile->bank_account_number ?? '') }}" required>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="ifsc_code">IFSC Code <span class="text-danger">*</span></label>
                                        <input type="text" id="ifsc_code" name="ifsc_code" class="form-control shadow-none" value="{{ old('ifsc_code', $profile->ifsc_code ?? '') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="stf6" role="tabpanel">
                                <div class="row">
                                    @foreach ([
                                        'basic' => ['label' => 'Basic', 'required' => true, 'readonly' => false],
                                        'vda' => ['label' => 'VDA', 'required' => true, 'readonly' => false],
                                        'basic_vda' => ['label' => 'Basic + VDA', 'required' => false, 'readonly' => true],
                                        'hra' => ['label' => 'HRA', 'required' => false, 'readonly' => false],
                                        'special_allowance' => ['label' => 'Special Allowance', 'required' => false, 'readonly' => false],
                                        'conveyance_allowance' => ['label' => 'Conveyance Allowance / Incentive', 'required' => false, 'readonly' => false],
                                        'bonus' => ['label' => 'Bonus', 'required' => false, 'readonly' => false],
                                        'gross_salary' => ['label' => 'Gross Salary', 'required' => false, 'readonly' => true],
                                    ] as $field => $options)
                                        <div class="col-lg-4 o-f-inp mb-3">
                                            <label for="{{ $field }}">
                                                {{ $options['label'] }}
                                                @if($options['required'])
                                                    <span class="text-danger">*</span>
                                                @endif
                                            </label>
                                            <input type="number" step="0.01" min="0" id="{{ $field }}" name="{{ $field }}"
                                                class="form-control shadow-none js-salary-field"
                                                value="{{ old($field, $profile->{$field} ?? '') }}"
                                                @if($options['required']) required @endif
                                                @if($options['readonly']) readonly @endif>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 mt-3">
                            <div class="btn-flex">
                                <a href="{{ route('staff-management.index') }}" class="btn btn-secondary me-2">Cancel</a>
                                <button type="button" class="btn btn-secondary me-2" id="wizardPrev">Previous</button>
                                <button type="button" class="submit-btn" id="wizardNext">Next</button>
                                <button type="submit" class="submit-btn js-loading-submit" id="wizardSubmit" data-loading-text="Loading...">{{ isset($record) ? 'Update' : 'Submit' }}</button>
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

            var wizardTabs = Array.from(document.querySelectorAll('[data-bs-toggle="tab"][data-bs-target^="#stf"]'));
            var prevButton = document.getElementById('wizardPrev');
            var nextButton = document.getElementById('wizardNext');
            var submitButton = document.getElementById('wizardSubmit');

            function activeWizardIndex() {
                return wizardTabs.findIndex(function (tab) {
                    return tab.classList.contains('active');
                });
            }

            function updateWizardButtons() {
                var index = activeWizardIndex();
                prevButton.classList.toggle('d-none', index <= 0);
                nextButton.classList.toggle('d-none', index >= wizardTabs.length - 1);
                submitButton.classList.toggle('d-none', index < wizardTabs.length - 1);
            }

            nextButton.addEventListener('click', function () {
                var index = activeWizardIndex();
                if (index < wizardTabs.length - 1) {
                    bootstrap.Tab.getOrCreateInstance(wizardTabs[index + 1]).show();
                }
            });

            prevButton.addEventListener('click', function () {
                var index = activeWizardIndex();
                if (index > 0) {
                    bootstrap.Tab.getOrCreateInstance(wizardTabs[index - 1]).show();
                }
            });

            wizardTabs.forEach(function (tab) {
                tab.addEventListener('shown.bs.tab', updateWizardButtons);
            });
            updateWizardButtons();

            document.getElementById('togglePassword').addEventListener('click', function () {
                var passwordInput = document.getElementById('password');
                var icon = this.querySelector('i');
                var isHidden = passwordInput.type === 'password';

                passwordInput.type = isHidden ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !isHidden);
                icon.classList.toggle('fa-eye-slash', isHidden);
                this.title = isHidden ? 'Hide password' : 'Show password';
            });

            var avatarInput = document.getElementById('avatar');
            var avatarPreview = document.getElementById('staffAvatarPreview');

            if (avatarInput && avatarPreview) {
                avatarInput.addEventListener('change', function () {
                    var file = this.files && this.files[0] ? this.files[0] : null;

                    if (! file) {
                        return;
                    }

                    avatarPreview.src = URL.createObjectURL(file);
                    avatarPreview.onload = function () {
                        URL.revokeObjectURL(avatarPreview.src);
                    };
                });
            }

            var salaryFields = ['basic', 'vda', 'hra', 'special_allowance', 'conveyance_allowance', 'bonus'];
            var basicVdaInput = document.getElementById('basic_vda');
            var grossSalaryInput = document.getElementById('gross_salary');

            function salaryValue(field) {
                var input = document.getElementById(field);
                var value = input ? parseFloat(input.value) : 0;

                return Number.isFinite(value) ? value : 0;
            }

            function hasSalaryInput() {
                return salaryFields.some(function (field) {
                    var input = document.getElementById(field);

                    return input && input.value !== '';
                });
            }

            function calculateSalary() {
                var basicVda = salaryValue('basic') + salaryValue('vda');
                var grossSalary = basicVda
                    + salaryValue('hra')
                    + salaryValue('special_allowance')
                    + salaryValue('conveyance_allowance')
                    + salaryValue('bonus');

                if (! hasSalaryInput()) {
                    basicVdaInput.value = '';
                    grossSalaryInput.value = '';

                    return;
                }

                basicVdaInput.value = basicVda.toFixed(2);
                grossSalaryInput.value = grossSalary.toFixed(2);
            }

            salaryFields.forEach(function (field) {
                var input = document.getElementById(field);

                if (input) {
                    input.addEventListener('input', calculateSalary);
                }
            });
            calculateSalary();

            document.querySelectorAll('.js-loading-form').forEach(function (form) {
                form.addEventListener('submit', function () {
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
