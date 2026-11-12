<link rel="stylesheet"
    href="{{ asset('assets/css/roster-import.css') }}?v={{ filemtime(public_path('assets/css/roster-import.css')) }}">
<div class="modal fade roster-import-modal" id="rosterImportModal" tabindex="-1" aria-labelledby="rosterImportTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title modal-title-cs" id="rosterImportTitle">Update Roaster Via Import</h5>
                    <div class="ri-subtitle">{{ $record->code ?: 'Selected trip' }} &middot; Import driver assignments
                        from Excel</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="monthlyRosterImport" action="{{ route('trips.roster.monthly-import', $record) }}" method="POST"
                enctype="multipart/form-data" style="display:contents">
                @csrf
                <div class="modal-body">
                    <ol class="ri-steps" aria-label="Import steps">
                        <li><span>1</span>
                            <div><strong>Download sample</strong><small>All trip dates and current assignments</small>
                            </div>
                        </li>
                        <li><span>2</span>
                            <div><strong>Upload &amp; preview</strong><small>Check drivers, conflicts and row
                                    statuses</small></div>
                        </li>
                        <li><span>3</span>
                            <div><strong>Confirm eligible rows</strong><small>Only valid assignments are updated</small>
                            </div>
                        </li>
                    </ol>
                    <div class="ri-import-tools">
                        <div class="ri-tool-card">
                            <div class="ri-tool-heading"><i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                                <div>
                                    <h6>Excel template</h6>
                                    <p>Trip Codes, dates and existing assignments are prefilled.</p>
                                </div>
                            </div>
                            <div class="btns-group-container ri-template-actions">
                                <a id="monthlyRosterTemplate" class="exp-btn"
                                    href="{{ route('trips.roster.monthly-template', $record) }}">Download Sample
                                    Excel</a>
                            </div>
                        </div>
                        <div class="ri-tool-card">
                            <label class="ri-upload-label" for="monthlyRosterFile">Upload your completed file
                                <span>.xlsx or .xls</span></label>
                            <div class="ri-upload-row o-f-inp">
                                <input id="monthlyRosterFile" name="roster_file" type="file" accept=".xlsx,.xls"
                                    class="form-control shadow-none" required>
                                <button class="modal-btn-2" type="submit">Preview Import</button>
                            </div>
                        </div>
                    </div>
                    <details class="ri-instructions">
                        <summary>Excel instructions &amp; assignment rules</summary>
                        <ul>
                            <li>Keep the full Trip Code, Date and Service Code unchanged. Edit Driver Name and Vehicle.
                            </li>
                            <li>Use the recorded driver name. Duplicate names are flagged for correction.</li>
                            <li>A vehicle is optional. If provided, the vehicle is validated and assigned. If blank, no
                                vehicle is required.</li>
                            <li>Duty times come from the trip sheet. Conflicting duties and other issues are shown in
                                preview.</li>
                        </ul>
                    </details>
                    <div class="ri-notice"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>No roasters
                            are updated until you confirm. Keep this window open during processing.</span></div>
                    <div class="alert alert-info ri-progress d-none" data-import-progress>
                        <div role="status" aria-live="polite" data-import-status></div>
                        <div class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" data-import-bar
                                role="progressbar" aria-label="Roster import progress" aria-valuemin="0"
                                aria-valuemax="100" aria-valuenow="0" style="width:0%"></div>
                        </div>
                        <div class="mt-2" data-import-counts></div>
                    </div>
                    <div class="alert alert-danger d-none" data-import-error role="alert"></div>
                    <div class="ri-preview d-none" data-import-preview>
                        <div class="ri-preview-heading">
                            <h6>Assignment preview</h6>
                            <p data-import-summary role="status" aria-live="polite"></p>
                        </div>
                        <div class="table-responsive ri-preview-scroll">
                            <table class="align-middle mb-0 table tble-cstm ri-preview-table">
                                <thead>
                                    <tr>
                                        <th>Excel Row</th>
                                        <th>Trip Code</th>
                                        <th>Date</th>
                                        <th>Service Code</th>
                                        <th>Current Driver</th>
                                        <th>Proposed Driver</th>
                                        <th>Current Vehicle</th>
                                        <th>Proposed Vehicle</th>
                                        <th>Status</th>
                                        <th>Reason / Conflicting Trip</th>
                                    </tr>
                                </thead>
                                <tbody data-import-rows></tbody>
                            </table>
                        </div>
                        <div class="ri-pagination">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-import-previous
                                disabled>Previous</button>
                            <span data-import-page aria-live="polite"></span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-import-next
                                disabled>Next</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-btns-last">
                    <span class="ri-footer-note">Review the preview before confirming eligible rows.</span>
                    <button type="button" class="modal-btn-1" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="modal-btn-2 d-none" data-import-confirm disabled>Confirm &amp; Update
                        Eligible Rows (0)</button>
                </div>
            </form>
        </div>
    </div>
</div>
