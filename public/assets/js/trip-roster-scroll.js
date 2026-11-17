(() => {
    const root = document.getElementById('rosterScroll');
    if (!root) return;
    const form = document.getElementById('rosterUpdateForm');
    const tbody = document.querySelector('#rosterTable tbody');
    const all = document.getElementById('checkAll');
    const count = document.getElementById('rosterSelectionCount');
    const status = document.getElementById('rosterLoadStatus');
    const spinner = document.getElementById('rosterLoadingSpinner');
    const more = document.getElementById('rosterLoadMore');
    const filters = ['dateFromFilter', 'dateToFilter', 'serFilter'].map(id => document.getElementById(id));
    const reset = document.getElementById('resetRosterFilters');
    const submit = form.querySelector('button[type="submit"]');
    const states = new Map();
    const loaded = new Map();
    let cursor = null, ended = false, busy = false, failed = false, saving = false, importBusy = false;
    let version = 0, controller = null, debounce = null, filterPending = false;

    function locked() { return saving || importBusy; }
    function selectedCount() { return [...states.values()].filter(state => state.selected).length; }
    function controls() {
        const total = selectedCount();
        count.textContent = `${total} duties selected. Selections are kept when filters change.`;
        submit.disabled = locked() || total === 0;
        all.disabled = locked() || loaded.size === 0;
        const checked = [...loaded.keys()].filter(id => states.get(id).selected).length;
        all.checked = loaded.size > 0 && checked === loaded.size;
        all.indeterminate = checked > 0 && checked < loaded.size;
        more.disabled = locked() || busy || filterPending;
        more.hidden = ended || busy;
        spinner.hidden = !busy;
        status.className = busy ? 'visually-hidden' : '';
        more.textContent = failed ? 'Retry loading duties' : 'Load more duties';
        filters.forEach(input => { input.disabled = locked(); });
        reset.disabled = locked();
        root.setAttribute('aria-busy', busy ? 'true' : 'false');
        tbody.querySelectorAll('input, select').forEach(input => { input.disabled = locked(); });
    }

    function choice(select) {
        return select.value ? { id: select.value, text: select.options[select.selectedIndex].textContent } : null;
    }

    function makeSelect(id, resource, selected, tripCode) {
        const select = document.createElement('select');
        select.className = `form-select shadow-none roster-select roster-${resource}-select`;
        select.dataset.resource = resource;
        select.dataset.entryId = id;
        select.setAttribute('aria-label', `${resource === 'driver' ? 'Driver' : 'Vehicle'} for ${tripCode}`);
        select.appendChild(new Option('', ''));
        if (selected) select.appendChild(new Option(selected.text, String(selected.id), true, true));
        return select;
    }

    function append(row) {
        const id = String(row.id);
        if (loaded.has(id)) return;
        if (!states.has(id)) states.set(id, { driver: row.driver, vehicle: row.vehicle, selected: false });
        const state = states.get(id);
        const tr = document.createElement('tr');
        tr.dataset.entryId = id;
        const td = document.createElement('td');
        td.className = 'text-center';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'row-check';
        checkbox.value = id;
        checkbox.checked = state.selected;
        checkbox.setAttribute('aria-label', `Select ${row.trip_code}`);
        td.appendChild(checkbox);
        tr.appendChild(td);
        [loaded.size + 1, row.date_text, row.trip_code, row.ser].forEach(value => {
            const cell = document.createElement('td');
            cell.className = 'text-center nowrap';
            cell.textContent = value;
            tr.appendChild(cell);
        });
        ['driver', 'vehicle'].forEach(resource => {
            const cell = document.createElement('td');
            cell.appendChild(makeSelect(id, resource, state[resource], row.trip_code));
            tr.appendChild(cell);
        });
        loaded.set(id, tr);
        tbody.appendChild(tr);
        tr.querySelectorAll('.roster-select').forEach(initializeSelect);
    }

    function nearEnd() { return root.scrollTop + root.clientHeight >= root.scrollHeight - 250; }

    async function loadNext() {
        if (busy || ended || locked() || filterPending) return;
        failed = false;
        busy = true;
        const requestVersion = version;
        const requestController = new AbortController();
        controller = requestController;
        const url = new URL(root.dataset.rowsUrl, window.location.href);
        ['date_from', 'date_to', 'ser'].forEach((name, i) => { if (filters[i].value.trim()) url.searchParams.set(name, filters[i].value.trim()); });
        if (cursor) url.searchParams.set('cursor', cursor);
        status.textContent = 'Loading duties…';
        controls();
        try {
            const response = await fetch(url, { signal: requestController.signal, headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (requestVersion !== version || locked()) return;
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message || 'Unable to load duties.');
            if (!Array.isArray(data.rows) || !(data.next_cursor === null || typeof data.next_cursor === 'string')) throw new Error('Unexpected roster response.');
            data.rows.forEach(append);
            cursor = data.next_cursor;
            ended = cursor === null;
            status.textContent = loaded.size === 0 ? 'No duties match these filters.'
                : `${loaded.size} duties loaded.${ended ? ' All matching duties loaded.' : ' Scroll to load more.'}`;
        } catch (error) {
            if (requestVersion !== version || error.name === 'AbortError') return;
            failed = true;
            status.textContent = `${error.message || 'Unable to load duties.'} Use Retry to continue.`;
        } finally {
            if (requestVersion === version) {
                busy = false;
                controller = null;
                controls();
                if (!failed && !ended && !locked() && nearEnd()) requestAnimationFrame(loadNext);
            }
        }
    }

    function changeFilters() {
        if (locked()) return;
        clearTimeout(debounce);
        filterPending = false;
        version++;
        controller?.abort();
        controller = null;
        busy = false;
        failed = false;
        ended = false;
        cursor = null;
        tbody.querySelectorAll('.select2-hidden-accessible').forEach(select => window.jQuery(select).select2('destroy'));
        tbody.replaceChildren();
        loaded.clear();
        root.scrollTop = 0;
        controls();
        loadNext();
    }

    function initializeSelect(select) {
        const $ = window.jQuery;
        if (locked() || !select.matches('.roster-select') || $(select).data('select2')) return;
        const resource = select.dataset.resource;
        $(select).select2({ allowClear: true, width: '100%', placeholder: resource === 'driver' ? 'Select driver' : 'Select vehicle',
            ajax: { url: resource === 'driver' ? root.dataset.driversUrl : root.dataset.vehiclesUrl,
                dataType: 'json', delay: 250, cache: true,
                data: params => ({ q: params.term || '', page: params.page || 1 }),
                processResults: data => data }
        });
    }
    window.jQuery(tbody).on('change', '.roster-select', function () {
        if (locked()) return;
        const state = states.get(this.dataset.entryId);
        state[this.dataset.resource] = choice(this);
        state.selected = true;
        loaded.get(this.dataset.entryId).querySelector('.row-check').checked = true;
        controls();
    });
    tbody.addEventListener('change', event => {
        if (locked() || !event.target.matches('.row-check')) return;
        states.get(event.target.value).selected = event.target.checked;
        controls();
    });
    all.addEventListener('change', () => {
        if (locked()) return;
        loaded.forEach((tr, id) => {
            states.get(id).selected = all.checked;
            tr.querySelector('.row-check').checked = all.checked;
        });
        controls();
    });
    more.addEventListener('click', loadNext);
    root.addEventListener('scroll', () => { if (!failed && nearEnd()) loadNext(); }, { passive: true });
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            if (entries.some(entry => entry.isIntersecting) && !failed) loadNext();
        }, { root, rootMargin: '0px 0px 250px 0px' });
        observer.observe(document.getElementById('rosterLoadSentinel'));
    }
    filters.slice(0, 2).forEach(input => input.addEventListener('change', changeFilters));
    filters[2].addEventListener('input', () => {
        if (locked()) return;
        // Invalidate immediately, so an old request cannot append while search is debounced.
        version++;
        controller?.abort();
        busy = false;
        filterPending = true;
        controls();
        clearTimeout(debounce);
        debounce = setTimeout(changeFilters, 300);
    });
    reset.addEventListener('click', () => { if (!locked()) { filters.forEach(input => { input.value = ''; }); changeFilters(); } });

    form.addEventListener('submit', event => {
        if (locked() || selectedCount() === 0) { event.preventDefault(); return; }
        clearTimeout(debounce);
        version++;
        controller?.abort();
        const payload = {};
        states.forEach((state, id) => {
            if (state.selected) payload[id] = { driver_profile_id: state.driver?.id || null, vehicle_id: state.vehicle?.id || null };
        });
        form.querySelectorAll('input[name="roster_payload"]').forEach(input => input.remove());
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = 'roster_payload'; hidden.value = JSON.stringify(payload);
        form.appendChild(hidden);
        saving = true;
        controls();
    });
    document.addEventListener('roster-import-busy', event => {
        importBusy = event.detail.busy;
        if (importBusy) {
            clearTimeout(debounce);
            version++;
            controller?.abort();
            busy = false;
            tbody.querySelectorAll('.select2-hidden-accessible').forEach(select => window.jQuery(select).select2('close'));
        }
        controls();
        if (!importBusy && filterPending) changeFilters();
        else if (!importBusy && !failed && !ended && nearEnd()) loadNext();
    });
    controls();
    loadNext();
})();
