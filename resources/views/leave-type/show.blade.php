@section('title')
    Leave Type Details
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Leave Type Details</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item"><a href="{{ route('leave-types.index') }}">Leave Types</a></li>
                    <li class="breadcrumb-item active">View</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="row g-3">
                        @php
                            $details = [
                                'Code' => $leaveType->code,
                                'Leave Name' => $leaveType->leave_name,
                                'Short Name' => $leaveType->short_name,
                                'Category' => $leaveType->leave_category,
                                'Max/Year' => $leaveType->max_leaves_per_year ?? 'Unlimited',
                                'Carry Forward' => $leaveType->carry_forward_allowed ? 'Yes' : 'No',
                                'Max Carry Forward Limit' => $leaveType->max_carry_forward_limit ?? '-',
                                'Encashment' => $leaveType->encashment_allowed ? 'Yes' : 'No',
                                'Applicable For' => \App\Models\LeaveType::APPLICABLE_FOR[$leaveType->applicable_for] ?? '-',
                                'Gender Specific' => \App\Models\LeaveType::GENDERS[$leaveType->gender_specific] ?? '-',
                                'Minimum Service Required' => $leaveType->minimum_service_required ?? '-',
                                'Minimum Leave Days' => $leaveType->minimum_leave_days,
                                'Maximum Leave Days Per Request' => $leaveType->maximum_leave_days_per_request ?? '-',
                                'Advance Notice Days' => $leaveType->advance_notice_days ?? '-',
                                'Allow Half Day' => $leaveType->allow_half_day ? 'Yes' : 'No',
                                'Requires Approval' => $leaveType->requires_approval ? 'Yes' : 'No',
                                'Status' => $leaveType->is_active ? 'Active' : 'Inactive',
                                'Description' => $leaveType->description ?? '-',
                                'Remarks' => $leaveType->remarks ?? '-',
                            ];
                        @endphp
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
                        <a href="{{ route('leave-types.index') }}" class="btn btn-secondary me-2">Back</a>
                        @can('leave-types.edit')
                            <a href="{{ route('leave-types.edit', $leaveType->id) }}" class="btn btn-primary">Edit</a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
