const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/bulk-update-sample.js'), 'utf8');

function harness() {
    const handlers = {};
    const scope = { value: 'all', addEventListener(event, handler) { handlers.scope = handler; } };
    const picker = { hidden: false };
    const error = { hidden: true };
    const users = { disabled: false, selectedOptions: [], dataset: { searchUrl: '/bulk-update/drivers/users' } };
    const nodes = { '[name="sample_scope"]': scope, '.js-update-user-picker': picker, '[name="user_ids[]"]': users, '.js-update-selection-error': error };
    const form = { querySelector(selector) { return nodes[selector]; }, addEventListener(event, handler) { handlers.submit = handler; } };
    const state = { config: null, opened: false };
    function $(value) {
        if (typeof value === 'function') return value();
        assert.equal(value, users);
        return { select2(config) { if (config === 'open') state.opened = true; else state.config = config; }, on(event, handler) { handlers.users = handler; } };
    }
    vm.runInNewContext(source, { jQuery: $, document: { querySelector() { return form; } } });
    return { handlers, scope, picker, error, users, state };
}

test('all employees disables selection; choose employees enables it and retains selection', () => {
    const ui = harness();
    assert.equal(ui.picker.hidden, true);
    assert.equal(ui.users.disabled, true);
    ui.scope.value = 'selected'; ui.handlers.scope();
    assert.equal(ui.picker.hidden, false);
    assert.equal(ui.users.disabled, false);
    ui.users.selectedOptions = [{ value: '7' }];
    ui.scope.value = 'all'; ui.handlers.scope();
    assert.equal(ui.users.disabled, true);
    ui.scope.value = 'selected'; ui.handlers.scope();
    assert.equal(ui.users.selectedOptions[0].value, '7');
});

test('search sends term and page to scoped endpoint and preserves pagination results', () => {
    const ui = harness();
    assert.equal(ui.state.config.ajax.url, '/bulk-update/drivers/users');
    const params = ui.state.config.ajax.data({ term: '000123', page: 2 });
    assert.equal(params.q, '000123'); assert.equal(params.page, 2);
    const result = { results: [{ id: 7, text: 'Employee | Code: D7 | Ref: 000123' }], pagination: { more: true } };
    assert.equal(ui.state.config.ajax.processResults(result), result);
});

test('empty selection blocks download; selection and all mode allow download', () => {
    const ui = harness();
    let prevented = 0;
    ui.scope.value = 'selected'; ui.handlers.scope();
    ui.handlers.submit({ preventDefault() { prevented++; } });
    assert.equal(prevented, 1); assert.equal(ui.error.hidden, false); assert.equal(ui.state.opened, true);
    ui.users.selectedOptions = [{ value: '7' }]; ui.handlers.users();
    assert.equal(ui.error.hidden, true);
    ui.handlers.submit({ preventDefault() { prevented++; } });
    assert.equal(prevented, 1);
    ui.scope.value = 'all'; ui.handlers.scope(); ui.users.selectedOptions = [];
    ui.handlers.submit({ preventDefault() { prevented++; } });
    assert.equal(prevented, 1);
});
