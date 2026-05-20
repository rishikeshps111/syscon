@section('title')
    Complaint Details
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Complaint Details</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('complaints.index', ['reported_by_role' => $record->reported_by_role]) }}">Complaints</a></li>
                    <li class="breadcrumb-item active">{{ $record->code }}</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="title-w-sec mb-1">{{ $record->code }}</h5>
                    <div class="text-muted">{{ $record->complaint_date?->format('d-m-Y') }}</div>
                </div>
                <div class="btn-flex">
                    <a href="{{ route('complaints.index', ['reported_by_role' => $record->reported_by_role]) }}" class="btn btn-secondary">Back</a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3 mb-3">
                    <strong>Status</strong><br>
                    <span class="status-orange">{{ $record->status_label }}</span>
                </div>
                <div class="col-lg-3 mb-3">
                    <strong>Severity</strong><br>{{ $record->severity_label }}
                </div>
                <div class="col-lg-3 mb-3">
                    <strong>Category</strong><br>{{ $record->category?->name ?? '-' }}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="main-table-container h-100">
                    <h5 class="title-w-sec">Reported By</h5>
                    <hr>
                    <div class="mb-3"><strong>Role</strong><br>{{ $record->reported_by_role_label }}</div>
                    <div><strong>User ID / Name</strong><br>{{ trim(($record->reportedBy?->code ? $record->reportedBy->code . ' - ' : '') . ($record->reportedBy?->name ?? '-')) }}</div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="main-table-container h-100">
                    <h5 class="title-w-sec">Against Whom</h5>
                    <hr>
                    <div class="mb-3"><strong>Role</strong><br>{{ $record->against_role_label }}</div>
                    <div><strong>Employee Name / ID</strong><br>{{ trim(($record->againstUser?->code ? $record->againstUser->code . ' - ' : '') . ($record->againstUser?->name ?? '-')) }}</div>
                </div>
            </div>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec">Complaint Details</h5>
            <hr>
            <div class="mb-3"><strong>Description</strong><br>{{ $record->description }}</div>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec">Attachments</h5>
            <hr>
            @if(count($record->attachment_urls))
                <div class="complaint-attachment-grid">
                    @foreach ($record->attachment_urls as $index => $url)
                        @php
                            $path = $record->attachment_paths[$index] ?? '';
                            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                            $fileName = basename($path) ?: 'Attachment ' . ($index + 1);
                        @endphp
                        <a href="{{ $url }}" target="_blank" class="complaint-attachment-card">
                            <div class="complaint-attachment-preview">
                                @if($isImage)
                                    <img src="{{ $url }}" alt="Attachment {{ $index + 1 }}">
                                @else
                                    <i class="fa-solid {{ $extension === 'pdf' ? 'fa-file-pdf' : 'fa-file-lines' }}"></i>
                                @endif
                            </div>
                            <div class="complaint-attachment-meta">
                                <strong>Attachment {{ $index + 1 }}</strong>
                                <span>{{ $fileName }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <span class="text-muted">No attachments uploaded.</span>
            @endif
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec">Action</h5>
            <hr>
            <div class="row">
                <div class="col-lg-4 mb-3"><strong>Assigned To</strong><br>{{ $record->assigned_to_label ?: '-' }}</div>
                <div class="col-lg-4 mb-3"><strong>Action Taken</strong><br>{{ $record->action_taken_label ?: '-' }}</div>
                <div class="col-lg-4 mb-3"><strong>Action Date</strong><br>{{ $record->action_date?->format('d-m-Y') ?? '-' }}</div>
            </div>
        </div>

        <div class="main-table-container">
            <h5 class="title-w-sec">Remarks</h5>
            <hr>
            {{ $record->remarks ?? '-' }}
        </div>
    </section>

    @section('styles')
        <style>
            .complaint-attachment-grid {
                display: grid;
                gap: 14px;
                grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            }

            .complaint-attachment-card {
                border: 1px solid #e2e6ea;
                border-radius: 8px;
                color: inherit;
                display: block;
                overflow: hidden;
                text-decoration: none;
                transition: border-color .2s ease, box-shadow .2s ease;
            }

            .complaint-attachment-card:hover {
                border-color: #8da2fb;
                box-shadow: 0 8px 22px rgba(18, 38, 63, .10);
                color: inherit;
            }

            .complaint-attachment-preview {
                align-items: center;
                background: #f6f8fb;
                display: flex;
                height: 132px;
                justify-content: center;
            }

            .complaint-attachment-preview img {
                height: 100%;
                object-fit: cover;
                width: 100%;
            }

            .complaint-attachment-preview i {
                color: #546179;
                font-size: 42px;
            }

            .complaint-attachment-meta {
                padding: 10px 12px;
            }

            .complaint-attachment-meta strong,
            .complaint-attachment-meta span {
                display: block;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .complaint-attachment-meta span {
                color: #697386;
                font-size: 12px;
                margin-top: 3px;
            }
        </style>
    @endsection
</x-app-layout>
