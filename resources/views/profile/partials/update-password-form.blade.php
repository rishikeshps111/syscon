<div class="tab-pane fade" id="profile-change-password">
    <form id="passwordForm" class="mt-3">

        <div class="row mb-3">
            <!-- Current Password -->
            <div class="col-lg-4 o-f-inp">
                <label for="currentPassword" class="col-form-label">
                    Current Password <span class="text-danger">*</span>
                </label>
                <div class="position-relative">
                    <input name="current_password" type="password" class="form-control shadow-none"
                        id="currentPassword">
                    <span class="password-toggle" onclick="togglePassword('currentPassword')">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
                <div class="text-danger mt-1 small" id="error-current_password"></div>
            </div>

            <!-- New Password -->
            <div class="col-lg-4 o-f-inp">
                <label for="newPassword" class="col-form-label">
                    New Password <span class="text-danger">*</span>
                </label>
                <div class="position-relative">
                    <input name="password" type="password" class="form-control shadow-none" id="newPassword">
                    <span class="password-toggle" onclick="togglePassword('newPassword')">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
                <div class="text-danger mt-1 small" id="error-password"></div>
            </div>

            <!-- Re-enter New Password -->
            <div class="col-lg-4 o-f-inp">
                <label for="renewPassword" class="col-form-label">
                    Re-enter New Password <span class="text-danger">*</span>
                </label>
                <div class="position-relative">
                    <input name="password_confirmation" type="password" class="form-control shadow-none"
                        id="renewPassword">
                    <span class="password-toggle" onclick="togglePassword('renewPassword')">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
                <div class="text-danger mt-1 small" id="error-password_confirmation"></div>
            </div>
        </div>

        <div class="text-center d_flex">
            <button type="submit" class="add-btn">Change Password</button>
        </div>

    </form>
</div>