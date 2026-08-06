@section('title')
    Import Attendance CSV
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Import Attendance CSV</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('attendance-management.index') }}">Attendance Management</a></li>
                    <li class="breadcrumb-item active">Import CSV</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-5 mb-3">
                <div class="main-table-container h-100">
                    <form class="js-loading-form" method="POST" action="{{ route('attendance-management.import') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="o-f-inp mb-3">
                            <label for="csvFile">CSV File <span class="text-danger">*</span></label>
                            <input type="file" id="csvFile" name="csv_file" class="form-control shadow-none" accept=".csv,text/csv">
                            @error('csv_file')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($errors->get('csv_file'))
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->get('csv_file') as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('attendance-management.index') }}" class="btn btn-secondary">Back</a>
                            <button type="submit" class="btn btn-primary js-loading-submit">Import</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-7 mb-3">
                <div class="main-table-container h-100">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h5 class="mb-0">CSV Instructions</h5>
                        <a href="{{ route('attendance-management.sample-csv') }}" class="btn btn-outline-primary">Download Sample CSV</a>
                    </div>

                    <p class="mb-2">Create a CSV file with this exact header row:</p>
                    <code class="d-block mb-3">{{ implode(',', $headers) }}</code>

                    <div class="table-over mb-3">
                        <table class="align-middle mb-0 table tble-cstm" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Required</th>
                                    <th>Instruction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>attendance_date</td>
                                    <td>Yes</td>
                                    <td>Use DD-MM-YYYY, for example {{ now()->format('d-m-Y') }}.</td>
                                </tr>
                                <tr>
                                    <td>user_type</td>
                                    <td>Yes</td>
                                    <td>Allowed: {{ implode(', ', array_keys($roles)) }}.</td>
                                </tr>
                                <tr>
                                    <td>usercode / name</td>
                                    <td>One required</td>
                                    <td>Use an active user's code or exact name. If multiple active users have the same name, use usercode.</td>
                                </tr>
                                <tr>
                                    <td>status</td>
                                    <td>Yes</td>
                                    <td>Allowed: {{ implode(', ', array_keys($statuses)) }}.</td>
                                </tr>
                                <tr>
                                    <td>half_day_period</td>
                                    <td>For half_day</td>
                                    <td>Allowed: {{ implode(', ', array_keys($halfDayPeriods)) }}. Leave blank for present or absent.</td>
                                </tr>
                                <tr>
                                    <td>shift</td>
                                    <td>For Driver</td>
                                    <td>Allowed: {{ implode(', ', array_keys($shifts)) }}. Required for Driver and Housekeeping attendance.</td>
                                </tr>
                                <tr>
                                    <td>leave_code</td>
                                    <td>No</td>
                                    <td>Use only for absent or half_day rows. Leave must be Pending or Approved for the same user/date.</td>
                                </tr>
                                <tr>
                                    <td>remarks</td>
                                    <td>No</td>
                                    <td>Optional notes for the attendance row.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="mb-0">
                        Import updates existing attendance for the same date and user, and creates missing records.
                        The file is checked completely before any row is saved.
                    </p>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                $('.js-loading-form').on('submit', function () {
                    $(this).find('.js-loading-submit').prop('disabled', true).html('Loading...');
                });
            });
        </script>
    @endsection
</x-app-layout>
