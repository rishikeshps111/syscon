@section('title')
    Salary Files
@endsection

<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Salary Files</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Payroll</li>
                    <li class="breadcrumb-item active">Salary Files</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <form id="salaryFilesForm" method="GET" action="{{ route('salary-files.index') }}">
                <input type="hidden" name="get_files" value="1">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="year">Year</label>
                        <select name="year" id="year" class="form-select shadow-none">
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected((int) $filters['year'] === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="month">Month</label>
                        <select name="month" id="month" class="form-select shadow-none">
                            <option value="">All Months</option>
                            @foreach ($months as $value => $label)
                                <option value="{{ $value }}" @selected((int) $filters['month'] === (int) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="depot_id">Depo</label>
                        <select name="depot_id" id="depot_id" class="form-select shadow-none">
                            <option value="">All Depos</option>
                            @foreach ($depots as $depot)
                                <option value="{{ $depot->id }}" @selected((int) $filters['depot_id'] === (int) $depot->id)>{{ $depot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="role_id">Role</label>
                        <select name="role_id" id="role_id" class="form-select shadow-none">
                            <option value="">All Roles</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((int) $filters['role_id'] === (int) $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-12 mt-2 d-flex gap-2 justify-content-end">
                        <button type="button" id="resetSalaryFiles" class="btn btn-secondary">Reset</button>
                        <button type="submit" class="btn btn-primary" data-loading-text="Getting Files...">Get
                            Files</button>
                    </div>
                </div>
            </form>
        </div>

        <div id="salaryFilesResults">
            @if (request()->boolean('get_files'))
                @include('salary-files.partials.cards', ['files' => $files])
            @endif
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                function setLoading(element) {
                    var $element = $(element);
                    var loadingText = $element.data('loading-text') || 'Loading...';

                    if (!$element.data('original-html')) {
                        $element.data('original-html', $element.html());
                    }

                    $element.addClass('disabled').attr('aria-disabled', 'true');

                    if ($element.is('button')) {
                        $element.prop('disabled', true);
                    }

                    $element.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + loadingText);
                }

                function resetLoading(element) {
                    var $element = $(element);

                    $element.removeClass('disabled').removeAttr('aria-disabled');

                    if ($element.is('button')) {
                        $element.prop('disabled', false);
                    }

                    if ($element.data('original-html')) {
                        $element.html($element.data('original-html'));
                    }
                }

                $('#salaryFilesForm').on('submit', function (event) {
                    event.preventDefault();

                    var form = this;
                    var button = $(form).find('button[type="submit"]').first();
                    setLoading(button);
                    $('#salaryFilesResults').html('<div class="main-table-container"><div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Getting files...</div></div>');

                    $.get($(form).attr('action'), $(form).serialize())
                        .done(function (response) {
                            $('#salaryFilesResults').html(response.html);
                        })
                        .fail(function (xhr) {
                            $('#salaryFilesResults').html('<div class="main-table-container"><div class="alert alert-danger mb-0">Unable to get salary files.</div></div>');
                            showToast('error', xhr.responseJSON?.message || 'Unable to get salary files.');
                        })
                        .always(function () {
                            resetLoading(button);
                        });
                });

                $('#resetSalaryFiles').on('click', function () {
                    $('#month, #depot_id, #role_id').val('');
                    $('#year').val('{{ date('Y') }}');
                    $('#salaryFilesResults').empty();
                });
            });
        </script>
    @endsection

    @section('styles')
        <style>
            .document-card {
                background: #fff;
                border: 1px solid #e6e9ef;
                border-radius: 8px;
                box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
                display: flex;
                flex-direction: column;
                gap: 8px;
                padding: 18px;
            }

            .document-icon {
                font-size: 34px;
                line-height: 1;
            }

            .document-name {
                color: #111827;
                font-size: 14px;
                font-weight: 700;
                line-height: 1.35;
                min-height: 38px;
                overflow-wrap: anywhere;
            }

            .document-folder,
            .document-meta-text {
                color: #6b7280;
                font-size: 12px;
                line-height: 1.35;
            }

            .document-card-footer {
                align-items: center;
                display: flex;
                justify-content: space-between;
                margin-top: auto;
                padding-top: 8px;
            }

            .document-download {
                align-items: center;
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 50%;
                color: #dc2626;
                display: inline-flex;
                height: 34px;
                justify-content: center;
                text-decoration: none;
                width: 34px;
            }

            .document-download-excel {
                color: #15803d;
            }

            .document-download.disabled {
                color: #9ca3af;
                cursor: not-allowed;
                opacity: 0.65;
            }
        </style>
    @endsection
</x-app-layout>
