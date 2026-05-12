<!-- Vendor JS Files -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js')}}"></script>
<script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ asset('assets/vendor/chart.js/chart.umd.js')}}"></script>
<script src="{{ asset('assets/vendor/echarts/echarts.min.js')}}"></script>
<script src="{{ asset('assets/vendor/quill/quill.js')}}"></script>
<script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js')}}"></script>
<script src="{{ asset('assets/vendor/tinymce/tinymce.min.js')}}"></script>
<script src="{{ asset('assets/vendor/php-email-form/validate.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<!-- Template Main JS File -->
<script src="{{ asset('assets/js/main.js')}}"></script>
<script src="{{ asset('assets/js/calendar.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.8.0/dist/chart.min.js"></script>
<script src="https://kit.fontawesome.com/111740f521.js" crossorigin="anonymous"></script>
<script src="{{ asset('assets/js/common.js')}}"></script>

@yield('scripts')

@if(session('success'))
    <script>
        showToast('success', '{{ session('success') }}');
    </script>
@endif

@if(session('error'))
    <script>
        showToast('error', '{{ session('error') }}');
    </script>
@endif

@if(session('warning'))
    <script>
        showToast('warning', '{{ session('warning') }}');
    </script>
@endif

@if ($errors->any())
    <script>
        showToast('error', @json($errors->first()));
    </script>
@endif