const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

// Exercise the actual split-editor calculation with a small DOM adapter.
const source = fs.readFileSync(path.join(__dirname, '../../resources/views/salary-processing/form.blade.php'), 'utf8');
const start = source.indexOf('var selected = split.filter');
const end = source.indexOf("$('#salarySplitModal').modal('hide');", start);
assert(start >= 0 && end > start);
const manualHelpers = source.slice(source.indexOf('function manualDeductionTotal'), source.indexOf("$(document).on('blur', '.manual-deduction'"));
const esiRecalculation = source.slice(source.indexOf('function recalculateEmployeeEsi'), source.indexOf("$(document).on('input', '.manual-deduction'"));
const calculations = manualHelpers + esiRecalculation + source.slice(start, end);
const helpers = source.slice(source.indexOf('function isDutyIncentive'), source.indexOf('function escapeHtml'));
const cases = [
    [15000, 30, 15000, 0, 113],
    [15001, 30, 15001, 150, 113],
    [20000, 30, 20000, 150, 150],
    [20001, 30, 20001, 200, 150],
    [15000, 31, 15500, 150, 116],
    [20000, 33, 22000, 200, 165],
    [21000, 20, 14000, 0, 105],
    [30000, 20, 20000, 150, 0],
    [21000, 30, 21000, 200, 158],
    [21001, 30, 21001, 200, 0],
    [20999, 30, 20999, 200, 157],
    [20000, 0, 0, 0, 0],
];

for (const [fixed, days, gross, tax, esi] of cases) {
    const split = [
        { name: ' Basic ', type: 'earning', amount: 10000, selected: true },
        { name: 'VDA', type: 'earning', amount: 1000, selected: true },
        { name: 'Allowance', type: 'earning', amount: fixed - 11000, selected: true },
        { name: ' Duty INCENTIVE ', type: 'earning', amount: 9999, selected: true },
        { name: 'Basic', type: 'earning', amount: 9999, selected: false },
        { name: 'Deduction', type: 'deduction', amount: 25, selected: true },
    ];
    const values = {};
    const manual = [77.77, 123.45, 234.56, 12.34, 45.67];
    const data = { 'pf-applicable': 1, 'calendar-days': 30, 'total-days': days, lop: 10, 'unpaid-leave-days': 0 };
    const ratio = Math.min(days, 30) / 30;
    const pf = Math.round(1320 * ratio * 100) / 100;
    const templateDeduction = 25 * ratio;
    const row = {
        data(key, value) { if (arguments.length === 2) data[key] = value; return data[key]; },
        find(selector) {
            return {
                each(callback) { manual.forEach(value => callback.call({ value })); },
                data() { return {}; },
                text(value) { values[selector] = Number(String(value).replaceAll(',', '')); },
                val(value) { if (selector === 'input[name$="[esi]"]') manual[0] = Number(value); values[selector] = Number(String(value).replaceAll(',', '')); },
                contents() { return { first() { return { replaceWith(value) { values[selector] = Number(String(value).replaceAll(',', '')); } }; } }; },
            };
        },
    };
    vm.runInNewContext(helpers + calculations, { split, row, button: { data() {} } });
    assert.equal(values['.gross-salary'], gross);
    assert.equal(values['.pf'], Math.round(pf));
    assert.equal(values['.professional-tax'], tax);
    assert.equal(manual[0], esi);
    assert.equal(values['.deduction-input'], Math.round(templateDeduction * 100) / 100);
    const deduction = Math.round((templateDeduction + pf + tax + esi + 416) * 100) / 100;
    assert.equal(values['.total-deduction'], Math.round(deduction));
    assert.equal(values['.net-total'], Math.sign(gross - deduction) * Math.floor(Math.abs(gross - deduction) + 0.5));
    manual.splice(0, 5, 0, 0, 0, 0, 0);
    vm.runInNewContext(helpers + manualHelpers + 'recalculateManualDeductions(row);', { row });
    assert.equal(values['.total-deduction'], Math.round(Math.round((templateDeduction + pf + tax) * 100) / 100));
    manual.splice(0, 5, 88.88, 100, 200, 30, 40);
    vm.runInNewContext(helpers + manualHelpers + 'recalculateManualDeductions(row);', { row });
    assert.equal(values['.total-deduction'], Math.round(templateDeduction + pf + tax + 89 + 370));
    const liveNet = gross - Math.round((templateDeduction + pf + tax + 89 + 370) * 100) / 100;
    assert.equal(values['.net-total'], Math.sign(liveNet) * Math.floor(Math.abs(liveNet) + 0.5));
    assert.equal(split[0].calculated_amount, 10000 * ratio);
    assert.equal(split[0].amount, 10000);
    assert.equal(split[3].amount, fixed / 30 * Math.max(days - 30, 0));
    data['pf-applicable'] = 0;
    vm.runInNewContext(helpers + calculations, { split, row, button: { data() {} } });
    assert.equal(values['.pf'], 0);
    const noPfDeduction = Math.round((templateDeduction + tax + esi + 370) * 100) / 100;
    assert.equal(values['.total-deduction'], Math.round(noPfDeduction));
    const noPfNet = gross - noPfDeduction;
    assert.equal(values['.net-total'], Math.sign(noPfNet) * Math.floor(Math.abs(noPfNet) + 0.5));

}

