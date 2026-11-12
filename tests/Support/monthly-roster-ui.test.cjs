// Run with: node --test tests/Support/monthly-roster-ui.test.cjs
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/monthly-roster-import.js'), 'utf8');

function harness(responses) {
    const nodes = new Map();
    const filters = {};
    const requests = [];
    let refreshes = 0;
    const completion = [];
    function node(key) {
        if (nodes.has(key)) return nodes.get(key);
        const classes = new Set();
        const result = {
            disabled: false, value: '', innerHTML: '', textContent: '', attrs: {}, style: {}, dataset: {}, children: [], handlers: {},
            classList: { add(value) { classes.add(value); }, remove(value) { classes.delete(value); }, contains(value) { return classes.has(value); }, toggle(value, force) { if (force) classes.add(value); else classes.delete(value); } },
            addEventListener(event, callback) { this.handlers[event] = callback; },
            setAttribute(name, value) { this.attrs[name] = String(value); }, removeAttribute(name) { delete this.attrs[name]; },
            querySelector: node, querySelectorAll: () => [], reportValidity: () => true,
            appendChild(child) { this.children.push(child); }, replaceChildren() { this.children = []; },
        };
        nodes.set(key, result);
        return result;
    }
    const form = node('monthlyRosterImport');
    form.action = '/trips/1/roster/monthly-import';
    const file = node('[name="roster_file"]');
    const button = node('button[type="submit"]');
    button.innerHTML = 'Preview Import';
    const confirm = node('[data-import-confirm]');
    const previous = node('[data-import-previous]');
    const next = node('[data-import-next]');
    const manual = node('manual');
    const modal = node('rosterImportModal');
    modal.classList.add('show');
    const close = node('close');
    node('[name="_token"]').value = 'csrf';
    node('[data-import-preview]').classList.add('d-none');
    form.querySelectorAll = () => [file, button, confirm, previous, next];
    modal.querySelectorAll = () => [close];
    vm.runInNewContext(source, {
        document: { getElementById: node, querySelectorAll: () => [manual], createElement: tag => ({ tag, textContent: '', children: [], appendChild(child) { this.children.push(child); } }) },
        FormData: class {
            constructor(initial) { this.data = new Map(initial ? [['_token', 'csrf']] : []); }
            set(name, value) { this.data.set(name, value); } get(name) { return this.data.get(name); }
        },
        window: {
            bootstrap: {
                Modal: {
                    getOrCreateInstance: () => ({
                        hide() {
                            let prevented = false;
                            modal.handlers['hide.bs.modal']({ preventDefault() { prevented = true; } });
                            assert.equal(prevented, false, 'Completed imports must be allowed to close');
                            completion.push('hide');
                            modal.classList.remove('show');
                            modal.handlers['hidden.bs.modal']();
                        }
                    })
                }
            },
            location: { reload() { completion.push('reload'); } },
            jQuery: selector => ({ DataTable: () => ({ ajax: { reload(...args) { filters[selector] = args; refreshes++; } } }) })
        },
        fetch: async (url, request) => {
            const step = request.body.get('step');
            if (step !== 'discard') {
                assert.equal(button.disabled, true);
                assert.equal(manual.disabled, true);
                assert.equal(close.disabled, true);
                assert.ok(button.innerHTML.includes('spinner-border') || confirm.innerHTML.includes('spinner-border'));
                assert.equal(form.attrs['aria-busy'], 'true');
                await form.handlers.submit({ preventDefault() { } });
                await confirm.handlers.click();
                let prevented = false;
                modal.handlers['hide.bs.modal']({ preventDefault() { prevented = true; } });
                assert.equal(prevented, true);
            }
            const response = responses[requests.length];
            requests.push({ step, offset: request.body.get('offset'), page: request.body.get('page') });
            if (response instanceof Error) throw response;
            assert.ok(response, 'Unexpected extra request');
            return { ok: !response.errors, json: async () => JSON.parse(JSON.stringify(response)) };
        },
    });
    return { node, requests, filters, completion, run: () => form.handlers.submit({ preventDefault() { } }), confirm: () => confirm.handlers.click(), get refreshes() { return refreshes; } };
}
const emptySummary = { ready_create: 0, ready_update: 0, unchanged: 0, blank: 0, issue: 0, eligible: 0 };
const prepared = { phase: 'preview', token: 'token', previewed: 0, processed: 0, total: 26, created: 0, updated: 0, unchanged: 0, skipped: 0, summary: emptySummary };
const ready = { ...prepared, phase: 'ready', previewed: 26, summary: { ...emptySummary, ready_create: 20, ready_update: 3, unchanged: 1, blank: 1, issue: 1, eligible: 23 } };
const row = { line: 2, trip_code: 'TRP2026#0051-0476', display_date: '08-09-2026', service: '001', current_driver: 'Driver One', proposed_driver: 'Driver Two', current_vehicle: 'BUS01', proposed_vehicle: 'BUS01', vehicle: 'BUS01', status: 'ready_update', reason: '<script>untrusted</script>' };
const readyPage = { ...ready, page: 1, pages: 2, rows: [row] };
const saving = { ...ready, phase: 'saving' };
const partial = { ...saving, processed: 25, created: 20, updated: 2, unchanged: 1, skipped: 2 };
const done = { ...partial, phase: 'done', processed: 26, updated: 3 };
const finalPage = { ...done, page: 1, pages: 2, rows: [{ ...row, outcome: 'updated' }] };
const initialResponses = () => [prepared, { ...prepared, previewed: 25 }, ready, readyPage];

