@section('title')
    Financial Year Settings
@endsection

<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Financial Year Settings</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item active">Financial Year Settings</li>
                </ol>
            </nav>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form class="js-loading-form" method="POST" action="{{ route('financial-year-settings.update') }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-12 mb-3">
                    <div class="main-table-container">
                        <div class="row">
                            <div class="col-lg-12 mb-0">
                                <h5 class="title-w-sec">Financial Year Period</h5>
                            </div>

                            <div class="col-lg-3 o-f-inp mb-3">
                                <label for="financial_year">From Year <span class="text-danger">*</span></label>
                                <select name="financial_year" id="financial_year"
                                    class="form-select shadow-none @error('financial_year') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}" {{ old('financial_year', $setting->financial_year ?? now()->year) == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('financial_year') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-3 o-f-inp mb-3">
                                <label for="financial_year_from_month">From Month <span class="text-danger">*</span></label>
                                <select name="financial_year_from_month" id="financial_year_from_month"
                                    class="form-select shadow-none @error('financial_year_from_month') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                    @foreach ($months as $monthNumber => $monthName)
                                        <option value="{{ $monthNumber }}" {{ old('financial_year_from_month', $setting->financial_year_from_month ?? 4) == $monthNumber ? 'selected' : '' }}>
                                            {{ $monthName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('financial_year_from_month') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-3 o-f-inp mb-3">
                                <label for="financial_year_to_year">To Year <span class="text-danger">*</span></label>
                                <select name="financial_year_to_year" id="financial_year_to_year"
                                    class="form-select shadow-none @error('financial_year_to_year') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}" {{ old('financial_year_to_year', $setting->financial_year_to_year ?? now()->addYear()->year) == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('financial_year_to_year') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-3 o-f-inp mb-3">
                                <label for="financial_year_to_month">To Month <span class="text-danger">*</span></label>
                                <select name="financial_year_to_month" id="financial_year_to_month"
                                    class="form-select shadow-none @error('financial_year_to_month') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                    @foreach ($months as $monthNumber => $monthName)
                                        <option value="{{ $monthNumber }}" {{ old('financial_year_to_month', $setting->financial_year_to_month ?? 3) == $monthNumber ? 'selected' : '' }}>
                                            {{ $monthName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('financial_year_to_month') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-12 d-flex justify-content-center align-items-center">
                                <div class="btn-flex">
                                    @can('settings.edit')
                                        <button type="submit" class="submit-btn js-loading-submit"
                                            data-loading-text="Loading...">Update</button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>

    @section('scripts')
        <script>
            document.querySelectorAll('.js-loading-form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    var submitButton = form.querySelector('.js-loading-submit');

                    if (! submitButton || submitButton.disabled) {
                        return;
                    }

                    submitButton.dataset.originalText = submitButton.innerHTML;
                    submitButton.disabled = true;
                    submitButton.innerHTML = submitButton.dataset.loadingText || 'Loading...';
                });
            });
        </script>
    @endsection
</x-app-layout>
