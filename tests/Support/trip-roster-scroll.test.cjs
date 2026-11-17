const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/trip-roster-scroll.js'), 'utf8');

class Element {
    constructor(tag = 'div') {
        this.tagName = tag.toUpperCase(); this.children = []; this.handlers = {}; this.dataset = {};
        this.attrs = {}; this.className = ''; this.checked = false; this.disabled = false;
        this.hidden = false; this.textContent = ''; this._value = ''; this.selectedIndex = 0;
    }
    get value() { return this.tagName === 'SELECT' ? this.children[this.selectedIndex]?.value || '' : this._value; }
    set value(value) {
        this._value = String(value);
        if (this.tagName === 'SELECT') this.selectedIndex = this.children.findIndex(option => option.value === String(value));
    }
    get options() { return this.children; }
    appendChild(child) {
        child.parent = this; this.children.push(child);
        if (this.tagName === 'SELECT' && child.selected) this.selectedIndex = this.children.length - 1;
        return child;
    }
    replaceChildren() { this.children = []; }
    remove() { this.parent.children = this.parent.children.filter(child => child !== this); }
    matches(selector) {
        if (selector.startsWith('.')) return this.className.split(' ').includes(selector.slice(1));
        if (selector === 'input[name="roster_payload"]') return this.tagName === 'INPUT' && this.name === 'roster_payload';
        return this.tagName.toLowerCase() === selector;
    }
    querySelectorAll(selector) {
        const selectors = selector.split(',').map(item => item.trim());
        return this.children.flatMap(child => [
            ...(selectors.some(item => child.matches(item)) ? [child] : []), ...child.querySelectorAll(selector),
        ]);
    }
    querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
    setAttribute(name, value) { this.attrs[name] = String(value); }
    addEventListener(name, callback) { (this.handlers[name] ??= []).push(callback); }
}

const flush = async () => { for (let index = 0; index < 12; index++) await Promise.resolve(); };
const deferred = () => { let resolve; const promise = new Promise(done => { resolve = done; }); return { promise, resolve }; };
const row = id => ({ id, date_text: '14 Nov 2026', trip_code: `TRIP-${id}`, ser: 'ES01', driver: null, vehicle: null });

function harness(responses) {
    const nodes = new Map();
    const get = id => {
        if (!nodes.has(id)) nodes.set(id, new Element());
        return nodes.get(id);
    };
    const root = get('rosterScroll');
    root.dataset = { rowsUrl: '/trips/1/roster/rows', driversUrl: '/trips/1/roster/drivers', vehiclesUrl: '/trips/1/roster/vehicles' };
    Object.assign(root, { scrollTop: 0, clientHeight: 200, scrollHeight: 10000 });
    const tbody = get('tbody');
    const form = get('rosterUpdateForm');
    const submit = new Element('button'); form.appendChild(submit);
    const originalQuery = form.querySelector.bind(form);
    form.querySelector = selector => selector === 'button[type="submit"]' ? submit : originalQuery(selector);
    const requests = [], selectConfigs = [], timers = new Map();
    const document = new Element();
    document.getElementById = get;
    document.querySelector = selector => selector === '#rosterTable tbody' ? tbody : null;
    document.createElement = tag => new Element(tag);
    const window = new Element();
    window.location = { href: 'https://example.test/trips/1/roster' };
    let changeSelect;
    window.jQuery = element => ({
        data: () => element.select2Config,
        on(name, selector, callback) { changeSelect = callback; return this; },
        select2(config) {
            if (typeof config === 'object') {
                element.select2Config = config; element.className += ' select2-hidden-accessible'; selectConfigs.push(config);
            } else if (config === 'destroy') { delete element.select2Config; element.className = element.className.replace(' select2-hidden-accessible', ''); }
            return this;
        },
    });
    const Option = function(text, value, defaultSelected = false, selected = false) {
        const option = new Element('option'); option.textContent = text; option.value = value; option.selected = selected; return option;
    };
    vm.runInNewContext(source, {
        document, window, Option, URL, AbortController,
        requestAnimationFrame() {},
        setTimeout(callback) { const id = timers.size + 1; timers.set(id, callback); return id; },
        clearTimeout(id) { timers.delete(id); },
        fetch: async (url, options) => {
            const index = requests.length;
            requests.push({ url: String(url), options });
            const data = await responses[index];
            assert.ok(data, 'Unexpected extra feed request');
            if (data instanceof Error) throw data;
            return { ok: !data.errors, json: async () => data };
        },
    });
    return {
        get, tbody, form, root, submit, requests, selectConfigs, document, window, Option,
        row(id) { return tbody.children.find(element => element.dataset.entryId === String(id)); },
        async fire(element, name, event = {}) { for (const callback of element.handlers[name] || []) await callback({ preventDefault() {}, ...event }); await flush(); },
        async change(select) { changeSelect.call(select); await flush(); },
        async timers() { for (const callback of timers.values()) callback(); timers.clear(); await flush(); },
    };
}

