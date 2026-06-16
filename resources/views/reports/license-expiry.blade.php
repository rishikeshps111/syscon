@section('title')
    License Expiry Report
@endsection

<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>License Expiry Report</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">License Expiry Report</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="main-table-container">
                    <form id="licenseExpiryFilterForm" action="{{ route('reports.license-expiry.index') }}" method="GET">
                        <div class="row align-items-end">
                            <div class="col-lg-3 col-md-4 mb-3">
                                <div class="o-f-inp">
                                    <label for="expiry_filter">Filter By</label>
                                    <select id="expiry_filter" name="expiry_filter" class="form-select shadow-none select2-filter">
                                        @foreach($expiryFilters as $value => $label)
                                            <option value="{{ $value }}" @selected(($filters['expiry_filter'] ?? '6_months') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('expiry_filter')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-lg-9 col-md-8 mb-3">
                                <div class="filter-btns-top justify-content-start">
                                    <button type="button" id="resetFilters" class="reset-btn border-0">Reset</button>
                                    <button type="button" id="exportLicenseExpiryReport" class="exp-btn">Export</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-over mt-3">
                        <table id="licenseExpiryTable" class="align-middle mb-0 table tble-cstm bg-transparent">
                            <thead>
                                <tr class="payroll-table">
                                    <th class="text-center nowrap">SL No</th>
                                    <th class="text-center nowrap">Driver Name</th>
                                    <th class="text-center nowrap">Assigned</th>
                                    <th class="text-center nowrap">Depot</th>
                                    <th class="text-center nowrap">License No</th>
                                    <th class="text-center nowrap">Badge No</th>
                                    <th class="text-center nowrap">License Expiry Date</th>
                                    <th class="text-center nowrap">Badge Expiry Date</th>
                                    <th class="text-center nowrap">Phone No</th>
                                    <th class="text-center nowrap">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var filter = $('#expiry_filter');

                $('.select2-filter').select2({
                    width: '100%',
                    minimumResultsForSearch: Infinity
                });

                var table = $('#licenseExpiryTable').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('reports.license-expiry.index') }}",
                        data: function (data) {
                            data.expiry_filter = filter.val();
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'driver_name', name: 'user.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'assigned', name: 'assigned', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'depot_name', name: 'depot.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'license_no', name: 'license_number', className: 'text-center' },
                        { data: 'badge_no', name: 'badge_number', className: 'text-center' },
                        { data: 'license_expiry_date', name: 'expiry_date', className: 'text-center nowrap' },
                        { data: 'badge_expiry_date', name: 'badge_expiry_date', className: 'text-center nowrap' },
                        { data: 'phone_no', name: 'user.phone', orderable: false, searchable: false, className: 'text-center nowrap' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[6, 'asc']]
                });

                filter.on('change', function () {
                    table.ajax.reload();
                });

                $('#resetFilters').on('click', function () {
                    filter.val('6_months').trigger('change.select2');
                    table.ajax.reload();
                });

                $('#exportLicenseExpiryReport').on('click', function () {
                    var button = $(this);
                    var originalText = button.text();

                    button.prop('disabled', true).text('Exporting...');

                    $.ajax({
                        url: "{{ route('reports.license-expiry.export') }}",
                        type: 'GET',
                        data: { expiry_filter: filter.val() },
                        xhrFields: { responseType: 'blob' },
                        success: function (data, status, xhr) {
                            var fileName = 'license-expiry-report.xlsx';
                            var disposition = xhr.getResponseHeader('Content-Disposition') || '';
                            var match = disposition.match(/filename="?([^"]+)"?/);

                            if (match && match[1]) {
                                fileName = match[1];
                            }

                            var blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                            var url = window.URL.createObjectURL(blob);
                            var link = document.createElement('a');
                            link.href = url;
                            link.download = fileName;
                            document.body.appendChild(link);
                            link.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(link);
                        },
                        error: function () {
                            if (typeof showToast === 'function') {
                                showToast('error', 'Export failed.');
                            }
                        },
                        complete: function () {
                            button.prop('disabled', false).text(originalText);
                        }
                    });
                });

                $('#licenseExpiryFilterForm').on('submit', function (event) {
                    event.preventDefault();
                    table.ajax.reload();
                });
            });
        </script>
    @endsection
</x-app-layout>
