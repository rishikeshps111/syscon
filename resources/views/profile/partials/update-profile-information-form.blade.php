@php
    $countryCodes = [
        '+91' => 'IN +91',
        '+1' => 'US +1',
        '+44' => 'UK +44',
        '+61' => 'AU +61',
        '+971' => 'AE +971',
        '+65' => 'SG +65',
        '+60' => 'MY +60',
        '+81' => 'JP +81',
        '+49' => 'DE +49',
        '+33' => 'FR +33',
    ];
    $selectedCountryCode = old('country_code', $user->country_code ?? '+91');
@endphp

<div class="tab-pane fade show active profile-overview" id="profile-overview">
    <form id="profileForm" enctype="multipart/form-data">
        @csrf
        <div class="row mb-3 o-f-inp">
            <div class="col-lg-4">
                <label class="col-form-label">Profile Image</label>
                <div class="d-flex align-items-center gap-3 profile-img">
                    <img id="avatarPreview" src="{{ $user->avatar_url }}" width="70">
                    <div>
                        <label for="upload" class="btn btn-primary btn-sm">
                            <i class="bi bi-upload"></i>
                        </label>
                        <input type="file" name="avatar" id="upload" class="d-none">
                        <a href="#" class="btn btn-danger btn-sm" id="removeAvatar" title="Remove my profile image">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </div>
                <div class="text-danger mt-1" id="error-avatar"></div>
            </div>
            <div class="col-lg-8">
                <div class="profile-preview">
                    <ul>
                        <li>
                            Name :
                            <span id="previewName">
                                {{ $user->name }}
                            </span>
                        </li>
                        <li>
                            Phone :
                            <span id="previewPhone">
                                {{ $user->full_phone }}
                            </span>
                        </li>
                        <li>
                            Email :
                            <span id="previewEmail">
                                {{ $user->email }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 o-f-inp mb-2">
                <label class="col-form-label">
                    Full Name
                </label>
                <input type="text" name="name" id="name" value="{{ $user->name }}" class="form-control shadow-none">
                <div class="text-danger mt-1" id="error-name"></div>
            </div>
            <div class="col-lg-4 o-f-inp mb-2">
                <label class="col-form-label">
                    Phone
                </label>
                <div class="input-group">
                    <select name="country_code" id="country_code"
                        class="form-select shadow-none flex-grow-0" style="max-width: 112px;">
                        @foreach ($countryCodes as $code => $label)
                            <option value="{{ $code }}" @selected($selectedCountryCode === $code)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="phone" id="phone" value="{{ $user->phone }}"
                        class="form-control shadow-none">
                </div>
                <div class="text-danger mt-1" id="error-country_code"></div>
                <div class="text-danger mt-1" id="error-phone"></div>
            </div>
            <div class="col-lg-4 o-f-inp mb-2">
                <label class="col-form-label">
                    Email
                </label>
                <input type="email" name="email" id="email" value="{{ $user->email }}" class="form-control shadow-none">
                <div class="text-danger mt-1" id="error-email"></div>
            </div>
        </div>
        <div class="text-center d_flex">
            <button type="submit" class="add-btn">
                Save Changes
            </button>
        </div>
    </form>
</div>
