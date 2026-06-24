@section('title')
    Salary Report
@endsection

<x-app-layout>
    @php
        $hasReport = $report && $report['processing'];
        $items = $report['items'] ?? collect();
        $componentNames = $report['componentNames'] ?? collect();
        $money = fn ($value) => number_format((float) $value, 2);
    @endphp

    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Salary Report</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Payroll</li>
                    <li class="breadcrumb-item active">Salary Report</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <form method="GET" action="{{ route('salary-reports.index') }}" class="js-loading-form">
                <input type="hidden" name="generate" value="1">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="year">Year <span class="text-danger">*</span></label>
                        <select name="year" id="year" class="form-select shadow-none" required>
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected((int) $filters['year'] === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="month">Month <span class="text-danger">*</span></label>
                        <select name="month" id="month" class="form-select shadow-none" required>
                            <option value="">--- Select ---</option>
                            @foreach ($months as $value => $label)
                                <option value="{{ $value }}" @selected((int) $filters['month'] === (int) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="depot_id">Depo <span class="text-danger">*</span></label>
                        <select name="depot_id" id="depot_id" class="form-select shadow-none" required>
                            <option value="">--- Select ---</option>
                            @foreach ($depots as $depot)
                                <option value="{{ $depot->id }}" @selected((int) $filters['depot_id'] === (int) $depot->id)>{{ $depot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="role_id">Role <span class="text-danger">*</span></label>
                        <select name="role_id" id="role_id" class="form-select shadow-none" required>
                            <option value="">--- Select ---</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((int) $filters['role_id'] === (int) $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-12 mt-2 d-flex gap-2 justify-content-end">
                        <a href="{{ route('salary-reports.index') }}" class="btn btn-secondary js-loading-link"
                            data-loading-text="Resetting...">Reset</a>
                        <button type="submit" class="btn btn-primary" data-loading-text="Generating...">Generate
                            Report</button>
                    </div>
                </div>
            </form>
        </div>

        @if ($report)
            <div class="main-table-container">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">Full Salary Sheet</h5>
                        <div class="text-muted">
                            <strong>Year:</strong> {{ $report['year'] }} |
                            <strong>Month:</strong> {{ $report['monthName'] }} |
                            <strong>Depo:</strong> {{ $report['depot']?->name ?? '-' }} |
                            <strong>Role:</strong> {{ $report['role']?->name ?? '-' }}
                        </div>
                    </div>
                    @if ($hasReport && $items->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-success js-loading-link" href="{{ route('salary-reports.export', $filters) }}"
                                data-loading-text="Downloading...">Download Excel</a>
                            <a class="btn btn-danger js-loading-link" href="{{ route('salary-reports.pdf', $filters) }}"
                                data-loading-text="Downloading...">Download PDF</a>
                            <form method="POST" action="{{ route('salary-reports.send-mail') }}"
                                class="d-inline js-loading-form">
                                @csrf
                                @foreach ($filters as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach
                                <button type="submit" class="btn btn-primary" data-loading-text="Sending...">Send PDF
                                    Mail</button>
                            </form>
                        </div>
                    @endif
                </div>

                @if (! $hasReport)
                    <div class="alert alert-warning mb-0">
                        No completed salary processing found for the selected Year, Month, Depo and Role.
                    </div>
                @elseif ($items->isEmpty())
                    <div class="alert alert-warning mb-0">
                        Salary processing exists, but no completed user salary records are available.
                    </div>
                @else
                    <div class="table-over salary-report-scroll">
                        <table class="align-middle mb-0 table table-striped tble-cstm bg-transparent salary-report-table">
                            <thead>
                                <tr>
                                    <th class="text-center">SL No</th>
                                    <th class="text-center">User Code</th>
                                    <th class="text-center">User Name</th>
                                    <th class="text-center">Aadhaar No</th>
                                    <th class="text-center">Total Leave Taken</th>
                                    <th class="text-center">Unauthorized Leaves</th>
                                    <th class="text-center">Total Shifts Completed</th>
                                    <th class="text-center">Total Working Days</th>
                                    @foreach ($componentNames as $componentName)
                                        <th class="text-center">{{ $componentName }}</th>
                                    @endforeach
                                    <th class="text-center">Gross Salary</th>
                                    <th class="text-center">Incentive</th>
                                    <th class="text-center">Deduction</th>
                                    <th class="text-center">LOP</th>
                                    <th class="text-center">Net Salary</th>
                                    <th class="text-center">Payment Method</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Approved By</th>
                                    <th class="text-center">Approved At</th>
                                    <th class="text-center">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    @php
                                        $components = collect($item->salary_split ?: [])->where('type', 'earning')->keyBy('name');
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">{{ $item->user?->code ?: '-' }}</td>
                                        <td>{{ $item->user?->name ?: '-' }}</td>
                                        <td class="text-center">{{ $item->aadhaar_no ?: '-' }}</td>
                                        <td class="text-center">{{ $item->total_leave_taken }}</td>
                                        <td class="text-center">{{ $item->unauthorized_leaves }}</td>
                                        <td class="text-center">{{ $item->total_shifts_completed }}</td>
                                        <td class="text-center">{{ $item->total_working_days }}</td>
                                        @foreach ($componentNames as $componentName)
                                            <td class="text-end">{{ $money($components->get($componentName)['amount'] ?? 0) }}</td>
                                        @endforeach
                                        <td class="text-end">{{ $money($item->basic_salary) }}</td>
                                        <td class="text-end">{{ $money($item->incentive) }}</td>
                                        <td class="text-end">{{ $money($item->deduction) }}</td>
                                        <td class="text-end">{{ $money($item->lop) }}</td>
                                        <td class="text-end">{{ $money($item->net_salary) }}</td>
                                        <td class="text-center">{{ $report['processing']->payment_method ?: '-' }}</td>
                                        <td class="text-center">{{ $report['processing']->status }}</td>
                                        <td class="text-center">{{ $report['processing']->approver?->name ?: '-' }}</td>
                                        <td class="text-center">{{ $report['processing']->approved_at?->format('d-m-Y h:i A') ?: '-' }}</td>
                                        <td>{{ $report['processing']->remarks ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted mt-2">PDF mail recipient: {{ $mailTo }}</div>
                @endif
            </div>
        @endif
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

                $('.js-loading-form').on('submit', function () {
                    var button = $(this).find('button[type="submit"]').first();

                    if (button.length) {
                        setLoading(button);
                    }
                });

                $('.js-loading-link').on('click', function () {
                    var link = this;
                    setLoading(link);

                    setTimeout(function () {
                        var $link = $(link);
                        $link.removeClass('disabled').removeAttr('aria-disabled');
                        $link.html($link.data('original-html'));
                    }, 3500);
                });
            });
        </script>
    @endsection

    @section('styles')
        <style>
            .salary-report-scroll {
                overflow-x: auto;
                width: 100%;
                -webkit-overflow-scrolling: touch;
            }

            .salary-report-table {
                min-width: 1500px;
                font-size: 12px;
            }

            .salary-report-table th,
            .salary-report-table td {
                vertical-align: middle;
                white-space: nowrap;
            }
        </style>
    @endsection
</x-app-layout>
