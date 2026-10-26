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
                <p>Upload an Excel file containing the fields listed below. Relationship columns use names, never database IDs. Names must exactly identify an existing record.</p>
                <p class="mb-0"><strong>Dates:</strong> {{ in_array($module, ['staff', 'drivers'], true) ? 'dd-mm-yyyy' : 'YYYY-MM-DD' }} &nbsp; <strong>Boolean values:</strong> yes/no, true/false, active/inactive, or 1/0.</p>
            </div>
            @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form class="{{ in_array($module, ['drivers', 'staff'], true) ? 'js-chunk-import' : 'js-loading-form' }}" method="POST" action="{{ route('bulk-import.store', $module) }}" enctype="multipart/form-data">
                @csrf
                @if (in_array($module, ['drivers', 'staff'], true))
                    <div class="alert alert-info mb-3">
                        Matching <strong>ref_code</strong> updates the existing {{ $module === 'drivers' ? 'driver' : 'employee' }}. New or blank ref codes create records.
                        Ref codes must not repeat in the file. Required fields must still be supplied.
                        Empty optional cells preserve existing values; a blank passcode keeps the current password.
                        Existing codes, roles and salary are preserved. Imports validate and save in batches of 25 rows.
                        Keep this page open until the import completes. If saving fails, earlier completed batches remain saved.
                    </div>
                @endif
                <div class="alert alert-info d-none js-import-progress">
                    <div class="js-import-status" role="status" aria-live="polite"></div>
                    <div class="progress mt-2">
                        <div class="progress-bar progress-bar-striped progress-bar-animated js-import-bar" role="progressbar" aria-label="Import progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0%"></div>
                    </div>
                    <div class="js-import-counts mt-2"></div>
                </div>
                <div class="alert alert-danger d-none js-import-errors" role="alert"></div>
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
                <div class="o-f-inp file-input mb-3"><label for="csv_file">Excel file</label><input class="form-control shadow-none @error('csv_file') is-invalid @enderror" type="file" id="csv_file" name="csv_file" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required></div>
                <div class="d-flex gap-2"><button class="btn-imp-sb  js-loading-submit" type="submit" data-loading-text="Loading..."><span class="js-submit-label">Import</span></button><a class="btn-imp-cancel" href="{{ route($config['index_route']) }}">Cancel</a></div>
            </form>
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 btns-group-container" style="justify-content:space-between !important;">
                    <h5 class="mb-0">Import Instructions</h5>
                    <a href="{{ route('bulk-import.sample', $module) }}" class="exp-btn">Download Sample Excel</a>
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
                <p style="font-size:13px; color:red; margin-top:10px; margin-bottom:0;">The complete file is validated before any record is saved. If validation fails, nothing is imported.</p>
            </div>
            @if (in_array($module, ['drivers', 'controllers', 'supervisors', 'staff', 'housekeeping'], true))<div class="alert alert-info mb-0">Salary structure is intentionally excluded. New profiles start with zero/default salary values. @if (in_array($module, ['drivers', 'staff'], true))Existing records retain their salary values.@endif</div>@endif
        </div>
    </section>
    @section('scripts')
        <script>
            $(function () {
                $('.js-chunk-import').on('submit', async function (event) {
                    event.preventDefault();
                    const form = this;
                    const button = $(form).find('button[type="submit"]');
                    if (button.prop('disabled')) return;
                    const progress = $(form).find('.js-import-progress');
                    const status = progress.find('.js-import-status');
                    const bar = progress.find('.js-import-bar');
                    const counts = progress.find('.js-import-counts');
                    const errors = $(form).find('.js-import-errors').empty().addClass('d-none');
                    const originalButton = button.html();
                    button.prop('disabled', true).attr('aria-busy', 'true').html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span><span>Importing&hellip;</span>');
                    $(form).attr('aria-busy', 'true');
                    progress.removeClass('d-none');
                    status.text('Reading file...');
                    counts.empty();
                    bar.addClass('progress-bar-animated').css('width', '0%').attr('aria-valuenow', 0).attr('aria-valuetext', 'Reading file');
                    let phase = 'prepare', offset = 0, token = null, saved = 0, created = 0, updated = 0;
                    try {
                        while (true) {
                            const body = phase === 'prepare' ? new FormData(form) : new FormData();
                            body.set('_token', form.querySelector('[name="_token"]').value);
                            body.set('import_step', phase);
                            if (token) { body.set('token', token); body.set('offset', offset); }
                            const response = await fetch(form.action, { method: 'POST', body, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                            const result = await response.json().catch(() => ({}));
                            if (!response.ok) {
                                const messages = result.errors ? Object.values(result.errors).flat() : [result.message || 'Import request failed.'];
                                throw new Error(messages.join('\n'));
                            }
                            if (!['validate', 'save', 'done'].includes(result.phase)) throw new Error('Unexpected import response.');
                            saved = result.saved;
                            created = result.created;
                            updated = result.updated;
                            const percent = result.total > 0 ? Math.round((result.validated + saved) / (2 * result.total) * 100) : 0;
                            const label = result.phase === 'done' ? result.message : `${result.phase === 'validate' ? 'Validating' : 'Saving'} ${result.processed} / ${result.total} rows`;
                            status.text(label);
                            bar.css('width', `${percent}%`).attr('aria-valuenow', percent).attr('aria-valuetext', label);
                            counts.text(`${created} rows created, ${updated} rows updated`);
                            if (result.phase === 'done') {
                                phase = 'done';
                                window.location.assign(result.redirect);
                                return;
                            }
                            token = result.token || token;
                            phase = result.phase;
                            offset = result.processed;
                        }
                    } catch (error) {
                        errors.removeClass('d-none').css('white-space', 'pre-wrap').text(error.message + (phase === 'save' || phase === 'done' ? `\n${saved} rows were confirmed saved: ${created} created, ${updated} updated. The last batch may also have completed if the connection was lost. Review records before uploading again.` : '\nNo records were saved.'));
                        status.text(phase === 'done' ? 'Import completed. Open the listing to view your records.' : 'Import stopped.');
                        bar.removeClass('progress-bar-animated');
                        $(form).removeAttr('aria-busy');
                        button.prop('disabled', false).removeAttr('aria-busy').html(originalButton);
                    }
                });
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
