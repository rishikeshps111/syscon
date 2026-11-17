const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/pf-file-report.js'), 'utf8');

function harness() {
    const nodes = new Map();
    let requests = 0;
    let active;
    let shown = 0;
    const form = { action: '/pf-files', dataset: { defaultYear: '2026' } };
    function node(key) {
        if (nodes.has(key)) return nodes.get(key);
        const state = { html: key === 'button[type="submit"]' ? 'Generate Report' : '', attrs: {}, props: {}, classes: new Set(), handlers: {}, value: '' };
        const element = {
            state,
            find: node,
            select2() { return this; },
            html(value) { if (value === undefined) return state.html; state.html = value; return this; },
            text(value) { state.html = value; return this; },
            attr(name, value) { if (value === undefined) return state.attrs[name]; state.attrs[name] = value; return this; },
            prop(name, value) { if (value === undefined) return state.props[name]; state.props[name] = value; return this; },
            toggleClass(name, enabled) { if (enabled) state.classes.add(name); else state.classes.delete(name); return this; },
            on(event, handler) { state.handlers[event] = handler; return this; },
            serialize() { return 'year=2026&month=9&depot_id=1&role_id=all&generate=1'; },
            val(value) { if (value === undefined) return state.value; state.value = value; return this; },
            trigger() { return this; }
        };
        nodes.set(key, element); return element;
    }
    function $(target) { if (typeof target === 'function') return target(); return node(target === form ? 'form' : target); }
    $.get = (url, data) => {
        assert.equal(url, '/pf-files'); assert.match(data, /generate=1/); requests++;
        const handlers = {};
        const req = {
            done(handler) { handlers.done = handler; return this; },
            fail(handler) { handlers.fail = handler; return this; },
            always(handler) { handlers.always = handler; return this; },
            succeed(response) { handlers.done(response); handlers.always(); },
            reject(message) { handlers.fail({ responseJSON: { message } }, 'error'); handlers.always(); },
            abort() { handlers.fail({}, 'abort'); handlers.always(); }
        };
        active = req; return req;
    };
    node('#downloadPfExcel').attr('aria-disabled', 'true');
    vm.runInNewContext(source, {
        jQuery: $, document: { getElementById(id) { return id === 'pfFileForm' ? form : {}; } },
        bootstrap: { Modal: class { show() { shown++; } } }
    });
    return { node, get request() { return active; }, get count() { return requests; }, get shown() { return shown; },
        submit() { node('form').state.handlers.submit({ preventDefault() {} }); } };
}

test('PF preview enables download only for successful approved report response', () => {
    const ui = harness();
    ui.submit(); ui.submit();
    assert.equal(ui.count, 1); assert.equal(ui.shown, 1);
    assert.equal(ui.node('button[type="submit"]').state.props.disabled, true);
    assert.equal(ui.node('#downloadPfExcel').attr('href'), '#');
    ui.request.succeed({ success: true, html: '<table>Approved PF</table>', download_excel_url: '/pf-files/export?year=2026' });
    assert.equal(ui.node('#pfFileBody').state.html, '<table>Approved PF</table>');
    assert.equal(ui.node('#downloadPfExcel').attr('href'), '/pf-files/export?year=2026');
    assert.equal(ui.node('#downloadPfExcel').attr('aria-disabled'), 'false');
    assert.equal(ui.node('button[type="submit"]').state.props.disabled, false);
    assert.equal(ui.node('button[type="submit"]').state.html, 'Generate Report');
});

test('PF empty and failed responses leave download disabled and restore generate button', () => {
    const ui = harness();
    ui.submit();
    ui.request.succeed({ success: false, html: 'No approved contributors', download_excel_url: null });
    assert.equal(ui.node('#downloadPfExcel').attr('aria-disabled'), 'true');
    assert.equal(ui.node('#pfFileBody').state.html, 'No approved contributors');
    ui.submit(); ui.request.reject('Invalid month');
    assert.equal(ui.node('#pfFileBody').state.html, 'Invalid month');
    assert.equal(ui.node('#downloadPfExcel').attr('href'), '#');
    assert.equal(ui.node('button[type="submit"]').state.props.disabled, false);
    let prevented = false;
    ui.node('#downloadPfExcel').state.handlers.click({ preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
});

test('PF filter changes cancel pending previews and reset removes stale downloads', () => {
    const ui = harness();
    ui.submit(); ui.node('select').state.handlers.change();
    assert.equal(ui.node('#downloadPfExcel').attr('href'), '#');
    assert.equal(ui.node('#pfFileBody').state.html, 'Generate a report to view details.');
    assert.equal(ui.node('button[type="submit"]').state.props.disabled, false);
    ui.submit(); ui.request.succeed({ success: true, html: 'Report', download_excel_url: '/pf-files/export' });
    ui.node('#resetPfFilters').state.handlers.click();
    assert.equal(ui.node('#downloadPfExcel').attr('aria-disabled'), 'true');
    assert.equal(ui.node('#pf_year').state.value, '2026');
    assert.equal(ui.node('#pf_month, #pf_depot, #pf_role').state.value, '');
});
