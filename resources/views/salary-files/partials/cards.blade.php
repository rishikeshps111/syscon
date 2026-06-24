<div class="row">
    @forelse ($files as $file)
        @php
            $isApproved = $file->status === 'Approved';
            $monthName = \Carbon\Carbon::create(null, $file->month, 1)->format('F');
            $baseNameParts = [
                $file->depot?->short_name ?: $file->depot?->code ?: $file->depot?->name ?: 'Depot',
                $monthName,
                $file->year,
                $file->role?->name ?: 'Role',
            ];
            $documentName = str_replace(' ', '', implode(' ', $baseNameParts)) . '_SalaryFiles';
        @endphp
        <div class="col-xl-4 col-md-6 mb-3">
            <article class="document-card h-100">
                <div class="document-icon" style="color: #dc2626;">
                    <i class="fa-solid fa-file-pdf"></i>
                </div>

                <div class="document-name">{{ $documentName }}.pdf</div>

                <div class="document-folder">
                    <span>{{ $file->depot?->name ?? 'Root folder' }} / {{ $file->role?->name ?? '-' }}</span>
                </div>

                <div class="document-meta-text">PDF / Excel • {{ $file->items_count }} users</div>
                <div class="document-meta-text">Updated {{ $file->updated_at?->format('d M Y h:i A') }}</div>
                <div class="document-meta-text">
                    {{ $isApproved ? 'Approved' : 'Pending Approval' }}
                    @if ($isApproved)
                        • {{ $file->approver?->name ?? '-' }}
                    @endif
                </div>

                <div class="document-card-footer">
                    <span class="badge bg-light text-dark">{{ $file->items_count }}</span>
                    @if ($isApproved)
                        <div class="d-flex gap-2">
                            <a href="{{ route('salary-files.pdf', $file->id) }}" class="document-download"
                                title="Download {{ $documentName }}.pdf">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>
                            <a href="{{ route('salary-files.excel', $file->id) }}" class="document-download document-download-excel"
                                title="Download {{ $documentName }}.xlsx">
                                <i class="fa-solid fa-file-excel"></i>
                            </a>
                        </div>
                    @else
                        <div class="d-flex gap-2">
                            <span class="document-download disabled" title="Approve salary processing to download PDF">
                                <i class="fa-solid fa-file-pdf"></i>
                            </span>
                            <span class="document-download disabled" title="Approve salary processing to download Excel">
                                <i class="fa-solid fa-file-excel"></i>
                            </span>
                        </div>
                    @endif
                </div>
            </article>
        </div>
    @empty
        <div class="col-12">
            <div class="main-table-container">
                <div class="alert alert-warning mb-0">No completed salary files found for the selected filters.</div>
            </div>
        </div>
    @endforelse
</div>
