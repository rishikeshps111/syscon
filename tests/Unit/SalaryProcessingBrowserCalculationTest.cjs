const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

// Exercise the actual split-editor calculation with a small DOM adapter.
const source = fs.readFileSync(path.join(__dirname, '../../resources/views/salary-processing/form.blade.php'), 'utf8');
const start = source.indexOf('var selected = split.filter');
const end = source.indexOf("$('#salarySplitModal').modal('hide');", start);
assert(start >= 0 && end > start);
const calculations = source.slice(start, end);
const helpers = source.slice(source.indexOf('function isDutyIncentive'), source.indexOf('function escapeHtml'));
const cases = [
    [15000, 30, 15000, 0, 112.5],
    [15001, 30, 15001, 150, 112.51],
    [20000, 30, 20000, 150, 150],
    [20001, 30, 20001, 200, 150.01],
    [15000, 31, 15500, 150, 116.25],
    [20000, 33, 22000, 200, 0],
    [21000, 20, 14000, 0, 105],
    [30000, 20, 20000, 150, 150],
    [21000, 30, 21000, 200, 0],
    [21001, 30, 21001, 200, 0],
    [20999, 30, 20999, 200, 157.49],
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
    const data = { 'calendar-days': 30, 'total-days': days, lop: 10, 'unpaid-leave-days': 0 };
    const ratio = Math.min(days, 30) / 30;
    const pf = Math.round(1320 * ratio * 100) / 100;
    const templateDeduction = 25 * ratio;
    const row = {
        data(key, value) { if (arguments.length === 2) data[key] = value; return data[key]; },
        find(selector) {
            return {
                data() { return {}; },
                text(value) { values[selector] = Number(String(value).replaceAll(',', '')); },
                val(value) { values[selector] = Number(String(value).replaceAll(',', '')); },
                contents() { return { first() { return { replaceWith(value) { values[selector] = Number(String(value).replaceAll(',', '')); } }; } }; },
            };
        },
    };
    vm.runInNewContext(helpers + calculations, { split, row, button: { data() {} } });
    assert.equal(values['.gross-salary'], gross);
    assert.equal(values['.pf'], Math.round(pf));
    assert.equal(values['.professional-tax'], tax);
    assert.equal(values['.esi'], Math.round(esi));
    assert.equal(values['.deduction-input'], Math.round(templateDeduction * 100) / 100);
    const deduction = Math.round((templateDeduction + pf + tax + esi) * 100) / 100;
    assert.equal(values['.total-deduction'], Math.round(deduction));
    assert.equal(values['.net-total'], Math.sign(gross - deduction) * Math.floor(Math.abs(gross - deduction) + 0.5));
    assert.equal(split[0].calculated_amount, 10000 * ratio);
    assert.equal(split[0].amount, 10000);
    assert.equal(split[3].amount, fixed / 30 * Math.max(days - 30, 0));
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
