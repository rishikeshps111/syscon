@section('title')
    Housekeeping Profile
@endsection
<x-app-layout>
    <div class="page-title">
        <h3>Housekeeping Profile</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('staff-management.index') }}">Staff Management</a></li>
                <li class="breadcrumb-item active">View Details</li>
            </ol>
        </nav>
    </div>

    @php
        $profile = $record->housekeepingProfile;
        $money = fn($value) => filled($value) ? number_format((float) $value, 2) : '-';
        $date = fn($value) => $value ? $value->format('d-m-Y') : '-';
        $verification = fn($value) => \App\Models\HousekeepingProfile::VERIFICATION_STATUSES[$value] ?? '-';
        $alternatePhone = trim(($profile?->alternate_country_code ?? '') . ' ' . ($profile?->alternate_phone ?? ''));
        $emergencyPhone = trim(($profile?->emergency_country_code ?? '') . ' ' . ($profile?->emergency_contact_no ?? ''));
    @endphp

    <section class="section dashboard">
        <div class="row">
            <div class="col-lg-10 mb-3">
                <div class="main-table-container mt-3 bg-white">
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <div class="btn-flex justify-content-end">
                                <a href="{{ route('staff-management.index') }}" class="btn btn-secondary">Back</a>
                                <a href="{{ route('housekeeping-management.download-pdf', $record->id) }}"
                                    class="btn btn-primary">Download PDF</a>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-preview">
                                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo">
                                @if ($record->is_active)
                                    <span><i class="fa-solid fa-circle-check"></i> Active</span>
                                @else
                                    <span class="status-red">Inactive</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <div class="student-preview-profile">
                                <img src="{{ $record->avatar_url }}" alt="{{ $record->name }}">
                            </div>
                        </div>
                        <div class="col-lg-9 mb-3">
                            <div class="v-preview-widget s-preview-widget">
                                <h3>{{ $record->name }}</h3>
                                <ul>
                                    <li>Housekeeping Code : <span>{{ $record->code ?: '-' }}</span></li>
                                    <li>Email : <span>{{ $record->email ?: '-' }}</span></li>
                                    <li>Phone : <span>{{ $record->full_phone ?: '-' }}</span></li>
                                    <li>Alternate Phone : <span>{{ $alternatePhone ?: '-' }}</span></li>
                                    <li>Aadhaar Number : <span>{{ $profile?->aadhaar_number ?: '-' }}</span></li>
                                    <li>Role :
                                        <span>{{ $record->roles->pluck('name')->implode(', ') ?: 'Housekeeping' }}</span>
                                    </li>
                                    <li>Status :
                                        @if ($record->is_active)
                                            <span class="status-green">Active</span>
                                        @else
                                            <span class="status-red">Inactive</span>
                                        @endif
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Identity Details</h6>
                                <ul>
                                    <li><label>Address :</label> <span>{{ $profile?->address ?: '-' }}</span></li>
                                    <li><label>Country :</label> <span>{{ $profile?->country ?: '-' }}</span></li>
                                    <li><label>State :</label> <span>{{ $profile?->state?->name ?: '-' }}</span></li>
                                    <li><label>District :</label> <span>{{ $profile?->district?->name ?: '-' }}</span>
                                    </li>
                                    <li><label>City :</label> <span>{{ $profile?->location?->name ?: '-' }}</span></li>
                                    <li><label>Pincode :</label> <span>{{ $profile?->pincode ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Employment Details</h6>
                                <ul>
                                    <li><label>Employment Type :</label>
                                        <span>{{ $profile?->employment_type_label ?: '-' }}</span></li>
                                    <li><label>Joining Date :</label> <span>{{ $date($profile?->joining_date) }}</span>
                                    </li>
                                    <li><label>Salary :</label> <span>{{ $money($profile?->salary) }}</span></li>
                                    <li><label>Depot :</label> <span>{{ $profile?->depot?->name ?: '-' }}</span></li>
                                    <li><label>Branch :</label>
                                        <span>{{ $profile?->branchLocation?->name ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address mb-3" style="height: unset;">
                                <h6>Emergency & Medical</h6>
                                <ul>
                                    <li><label>Emergency Contact :</label>
                                        <span>{{ $profile?->emergency_contact_name ?: '-' }}</span></li>
                                    <li><label>Emergency Phone :</label> <span>{{ $emergencyPhone ?: '-' }}</span></li>
                                    <li><label>Medical Fitness Expiry :</label>
                                        <span>{{ $date($profile?->medical_fitness_expiry) }}</span></li>
                                </ul>
                            </div>
                            <div class="v-preview-widget s-preview-address" style="height: unset;">
                                <h6>Bank Details</h6>
                                <ul>
                                    <li><label>Account Number :</label>
                                        <span>{{ $profile?->account_number ?: '-' }}</span></li>
                                    <li><label>IFSC Code :</label> <span>{{ $profile?->ifsc_code ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Status & Verification</h6>
                                <ul>
                                    <li><label>Police Verification :</label>
                                        <span>{{ $verification($profile?->police_verification_status) }}</span></li>
                                    <li><label>Verification Status :</label>
                                        <span>{{ $verification($profile?->verification_status) }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-document-view">
                                <h6>Documents</h6>
                                <div class="row">
                                    @forelse ($record->housekeepingDocuments as $document)
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="v-doc-preview s-doc-preview">
                                                <p>{{ $document->documentType?->name ?: 'Document' }}</p>
                                                <img src="{{ asset('assets/img/file.avif') }}" alt="Document">
                                                <a href="#!" class="mb-3 view-document" data-bs-toggle="modal"
                                                    data-bs-target="#viewDoc"
                                                    data-preview="{{ route('housekeeping-documents.preview', $document->id) }}"
                                                    data-download="{{ route('housekeeping-documents.download', $document->id) }}">
                                                    View
                                                </a>
                                                <small>
                                                    {{ $document->is_verified ? 'Verified' : 'Not Verified' }}
                                                    @if ($document->expiry_date)
                                                        | Expiry {{ $document->expiry_date->format('d-m-Y') }}
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-lg-12">
                                            <p class="text-muted mb-0">No documents uploaded.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="viewDoc" tabindex="-1" aria-labelledby="viewDocLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body pb-0">
                    <iframe id="documentPreviewFrame" src="" width="100%" height="600px"></iframe>
                </div>
                <div class="lock-modal-footer">
                    <button type="button" data-bs-dismiss="modal">Close</button>
                    <a href="#!" id="documentDownloadLink">Download</a>
                </div>
            </div>
        </div>
    </div>

    @section('scripts')
        <script>
            $(function () {
                $(document).on('click', '.view-document', function () {
                    $('#documentPreviewFrame').attr('src', $(this).data('preview'));
                    $('#documentDownloadLink').attr('href', $(this).data('download'));
                });

                $('#viewDoc').on('hidden.bs.modal', function () {
                    $('#documentPreviewFrame').attr('src', '');
                });
            });
        </script>
    @endsection
</x-app-layout>