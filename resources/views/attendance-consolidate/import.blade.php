@section('title', 'Import Attendance Consolidate')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Import Attendance Consolidate</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('attendance-consolidate.index') }}">Attendance
                            Consolidate</a></li>
                    <li class="breadcrumb-item active">Import CSV / Excel</li>
                </ol>
            </nav>
        </div>
        <div class="row">
            <div class="col-lg-5 mb-3">
                <div class="main-table-container h-100">
                    <form class="js-loading-form" method="POST" action="{{ route('attendance-consolidate.preview') }}"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="import_now" value="1">
                        <div class="o-f-inp mb-3">
                            <label for="year">Year <span class="text-danger">*</span></label>
                            <select name="year" id="year" class="form-select shadow-none consolidate-select" required>
                                @foreach($years as $year)
                                    <option value="{{ $year }}" @selected(old('year', now()->year) == $year)>{{ $year }}
                                </option>@endforeach
                            </select>
                        </div>
                        <div class="o-f-inp mb-3">
                            <label for="month">Month <span class="text-danger">*</span></label>
                            <select name="month" id="month" class="form-select shadow-none consolidate-select" required>
                                @foreach($months as $value => $label)
                                    <option value="{{ $value }}" @selected(old('month', now()->month) == $value)>{{ $label }}
                                </option>@endforeach
                            </select>
                        </div>
                        <div class="o-f-inp mb-3">
                            <label for="depot_id">Depot <span class="text-danger">*</span></label>
                            <select name="depot_id" id="depot_id" class="form-select shadow-none consolidate-select"
                                required>
                                <option value="">---Select---</option>
                                @foreach($depots as $depot)
                                    <option value="{{ $depot->id }}" @selected(old('depot_id') == $depot->id)>
                                        {{ $depot->name }}
                                </option>@endforeach
                            </select>
                        </div>
                        <div class="o-f-inp file-input mb-3">
                            <label for="csv_file">CSV / Excel File <span class="text-danger">*</span></label>
                            <input type="file" name="csv_file" id="csv_file" class="form-control shadow-none"
                                accept=".csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required>
                        </div>
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">@foreach($errors->all() as $error)
                                <li>{{ $error }}</li>@endforeach
                                </ul>
                        </div>@endif
                        <div class="modal-btns-last">
                            <a href="{{ route('attendance-consolidate.index') }}" class="modal-btn-1">Back</a>
                            <button type="submit" class="modal-btn-2 js-loading-submit">Import CSV / Excel</button>
                        </div>
                        <p class="import-loading-message mt-3 mb-0" role="status" aria-live="polite" hidden>Importing
                            attendance. Please keep this page open…</p>
                    </form>
                </div>
            </div>
            <div class="col-lg-7 mb-3">
                <div class="main-table-container h-100">
                    <h5 class="mb-3">Import Instructions</h5>
                    <form method="GET" action="{{ route('attendance-consolidate.sample') }}" class="mb-3">
                        <div class="o-f-inp mb-3">
                            <label for="sample_depot_id">Sample depot <span class="text-danger">*</span></label>
                            <select name="depot_id" id="sample_depot_id"
                                class="form-select shadow-none consolidate-select" required>
                                <option value="">---Select---</option>
                                @foreach($depots as $depot)
                                <option value="{{ $depot->id }}">{{ $depot->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="btns-group-container"><button type="submit" class="exp-btn"
                                style="font-size: 10px !important;">Download Sample
                                Excel</button></div>
                    </form>
                    <p class="mb-2">Use these six columns in CSV or Excel (headers on row 1, or row 2 below a title):</p>
                    <code class="d-block mb-3">Emp Id,Name Of The Employee,P,W/O,A,Total</code>
                    <div class="table-over mb-3">
                        <table class="align-middle mb-0 table tble-cstm" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Required</th>
                                    <th>Instruction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Emp Id</td>
                                    <td>Yes</td>
                                    <td>Employee reference code. The depot sample fills this automatically. Preserve any
                                        leading zeros.</td>
                                </tr>
                                <tr>
                                    <td>Name Of The Employee</td>
                                    <td>Yes</td>
                                    <td>Employee name, filled automatically in the depot sample.</td>
                                </tr>
                                <tr>
                                    <td>P</td>
                                    <td>Yes</td>
                                    <td>Present days. Use a non-negative number with up to two decimal places.</td>
                                </tr>
                                <tr>
                                    <td>W/O</td>
                                    <td>Yes</td>
                                    <td>Week off days. Enter 0 if none.</td>
                                </tr>
                                <tr>
                                    <td>A</td>
                                    <td>Yes</td>
                                    <td>Absent days. Enter 0 if none.</td>
                                </tr>
                                <tr>
                                    <td>Total</td>
                                    <td>Yes</td>
                                    <td>P + W/O. Absent days are excluded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p>Fill the attendance columns in the Excel sample, then save as <strong>CSV UTF-8</strong> to
                        import. Excel downloads preserve the reference sheet’s colors; CSV files do not support colors.
                    </p>
                    <p class="mb-2">Maximum 2 MB and 10,000 employees per file. Each employee ID must be unique. Name or
                        depot differences require confirmation.</p>
                    <p class="mb-0" style="font-size:13px; color:red; margin-top:5px;">The whole file is validated
                        before saving. Imports create separate batches without updating existing attendance or payroll.
                        An identical file cannot be imported twice for the same year, month and depot.</p>
                </div>
            </div>
        </div>
    </section>
    @section('scripts')
        @include('attendance-consolidate.partials.loading')
        <script>
            $(function () {
                $('.consolidate-select').select2({ width: '100%' });
                $('#depot_id').on('change', function () { $('#sample_depot_id').val(this.value).trigger('change'); });
                if ($('#depot_id').val()) { $('#sample_depot_id').val($('#depot_id').val()).trigger('change'); }
            });
        </script>
    @endsection
</x-app-layout>