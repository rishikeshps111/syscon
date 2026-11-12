(() => {
    const form = document.getElementById('monthlyRosterImport');
    if (!form) return;
    const modal = document.getElementById('rosterImportModal');
    const get = selector => form.querySelector(selector);
    const file = get('[name="roster_file"]');
    const button = get('button[type="submit"]');
    const confirm = get('[data-import-confirm]');
    const template = document.getElementById('monthlyRosterTemplate');
    const progress = get('[data-import-progress]');
    const status = get('[data-import-status]');
    const bar = get('[data-import-bar]');
    const counts = get('[data-import-counts]');
    const error = get('[data-import-error]');
    const preview = get('[data-import-preview]');
    const summary = get('[data-import-summary]');
    const tbody = get('[data-import-rows]');
    const previous = get('[data-import-previous]');
    const next = get('[data-import-next]');
    let token = null, last = null, running = false, page = 1, pages = 1, reloadAfterClose = false;
    const labels = { ready_create: 'Ready to create', ready_update: 'Ready to update', unchanged: 'Unchanged', blank: 'Blank—skipped', issue: 'Issue', created: 'Created', updated: 'Updated', skipped: 'Skipped' };
    const totals = data => `${data.created} created, ${data.updated} updated, ${data.unchanged} unchanged, ${data.skipped} skipped`;
    const csrf = () => get('[name="_token"]').value;

    function controls() {
        const eligible = last?.summary?.eligible || 0;
        confirm.disabled = running || last?.phase !== 'ready' || eligible === 0;
        confirm.classList.toggle('d-none', !last || !['ready', 'saving'].includes(last.phase));
        confirm.textContent = `Confirm & Update Eligible Rows (${eligible})`;
        previous.disabled = running || page <= 1;
        next.disabled = running || page >= pages;
    }

    async function request(step, values = {}, body = new FormData()) {
        body.set('_token', csrf());
        body.set('step', step);
        if (token) body.set('token', token);
        Object.entries(values).forEach(([key, value]) => body.set(key, value));
        const response = await fetch(form.action, { method: 'POST', body, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join('\n') : data.message || 'Import request failed.');
        return data;
    }

    function showProgress(data) {
        last = data;
        const saving = ['saving', 'done'].includes(data.phase);
        const processed = saving ? data.processed : data.previewed;
        const percent = data.total ? Math.round(processed / data.total * 100) : 0;
        progress.classList.remove('d-none');
        bar.style.width = `${percent}%`;
        bar.setAttribute('aria-valuenow', percent);
        status.textContent = data.phase === 'done' ? 'Import completed.' : `${saving ? 'Saving' : 'Checking'} ${processed} / ${data.total} rows`;
        counts.textContent = saving ? totals(data) : `${data.summary.eligible} eligible, ${data.summary.unchanged} unchanged, ${data.summary.blank} blank, ${data.summary.issue} with issues`;
    }

    async function loadPage(number) {
        const data = await request('rows', { page: number });
        if (!Array.isArray(data.rows)) throw new Error('Unexpected preview response.');
        page = data.page;
        pages = data.pages;
        last = data;
        tbody.replaceChildren();
        data.rows.forEach(row => {
            const tr = document.createElement('tr');
            const state = row.outcome || row.status;
            if (state === 'issue' || state === 'skipped') tr.className = 'table-warning';
            [row.line, row.trip_code, row.display_date, row.service, row.current_driver, row.proposed_driver, row.current_vehicle || row.vehicle || 'Not assigned', row.proposed_vehicle || row.vehicle || 'Not assigned', labels[state] || state, row.reason]
                .forEach((value, index) => {
                    const td = document.createElement('td');
                        if (index === 8) {
                            const badge = document.createElement('span');
                            badge.className = `ri-status ri-status--${Object.hasOwn(labels, state) ? state : 'issue'}`;
                            badge.textContent = value;
                            td.appendChild(badge);
                        } else if (index === 9) {
                            td.className = 'ri-reason';
                            const reason = document.createElement('div');
                            reason.textContent = value || '—';
                            td.appendChild(reason);
                            if (row.conflicts?.length) {
                                const duties = document.createElement('div');
                                duties.className = 'ri-conflicts';
                                row.conflicts.forEach(conflict => {
                                    const card = document.createElement('div');
                                    card.className = 'ri-conflict';
                                    const label = document.createElement('span');
                                    label.className = 'ri-conflict-label';
                                    label.textContent = conflict.source === 'upload' ? `Uploaded row ${conflict.row}`
                                        : `${conflict.resource === 'vehicle' ? 'Vehicle' : 'Driver'} assigned to another trip`;
                                    const code = document.createElement('strong');
                                    code.className = 'ri-conflict-code';
                                    code.textContent = conflict.trip_code;
                                    const detail = document.createElement('span');
                                    detail.className = 'ri-conflict-meta';
                                    detail.textContent = [conflict.parent_trip_code && conflict.parent_trip_code !== conflict.trip_code ? `Trip ${conflict.parent_trip_code}` : '',
                                    conflict.service_code ? `Service ${conflict.service_code}` : '', conflict.date,
                                    `${conflict.start_time}–${conflict.end_time}`, conflict.status === 'in_progress' ? 'In progress' : ''].filter(Boolean).join(' · ');
                                    card.appendChild(label);
                                    card.appendChild(code);
                                    card.appendChild(detail);
                                    duties.appendChild(card);
                                });
                                td.appendChild(duties);
                            }
                        } else td.textContent = value ?? '';
                        tr.appendChild(td);
                    });
            tbody.appendChild(tr);
        });
        get('[data-import-page]').textContent = `Page ${page} of ${pages} (${data.total} rows)`;
        summary.textContent = data.phase === 'done' ? `Import completed: ${totals(data)}.`
            : `${data.summary.ready_create} ready to create, ${data.summary.ready_update} ready to update, ${data.summary.unchanged} unchanged, ${data.summary.blank} blank, ${data.summary.issue} with issues. Only eligible rows will be updated after confirmation.`;
        preview.classList.remove('d-none');
    }

    async function run(label, action, saving = false) {
        if (running) return;
        running = true;
        const busyButton = saving ? confirm : button;
        const original = busyButton.innerHTML;
        const inputs = [...form.querySelectorAll('input, button'), ...modal.querySelectorAll('[data-bs-dismiss="modal"]'), ...document.querySelectorAll('.js-loading-form button[type="submit"]')];
        const disabled = inputs.map(input => input.disabled);
        inputs.forEach(input => { input.disabled = true; });
        form.setAttribute('aria-busy', 'true');
        template.setAttribute('aria-disabled', 'true');
        busyButton.innerHTML = `<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>${label}`;
        error.classList.add('d-none');
        bar.classList.add('progress-bar-animated');
        try {
            await action();
        } catch (exception) {
            const completed = last?.phase === 'done';
            status.textContent = completed ? 'Import completed; refresh the roster table.' : (saving ? 'Import stopped.' : 'Preview stopped.');
            error.style.whiteSpace = 'pre-wrap';
            error.textContent = `${exception.message}\n${saving && last ? `Confirmed: ${totals(last)} (${last.processed} / ${last.total} rows processed).${completed ? '' : ' The last batch may have partially completed. Review assignments before uploading again.'}` : 'No assignments were saved by this preview.'}`;
            error.classList.remove('d-none');
            if (saving && last && !completed) last.phase = 'interrupted';
            if (!saving && last && preview.classList.contains('d-none')) last.phase = 'preview_failed';
        } finally {
            if (!reloadAfterClose) {
                running = false;
                inputs.forEach((input, index) => { input.disabled = disabled[index]; });
                busyButton.innerHTML = original;
                form.removeAttribute('aria-busy');
                template.removeAttribute('aria-disabled');
                bar.classList.remove('progress-bar-animated');
                controls();
            }
        }
    }

    function reset() {
        const old = token;
        token = null;
        last = null;
        page = pages = 1;
        preview.classList.add('d-none');
        progress.classList.add('d-none');
        error.classList.add('d-none');
        tbody.replaceChildren();
        controls();
        return old;
    }

    async function discard(old) {
        if (old) await request('discard', { token: old });
    }

    modal.addEventListener('hide.bs.modal', event => { if (running && !reloadAfterClose) event.preventDefault(); });
    modal.addEventListener('hidden.bs.modal', () => {
        if (reloadAfterClose) { window.location.reload(); return; }
        const old = reset();
        file.value = '';
        return discard(old).catch(() => { });
    });
    template.addEventListener('click', event => { if (running) event.preventDefault(); });
    file.addEventListener('change', () => {
        if (running) return;
        const old = reset();
        if (old) return run('Resetting preview...', () => discard(old));
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (running || !form.reportValidity()) return;
        const body = new FormData(form);
        const old = reset();
        return run('Checking file...', async () => {
            await discard(old);
            status.textContent = 'Reading file...';
            progress.classList.remove('d-none');
            let data = await request('prepare', {}, body);
            token = data.token;
            if (!token || data.phase !== 'preview') throw new Error('Unexpected upload response.');
            showProgress(data);
            while (data.phase === 'preview') {
                data = await request('preview', { offset: data.previewed });
                showProgress(data);
            }
            if (data.phase !== 'ready') throw new Error('Preview did not complete.');
            await loadPage(1);
            status.textContent = 'Preview complete. Review row statuses before confirming.';
        });
    });
    confirm.addEventListener('click', () => {
        if (running || last?.phase !== 'ready' || !last.summary.eligible) return;
        return run('Updating roasters...', async () => {
            let data = await request('confirm', { confirmed: '1' });
            showProgress(data);
            while (data.phase === 'saving') {
                data = await request('save', { offset: data.processed });
                showProgress(data);
            }
            if (data.phase !== 'done') throw new Error('Import did not complete.');
            summary.textContent = `Import completed: ${totals(data)}.`;
            reloadAfterClose = true;
            if (window.bootstrap?.Modal && modal.classList.contains('show')) {
                window.bootstrap.Modal.getOrCreateInstance(modal).hide();
            } else window.location.reload();
        }, true);
    });
    previous.addEventListener('click', () => { if (page > 1) return run('Loading preview...', () => loadPage(page - 1)); });
    next.addEventListener('click', () => { if (page < pages) return run('Loading preview...', () => loadPage(page + 1)); });
    controls();
})();
