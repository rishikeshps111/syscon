<!-- Google Fonts -->
<link href="https://fonts.gstatic.com" rel="preconnect">
<link
    href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
    rel="stylesheet">

<!-- Vendor CSS Files -->
<link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/fontawesome/css/fontawesome.css') }}">
<link rel="stylesheet" href="{{ asset('assets/fontawesome/css/brands.css') }}">
<link rel="stylesheet" href="{{ asset('assets/fontawesome/css/solid.css') }}">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.5.6/build/css/intlTelInput.css">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<!-- Template Main CSS File -->
<link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/custom-two.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/dashboard.css') }}" rel="stylesheet">

@yield('styles')
<style>
    .chat-nav-bell {
        display: inline-flex;
        position: relative;
    }

    .chat-nav-badge,
    .chat-sidebar-badge {
        align-items: center;
        background: #dc3545;
        border-radius: 999px;
        color: #fff;
        display: inline-flex;
        font-size: 10px;
        font-weight: 700;
        height: 18px;
        justify-content: center;
        min-width: 18px;
        padding: 0 5px;
    }

    .chat-nav-badge {
        position: absolute;
        right: -8px;
        top: -8px;
    }

    .driver-license-expired-toast {
        border-radius: 8px;
        box-shadow: 0 14px 40px rgba(15, 23, 42, .18);
        padding: 12px 14px;
    }

    .driver-license-expired-toast .swal2-title {
        font-size: 15px;
        margin: 0 0 4px;
        text-align: left;
    }

    .driver-license-expired-toast .swal2-html-container {
        font-size: 13px;
        margin: 0;
        text-align: left;
    }

    .driver-license-expired-toast .swal2-actions {
        margin: 8px 0 0;
        justify-content: flex-start;
    }
</style>
