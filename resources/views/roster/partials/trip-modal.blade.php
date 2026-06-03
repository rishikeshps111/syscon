<div class="modal fade" id="tripSelectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Choose Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="trip-picker-search mb-3">
                    <div class="o-f-inp flex-grow-1">
                        <label for="tripSearchInput">Search Trip</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="tripSearchInput" class="form-control shadow-none"
                                placeholder="Trip sheet code or trip title">
                            <button type="button" class="btn btn-primary" id="tripSearchBtn">Search</button>
                        </div>
                    </div>
                </div>
                <div class="trip-picker-meta mb-3">
                    <span><i class="fa-regular fa-calendar"></i> Duty Date: <strong id="tripPickerDutyDate">-</strong></span>
                    <span><i class="fa-solid fa-circle-info"></i> Driver and vehicle will auto-fill from the active assignment.</span>
                </div>
                <div id="tripSearchResults" class="trip-result-list">
                    <div class="trip-result-state text-muted">Select duty date and search trip.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .trip-picker-search {
        display: flex;
        gap: 12px;
        align-items: end;
    }

    .trip-picker-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 13px;
        color: #667085;
    }

    .trip-picker-meta span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .trip-result-list {
        display: grid;
        gap: 10px;
        max-height: 430px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .trip-result-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px;
        background: #fff;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
    }

    .trip-result-card:hover {
        border-color: #86b7fe;
        background: #f8fbff;
    }

    .trip-result-code {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        padding: 3px 8px;
        border-radius: 6px;
        background: #eef4ff;
        color: #1849a9;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .trip-result-title {
        font-size: 15px;
        font-weight: 700;
        color: #101828;
        margin-bottom: 8px;
    }

    .trip-result-info {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        color: #667085;
        font-size: 13px;
    }

    .trip-result-info span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .trip-result-state {
        border: 1px dashed #d0d5dd;
        border-radius: 8px;
        padding: 22px;
        text-align: center;
        background: #fcfcfd;
    }

    @media (max-width: 575px) {
        .trip-result-card {
            grid-template-columns: 1fr;
        }
    }
</style>
