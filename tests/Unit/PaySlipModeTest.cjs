const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../resources/views/payroll/pay-slip/index.blade.php'), 'utf8');
const script = source.split('<script>')[1].split('</script>')[0].replace(/@json\([^\n]*?\)\)/g, '"test"');
new Function(script); // Check all handlers, including the asynchronous ZIP download.
const code = source.slice(source.indexOf('function clearPreview()'), source.indexOf("$('#paySlipType').on"));
const controls = new Map();
function $(key) {
    if (!controls.has(key)) controls.set(key, {
        value: 'individual', hidden: false, properties: {},
        val() { return this.value; },
        toggleClass(name, hidden) { this.hidden = hidden; return this; },
        prop(name, value) { this.properties[name] = value; return this; },
        html(value) { this.content = value; return this; },
    });
    return controls.get(key);
}
let disabledDownload = false;
const context = { $, userSelect: $('#paySlipUser'), previewVersion: 0, paySlipModal: null,
    setDownloadState(enabled) { disabledDownload = !enabled; } };
vm.createContext(context);
vm.runInContext(code, context);
$('#paySlipType').value = 'bulk';
vm.runInContext('updateType()', context);
assert.equal($('#paySlipUserField, #generatePaySlip').hidden, true);
assert.equal($('#downloadPaySlipZip').hidden, false);
assert.equal(context.userSelect.properties.required, false);
assert.equal(disabledDownload, true);
$('#paySlipType').value = 'individual';
vm.runInContext('updateType()', context);
assert.equal($('#paySlipUserField, #generatePaySlip').hidden, false);
assert.equal($('#downloadPaySlipZip').hidden, true);
assert.equal(context.userSelect.properties.required, true);
assert(source.includes("$('#paySlipForm select').select2({ width: '100%' })"));
assert(source.includes("$('#paySlipType').val('individual').trigger('change.select2')"));
console.log('Passed payslip mode, Select2 initialization, reset, and script syntax checks.');
