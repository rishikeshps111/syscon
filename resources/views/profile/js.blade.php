<script>
    $(document).ready(function () {

        // avatar preview
        $("#upload").change(function () {
            let reader = new FileReader();
            reader.onload = function (e) {
                $("#avatarPreview")
                    .attr("src", e.target.result);
            };
            reader.readAsDataURL(this.files[0]);
        });


        // live preview
        $("#name").on("input", function () {
            $("#previewName").text($(this).val());
        });

        $("#email").on("input", function () {
            $("#previewEmail").text($(this).val());
        });

        function updatePhonePreview() {
            let phone = $("#phone").val().trim();
            let countryCode = $("#country_code").val();

            $("#previewPhone").text(phone ? `${countryCode} ${phone}` : '');
        }

        $("#phone").on("input", updatePhonePreview);
        $("#country_code").on("change", updatePhonePreview);

        // PROFILE UPDATE
        $("#profileForm").submit(function (e) {
            e.preventDefault();

            let formData = new FormData(this);
            formData.append('_method', 'PATCH'); // Laravel PATCH method

            // Clear previous errors
            $("#profileForm .text-danger").text('');

            let $btn = $(this).find('button[type="submit"]'); // get submit button
            let originalText = $btn.html(); // store original text

            // Show loading
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...');

            $.ajax({
                url: "{{ route('profile.update') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,

                success: function (res) {
                    showToast('success', 'Profile updated successfully');

                    // Update the preview
                    $("#previewName").text($("#name").val());
                    updatePhonePreview();
                    $("#previewEmail").text($("#email").val());

                    if (res.avatar_url) {
                        $("#avatarPreview").attr('src', res.avatar_url);
                    }
                },

                error: function (err) {
                    if (err.status === 422) {
                        let errors = err.responseJSON.errors;
                        // Loop through errors and show under fields
                        $.each(errors, function (field, messages) {
                            $("#error-" + field).text(messages[0]);
                        });
                    } else {
                        showToast('error', 'Something went wrong');
                    }
                },

                complete: function () {
                    // Revert button back to normal
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
        
        // PASSWORD UPDATE
        $("#passwordForm").submit(function (e) {
            e.preventDefault();

            // Clear previous errors
            $("#error-current_password").text('');
            $("#error-password").text('');
            $("#error-password_confirmation").text('');

            let $btn = $(this).find('button[type="submit"]'); // get submit button
            let originalText = $btn.html(); // store original text

            // Show loading
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Updating...');

            let data = $(this).serialize();
            data += '&_method=PUT'; // use PUT method

            $.ajax({
                url: "{{ route('password.update') }}",
                type: "POST",
                data: data,
                success: function (res) {
                    showToast('success', 'Password updated');
                    $("#passwordForm")[0].reset();
                },
                error: function (err) {
                    if (err.status === 422) {
                        let errors = err.responseJSON.errors;
                        if (errors.current_password) {
                            $("#error-current_password").text(errors.current_password[0]);
                        }
                        if (errors.password) {
                            $("#error-password").text(errors.password[0]);
                        }
                        if (errors.password_confirmation) {
                            $("#error-password_confirmation").text(errors.password_confirmation[0]);
                        }
                    } else {
                        showToast('error', 'Something went wrong');
                        console.log(err);
                    }
                },
                complete: function () {
                    // Revert button back to normal
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });

        $("#removeAvatar").click(function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to remove your profile image?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, remove it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request to delete avatar
                    $.ajax({
                        url: "{{ route('profile.remove-avatar') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (res) {
                            if (res.status) {
                                // Set preview to default
                                $("#avatarPreview").attr('src', '{{ asset('assets/img/user.png') }}');
                                // Clear file input
                                $("#upload").val('');
                                showToast('success', 'Profile image removed');
                            }
                        },
                        error: function (err) {
                            showToast('error', 'Something went wrong');
                            console.log(err);
                        }
                    });
                }
            });
        });
    });
</script>
