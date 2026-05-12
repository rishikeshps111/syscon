function showToast(icon, message) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

function togglePassword(inputId) {
    let input = document.getElementById(inputId);
    let icon = input.nextElementSibling.querySelector('i');

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = "password";
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function deleteRecord(url, tableId, confirmMessage = 'Are you sure?') {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    Swal.fire({
        title: 'Are you sure?',
        text: confirmMessage,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'DELETE',
                data: {
                    _token: csrfToken
                },
                success: function (response) {
                    $('#' + tableId).DataTable().ajax.reload();

                    Swal.fire({
                        title: 'Deleted!',
                        text: 'The record has been deleted.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    let message = 'Something went wrong!';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        if (xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors)[0][0];
                        }
                    }
                    if (xhr.status === 422) {
                        showToast('error', message);
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: message,
                            icon: 'error'
                        });
                    }
                }
            });
        }
    });
}
