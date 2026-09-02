@section('title')
    Complaint Details
@endsection

<style>
    .comp-top-container {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 20px !important;

    margin-bottom: 18px !important;

    padding-bottom: 14px !important;

    border-bottom: 1px solid #e2e8f0 !important;
}

.comp-top {
    min-width: 0 !important;
}

.comp-top .title-w-sec-1 {
    margin: 0 !important;

    color: #1e293b !important;

    font-size: 18px !important;
    font-weight: 600 !important;
}

.comp-top p {
    margin: 3px 0 0 !important;

    color: #64748b !important;

    font-size: 12px !important;
}



.comp-widget {
    position: relative !important;

    min-height: 88px !important;

    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;

    padding: 16px 18px !important;

    background: #eff3fd !important;

    /*border: 1px solid #e2e8f0 !important;*/
    border-radius: 11px !important;

    box-shadow: 0 2px 7px rgba(15, 23, 42, 0.04) !important;

    transition: all 0.2s ease !important;
}


.comp-widget strong {
    display: block !important;

    margin-bottom: 6px !important;

    color: #64748b !important;

    font-size: 11px !important;
    font-weight: 600 !important;

    text-transform: uppercase !important;
    letter-spacing: 0.4px !important;
}


/* =========================================
   Widget Value
   ========================================= */

.comp-widget {
    color: #1e293b !important;

    font-size: 14px !important;
    font-weight: 600 !important;
}


.comp-widget .status-orange {
    display: inline-flex !important;
    align-items: center !important;

    width: fit-content !important;

    padding: 5px 10px !important;

    color: #c2410c !important;

    background: #fff7ed !important;

    border: 1px solid #fed7aa !important;
    border-radius: 20px !important;

    font-size: 11px !important;
    font-weight: 700 !important;
}

.comp-widget .status-orange::before {
    content: "" !important;

    width: 6px !important;
    height: 6px !important;

    margin-right: 6px !important;

    background: #f97316 !important;

    border-radius: 50% !important;
}

.complaint-attachment-card {
    display: flex !important;
    align-items: center !important;
    gap: 14px !important;

    width: 100% !important;
    min-height: 78px !important;

    padding: 10px !important;

    background: #ffffff !important;

    border: 1px solid #e2e8f0 !important;
    border-radius: 11px !important;

    text-decoration: none !important;

    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04) !important;

    transition: all 0.22s ease !important;
}

.complaint-attachment-card:hover {
    transform: translateY(-2px) !important;

    background: #f8fbff !important;

    border-color: #bfdbfe !important;

    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.10) !important;
}



.complaint-attachment-preview {
    flex: 0 0 58px !important;

    width: 58px !important;
    height: 58px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    overflow: hidden !important;

    background: #eff6ff !important;

    border: 1px solid #dbeafe !important;
    border-radius: 9px !important;

    color: #2563eb !important;

    font-size: 25px !important;

    transition: all 0.2s ease !important;
}

.complaint-attachment-card:hover .complaint-attachment-preview {
    background: #dbeafe !important;

    transform: scale(1.03) !important;
}


.complaint-attachment-preview img {
    width: 100% !important;
    height: 100% !important;

    display: block !important;

    object-fit: cover !important;
}



.complaint-attachment-preview .fa-file-pdf {
    color: #dc2626 !important;
}


/* PDF background */

.complaint-attachment-card:has(.fa-file-pdf) .complaint-attachment-preview {
    background: #fef2f2 !important;
    border-color: #fecaca !important;
}



.complaint-attachment-preview .fa-file-lines {
    color: #64748b !important;
}

.complaint-attachment-card:has(.fa-file-lines) .complaint-attachment-preview {
    background: #f8fafc !important;
    border-color: #e2e8f0 !important;
}


.complaint-attachment-meta {
    min-width: 0 !important;

    display: flex !important;
    flex-direction: column !important;

    gap: 4px !important;
}

.complaint-attachment-meta strong {
    display: block !important;

    color: #1e293b !important;

    font-size: 13px !important;
    font-weight: 700 !important;

    line-height: 1.3 !important;
}

