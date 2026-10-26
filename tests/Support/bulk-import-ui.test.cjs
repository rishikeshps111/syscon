// Run with: node --test tests/Support/bulk-import-ui.test.cjs
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../resources/views/bulk-import/form.blade.php'), 'utf8')
    .match(/<script>([\s\S]*?)<\/script>/)[1];

function harness(responses) {
    const nodes = new Map();
    let submit;
    let requestCount = 0;
    let redirect;
    const snapshots = [];
    const form = { action: '/bulk-import/drivers', querySelector: () => ({ value: 'csrf' }) };
    function node(key) {
        if (nodes.has(key)) return nodes.get(key);
        const state = { html: key === 'button[type="submit"]' ? '<span>Import</span>' : '', text: '', props: {}, attrs: {}, styles: {}, classes: new Set() };
        const element = {
            state,
            find: node,
            on(event, handler) { if (key === '.js-chunk-import') submit = handler; return this; },
            prop(name, value) { if (value === undefined) return state.props[name]; state.props[name] = value; return this; },
            html(value) { if (value === undefined) return state.html; state.html = value; return this; },
            text(value) { if (value === undefined) return state.text; state.text = value; return this; },
            attr(name, value) { state.attrs[name] = value; return this; },
            removeAttr(name) { delete state.attrs[name]; return this; },
            css(name, value) { state.styles[name] = value; return this; },
            addClass(value) { state.classes.add(value); return this; },
            removeClass(value) { state.classes.delete(value); return this; },
            empty() { state.text = ''; return this; },
        };
        nodes.set(key, element);
        return element;
    }
    const $ = value => {
        if (typeof value === 'function') return value();
        return node(value === form ? 'form' : value);
    };
    vm.runInNewContext(source, {
        $, FormData: class { set() {} },
        window: { location: { assign(url) { redirect = url; } } },
        fetch: async () => {
            assert.equal(node('button[type="submit"]').state.props.disabled, true);
            assert.match(node('button[type="submit"]').state.html, /spinner-border/);
            assert.match(node('button[type="submit"]').state.html, /Importing/);
            assert.equal(node('form').state.attrs['aria-busy'], 'true');
            snapshots.push(node('.js-import-status').state.text);
            const index = requestCount++;
            // A second submit while a request is pending must not start another import.
            await submit.call(form, { preventDefault() {} });
            const result = responses[index];
            if (result instanceof Error) throw result;
            assert.ok(result, 'Unexpected extra request');
            return { ok: !result.errors, json: async () => result };
        },
    });
    return {
        run: () => submit.call(form, { preventDefault() {} }), node, snapshots,
        get requestCount() { return requestCount; }, get redirect() { return redirect; },
    };
}

const prepared = { phase: 'validate', token: 'token', processed: 0, total: 26, validated: 0, saved: 0, created: 0, updated: 0 };
const validated = { ...prepared, phase: 'save', processed: 0, validated: 26 };
const saved = { ...validated, processed: 25, saved: 25, created: 10, updated: 15 };

test('spinner remains active through batches and redirect; progress and totals reach completion', async () => {
    const ui = harness([
        prepared,
        { ...prepared, processed: 25, validated: 25 },
        validated,
        saved,
        { ...saved, phase: 'done', processed: 26, saved: 26, created: 11, message: 'Import completed: 11 rows created, 15 rows updated', redirect: '/drivers' },
    ]);
    await ui.run();
    assert.equal(ui.requestCount, 5);
    assert.equal(ui.redirect, '/drivers');
    assert.equal(ui.node('button[type="submit"]').state.props.disabled, true);
    assert.equal(ui.node('.js-import-bar').state.attrs['aria-valuenow'], 100);
    assert.equal(ui.node('.js-import-counts').state.text, '11 rows created, 15 rows updated');
    assert.deepEqual(ui.snapshots, ['Reading file...', 'Validating 0 / 26 rows', 'Validating 25 / 26 rows', 'Saving 0 / 26 rows', 'Saving 25 / 26 rows']);
});

test('validation failure restores button and reports no saved rows', async () => {
    const ui = harness([prepared, { errors: { csv_file: ['Row 2: invalid email.'] } }]);
    await ui.run();
    assert.equal(ui.node('button[type="submit"]').state.props.disabled, false);
    assert.equal(ui.node('button[type="submit"]').state.html, '<span>Import</span>');
    assert.match(ui.node('.js-import-errors').state.text, /Row 2: invalid email\./);
    assert.match(ui.node('.js-import-errors').state.text, /No records were saved/);
    assert.equal(ui.node('.js-import-bar').state.classes.has('progress-bar-animated'), false);
    assert.equal(ui.redirect, undefined);
});

test('save network failure retains confirmed totals and restores button', async () => {
    const ui = harness([prepared, validated, saved, new Error('Connection lost')]);
    await ui.run();
    assert.equal(ui.node('button[type="submit"]').state.props.disabled, false);
    assert.match(ui.node('.js-import-errors').state.text, /25 rows were confirmed saved: 10 created, 15 updated/);
    assert.match(ui.node('.js-import-errors').state.text, /last batch may also have completed/);
    assert.equal(ui.node('.js-import-counts').state.text, '10 rows created, 15 rows updated');
    assert.equal(ui.node('form').state.attrs['aria-busy'], undefined);
});

test('reading failure restores button before any rows are saved', async () => {
    const ui = harness([new Error('Upload failed')]);
    await ui.run();
    assert.equal(ui.node('button[type="submit"]').state.props.disabled, false);
    assert.match(ui.node('.js-import-errors').state.text, /Upload failed\nNo records were saved/);
});