test('renders Select2 immediately for every appended row while keeping scoped remote option searches', async () => {
    const ui = harness([{ rows: [row(1), row(2)], next_cursor: 'next' }, { rows: [row(2), row(3)], next_cursor: null }]);
    await flush();
    assert.deepEqual(ui.tbody.children.map(item => item.dataset.entryId), ['1', '2']);
    assert.equal(ui.selectConfigs.length, 4);
    assert.equal(ui.row(1).querySelector('.roster-driver-select').select2Config.ajax.url, '/trips/1/roster/drivers');
    assert.equal(ui.row(1).querySelector('.roster-vehicle-select').select2Config.ajax.url, '/trips/1/roster/vehicles');
    await ui.fire(ui.get('rosterLoadMore'), 'click');
    assert.deepEqual(ui.tbody.children.map(item => item.dataset.entryId), ['1', '2', '3']);
    assert.equal(new URL(ui.requests[1].url).searchParams.get('cursor'), 'next');
    assert.equal(ui.get('rosterLoadMore').hidden, true);
    assert.equal(ui.selectConfigs.length, 6);
    const driver = ui.row(1).querySelector('.roster-driver-select');
    assert.equal(driver.select2Config.ajax.data({ term: 'D01', page: 2 }).q, 'D01');
    assert.equal(driver.select2Config.ajax.data({ term: 'D01', page: 2 }).page, 2);
    assert.ok(ui.row(3).querySelector('.roster-driver-select').select2Config);
    assert.ok(ui.row(3).querySelector('.roster-vehicle-select').select2Config);
});

test('shows an animated loading indicator and accessible text only while a batch is pending', async () => {
    const pending = deferred();
    const ui = harness([pending.promise]);
    assert.equal(ui.get('rosterLoadingSpinner').hidden, false);
    assert.equal(ui.get('rosterLoadStatus').className, 'visually-hidden');
    assert.equal(ui.get('rosterLoadMore').hidden, true);
    pending.resolve({ rows: [row(1)], next_cursor: 'next' });
    await flush();
    assert.equal(ui.get('rosterLoadingSpinner').hidden, true);
    assert.equal(ui.get('rosterLoadStatus').className, '');
    assert.match(ui.get('rosterLoadStatus').textContent, /1 duties loaded/);
    assert.equal(ui.get('rosterLoadMore').hidden, false);
});

test('scrolling near the end loads one page at a time and stops after the final cursor', async () => {
    const second = deferred();
    const ui = harness([{ rows: [row(1)], next_cursor: 'next' }, second.promise]);
    await flush();
    ui.root.scrollHeight = 300;
    await ui.fire(ui.root, 'scroll');
    await ui.fire(ui.root, 'scroll');
    assert.equal(ui.requests.length, 2);
    assert.equal(ui.get('rosterLoadMore').disabled, true);
    second.resolve({ rows: [row(2)], next_cursor: null });
    await flush();
    assert.deepEqual(ui.tbody.children.map(item => item.dataset.entryId), ['1', '2']);
    assert.equal(ui.get('rosterLoadMore').hidden, true);
    await ui.fire(ui.root, 'scroll');
    assert.equal(ui.requests.length, 2);
});

