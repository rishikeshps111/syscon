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
                    <div class="col-lg-12 mt-2 modal-btns-last">
                        <button type="button" id="resetSalaryFiles" class="modal-btn-1">Reset</button>
                        <button type="submit" class="modal-btn-2" data-loading-text="Getting Files...">Get
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
            
            .document-card {
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;

    height: 100% !important;
    min-height: 245px !important;

    padding: 18px !important;

    background: #ffffff !important;

    border: 1px solid #e2e8f0 !important;
    border-radius: 14px !important;

    overflow: hidden !important;

    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.05) !important;

    transition: all 0.25s ease !important;
}


/* Top accent */
.document-card::before {
    content: "" !important;

    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;

    height: 3px !important;

    background: #2563eb !important;

    opacity: 0 !important;

    transition: opacity 0.25s ease !important;
}


/* Decorative circle */
.document-card::after {
    content: "" !important;

    position: absolute !important;

    width: 100px !important;
    height: 100px !important;

    top: -45px !important;
    right: -35px !important;

    background: #eff6ff !important;

    border-radius: 50% !important;

    pointer-events: none !important;
}


/* Hover */
.document-card:hover {
    transform: translateY(-4px) !important;

    border-color: #cbd5e1 !important;

    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.10) !important;
}

.document-card:hover::before {
    opacity: 1 !important;
}


/* =========================================
   Document Icon
   ========================================= */

.document-card .document-icon {
    position: relative !important;
    z-index: 2 !important;

    width: 48px !important;
    height: 48px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    margin-bottom: 13px !important;

    background: #fef2f2 !important;

    border: 1px solid #fee2e2 !important;
    border-radius: 10px !important;

    font-size: 22px !important;

    transition: all 0.2s ease !important;
}


/* PDF icon */
.document-card .document-icon[style*="#dc2626"] {
    color: #dc2626 !important;
}

.document-card:hover .document-icon {
    transform: scale(1.05) !important;
}


/* =========================================
   Document Name
   ========================================= */

.document-card .document-name {
    position: relative !important;
    z-index: 2 !important;

    margin-bottom: 8px !important;

    color: #1e293b !important;

    font-size: 14px !important;
    font-weight: 700 !important;
    line-height: 1.45 !important;

    word-break: break-word !important;
}


/* =========================================
   Folder
   ========================================= */

.document-card .document-folder {
    position: relative !important;
    z-index: 2 !important;

    display: flex !important;
    align-items: center !important;

    margin-bottom: 10px !important;

    color: #64748b !important;

    font-size: 11px !important;
    font-weight: 500 !important;
}

.document-card .document-folder::before {
    content: "\f07b" !important;

    margin-right: 6px !important;

    color: #f59e0b !important;

    font-family: "Font Awesome 6 Free" !important;
    font-size: 11px !important;
    font-weight: 900 !important;
}


/* =========================================
   Meta Information
   ========================================= */

.document-card .document-meta-text {
    position: relative !important;
    z-index: 2 !important;

    margin-bottom: 5px !important;

    color: #64748b !important;

    font-size: 11px !important;
    font-weight: 500 !important;

    line-height: 1.5 !important;
}


/* Approved text */
.document-card .document-meta-text:last-of-type {
    color: #15803d !important;
}


/* =========================================
   Footer
   ========================================= */

.document-card .document-card-footer {
    position: relative !important;
    z-index: 3 !important;

    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;

    gap: 10px !important;

    margin-top: auto !important;
    padding-top: 14px !important;

    border-top: 1px solid #f1f5f9 !important;
}


/* =========================================
   Count Badge
   ========================================= */

.document-card .document-card-footer .badge {
    min-width: 28px !important;
    height: 26px !important;

    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;

    padding: 0 8px !important;

    color: #475569 !important;
    background: #f1f5f9 !important;

    border: 1px solid #e2e8f0 !important;
    border-radius: 7px !important;

    font-size: 11px !important;
    font-weight: 700 !important;
}


/* =========================================
   Download Buttons
   ========================================= */

.document-card .document-download {
    width: 34px !important;
    height: 34px !important;

    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;

    color: #dc2626 !important;
    background: #fef2f2 !important;

    border: 1px solid #fee2e2 !important;
    border-radius: 8px !important;

    text-decoration: none !important;

    font-size: 14px !important;

    transition: all 0.2s ease !important;
}


/* PDF Hover */
.document-card .document-download:hover {
    color: #ffffff !important;
    background: #dc2626 !important;
    border-color: #dc2626 !important;

    transform: translateY(-2px) !important;

    box-shadow: 0 5px 12px rgba(220, 38, 38, 0.18) !important;
}


/* =========================================
   Excel Button
   ========================================= */

.document-card .document-download.document-download-excel {
    color: #16a34a !important;
    background: #f0fdf4 !important;

    border-color: #dcfce7 !important;
}


.document-card .document-download.document-download-excel:hover {
    color: #ffffff !important;
    background: #16a34a !important;
    border-color: #16a34a !important;

    box-shadow: 0 5px 12px rgba(22, 163, 74, 0.18) !important;
}
        </style>
    @endsection
</x-app-layout>
