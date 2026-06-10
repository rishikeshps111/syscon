@section('title')
    OEM Details
@endsection
<x-app-layout>
    <div class="page-title">
        <h3>OEM Details</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('oems.index') }}">OEM</a></li>
                <li class="breadcrumb-item active">View Details</li>
            </ol>
        </nav>
    </div>

    @php
        $yesNo = fn ($value) => $value ? 'Yes' : 'No';
        $date = fn ($value) => $value ? $value->format('d-m-Y') : '-';
    @endphp

    <section class="section dashboard">
        <div class="row">
            <div class="col-lg-10 mb-3">
                <div class="main-table-container mt-3 bg-white">
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <div class="btn-flex justify-content-end">
                                <a href="{{ route('oems.index') }}" class="btn btn-secondary">Back</a>
                                <a href="{{ route('oems.download-pdf', $record->id) }}" class="btn btn-primary">Download PDF</a>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-preview">
                                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo">
                                @if ($record->is_verified)
                                    <span><i class="fa-solid fa-circle-check"></i> Verified</span>
                                @else
                                    <span class="status-orange">Pending</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <h3 class="v-title">{{ $record->oem_name ?: '-' }}</h3>
                        </div>

                        <div class="col-lg-12">
                            <h5 class="title-cs-bnk">Basic Information</h5>
                            <div class="list-program-dt mt-3">
                                <ul>
                                    <li>OEM Code : <span>{{ $record->oem_code ?: '-' }}</span></li>
                                    <li>OEM Name : <span>{{ $record->oem_name ?: '-' }}</span></li>
                                    <li>Short Name : <span>{{ $record->short_name ?: '-' }}</span></li>
                                    <li>OEM Type : <span>{{ $record->oem_type ?: '-' }}</span></li>
                                    <li>Registration Type : <span>{{ $record->registration_type ?: '-' }}</span></li>
                                    <li>Registered State : <span>{{ $record->state?->name ?: '-' }}</span></li>
                                    <li>Verification Status : <span>{{ $record->is_verified ? 'Verified' : 'Pending' }}</span></li>
                                    <li>Verified By : <span>{{ $record->verifiedBy?->name ?: '-' }}</span></li>
                                    <li>Verified At : <span>{{ $date($record->verified_at) }}</span></li>
                                    <li>Status :
                                        @if ($record->status === 'Active')
                                            <span class="status-green">Active</span>
                                        @elseif ($record->status === 'Blocked')
                                            <span class="status-red">Blocked</span>
                                        @else
                                            <span class="status-orange">Inactive</span>
                                        @endif
                                    </li>
                                </ul>
                            </div>

                            <h5 class="title-cs-bnk mt-4">Business Details</h5>
                            <div class="list-program-dt mt-3">
                                <ul>
                                    <li>GST Number : <span>{{ $record->gst_number ?: '-' }}</span></li>
                                    <li>PAN Number : <span>{{ $record->pan_number ?: '-' }}</span></li>
                                    <li>CIN Number : <span>{{ $record->cin_number ?: '-' }}</span></li>
                                </ul>
                            </div>

                            <div class="row mt-3">
                                <div class="col-lg-12 mb-3">
                                    <div class="desc-program-dt">
                                        <h6>Remarks</h6>
                                        <p>{{ $record->remarks ?: '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="oem-dt-box">
                                <h3>Contact Details</h3>
                                <div class="table-over">
                                    <table class="align-middle mb-0 table tble-cstm bg-transparent mt-3">
                                        <thead>
                                            <tr class="min-max-width">
                                                <th class="text-center">SL NO</th>
                                                <th class="text-center">Contact Person</th>
                                                <th class="text-center">Designation</th>
                                                <th class="text-center">Phone</th>
                                                <th class="text-center">Alternate Phone</th>
                                                <th class="text-center">Email</th>
                                                <th class="text-center">Primary</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($record->contacts as $contact)
                                                <tr>
                                                    <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                                    <td class="text-center text-muted">{{ $contact->contact_person ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $contact->designation ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $contact->full_phone ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $contact->full_alternate_phone ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $contact->email ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $yesNo($contact->is_primary) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="7" class="text-center text-muted">No contacts found.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="oem-dt-box">
                                <h3>Address Details</h3>
                                <div class="table-over">
                                    <table class="align-middle mb-0 table tble-cstm bg-transparent mt-3">
                                        <thead>
                                            <tr class="min-max-width">
                                                <th class="text-center">SL NO</th>
                                                <th class="text-center">Address Type</th>
                                                <th class="text-center">Address Line 1</th>
                                                <th class="text-center">Address Line 2</th>
                                                <th class="text-center">City</th>
                                                <th class="text-center">District</th>
                                                <th class="text-center">State</th>
                                                <th class="text-center">Pincode</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($record->addresses as $address)
                                                <tr>
                                                    <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                                    <td class="text-center text-muted">{{ $address->address_type ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $address->address_line1 ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $address->address_line2 ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $address->city?->name ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $address->district?->name ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $address->state?->name ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $address->pincode ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="8" class="text-center text-muted">No addresses found.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="oem-dt-box">
                                <h3>Bank Details</h3>
                                <div class="table-over">
                                    <table class="align-middle mb-0 table tble-cstm mt-3">
                                        <thead>
                                            <tr>
                                                <th class="text-center nowrap">SL NO</th>
                                                <th class="text-center">Account Name</th>
                                                <th class="text-center">Account Number</th>
                                                <th class="text-center">Bank Name</th>
                                                <th class="text-center">Branch</th>
                                                <th class="text-center">IFSC Code</th>
                                                <th class="text-center">Primary</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($record->bankDetails as $bankDetail)
                                                <tr>
                                                    <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                                    <td class="text-center text-muted">{{ $bankDetail->account_name ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $bankDetail->account_number ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $bankDetail->bank_name ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $bankDetail->branch ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $bankDetail->ifsc_code ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $yesNo($bankDetail->is_primary) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="7" class="text-center text-muted">No bank details found.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="oem-dt-box">
                                <h3>State Mappings</h3>
                                <div class="table-over">
                                    <table class="align-middle mb-0 table tble-cstm mt-3">
                                        <thead>
                                            <tr>
                                                <th class="text-center nowrap">SL NO</th>
                                                <th class="text-center">State</th>
                                                <th class="text-center">GST Number</th>
                                                <th class="text-center">Primary</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($record->stateMappings as $mapping)
                                                <tr>
                                                    <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                                    <td class="text-center text-muted">{{ $mapping->state?->name ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $mapping->gst_number ?: '-' }}</td>
                                                    <td class="text-center text-muted">{{ $yesNo($mapping->is_primary) }}</td>
                                                    <td class="text-center text-muted">{{ $mapping->status ? 'Active' : 'Inactive' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-center text-muted">No state mappings found.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="v-document-view">
                                <h6>Documents</h6>
                                <div class="row">
                                    @forelse ($record->documents as $document)
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="v-doc-preview s-doc-preview">
                                                <p>{{ $document->documentType?->name ?: 'Document' }}</p>
                                                <img src="{{ asset('assets/img/file.avif') }}" alt="Document">
                                                <a href="#!" class="mb-3 view-document"
                                                    data-bs-toggle="modal" data-bs-target="#viewDoc"
                                                    data-preview="{{ route('oem-documents.preview', $document->id) }}"
                                                    data-download="{{ route('oem-documents.download', $document->id) }}">
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