test('keeps edited selected duties across filters and loaded-only select-all submits offscreen selection', async () => {
    const ui = harness([{ rows: [row(1)], next_cursor: null }, { rows: [row(2)], next_cursor: null }, { rows: [row(1)], next_cursor: null }]);
    await flush();
    const driver = ui.row(1).querySelector('.roster-driver-select');
    driver.appendChild(new ui.Option('Chosen Driver', '9', true, true));
    await ui.change(driver);
    assert.equal(ui.row(1).querySelector('.row-check').checked, true);
    ui.get('dateFromFilter').value = '2026-11-15';
    await ui.fire(ui.get('dateFromFilter'), 'change');
    assert.equal(new URL(ui.requests[1].url).searchParams.get('date_from'), '2026-11-15');
    ui.get('checkAll').checked = true; await ui.fire(ui.get('checkAll'), 'change');
    assert.match(ui.get('rosterSelectionCount').textContent, /^2 duties selected/);
    ui.get('checkAll').checked = false; await ui.fire(ui.get('checkAll'), 'change');
    assert.match(ui.get('rosterSelectionCount').textContent, /^1 duties selected/);
    await ui.fire(ui.get('resetRosterFilters'), 'click');
    assert.equal(ui.row(1).querySelector('.row-check').checked, true);
    assert.equal(ui.row(1).querySelector('.roster-driver-select').value, '9');
    await ui.fire(ui.form, 'submit');
    const payload = JSON.parse(ui.form.querySelector('input[name="roster_payload"]').value);
    assert.deepEqual(payload, { '1': { driver_profile_id: '9', vehicle_id: null } });
    assert.equal(ui.submit.disabled, true);
    assert.equal(ui.get('dateFromFilter').disabled, true);
});

test('invalidates an old feed immediately during search debounce and ignores its late response', async () => {
    const old = deferred();
    const ui = harness([old.promise, { rows: [row(2)], next_cursor: null }]);
    ui.get('serFilter').value = 'EX';
    await ui.fire(ui.get('serFilter'), 'input');
    assert.equal(ui.requests[0].options.signal.aborted, true);
    await ui.timers();
    assert.equal(new URL(ui.requests[1].url).searchParams.get('ser'), 'EX');
    old.resolve({ rows: [row(1)], next_cursor: 'old' });
    await flush();
    assert.deepEqual(ui.tbody.children.map(item => item.dataset.entryId), ['2']);
    assert.equal(ui.get('rosterLoadMore').hidden, true);
});

test('failed feed exposes retry and resumes without duplicate rows or losing selected edits', async () => {
    const ui = harness([{ rows: [row(1)], next_cursor: 'next' }, { errors: { cursor: ['Temporary error'] } }, { rows: [row(2)], next_cursor: null }]);
    await flush();
    const checkbox = ui.row(1).querySelector('.row-check'); checkbox.checked = true;
    await ui.fire(ui.tbody, 'change', { target: checkbox });
    await ui.fire(ui.get('rosterLoadMore'), 'click');
    assert.match(ui.get('rosterLoadStatus').textContent, /Temporary error/);
    assert.equal(ui.get('rosterLoadMore').textContent, 'Retry loading duties');
    await ui.fire(ui.get('rosterLoadMore'), 'click');
    assert.equal(new URL(ui.requests[2].url).searchParams.get('cursor'), 'next');
    assert.deepEqual(ui.tbody.children.map(item => item.dataset.entryId), ['1', '2']);
    assert.equal(ui.row(1).querySelector('.row-check').checked, true);
});

test('bulk import locks editing and discards an in-flight feed response until the import releases controls', async () => {
    const old = deferred();
    const ui = harness([old.promise, { rows: [row(2)], next_cursor: null }]);
    await ui.fire(ui.document, 'roster-import-busy', { detail: { busy: true } });
    assert.equal(ui.requests[0].options.signal.aborted, true);
    assert.equal(ui.get('serFilter').disabled, true);
    old.resolve({ rows: [row(1)], next_cursor: null });
    await flush();
    assert.equal(ui.tbody.children.length, 0);
    await ui.fire(ui.document, 'roster-import-busy', { detail: { busy: false } });
    assert.equal(ui.get('serFilter').disabled, false);
    await ui.fire(ui.get('rosterLoadMore'), 'click');
    assert.deepEqual(ui.tbody.children.map(item => item.dataset.entryId), ['2']);
});
