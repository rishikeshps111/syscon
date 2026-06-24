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
            <form method="GET" action="{{ route('salary-files.index') }}" class="js-loading-form">
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
                        <a href="{{ route('salary-files.index') }}" class="btn btn-secondary js-loading-link"
                            data-loading-text="Resetting...">Reset</a>
                        <button type="submit" class="btn btn-primary" data-loading-text="Getting Files...">Get
                            Files</button>
                    </div>
                </div>
            </form>
        </div>

        @if (request()->boolean('get_files'))
            <div class="row">
                @forelse ($files as $file)
                    <div class="col-xl-4 col-md-6 mb-3">
                        <div class="salary-file-card h-100">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <h5 class="mb-0">{{ $file->role?->name ?? '-' }}</h5>
                                <span class="{{ $file->status === 'Approved' ? 'status-green' : 'status-yellow' }}">{{ $file->status }}</span>
                            </div>
                            <div class="text-muted mb-3">
                                <div><strong>Year:</strong> {{ $file->year }}</div>
                                <div><strong>Month:</strong> {{ \Carbon\Carbon::create(null, $file->month, 1)->format('F') }}</div>
                                <div><strong>Depo:</strong> {{ $file->depot?->name ?? '-' }}</div>
                                <div><strong>Users:</strong> {{ $file->items_count }}</div>
                                <div><strong>Approved By:</strong> {{ $file->approver?->name ?? '-' }}</div>
                                <div><strong>Approved At:</strong> {{ $file->approved_at?->format('d-m-Y h:i A') ?? '-' }}</div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('salary-files.pdf', $file->id) }}"
                                    class="btn btn-danger flex-fill js-loading-link" data-loading-text="Downloading...">Download
                                    PDF</a>
                                <a href="{{ route('salary-files.excel', $file->id) }}"
                                    class="btn btn-success flex-fill js-loading-link" data-loading-text="Downloading...">Download
                                    Excel</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="main-table-container">
                            <div class="alert alert-warning mb-0">No completed salary files found for the selected filters.</div>
                        </div>
                    </div>
                @endforelse
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
            .salary-file-card {
                background: #fff;
                border: 1px solid #e6e9ef;
                border-radius: 8px;
                padding: 18px;
                box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
            }
        </style>
    @endsection
</x-app-layout>