test('preview stays read only then completed import closes modal before page reload', async () => {
    const ui = harness([...initialResponses(), saving, partial, done, finalPage]);
    assert.equal(ui.node('[data-import-confirm]').disabled, true);
    await ui.run();
    assert.deepEqual(ui.requests.map(request => request.step), ['prepare', 'preview', 'preview', 'rows']);
    assert.equal(ui.node('[data-import-confirm]').disabled, false);
    assert.equal(ui.node('[data-import-confirm]').textContent, 'Confirm & Update Eligible Rows (23)');
    const cells = ui.node('[data-import-rows]').children[0].children;
    assert.equal(cells[8].children[0].textContent, 'Ready to update');
    assert.equal(cells[8].children[0].className, 'ri-status ri-status--ready_update');
    assert.equal(cells[1].textContent, 'TRP2026#0051-0476');
    assert.equal(cells[9].children[0].textContent, '<script>untrusted</script>');
    assert.equal(ui.refreshes, 0);
    await ui.confirm();
    assert.deepEqual(ui.requests.map(request => request.step), ['prepare', 'preview', 'preview', 'rows', 'confirm', 'save', 'save']);
    assert.equal(ui.node('[data-import-bar]').attrs['aria-valuenow'], '100');
    assert.equal(ui.node('[data-import-summary]').textContent, 'Import completed: 20 created, 3 updated, 1 unchanged, 2 skipped.');
    assert.equal(ui.node('[data-import-confirm]').disabled, true);
    assert.equal(ui.node('button[type="submit"]').disabled, true);
    assert.equal(ui.node('close').disabled, true);
    assert.deepEqual(ui.filters, {});
    assert.deepEqual(ui.completion, ['hide', 'reload']);
    assert.equal(ui.refreshes, 0);
});

test('preview pagination loads status rows without confirming or resetting filters', async () => {
    const ui = harness([...initialResponses(), { ...readyPage, page: 2, rows: [{ ...row, line: 27, status: 'issue' }] }]);
    await ui.run();
    assert.equal(ui.node('[data-import-next]').disabled, false);
    await ui.node('[data-import-next]').handlers.click();
    assert.equal(ui.requests.at(-1).step, 'rows');
    assert.equal(ui.requests.at(-1).page, 2);
    assert.equal(ui.node('[data-import-rows]').children[0].children[0].textContent, 27);
    assert.equal(ui.node('[data-import-next]').disabled, true);
    assert.equal(ui.node('[data-import-previous]').disabled, false);
    assert.equal(ui.refreshes, 0);
    assert.deepEqual(ui.completion, []);
});

