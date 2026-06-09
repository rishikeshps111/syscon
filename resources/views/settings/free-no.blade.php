@section('title')
    Free No Settings
@endsection

<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Toll Free No Settings</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item active">Toll Free No Settings</li>
                </ol>
            </nav>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form class="js-loading-form" method="POST" action="{{ route('free-no-settings.update') }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-12 mb-3">
                    <div class="main-table-container">
                        <div class="row">
                            <div class="col-lg-12 mb-0">
                                <h5 class="title-w-sec">Toll Free No</h5>
                            </div>

                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="free_no">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="free_no" id="free_no" maxlength="10" inputmode="numeric"
                                    pattern="[0-9]{10}"
                                    class="form-control shadow-none @error('free_no') is-invalid @enderror"
                                    value="{{ old('free_no', $setting->free_no) }}">
                                @error('free_no') <span class="text-danger">{{ $message }}</span> @enderror
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

                    if (!submitButton || submitButton.disabled) {
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