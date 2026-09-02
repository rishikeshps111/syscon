@section('title', isset($record) ? 'Edit Staff' : 'Add Edit Staff')
<x-app-layout>
    @php
        $record = $record ?? null;
        $profile = $profile ?? null;
        $selectedRole = old('role', $employeeRole ?? 'Staff');
        $selectedReporting = old('reporting_to', $profile?->reporting_to);
        $countryCodes = ['+91', '+1', '+44', '+61', '+971', '+65', '+60', '+81', '+49', '+33'];
        $locationDefaults = ['state_id' => $defaultStateId ?? '', 'district_id' => $defaultDistrictId ?? '', 'location_id' => $defaultLocationId ?? ''];
        $value = fn($field, $default = '') => old($field, $profile?->{$field} ?? ($locationDefaults[$field] ?? $default));
        $dateValue = fn($field) => old($field, $profile?->{$field}?->format('Y-m-d'));
        $avatarUrl = $record?->avatar_url ?? asset('assets/img/user.png');
    @endphp

    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ isset($record) ? 'Edit Staff' : 'Add Staff' }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item"><a href="{{ route('staff-management.index') }}">Staff Management</a>
                    </li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit' : 'Add' }}</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container">
            <form id="staffWizardForm" class="js-loading-form" method="POST" enctype="multipart/form-data"
                action="{{ isset($record) ? route('staff-management.update', $record) : route('staff-management.store') }}"
                novalidate>
                @csrf
                @isset($record) @method('PUT') @endisset

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <ul class="nav nav-tabs-custom" role="tablist">
                    @foreach (['Basic Information', 'Personal Details', 'Employment Details', 'Location Details', 'Bank Details', 'Salary Structure'] as $index => $tab)
                        <li class="nav-item {{ $index === 0 ? 'ps-0 ms-0' : '' }}" role="presentation">
                            <button type="button" class="nav-link {{ $index === 0 ? 'active ms-0' : '' }}"
                                data-bs-toggle="tab" data-bs-target="#stf{{ $index + 1 }}">{{ $tab }}</button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content pt-3">
                    <div class="tab-pane fade show active" id="stf1" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3"><label for="name">Name <span
                                        class="text-danger">*</span></label><input id="name" name="name"
                                    class="form-control shadow-none @error('name') is-invalid @enderror"
                                    value="{{ old('name', $record?->name) }}" required>@error('name')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="col-lg-4 o-f-inp mb-3"><label for="email">Email</label><input type="email"
                                    id="email" name="email"
                                    class="form-control shadow-none @error('email') is-invalid @enderror"
                                    value="{{ old('email', $record?->email) }}">@error('email')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="phone">Phone <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="country_code" id="country_code"
                                        class="form-select shadow-none @error('country_code') is-invalid @enderror"
                                        style="max-width:115px" required>@foreach($countryCodes as $code)
                                        <option value="{{ $code }}" @selected(old('country_code', $record?->country_code ?? '+91') === $code)>{{ $code }}</option>@endforeach
                                    </select>
                                    <input id="phone" name="phone"
                                        class="form-control shadow-none @error('phone') is-invalid @enderror"
                                        value="{{ old('phone', $record?->phone) }}" required>
                                </div>
                                @error('country_code')<span class="text-danger">{{ $message }}</span>@enderror
                                @error('phone')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3"><label for="ref_code">Ref Code</label><input
                                    id="ref_code" name="ref_code"
                                    class="form-control shadow-none @error('ref_code') is-invalid @enderror"
                                    value="{{ old('ref_code', $record?->ref_code) }}">@error('ref_code')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="col-lg-4 o-f-inp mb-3"><label for="employeeRole">Role <span
                                        class="text-danger">*</span></label><select name="role" id="employeeRole"
                                    class="form-select shadow-none select2 @error('role') is-invalid @enderror"
                                    required>
                                    <option value="">--- Select ---</option>@foreach($employeeRoles as $role)
                                        <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
                                    @endforeach
                                </select>@error('role')<span class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="col-lg-4 o-f-inp mb-3"><label for="depot_id">Depot <span
                                        class="text-danger">*</span></label><select name="depot_id" id="depot_id"
                                    class="form-select shadow-none select2 @error('depot_id') is-invalid @enderror"
                                    required>
                                    <option value="">--- Select ---</option>@foreach($depots as $depot)
                                        <option value="{{ $depot->id }}" @selected($value('depot_id') == $depot->id)>
                                            {{ $depot->name }}
                                    </option>@endforeach
                                </select>@error('depot_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div id="designationWrap" class="col-lg-4 o-f-inp mb-3"><label
                                    for="designation_id">Designation <span class="text-danger">*</span></label><select
                                    name="designation_id" id="designation_id"
                                    class="form-select shadow-none select2 @error('designation_id') is-invalid @enderror">
                                    <option value="">--- Select ---</option>@foreach($designations as $designation)
                                        <option value="{{ $designation->id }}"
                                            @selected($value('designation_id') == $designation->id)>{{ $designation->name }}
                                    </option>@endforeach
                                </select>@error('designation_id')<span
                                class="text-danger">{{ $message }}</span>@enderror</div>
                            <div id="passwordWrap" class="col-lg-4 o-f-inp mb-3"><label for="password">Password
                                    @unless($record)<span class="text-danger">*</span>@endunless</label>
                                <div class="input-group"><input type="password" id="password" name="password"
                                        class="form-control shadow-none @error('password') is-invalid @enderror"><button
                                        class="btn btn-outline-secondary credential-toggle" type="button"
                                        data-target="password"><i class="fa-solid fa-eye"></i></button></div>
                                @error('password')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div id="passcodeWrap" class="col-lg-4 o-f-inp mb-3"><label for="passcode">Passcode
                                    @unless($record)<span class="text-danger">*</span>@endunless</label>
                                <div class="input-group"><input type="password" inputmode="numeric" pattern="[0-9]{6}"
                                        maxlength="6" id="passcode" name="passcode"
                                        class="form-control shadow-none @error('passcode') is-invalid @enderror"><button
                                        class="btn btn-outline-secondary credential-toggle" type="button"
                                        data-target="passcode"><i class="fa-solid fa-eye"></i></button></div>
                                @error('passcode')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3"><label for="employment_type">Employment Type <span
                                        class="text-danger">*</span></label><select name="employment_type"
                                    id="employment_type"
                                    class="form-select shadow-none select2 @error('employment_type') is-invalid @enderror"
                                    required>
                                    <option value="">--- Select ---</option>@foreach($employmentTypes as $key => $label)
                                        <option value="{{ $key }}" @selected($value('employment_type') === $key)>{{ $label }}
                                    </option>@endforeach
                                </select>@error('employment_type')<span
                                class="text-danger">{{ $message }}</span>@enderror</div>
                            <div id="reportingToWrap" class="col-lg-4 o-f-inp mb-3"><label for="reporting_to">Reporting
                                    To</label><select name="reporting_to" id="reporting_to"
                                    class="form-select shadow-none select2 @error('reporting_to') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                </select>@error('reporting_to')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            {{-- <div class="col-lg-4 o-f-inp mb-3"><label for="is_active">Status <span
                                        class="text-danger">*</span></label><select name="is_active" id="is_active"
                                    class="form-select shadow-none select2 @error('is_active') is-invalid @enderror"
                                    required>
                                    <option value="1" @selected(old('is_active', $record?->is_active ?? 1) == 1)>Active
                                    </option>
                                    <option value="0" @selected(old('is_active', $record?->is_active ?? 1) ==
                                        0)>Inactive
                                    </option>
                                </select>@error('is_active')<span class="text-danger">{{ $message }}</span>@enderror
                            </div> --}}
                            <input type="hidden" name="is_active"
                                value="{{ old('is_active', $record ? (int) $record->is_active : 0) }}">
                            <div class="col-lg-4 o-f-inp file-input mb-3"><label for="avatar">Image</label>
                                <div class="d-flex align-items-center gap-3"><img id="employeeAvatarPreview"
                                        src="{{ $avatarUrl }}" width="72" height="72"
                                        class="rounded object-fit-cover border" alt="Employee image preview"><input
                                        type="file" id="avatar" name="avatar" accept="image/*"
                                        class="form-control shadow-none @error('avatar') is-invalid @enderror"></div>
                                @error('avatar')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="stf2" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-6 o-f-inp mb-3"><label for="father_name">Father's Name <span
                                        class="text-danger">*</span></label><input id="father_name" name="father_name"
                                    class="form-control shadow-none @error('father_name') is-invalid @enderror"
                                    value="{{ $value('father_name') }}" required>@error('father_name')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="col-lg-6 o-f-inp mb-3"><label for="date_of_birth">Date of Birth <span
                                        class="text-danger">*</span></label><input type="date" id="date_of_birth"
                                    name="date_of_birth"
                                    class="form-control shadow-none @error('date_of_birth') is-invalid @enderror"
                                    value="{{ $dateValue('date_of_birth') }}" required>@error('date_of_birth')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="col-lg-6 o-f-inp mb-3"><label for="aadhaar_number">Aadhaar Number <span
                                        class="text-danger">*</span></label><input id="aadhaar_number"
                                    name="aadhaar_number"
                                    class="form-control shadow-none @error('aadhaar_number') is-invalid @enderror"
                                    value="{{ $value('aadhaar_number') }}" required>@error('aadhaar_number')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="col-lg-6 o-f-inp mb-3"><label for="pan_number">PAN Number <span
                                        class="text-danger">*</span></label><input id="pan_number" name="pan_number"
                                    class="form-control shadow-none @error('pan_number') is-invalid @enderror"
                                    value="{{ $value('pan_number') }}" required>@error('pan_number')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="stf3" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3"><label for="date_of_joining">Date of Joining <span
                                        class="text-danger">*</span></label><input type="date" id="date_of_joining"
                                    name="date_of_joining"
                                    class="form-control shadow-none @error('date_of_joining') is-invalid @enderror"
                                    value="{{ $dateValue('date_of_joining') }}" required>@error('date_of_joining')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="col-lg-4 o-f-inp mb-3"><label for="uan">UAN <span
                                        class="text-danger">*</span></label><input id="uan" name="uan"
                                    class="form-control shadow-none @error('uan') is-invalid @enderror"
                                    value="{{ $value('uan') }}" required>@error('uan')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="col-lg-4 o-f-inp mb-3"><label for="esic_wc">ESIC / WC <span
                                        class="text-danger">*</span></label><input id="esic_wc" name="esic_wc"
                                    class="form-control shadow-none @error('esic_wc') is-invalid @enderror"
                                    value="{{ $value('esic_wc') }}" required>@error('esic_wc')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="stf4" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-6 o-f-inp mb-3"><label for="country">Country <span
                                        class="text-danger">*</span></label><select name="country" id="country"
                                    class="form-select shadow-none select2 @error('country') is-invalid @enderror"
                                    required>
                                    <option value="">--- Select ---</option>@foreach($countries as $country)
                                        <option value="{{ $country }}" @selected($value('country', 'India') === $country)>
                                            {{ $country }}
                                    </option>@endforeach
                                </select>@error('country')<span class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="col-lg-6 o-f-inp mb-3"><label for="state_id">State <span
                                        class="text-danger">*</span></label><select name="state_id" id="state_id"
                                    class="form-select shadow-none select2 @error('state_id') is-invalid @enderror"
                                    required>
                                    <option value="">--- Select ---</option>@foreach($states as $state)
                                        <option value="{{ $state->id }}" @selected($value('state_id') == $state->id)>
                                            {{ $state->name }}
                                    </option>@endforeach
                                </select>@error('state_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3"><label for="district_id">District <span
                                        class="text-danger">*</span></label><select name="district_id" id="district_id"
                                    class="form-select shadow-none select2 @error('district_id') is-invalid @enderror"
                                    required>
                                    <option value="">--- Select ---</option>@foreach($districts as $district)
                                        <option value="{{ $district->id }}" @selected($value('district_id') == $district->id)>
                                            {{ $district->name }}
                                    </option>@endforeach
                                </select>@error('district_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3"><label for="location_id">Location <span
                                        class="text-danger">*</span></label><select name="location_id" id="location_id"
                                    class="form-select shadow-none select2 @error('location_id') is-invalid @enderror"
                                    required>
                                    <option value="">--- Select ---</option>@foreach($locations as $location)
                                        <option value="{{ $location->id }}" @selected($value('location_id') == $location->id)>
                                            {{ $location->name }}
                                    </option>@endforeach
                                </select>@error('location_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="stf5" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-6 o-f-inp mb-3"><label for="bank_account_number">Bank Account Number
                                    <span class="text-danger">*</span></label><input id="bank_account_number"
                                    name="bank_account_number"
                                    class="form-control shadow-none @error('bank_account_number') is-invalid @enderror"
                                    value="{{ $value('bank_account_number') }}"
                                    required>@error('bank_account_number')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                            <div class="col-lg-6 o-f-inp mb-3"><label for="ifsc_code">IFSC Code <span
                                        class="text-danger">*</span></label><input id="ifsc_code" name="ifsc_code"
                                    class="form-control shadow-none @error('ifsc_code') is-invalid @enderror"
                                    value="{{ $value('ifsc_code') }}" required>@error('ifsc_code')<span
                                    class="text-danger">{{ $message }}</span>@enderror</div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="stf6" role="tabpanel">
                        <div id="salaryStructureContainer">@include('components.dynamic-salary-structure')</div>
                    </div>
                </div>

                <div class="btn-flex-cs mt-3">
                    <a href="{{ route('staff-management.index') }}" class="btn-cancel-cs">Cancel</a>
                    <button type="button" class="btn-prev-cs" id="wizardPrev">Previous</button>
                    <button type="button" class="btn-next-cs" id="wizardNext">Next</button>
                    <button type="submit" class="btn-submit-cs js-loading-submit d-none" id="wizardSubmit"
                        data-loading-text="Loading...">{{ isset($record) ? 'Update' : 'Submit' }}</button>
                </div>
            </form>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                $('.select2').select2({ placeholder: '--- Select ---', allowClear: true, width: '100%' });
                $('.select2-basic').select2({ minimumResultsForSearch: Infinity, width: '100%' });

                const isEditing = @json((bool) $record);
                const recordId = @json($record?->id);
                let selectedReporting = @json($selectedReporting);
                let initialRole = @json($selectedRole);
                let initialDesignation = @json((string) $value('designation_id'));

                function resetSelect(selector, text, disabled = false) {
                    $(selector).html(new Option(text, '')).prop('disabled', disabled).val('').trigger('change.select2');
                }

                function updateCredentialFields() {
                    const role = $('#employeeRole').val();
                    const staff = role === 'Staff';
                    const passcodeRole = role === 'Controller' || role === 'Supervisor';
                    $('#designationWrap, #passwordWrap').toggle(staff);
                    $('#passcodeWrap').toggle(passcodeRole);
                    $('#reportingToWrap').toggle(role !== 'Supervisor');
                    $('#designation_id').prop('required', staff).prop('disabled', !staff);
                    $('#password').prop('required', !isEditing && staff).prop('disabled', !staff);
                    $('#passcode').prop('required', !isEditing && passcodeRole).prop('disabled', !passcodeRole);
                    $('#reporting_to').prop('disabled', role === 'Supervisor');
                    if (!staff) $('#designation_id').val('').trigger('change.select2');
                }

                function loadManagers() {
                    const role = $('#employeeRole').val();
                    const depot = $('#depot_id').val();
                    const designation = $('#designation_id').val();
                    resetSelect('#reporting_to', '--- Select ---', true);
                    if (!role || role === 'Supervisor' || !depot || (role === 'Staff' && !designation)) return;
                    resetSelect('#reporting_to', 'Loading...', true);
                    $.get(@json(route('staff-management.reporting-managers')), { role, depot_id: depot, designation_id: designation || null, exclude_user_id: recordId })
                        .done(function (rows) {
                            const select = $('#reporting_to').html(new Option('--- Select ---', '')).prop('disabled', false);
                            rows.forEach(row => select.append(new Option(`${row.name}${row.code ? ` (${row.code})` : ''}`, row.id)));
                            select.val(selectedReporting ? String(selectedReporting) : '').trigger('change.select2');
                            selectedReporting = null;
                        })
                        .fail(() => { resetSelect('#reporting_to', '--- Select ---'); showToast('error', 'Unable to load reporting users.'); });
                }

                function bindSalaryCalculation() {
                    function calculate() {
                        let total = 0;
                        document.querySelectorAll('.js-dynamic-salary-field').forEach(input => {
                            if (!input.disabled) total += (input.dataset.type === 'deduction' ? -1 : 1) * (parseFloat(input.value) || 0);
                        });
                        const preview = document.getElementById('gross_salary_preview');
                        if (preview) preview.value = total.toFixed(2);
                    }
                    $('.js-dynamic-salary-field').off('input.salary').on('input.salary', calculate);
                    calculate();
                }

                function loadSalaryStructure(force = false) {
                    const role = $('#employeeRole').val();
                    const designation = $('#designation_id').val();
                    if (!role || (role === 'Staff' && !designation)) {
                        $('#salaryStructureContainer').html('<div class="alert alert-info mb-0">Select a role' + (role === 'Staff' ? ' and designation' : '') + ' to load salary components.</div>');
                        return;
                    }
                    if (!force && role === initialRole && String(designation || '') === initialDesignation) { bindSalaryCalculation(); return; }
                    $('#salaryStructureContainer').html('<div class="text-muted">Loading salary components...</div>');
                    $.get(@json(route('staff-management.salary-structure')), { role, designation_id: designation || null, user_id: isEditing && role === initialRole ? recordId : null })
                        .done(html => { $('#salaryStructureContainer').html(html); bindSalaryCalculation(); })
                        .fail(() => $('#salaryStructureContainer').html('<div class="alert alert-danger mb-0">Unable to load salary components.</div>'));
                }

                $('#employeeRole').on('change', function () { updateCredentialFields(); selectedReporting = null; loadManagers(); loadSalaryStructure(true); });
                $('#depot_id').on('change', function () { selectedReporting = null; loadManagers(); });
                $('#designation_id').on('change', function () { selectedReporting = null; loadManagers(); loadSalaryStructure(true); });
                updateCredentialFields(); loadManagers(); loadSalaryStructure(true);

                $('#country').on('change', function () {
                    const targetState = String($('#state_id').val() || @json($value('state_id')) || '');
                    const targetDistrict = String(@json($value('district_id')) || '');
                    const targetLocation = String(@json($value('location_id')) || '');
                    resetSelect('#state_id', this.value ? 'Loading...' : '--- Select ---', !!this.value);
                    resetSelect('#district_id', '--- Select ---', true); resetSelect('#location_id', '--- Select ---', true);
                    if (!this.value) return;
                    $.get(@json(route('staff-management.states-by-country')), { country: this.value }).done(rows => {
                        const select = $('#state_id').html(new Option('--- Select ---', '')).prop('disabled', false);
                        rows.forEach(row => select.append(new Option(row.name, row.id)));
                        select.val(targetState).trigger('change.select2').trigger('change', [targetDistrict, targetLocation]);
                    }).fail(() => showToast('error', 'Unable to load states.'));
                });
                $('#state_id').on('change', function (event, targetDistrict = '', targetLocation = '') {
                    resetSelect('#district_id', this.value ? 'Loading...' : '--- Select ---', !!this.value); resetSelect('#location_id', '--- Select ---', true);
                    if (!this.value) return;
                    $.get(@json(route('staff-management.districts-by-state')), { state_id: this.value }).done(rows => {
                        const select = $('#district_id').html(new Option('--- Select ---', '')).prop('disabled', false); rows.forEach(row => select.append(new Option(row.name, row.id))); select.val(targetDistrict).trigger('change.select2').trigger('change', [targetLocation]);
                    }).fail(() => showToast('error', 'Unable to load districts.'));
                });
                $('#district_id').on('change', function (event, targetLocation = '') {
                    resetSelect('#location_id', this.value ? 'Loading...' : '--- Select ---', !!this.value);
                    if (!this.value || !$('#state_id').val()) return;
                    $.get(@json(route('staff-management.locations-by-district')), { state_id: $('#state_id').val(), district_id: this.value }).done(rows => {
                        const select = $('#location_id').html(new Option('--- Select ---', '')).prop('disabled', false); rows.forEach(row => select.append(new Option(row.name, row.id))); select.val(targetLocation).trigger('change.select2');
                    }).fail(() => showToast('error', 'Unable to load locations.'));
                });
                if (!isEditing && $('#country').val()) $('#country').trigger('change');

                $('.credential-toggle').on('click', function () { const input = document.getElementById(this.dataset.target); const show = input.type === 'password'; input.type = show ? 'text' : 'password'; $(this).find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show); });
                $('#avatar').on('change', function () { const file = this.files?.[0]; if (!file) return; const url = URL.createObjectURL(file); $('#employeeAvatarPreview').attr('src', url).one('load', () => URL.revokeObjectURL(url)); });

                const tabs = Array.from(document.querySelectorAll('[data-bs-toggle="tab"][data-bs-target^="#stf"]'));
                function activeIndex() { return tabs.findIndex(tab => tab.classList.contains('active')); }
                function wizardState() { const index = activeIndex(); $('#wizardPrev').toggleClass('d-none', index === 0); $('#wizardNext').toggleClass('d-none', index === tabs.length - 1); $('#wizardSubmit').toggleClass('d-none', index !== tabs.length - 1); }
                function firstInvalid(container) { return Array.from(container.querySelectorAll('input, select, textarea')).find(field => !field.disabled && field.offsetParent !== null && !field.checkValidity()); }
                $('#wizardNext').on('click', function () { const index = activeIndex(); const invalid = firstInvalid(document.querySelector(tabs[index].dataset.bsTarget)); if (invalid) { invalid.reportValidity(); return; } if (index < tabs.length - 1) bootstrap.Tab.getOrCreateInstance(tabs[index + 1]).show(); });
                $('#wizardPrev').on('click', function () { const index = activeIndex(); if (index > 0) bootstrap.Tab.getOrCreateInstance(tabs[index - 1]).show(); });
                tabs.forEach(tab => tab.addEventListener('shown.bs.tab', wizardState)); wizardState();
                $('#staffWizardForm').on('submit', function (event) {
                    const invalid = Array.from(this.elements).find(field => !field.disabled && !field.checkValidity());

                    if (!invalid) {
                        if (this.dataset.submitting === 'true') {
                            event.preventDefault();
                            return;
                        }

                        this.dataset.submitting = 'true';
                        $('#wizardSubmit')
                            .prop('disabled', true)
                            .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...');
                        return;
                    }

                    event.preventDefault();
                    const pane = invalid.closest('.tab-pane');
                    const tab = tabs.find(item => item.dataset.bsTarget === `#${pane?.id}`);
                    if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
                    setTimeout(() => invalid.reportValidity(), 150);
                });
            });
        </script>
    @endsection
</x-app-layout>