assert(!source.includes('colspan="12"'));
assert(!source.includes('<th class="text-center">Extra Duty Incentive</th>'));
assert(!source.includes('<th class="text-center">Total Earned</th>'));
console.log(`Passed ${cases.length} browser calculation scenarios.`);

for (const [value, expected] of [[1.49, '1'], [1.5, '2'], [1.51, '2'], [-1.49, '-1'], [-1.5, '-2'], [-1.51, '-2'], [1234.5, '1,235']]) {
    const context = { value };
    vm.runInNewContext(helpers + 'result = displayMoney(value);', context);
    assert.equal(context.result, expected);
}

const htmlContext = { row: { user_id: 7, name: 'Employee', salary_split: [], total_earned: 1000, total_deduction: 0 }, escapeHtml: value => String(value ?? '') };
const rowFunction = source.slice(source.indexOf('function rowHtml'), source.indexOf('function reloadUsers'));
vm.runInNewContext(helpers + rowFunction + 'result = rowHtml(row, 0);', htmlContext);
for (const field of ['esi', 'tds', 'salary_advance', 'lwf', 'penalty']) {
    assert(htmlContext.result.includes(`name="items[0][${field}]" value="0"`));
}
console.log('Passed default input rendering and live deduction edit/clear checks.');

for (const [value, expected] of [['150.49', '150'], ['150.50', '151'], ['150.51', '151'], ['', '0'], ['abc12.50', '13']]) {
    const context = { input: { value } };
    vm.runInNewContext(helpers + manualHelpers + 'roundDeductionInput(input);', context);
    assert.equal(context.input.value, expected);
}
const historical = { user_id: 7, name: 'Employee', salary_split: [], esi: 150.49, tds: 100.5,
    salary_advance: 200.51, lwf: 0.49, penalty: 0.5, total_deduction: 500.49, net_total: 9499.51 };
const editContext = { row: historical, escapeHtml: value => String(value ?? '') };
vm.runInNewContext(helpers + rowFunction + 'result = rowHtml(row, 0);', editContext);
assert(editContext.result.includes('data-base-deduction="48"'));
assert(editContext.result.includes('value="150"'));
assert(editContext.result.includes('value="101"'));
assert(editContext.result.includes('value="201"'));
assert(editContext.result.includes('text-center total-deduction">501'));
assert(editContext.result.includes('text-center net-total">9,499'));
assert.equal(historical.total_deduction, 500.49);
console.log('Passed whole-rupee rounding boundaries and historical edit totals.');

const noUanRow = { ...historical, pf_applicable: false, pf: 100, total_deduction: 600.49, net_total: 9399.51 };
const noUanContext = { row: noUanRow, escapeHtml: value => String(value ?? '') };
vm.runInNewContext(helpers + rowFunction + 'result = rowHtml(row, 0);', noUanContext);
assert(noUanContext.result.includes('data-pf-applicable="0"'));
assert(noUanContext.result.includes('text-center pf">0'));
assert(noUanContext.result.includes('text-center total-deduction">501'));
assert(noUanContext.result.includes('text-center net-total">9,499'));
console.log('Passed blank-UAN PF edit presentation checks.');


for (const [fixed, earned, expected] of [[20000, 10000, 75], [21000, 10000, 75], [21001, 10000, 0], [20000, 22000, 165], [21000, 23100, 173], [20000, 0, 0]]) {
    const values = {};
    const manual = [999, 0, 0, 0, 0];
    const data = { basic: fixed, 'total-earned': earned, 'base-deduction': 200 };
    const row = {
        data(key) { return data[key]; },
        find(selector) { return {
            val(value) { if (selector === 'input[name$="[esi]"]') manual[0] = Number(value); values[selector] = value; },
            each(fn) { manual.forEach(value => fn.call({ value })); },
            text(value) { values[selector] = value; },
            contents() { return { first() { return { replaceWith(value) { values[selector] = value; } }; } }; },
        }; },
    };
    vm.runInNewContext(helpers + manualHelpers + esiRecalculation + 'recalculateEmployeeEsi(row);', { row });
    assert.equal(manual[0], expected);
    assert.equal(values['.recalculate-esi-flag'], '1');
    assert.equal(values['.total-deduction'], (200 + expected).toLocaleString('en-US'));
    assert.equal(values['.net-total'], (earned - 200 - expected).toLocaleString('en-US'));
}
assert(!htmlContext.result.includes('Recalculate ESI'));
assert(!source.includes("on('click', '.recalculate-esi'"));
console.log('Passed earned-gross ESI recalculation scenarios.');