test('file replacement invalidates preview and requires a new preview and confirmation', async () => {
    const ui = harness([...initialResponses(), { phase: 'discarded' }]);
    await ui.run();
    await ui.node('[name="roster_file"]').handlers.change();
    assert.equal(ui.requests.at(-1).step, 'discard');
    assert.equal(ui.node('[data-import-confirm]').disabled, true);
    assert.equal(ui.node('[data-import-preview]').classList.contains('d-none'), true);
    await ui.confirm();
    assert.equal(ui.requests.length, 5);
    assert.equal(ui.refreshes, 0);
    assert.deepEqual(ui.completion, []);
});

test('closing before confirmation discards preview and makes no saving request', async () => {
    const ui = harness([...initialResponses(), { phase: 'discarded' }]);
    await ui.run();
    let prevented = false;
    ui.node('rosterImportModal').handlers['hide.bs.modal']({ preventDefault() { prevented = true; } });
    assert.equal(prevented, false);
    await ui.node('rosterImportModal').handlers['hidden.bs.modal']();
    assert.equal(ui.requests.at(-1).step, 'discard');
    assert.equal(ui.node('[data-import-confirm]').disabled, true);
    assert.equal(ui.requests.some(request => request.step === 'save' || request.step === 'confirm'), false);
});

test('partial save failure restores controls and reports confirmed progress and uncertainty', async () => {
    const ui = harness([...initialResponses(), saving, partial, new Error('Connection lost')]);
    await ui.run();
    await ui.confirm();
    assert.equal(ui.node('button[type="submit"]').disabled, false);
    assert.equal(ui.node('manual').disabled, false);
    assert.equal(ui.node('[data-import-confirm]').disabled, true);
    assert.match(ui.node('[data-import-error]').textContent, /Confirmed: 20 created, 2 updated, 1 unchanged, 2 skipped \(25 \/ 26 rows processed\)/);
    assert.match(ui.node('[data-import-error]').textContent, /last batch may have partially completed/);
    assert.equal(ui.node('[data-import-bar]').classList.contains('progress-bar-animated'), false);
    assert.equal(ui.refreshes, 0);
    assert.deepEqual(ui.completion, []);
});

test('preview errors restore controls and cannot enable confirmation', async () => {
    const ui = harness([{ errors: { roster_file: ['Use the downloaded template.'] } }]);
    await ui.run();
    assert.match(ui.node('[data-import-error]').textContent, /Use the downloaded template/);
    assert.match(ui.node('[data-import-error]').textContent, /No assignments were saved/);
    assert.equal(ui.node('monthlyRosterImport').attrs['aria-busy'], undefined);
    assert.equal(ui.node('button[type="submit"]').innerHTML, 'Preview Import');
    assert.equal(ui.node('[data-import-confirm]').disabled, true);
});

test('no eligible rows disables confirmation after full preview', async () => {
    const noEligible = { ...ready, summary: { ...emptySummary, issue: 26 } };
    const ui = harness([prepared, noEligible, { ...noEligible, page: 1, pages: 2, rows: [{ ...row, status: 'issue' }] }]);
    await ui.run();
    assert.equal(ui.node('[data-import-confirm]').disabled, true);
    await ui.confirm();
    assert.equal(ui.requests.length, 3);
    assert.equal(ui.refreshes, 0);
    assert.deepEqual(ui.completion, []);
});

test('failure to load completed preview cannot enable confirmation', async () => {
    const ui = harness([prepared, ready, new Error('Preview unavailable')]);
    await ui.run();
    assert.equal(ui.node('[data-import-confirm]').disabled, true);
    assert.match(ui.node('[data-import-error]').textContent, /Preview unavailable/);
    assert.equal(ui.refreshes, 0);
    assert.deepEqual(ui.completion, []);
});

