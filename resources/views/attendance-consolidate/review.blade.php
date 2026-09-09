@section('title', 'Review Attendance Consolidate')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Review Attendance Consolidate</h3>
        </div>
        <div class="main-table-container">
            <p><strong>{{ $months[$draft['month']] }} {{ $draft['year'] }} ·
                    {{ $depot->name }}</strong><br>{{ $draft['original_filename'] }} · {{ count($rows) }} employees</p>
            <p>Review the employee matches before importing. This preview expires after 30 minutes.</p>
            @if($notice)
            <div class="alert alert-warning">{{ $notice }}</div>@endif
            @include('attendance-consolidate.partials.rows', ['showReview' => true])
            <form method="POST" action="{{ route('attendance-consolidate.import') }}" class="js-loading-form">
                @csrf
                <input type="hidden" name="token" value="{{ $draft['token'] }}">
                @if($hasWarnings)
                    <div class="form-check my-3"><input class="form-check-input" type="checkbox"
                            name="acknowledge_mismatches" id="acknowledge_mismatches" value="1" required><label
                            class="form-check-label" for="acknowledge_mismatches">I reviewed the name/depot differences and
                confirm these records belong to the selected depot and period.</label></div>@endif
                <button class="modal-btn-2 js-loading-submit" type="submit">Import CSV / Excel</button>
                <p class="import-loading-message mt-3" role="status" aria-live="polite" hidden>Importing attendance.
                    Please keep this page open…</p>
                <a class="btn btn-outline-secondary" href="{{ route('attendance-consolidate.import.form') }}">Choose
                    another file</a>
            </form>
        </div>
    </section>
    @section('scripts')
        @include('attendance-consolidate.partials.loading')
    @endsection
</x-app-layout>