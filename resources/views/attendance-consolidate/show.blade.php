@section('title', 'Attendance Consolidate Details')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Attendance Consolidate Details</h3>
        </div>
        <div class="main-table-container">
            <div class="gap-between mb-3">
                <p>
                    <strong>{{ $months[$import->month] }} {{ $import->year }} ·
                        {{ $import->depot_name }}</strong><br>Imported by {{ $import->imported_by_name }} on
                    {{ $import->imported_at->format('d M Y H:i') }}
                </p>
                <div class="btns-group-container">
                    <a class="fil-btn fil-btn-unset me-2" href="{{ route('attendance-consolidate.index') }}" style="    padding: 7px 15px !important;">Back</a><a
                        class="imp-btn" href="{{ route('attendance-consolidate.download', $import) }}">Download
                        Excel</a>
                </div>
            </div>

            @include('attendance-consolidate.partials.rows')
            {{ $rows->links() }}
        </div>
    </section>
</x-app-layout>