.complaint-attachment-meta span {
    display: block !important;

    max-width: 100% !important;

    overflow: hidden !important;

    color: #64748b !important;

    font-size: 11px !important;
    font-weight: 500 !important;

    line-height: 1.3 !important;

    white-space: nowrap !important;
    text-overflow: ellipsis !important;
}


.complaint-attachment-card::after {
    content: "\f35d" !important;

    margin-left: auto !important;
    padding-right: 7px !important;

    color: #94a3b8 !important;

    font-family: "Font Awesome 6 Free" !important;
    font-size: 11px !important;
    font-weight: 900 !important;

    transition: all 0.2s ease !important;
}

.complaint-attachment-card:hover::after {
    color: #2563eb !important;

    transform: translate(2px, -2px) !important;
}



.complaint-attachments {
    display: grid !important;

    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;

    gap: 12px !important;
}
</style>





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
            <div class="comp-top-container">
                <div class="comp-top">
                    <h5 class="title-w-sec-1 mb-1">{{ $record->code }}</h5>
                    <p class="text-muted">{{ $record->complaint_date?->format('d-m-Y') }}</p>
                </div>
                <div class="btns-group-container">
                    <a href="{{ route('complaints.index', ['reported_by_role' => $record->reported_by_role]) }}" class="bk-btn">Back</a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-3">
                   <div class="comp-widget">
                        <strong>Status</strong>
                    <span class="status-orange">{{ $record->status_label }}</span>
                   </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="comp-widget">
                        <strong>Severity</strong> {{ $record->severity_label }}
                    </div>
                    
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="comp-widget">
                         <strong>Category</strong>{{ $record->category?->name ?? '-' }}
                    </div>
                   
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="complaint-info-cont">
                    <h5 class="title-w-sec">Reported By</h5>
                    <hr>
                    <div class="mb-3"><strong>Role</strong><br>{{ $record->reported_by_role_label }}</div>
                    <div><strong>User ID / Name</strong><br>{{ trim(($record->reportedBy?->code ? $record->reportedBy->code . ' - ' : '') . ($record->reportedBy?->name ?? '-')) }}</div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="complaint-info-cont">
                    <h5 class="title-w-sec">Against Whom</h5>
                    <hr>
                    <div class="mb-3"><strong>Role</strong><br>{{ $record->against_role_label }}</div>
                    <div><strong>Employee Name / ID</strong><br>{{ trim(($record->againstUser?->code ? $record->againstUser->code . ' - ' : '') . ($record->againstUser?->name ?? '-')) }}</div>
                </div>
            </div>
            <div class="col-lg-12 mb-3">
                <div class="complaint-info-cont">
                <h5 class="title-w-sec">Complaint Details</h5>
            <hr>
            <div class="mb-3"><strong>Description</strong><br>{{ $record->description }}</div>
            </div>
            </div>
            <div class="col-lg-12 mb-3">
                <div class="complaint-info-cont">
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
                
            </div>
            <div class="col-lg-12 mb-3">
                <div class="complaint-info-cont">
                    <h5 class="title-w-sec">Action</h5>
            <hr>
             <div class="row" style="background: transparent !important;
    padding: 0 !important; border: 0 !important;">
                <div class="col-lg-4 mb-3">
                      <div class="comp-widget">
                        <strong>Assigned To</strong><br>{{ $record->assigned_to_label ?: '-' }}
                   </div>
                </div>
                <div class="col-lg-4 mb-3">
                      <div class="comp-widget">
                       <strong>Action Taken</strong><br>{{ $record->action_taken_label ?: '-' }}
                   </div>
                </div>
                <div class="col-lg-4 mb-3">
                      <div class="comp-widget">
                        <strong>Action Date</strong><br>{{ $record->action_date?->format('d-m-Y') ?? '-' }}
                   </div>
                </div>
              
            </div>
           
                </div>
                
            </div>
            <div class="col-lg-12 mb-3">
                <div class="complaint-info-cont">
                    <h5 class="title-w-sec">Remarks</h5>
            <hr>
            
            <div class="mb-3">{{ $record->remarks ?? '-' }}</div>
            
           
                </div>
                
            </div>
        </div>

      
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
