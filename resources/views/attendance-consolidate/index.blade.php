@section('title', 'Attendance Consolidate')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Attendance Consolidate</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">HRMS</li>
                    <li class="breadcrumb-item active">Attendance Consolidate</li>
                </ol>
            </nav>
        </div>
        <div class="main-table-container">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="row mb-3">
                <div class="col-md-3"><label for="yearFilter">Year</label><select id="yearFilter"
                        class="form-select consolidate-filter">
                        <option value="">All years</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label for="monthFilter">Month</label><select id="monthFilter"
                        class="form-select consolidate-filter">
                        <option value="">All months</option>
                        @foreach ($months as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label for="depotFilter">Depot</label><select id="depotFilter"
                        class="form-select consolidate-filter">
                        <option value="">All depots</option>
                        @foreach ($depots as $depot)
                            <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2 align-items-end">
                    <button id="resetFilters" class="fil-btn" type="button">Reset</button>
                    <div class="btns-group-container">
                        @can('attendance-management.create')
                            <a class="imp-btn" href="{{ route('attendance-consolidate.import.form') }}">Import CSV /
                                Excel</a>
                        @endcan
                    </div>

                </div>
            </div>
            <div class="table-responsive">
                <table id="consolidateTable" class="table tble-cstm align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>SL NO</th>
                            <th>Year</th>
                            <th>Month</th>
                            <th>Depot</th>
                            <th>Imported by and date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>
    @section('scripts')
        <script>
            $(function () {
                $('.consolidate-filter').select2({ width: '100%', allowClear: true, placeholder: '---Select---' });
                const table = $('#consolidateTable').DataTable({
                    processing: true,
                    serverSide: true,
                    order: [
                        [4, 'desc']
                    ],
                    ajax: {
                        url: @json(route('attendance-consolidate.index')),
                        data: function (d) {
                            d.year = $('#yearFilter').val();
                            d.month = $('#monthFilter').val();
                            d.depot_id = $('#depotFilter').val();
                        }
                    },
                    columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    }, {
                        data: 'year'
                    },
                    {
                        data: 'month_name',
                        name: 'month',
                        searchable: false
                    }, {
                        data: 'depot_name'
                    },
                    {
                        data: 'imported_details', name: 'imported_at', searchable: false
                    }, {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                    ]
                });
                $('.consolidate-filter').on('change', function () {
                    table.ajax.reload();
                });
                $('#resetFilters').on('click', function () {
                    $('.consolidate-filter').val('').trigger('change.select2');
                    table.ajax.reload();
                });
            });
        </script>
    @endsection
</x-app-layout>