test('vehicle-free preview displays Not assigned and permits explicit confirmation', async () => {
    const ui = harness([prepared, ready, { ...readyPage, rows: [{ ...row, proposed_vehicle: 'Not assigned', vehicle: 'Not assigned' }] }]);
    await ui.run();
    assert.equal(ui.node('[data-import-error]').textContent, '');
    assert.equal(ui.node('[data-import-rows]').children[0].children[7].textContent, 'Not assigned');
    assert.equal(ui.node('[data-import-confirm]').disabled, false);
    assert.equal(ui.requests.some(request => request.step === 'save'), false);
});

test('conflict cards show other trip identity duty window and uploaded conflicts as safe text', async () => {
    const conflict = {
        trip_code: 'TRP2026#0099-0007<script>unsafe</script>', parent_trip_code: 'TRP2026#0099',
        service_code: 'ED77', date: '08-09-2026', start_time: '09:00', end_time: '11:00', status: 'in_progress', source: 'running_trip', resource: 'driver'
    };
    const ui = harness([prepared, ready, { ...readyPage, rows: [{ ...row, status: 'issue', conflicts: [conflict, { ...conflict, source: 'upload', row: 27 }] }] }]);
    await ui.run();
    const cells = ui.node('[data-import-rows]').children[0].children;
    assert.equal(cells[8].children[0].className, 'ri-status ri-status--issue');
    const cards = cells[9].children[1].children;
    assert.equal(cards.length, 2);
    assert.equal(cards[0].children[0].textContent, 'Driver assigned to another trip');
    assert.equal(cards[0].children[1].textContent, conflict.trip_code);
    assert.equal(cards[0].children[1].children.length, 0);
    assert.match(cards[0].children[2].textContent, /TRP2026#0099/);
    assert.match(cards[0].children[2].textContent, /Service ED77/);
    assert.match(cards[0].children[2].textContent, /08-09-2026/);
    assert.match(cards[0].children[2].textContent, /09:00–11:00/);
    assert.match(cards[0].children[2].textContent, /In progress/);
    assert.equal(cards[1].children[0].textContent, 'Uploaded row 27');
    assert.deepEqual(ui.completion, []);
});

test('blocked notification helper stays behind the Bootstrap modal instead of covering its controls', () => {
    const layout = fs.readFileSync(path.join(__dirname, '../../resources/views/layouts/script.blade.php'), 'utf8');
    const renderer = layout.slice(layout.indexOf('function renderChromeNotificationButton()'), layout.indexOf('function requestChromeNotificationPermission()'));
    const bootstrapCss = fs.readFileSync(path.join(__dirname, '../../public/assets/vendor/bootstrap/css/bootstrap.min.css'), 'utf8');
    const modalLayer = Number(bootstrapCss.match(/--bs-modal-zindex:\s*(\d+)/)?.[1]
        || bootstrapCss.match(/\.modal\{[^}]*z-index:\s*(\d+)/)?.[1]);
    assert.ok(modalLayer > 0, 'Resolve the actual modal layer from the installed Bootstrap stylesheet');
    const captured = {};
    const button = {
        appendTo() { return this; }, text(value) { captured.message = value; return this; },
        prop(name, value) { captured[name] = value; return this; }
    };
    vm.runInNewContext(`let chromeNotificationButton = null; ${renderer}; renderChromeNotificationButton();`, {
        window: { Notification: {} }, Notification: { permission: 'denied' }, document: { body: {} },
        $: (tag, settings) => { captured.settings = settings; return button; },
    });
    assert.match(captured.message, /Chrome notifications blocked/);
    assert.equal(captured.disabled, true);
    assert.ok(captured.settings.css.zIndex < modalLayer, 'The permission helper must not cover modal action buttons');
});
