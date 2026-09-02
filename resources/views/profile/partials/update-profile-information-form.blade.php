@php
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
    $selectedCountryCode = old('country_code', $user->country_code ?? '+91');
@endphp

<div class="tab-pane fade show active profile-overview" id="profile-overview">
    <form id="profileForm" enctype="multipart/form-data">
        @csrf
       <div class="profile-view-cs">

    <div class="profile-avatar-section">
        <div class="profile-avatar-wrap">
            <img id="avatarPreview"
                 src="{{ $user->avatar_url }}"
                 alt="{{ $user->name }}">

            <label for="upload" class="profile-avatar-upload" title="Change profile image">
                <i class="bi bi-camera-fill"></i>
            </label>
        </div>

        <input type="file"
               name="avatar"
               id="upload"
               class="d-none"
               accept="image/*">

        <div class="profile-avatar-actions">
            <label for="upload" class="profile-upload-btn">
                <i class="bi bi-upload"></i>
                Change Photo
            </label>

            <a href="#"
               class="profile-remove-btn"
               id="removeAvatar"
               title="Remove my profile image">
                <i class="bi bi-trash3"></i>
            </a>
        </div>

        <div class="text-danger mt-2" id="error-avatar"></div>
    </div>


    <div class="profile-info-section">

        <div class="profile-info-heading">
            <div>
                <span class="profile-info-label">ACCOUNT PROFILE</span>
                <h4>Personal Information</h4>
            </div>

            <span class="profile-status">
                <i class="bi bi-check-circle-fill"></i>
                Active
            </span>
        </div>


        <div class="profile-preview-cs">

            <div class="profile-detail">
                <div class="profile-detail-icon">
                    <i class="bi bi-person-fill"></i>
                </div>

                <div>
                    <small>Name</small>
                    <strong id="previewName">
                        {{ $user->name }}
                    </strong>
                </div>
            </div>


            <div class="profile-detail">
                <div class="profile-detail-icon">
                    <i class="bi bi-telephone-fill"></i>
                </div>

                <div>
                    <small>Phone</small>
                    <strong id="previewPhone">
                        {{ $user->full_phone }}
                    </strong>
                </div>
            </div>


            <div class="profile-detail w-100">
                <div class="profile-detail-icon">
                    <i class="bi bi-envelope-fill"></i>
                </div>

                <div>
                    <small>Email Address</small>
                    <strong id="previewEmail">
                        {{ $user->email }}
                    </strong>
                </div>
            </div>

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
                    <select name="country_code" id="country_code" class="form-select shadow-none flex-grow-1"
                        style=" max-width: 66px;  padding: 0; padding-left: 5px;">
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