@section('title')
    Holiday Details
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Holiday Details</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item"><a href="{{ route('holidays.index') }}">Holidays</a></li>
                    <li class="breadcrumb-item active">View</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    @php
                        $applicableFor = $holiday->applicable_for_label;
                        if ($holiday->applicable_for === 'specific_departments') {
                            $applicableFor = \App\Models\Department::whereIn('id', $holiday->department_ids ?? [])->pluck('name')->implode(', ');
                        }
                        if ($holiday->applicable_for === 'specific_designations') {
                            $applicableFor = \App\Models\Designation::whereIn('id', $holiday->designation_ids ?? [])->pluck('name')->implode(', ');
                        }

                        $details = [
                            'Code' => $holiday->code,
                            'Holiday Name' => $holiday->holiday_name,
                            'Holiday Date' => $holiday->holiday_date->format('d M Y'),
                            'Holiday Type' => $holiday->holiday_type_label,
                            'Location' => $holiday->applicable_location_label,
                            'Applicable For' => $applicableFor ?: '-',
                            'Holiday Duration' => $holiday->holiday_duration_label,
                            'Recurring Yearly' => $holiday->is_recurring_yearly ? 'Yes' : 'No',
                            'Status' => $holiday->is_active ? 'Active' : 'Inactive',
                            'Description' => $holiday->description ?? '-',
                            'Remarks' => $holiday->remarks ?? '-',
                        ];
                    @endphp
                    <div class="row g-3">
                        @foreach ($details as $label => $value)
                            <div class="col-lg-4 col-md-6">
                                <div class="border rounded p-2 h-100">
                                    <div class="text-muted small">{{ $label }}</div>
                                    <div class="fw-semibold">{{ $value }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="col-lg-12 mt-3 text-center">
                        <a href="{{ route('holidays.index') }}" class="btn btn-secondary me-2">Back</a>
                        @can('holidays.edit')
                            <a href="{{ route('holidays.edit', $holiday->id) }}" class="btn btn-primary">Edit</a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
