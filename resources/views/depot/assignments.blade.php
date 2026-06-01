@section('title')
    Depot Assignments
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Depot Assignments</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('depots.index') }}">Depot Management</a></li>
                    <li class="breadcrumb-item active">Assignments</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec mb-3">Depot Details</h5>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="depot-assignment-card">
                        <span>Depot Code</span>
                        <strong>{{ $depot->code ?: '-' }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="depot-assignment-card">
                        <span>Depot</span>
                        <strong>{{ $depot->name ?: '-' }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="depot-assignment-card">
                        <span>District</span>
                        <strong>{{ $depot->district?->name ?: '-' }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="depot-assignment-card">
                        <span>Location</span>
                        <strong>{{ $depot->location?->name ?: '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-table-container mb-3">
            <div class="row">
                <div class="col-lg-3 mb-3">
                    <div class="o-f-inp">
                        <label for="dateFrom">From Date</label>
                        <input type="date" id="dateFrom" class="form-control shadow-none"
                            value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                </div>
                <div class="col-lg-3 mb-3">
                    <div class="o-f-inp">
                        <label for="dateTo">To Date</label>
                        <input type="date" id="dateTo" class="form-control shadow-none"
                            value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                </div>
                <div class="col-lg-6 d-flex align-items-end justify-content-end mb-3">
                    <div class="btn-flex">
                        <button type="button" id="resetFilters" class="btn btn-secondary">Reset</button>
                        <a href="{{ route('depots.index') }}" class="add-btn bg-filter">Back</a>
                        <a href="{{ route('depots.assignments.index', $depot->id, ['export' => 'csv']) }}"
                            id="exportCsv" class="btn btn-primary">Export CSV</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-table-container">
            <div class="table-over">
                <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center nowrap">SL No</th>
                            <th class="text-center nowrap">Module</th>
                            <th class="text-center nowrap">Assigned To</th>
                            <th class="text-center nowrap">From Date</th>
                            <th class="text-center nowrap">To Date</th>
                            <th class="text-center nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

    @section('scripts')
        <style>
            .depot-assignment-card {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                min-height: 78px;
                padding: 14px 16px;
            }

            .depot-assignment-card span {
                color: #6b7280;
                display: block;
                font-size: 13px;
                margin-bottom: 8px;
            }

            .depot-assignment-card strong {
                color: #111827;
                display: block;
                font-size: 15px;
                font-weight: 600;
                word-break: break-word;
            }
        </style>
        <script>
            $(function () {
                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('depots.assignments.index', $depot->id) }}",
                        data: function (data) {
                            data.date_from = $('#dateFrom').val();
                            data.date_to = $('#dateTo').val();
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'module_name', name: 'assignable_type', className: 'text-center' },
                        { data: 'assigned_to', name: 'assignable_id', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'from_date_display', name: 'from_date', className: 'text-center' },
                        { data: 'to_date_display', name: 'to_date', className: 'text-center' },
                        { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[3, 'desc']]
                });

                $('#dateFrom, #dateTo').on('change', function () {
                    updateExportUrl();
                    table.ajax.reload();
                });

                $('#resetFilters').on('click', function () {
                    $('#dateFrom, #dateTo').val('');
                    updateExportUrl();
                    table.ajax.reload();
                });

                function updateExportUrl() {
                    var url = new URL("{{ route('depots.assignments.index', $depot->id) }}", window.location.origin);
                    url.searchParams.set('export', 'csv');

                    if ($('#dateFrom').val()) {
                        url.searchParams.set('date_from', $('#dateFrom').val());
                    }

                    if ($('#dateTo').val()) {
                        url.searchParams.set('date_to', $('#dateTo').val());
                    }

                    $('#exportCsv').attr('href', url.toString());
                }

                updateExportUrl();
            });
        </script>
    @endsection
</x-app-layout>