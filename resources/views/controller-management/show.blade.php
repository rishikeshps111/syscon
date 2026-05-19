@section('title')
    Controller Profile
@endsection
<x-app-layout>
    <div class="page-title">
        <h3>Controller Profile</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('controller-management.index') }}">Controller Management</a></li>
                <li class="breadcrumb-item active">View Details</li>
            </ol>
        </nav>
    </div>

    @php
        $profile = $record->controllerProfile;
        $money = fn ($value) => filled($value) ? number_format((float) $value, 2) : '-';
        $date = fn ($value) => $value ? $value->format('d-m-Y') : '-';
    @endphp

    <section class="section dashboard">
        <div class="row">
            <div class="col-lg-10 mb-3">
                <div class="main-table-container mt-3 bg-white">
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <div class="btn-flex justify-content-end">
                                <a href="{{ route('controller-management.index') }}" class="btn btn-secondary">Back</a>
                                <a href="{{ route('controller-management.download-pdf', $record->id) }}" class="btn btn-primary">Download PDF</a>
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
                                    <li>Controller Code : <span>{{ $record->code ?: '-' }}</span></li>
                                    <li>Email : <span>{{ $record->email ?: '-' }}</span></li>
                                    <li>Phone : <span>{{ $record->full_phone ?: '-' }}</span></li>
                                    <li>Date of Birth : <span>{{ $date($profile?->date_of_birth) }}</span></li>
                                    <li>Father's Name : <span>{{ $profile?->father_name ?: '-' }}</span></li>
                                    <li>Role : <span>{{ $record->roles->pluck('name')->implode(', ') ?: 'Controller' }}</span></li>
                                    <li>Depot : <span>{{ $profile?->depot?->name ?: '-' }}</span></li>
                                    <li>DOJ : <span>{{ $date($profile?->date_of_joining) }}</span></li>
                                    <li>Aadhaar Number : <span>{{ $profile?->aadhaar_number ?: '-' }}</span></li>
                                    <li>PAN Number : <span>{{ $profile?->pan_number ?: '-' }}</span></li>
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
                                <h6>Location Details</h6>
                                <ul>
                                    <li><label>Country :</label> <span>{{ $profile?->country ?: '-' }}</span></li>
                                    <li><label>State :</label> <span>{{ $profile?->state?->name ?: '-' }}</span></li>
                                    <li><label>District :</label> <span>{{ $profile?->district?->name ?: '-' }}</span></li>
                                    <li><label>Location :</label> <span>{{ $profile?->location?->name ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Employment Details</h6>
                                <ul>
                                    <li><label>Employment Type :</label> <span>{{ $profile?->employment_type_label ?: '-' }}</span></li>
                                    <li><label>Joining Date :</label> <span>{{ $date($profile?->date_of_joining) }}</span></li>
                                    <li><label>UAN :</label> <span>{{ $profile?->uan ?: '-' }}</span></li>
                                    <li><label>ESIC / WC :</label> <span>{{ $profile?->esic_wc ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Salary Structure</h6>
                                <ul>
                                    <li><label>Basic :</label> <span>{{ $money($profile?->basic) }}</span></li>
                                    <li><label>VDA :</label> <span>{{ $money($profile?->vda) }}</span></li>
                                    <li><label>Basic + VDA :</label> <span>{{ $money($profile?->basic_vda) }}</span></li>
                                    <li><label>HRA :</label> <span>{{ $money($profile?->hra) }}</span></li>
                                    <li><label>Special Allowance :</label> <span>{{ $money($profile?->special_allowance) }}</span></li>
                                    <li><label>Conveyance Allowance / Incentive :</label> <span>{{ $money($profile?->conveyance_allowance) }}</span></li>
                                    <li><label>Bonus :</label> <span>{{ $money($profile?->bonus) }}</span></li>
                                    <li><label>Gross Salary :</label> <span>{{ $money($profile?->gross_salary) }}</span></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <div class="v-preview-widget s-preview-address">
                                <h6>Bank Details</h6>
                                <ul>
                                    <li><label>Account Number :</label> <span>{{ $profile?->bank_account_number ?: '-' }}</span></li>
                                    <li><label>IFSC Code :</label> <span>{{ $profile?->ifsc_code ?: '-' }}</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-document-view">
                                <h6>Documents</h6>
                                <div class="row">
                                    @forelse ($record->controllerDocuments as $document)
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="v-doc-preview s-doc-preview">
                                                <p>{{ $document->documentType?->name ?: 'Document' }}</p>
                                                <img src="{{ asset('assets/img/file.avif') }}" alt="Document">
                                                <a href="#!" class="mb-3 view-document"
                                                    data-bs-toggle="modal" data-bs-target="#viewDoc"
                                                    data-preview="{{ route('controller-documents.preview', $document->id) }}"
                                                    data-download="{{ route('controller-documents.download', $document->id) }}">
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

