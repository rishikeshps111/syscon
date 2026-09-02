@section('title') Import {{ $config['label'] }} @endsection
<style>
    .alert {
        font-size:14px;
    }
</style>
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Import {{ $config['label'] }}</h3>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item"><a href="{{ route($config['index_route']) }}">{{ $config['label'] }}</a></li><li class="breadcrumb-item active">Import</li></ol></nav>
        </div>
        <div class="main-table-container">
            <div class="mb-4 import-desc-top">
                <p>Upload {{ $module === 'staff' ? 'an Excel or CSV file' : 'a CSV' }} containing the fields listed below. Relationship columns use names, never database IDs. Names must exactly identify an existing record.</p>
                <p class="mb-0"><strong>Dates:</strong> {{ in_array($module, ['staff', 'drivers'], true) ? 'dd-mm-yyyy' : 'YYYY-MM-DD' }} &nbsp; <strong>Boolean values:</strong> yes/no, true/false, active/inactive, or 1/0.</p>
            </div>
            @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form class="js-loading-form" method="POST" action="{{ route('bulk-import.store', $module) }}" enctype="multipart/form-data">
                @csrf
                @if ($module === 'staff')
                    <div class="alert alert-info mb-3">
                        Imported Staff accounts use <strong>Syscon@123</strong> as the default login password. Imported Controller and Supervisor accounts use <strong>111111</strong> as the default passcode.
                    </div>
                @endif
                @if (in_array($module, ['staff', 'drivers'], true))
                    <div class="alert alert-warning mb-3">
                        <strong>Salary template warning:</strong>
                        @if ($module === 'staff')
                            If no salary template is assigned for an imported employee's role and designation, the salary values will remain zero until a template is assigned.
                        @else
                            If no salary template is assigned for the Driver role, the imported driver's salary values will remain zero until a template is assigned.
                        @endif
                    </div>
                @endif
                <div class="o-f-inp file-input mb-3"><label for="csv_file">{{ $module === 'staff' ? 'Excel or CSV file' : 'CSV file' }}</label><input class="form-control shadow-none @error('csv_file') is-invalid @enderror" type="file" id="csv_file" name="csv_file" accept="{{ $module === 'staff' ? '.xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv' : '.csv,text/csv' }}" required></div>
                <div class="d-flex gap-2"><button class="btn-imp-sb  js-loading-submit" type="submit" data-loading-text="Loading..."><span class="js-submit-label">Import</span></button><a class="btn-imp-cancel" href="{{ route($config['index_route']) }}">Cancel</a></div>
            </form>
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 btns-group-container" style="justify-content:space-between !important;">
                    <h5 class="mb-0">{{ $module === 'staff' ? 'Import Instructions' : 'CSV Instructions' }}</h5>
                    <a href="{{ route('bulk-import.sample', $module) }}" class="exp-btn">Download Sample {{ $module === 'staff' ? 'Excel' : 'CSV' }}</a>
                </div>
              <div class="instrction-odr">
                    <p class="mb-2">Use these columns in this exact order:</p>
                <code class="d-block mb-3">{{ implode(', ', $config['sample_headers'] ?? $config['headers']) }}</code>
              </div>
                <div class="table-over mb-3">
                    <table class="align-middle mb-0 table tble-cstm" style="width:100%;">
                        <thead><tr><th>Column</th><th>Required</th><th>Instruction</th></tr></thead>
                        <tbody>
                            @foreach ($config['instructions'] as $instruction)
                                <tr>
                                    <td><code>{{ $instruction['column'] }}</code></td>
                                    <td>{{ $instruction['required'] }}</td>
                                    <td>{{ $instruction['instruction'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p style="font-size:13px; color:red; margin-top:10px; margin-bottom:0;">The complete file is validated before any record is saved. If any row has an error, nothing is imported.</p>
            </div>
            @if (in_array($module, ['drivers', 'controllers', 'supervisors', 'staff', 'housekeeping'], true))<div class="alert alert-info mb-0">Salary structure is intentionally excluded. Imported profiles start with the existing zero/default salary values.</div>@endif
        </div>
    </section>
    @section('scripts')
        <script>
            $(function () {
                $('.js-loading-form').on('submit', function () {
                    var button = $(this).find('.js-loading-submit');

                    if (button.prop('disabled')) {
                        return;
                    }

                    button.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' +
                        (button.data('loading-text') || 'Loading...')
                    );
                });
            });
        </script>
    @endsection
</x-app-layout>
