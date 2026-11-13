<?php

namespace Tests\Integration;

use App\Models\SalaryProcessing;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalaryReportRegisterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'activitylog.enabled' => false]);
        DB::purge('sqlite');
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_05_12_031408_create_permission_tables.php'))->up();
        Schema::table('users', fn (Blueprint $t) => $t->string('ref_code')->nullable());
        foreach (['depots', 'locations', 'designations'] as $table) {
            Schema::create($table, function (Blueprint $t) { $t->id(); $t->string('name'); });
        }
        Schema::table('depots', fn (Blueprint $t) => $t->string('short_name')->nullable());
        DB::table('depots')->insert(['id' => 1, 'name' => 'Register Depot', 'short_name' => 'NLG']);
        foreach (['staff_profiles', 'driver_profiles', 'housekeeping_profiles', 'controller_profiles', 'supervisor_profiles'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('depot_id');
                $t->unsignedBigInteger('location_id')->nullable(); $t->unsignedBigInteger('designation_id')->nullable();
                $t->string('ifsc_code')->nullable(); $t->string('uan')->nullable();
            });
        }
        Schema::create('salary_processings', function (Blueprint $t) {
            $t->id(); $t->integer('year'); $t->integer('month'); $t->unsignedBigInteger('depot_id');
            $t->unsignedBigInteger('role_id')->nullable(); $t->unsignedBigInteger('approved_by')->nullable();
            $t->string('status'); $t->string('remarks')->nullable(); $t->timestamps();
        });
        (require database_path('migrations/2026_06_17_000002_create_salary_processing_items_table.php'))->up();
        (require database_path('migrations/2026_09_24_000002_add_consolidated_attendance_to_salary_items.php'))->up();
        (require database_path('migrations/2026_09_28_000006_add_statutory_salary_fields_to_salary_processing_items.php'))->up();
        (require database_path('migrations/2026_11_13_000002_add_manual_deductions_to_salary_processing_items.php'))->up();
        (require database_path('migrations/2026_11_13_000004_add_employer_esi_to_salary_processing_items.php'))->up();
        $this->withoutMiddleware([
            \Illuminate\Auth\Middleware\Authenticate::class,
            \Spatie\Permission\Middleware\PermissionMiddleware::class,
            \App\Http\Middleware\TrackStaffUserLog::class,
        ]);
        Mail::fake();
    }

    private function payroll(string $status = 'Approved'): SalaryProcessing
    {
        $payroll = SalaryProcessing::create(['year' => 2026, 'month' => 9, 'depot_id' => 1, 'status' => $status]);
        foreach (['Driver', 'Housekeeping', 'Controller', 'Supervisor', 'Staff'] as $index => $role) {
            $user = User::factory()->create(['ref_code' => '000'.($index + 1), 'code' => 'NOT-REF-'.$index]);
            $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
            $table = match ($role) {
                'Driver' => 'driver_profiles', 'Housekeeping' => 'housekeeping_profiles',
                'Controller' => 'controller_profiles', 'Supervisor' => 'supervisor_profiles', default => 'staff_profiles',
            };
            DB::table($table)->insert(['user_id' => $user->id, 'depot_id' => 1, 'ifsc_code' => ' sbin0001234 ', 'uan' => '100000000001']);
            $payroll->items()->create([
                'user_id' => $user->id, 'basic_salary' => 20000, 'total_attendance_days' => 20,
                'present_days' => 16, 'week_off_days' => 4, 'total_earned' => 13333.33,
                'pf' => 880, 'esi' => 150, 'total_deduction' => 1030, 'net_total' => 12303.33,
                'salary_split' => [
                    ['name' => 'Basic', 'type' => 'earning', 'amount' => 10000],
                    ['name' => 'VDA', 'type' => 'earning', 'amount' => 1000],
                    ['name' => 'Special Allowance', 'type' => 'earning', 'amount' => 9000],
                ],
            ]);
        }
        return $payroll;
    }

    private function filters(): array
    {
        return ['year' => 2026, 'month' => 9, 'depot_id' => 1, 'role_id' => 'all'];
    }

    public function test_approved_depot_payroll_generates_all_roles_and_employee_role_filters(): void
    {
        $payroll = $this->payroll();
        $url = '/salary-reports?'.http_build_query($this->filters() + ['generate' => 1]);
        $this->getJson($url, ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('html', fn ($html) => str_contains($html, '0001') && str_contains($html, '0005') && !str_contains($html, 'NOT-REF'));
        $filters = array_replace($this->filters(), ['role_id' => Role::where('name', 'Driver')->value('id')]);
        $this->getJson('/salary-reports?'.http_build_query($filters + ['generate' => 1]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('html', fn ($html) => str_contains($html, '0001') && !str_contains($html, '0005'));
        $this->getJson('/salary-reports/'.$payroll->items()->first()->id)->assertOk()->assertJsonPath('user.code', '0001');
        $response = $this->get('/salary-reports/export?'.http_build_query($this->filters()))->assertOk();
        // The export binder is global in this PhpSpreadsheet version; use the normal reader binder for inspection.
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $book->getActiveSheet();
        $this->assertSame('BK', $sheet->getHighestDataColumn());
        $this->assertSame('EMPLOYEE DATA', $sheet->getCell('A3')->getValue());
        $this->assertSame('FIXED SALARY', $sheet->getCell('V3')->getValue());
        $this->assertSame(array_map(fn ($v) => $v === '' ? null : $v, \App\Support\WageRegister::headings()), $sheet->rangeToArray('A4:BK4', null, true, false)[0]);
        $this->assertSame(array_fill(0, 63, null), $sheet->rangeToArray('A2:BK2', null, true, false)[0]);
        $this->assertEquals(1, $sheet->getCell('A5')->getValue());
        $this->assertEquals(5, $sheet->getCell('A9')->getValue());
        $this->assertSame('A4:BK9', $sheet->getAutoFilter()->getRange());
        foreach ($sheet->getMergeCells() as $range) {
            $this->assertDoesNotMatchRegularExpression('/[A-Z]+[5-9]:|:[A-Z]+[5-9]$/', $range);
        }
        $this->assertSame('Operations', $sheet->getCell('O5')->getValue());
        $this->assertSame('State Bank of India', $sheet->getCell('P5')->getValue());
        $this->assertSame('SBIN0001234', $sheet->getCell('R5')->getValue());
        $this->assertContains($sheet->getCell('T5')->getValue(), ['NLGDriver', 'NLGAdmin']);
        $this->assertSame('s', $sheet->getCell('B5')->getDataType());
        $this->assertStringStartsWith('000', $sheet->getCell('B5')->getValue());
        $this->assertEquals(0, $sheet->getCell('AZ5')->getValue());
        $this->assertSame('n', $sheet->getCell('AZ5')->getDataType());
        $this->assertEquals(13333.33, $sheet->getCell('AW5')->getValue());
        $this->assertSame('#,##0', $sheet->getStyle('AW5')->getNumberFormat()->getFormatCode());
        $this->assertSame('13,333', $sheet->getCell('AW5')->getFormattedValue());
        $book->disconnectWorksheets();
        $this->get('/salary-reports/pdf?'.http_build_query($this->filters()))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        config(['mail.salary_report_to' => 'register@example.test']);
        $this->postJson('/salary-reports/send-mail', $this->filters(), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('success', true);
        Mail::assertSent(\App\Mail\SalaryReportMail::class, 1);
    }

    public function test_manual_deductions_persist_and_populate_report_exports(): void
    {
        $payroll = $this->payroll();
        $item = $payroll->items()->first()->fresh();
        foreach (['tds', 'salary_advance', 'lwf', 'penalty'] as $field) {
            $this->assertSame('0.00', $item->{$field});
        }
        $item->update(['tds' => 123.45, 'salary_advance' => 234.56, 'lwf' => 12.34, 'penalty' => 45.67,
            'total_deduction' => 1446.02, 'net_total' => 11887.31, 'net_salary' => 11887.31]);
        $row = \App\Support\WageRegister::row($item->fresh(), 0);
        $this->assertSame([123.45, 234.56, 12.34, 45.67, 1446.02, 11887.31], array_slice($row, 53, 6));
        $this->getJson('/salary-reports/'.$item->id)->assertOk()
            ->assertJsonPath('register.row.53', 123.45)->assertJsonPath('register.row.54', 234.56)
            ->assertJsonPath('register.row.55', 12.34)->assertJsonPath('register.row.56', 45.67)
            ->assertJsonPath('salary.deduction', 1446.02)->assertJsonPath('salary.net_salary', 11887.31);
        $response = $this->get('/salary-reports/export?'.http_build_query($this->filters()))->assertOk();
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $book->getActiveSheet();
        $exportRow = null;
        for ($r = 5; $r <= $sheet->getHighestDataRow(); $r++) {
            if ($sheet->getCell('B'.$r)->getValue() === $item->user->ref_code) $exportRow = $r;
        }
        $this->assertNotNull($exportRow);
        foreach (['BB' => 123.45, 'BC' => 234.56, 'BD' => 12.34, 'BE' => 45.67, 'BF' => 1446.02, 'BG' => 11887.31] as $column => $amount) {
            $this->assertEquals($amount, $sheet->getCell($column.$exportRow)->getValue());
        }
        $book->disconnectWorksheets();
        $item->update(['tds' => 0, 'salary_advance' => 0, 'lwf' => 0, 'penalty' => 0]);
        foreach (['tds', 'salary_advance', 'lwf', 'penalty'] as $field) {
            $this->assertSame('0.00', $item->fresh()->{$field});
        }
    }

    public function test_processing_save_reopen_and_clear_manual_deductions(): void
    {
        $payroll = $this->payroll();
        $item = $payroll->items()->latest('id')->first();
        $split = $item->salary_split;
        foreach ($split as $index => &$component) $component['id'] = $index + 1;
        unset($component);
        $item->update(['salary_split' => $split]);
        (require database_path('migrations/2026_09_24_000003_add_actual_worked_days_to_salary_items.php'))->up();
        (require database_path('migrations/2026_09_24_000004_add_salary_calculation_fields.php'))->up();
        (require database_path('migrations/2026_09_09_000001_create_attendance_consolidate_tables.php'))->up();
        Schema::create('salary_templates', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('role_id'); $t->unsignedBigInteger('designation_id')->nullable();
        });
        Schema::create('leaves', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('leave_type_id')->nullable();
            $t->string('status'); $t->date('leave_date')->nullable(); $t->date('from_date')->nullable();
            $t->date('to_date')->nullable(); $t->decimal('number_of_days');
        });
        Schema::create('leave_types', function (Blueprint $t) { $t->id(); $t->string('leave_category'); });
        $import = \App\Models\AttendanceConsolidateImport::create(['year' => 2026, 'month' => 9, 'depot_id' => 1,
            'depot_name' => 'Register Depot', 'original_filename' => 'test.xlsx', 'file_path' => 'test.xlsx',
            'file_checksum' => str_repeat('a', 64), 'employee_count' => 1, 'imported_by_name' => 'Test', 'imported_at' => now()]);
        $import->rows()->create(['user_id' => $item->user_id, 'employee_ref_code' => $item->user->ref_code,
            'employee_name' => $item->user->name, 'present_days' => 16, 'week_off_days' => 4,
            'absent_days' => 4, 'total_days' => 20, 'source_row' => 2]);
        $controller = new \App\Http\Controllers\SalaryProcessingController;
        $this->assertNull($item->fresh()->employer_esi);
        $this->assertEquals(650, \App\Support\WageRegister::row($item->fresh(), 0)[61]);
        $sync = new \ReflectionMethod($controller, 'syncItems');
        $data = ['year' => 2026, 'month' => 9, 'depot_id' => 1, 'attendance_consolidate_import_id' => $import->id];
        foreach ([[123.45, 234.56, 12.34, 45.67, 1446.0, 11887.33], [0, 0, 0, 0, 1030.0, 12303.33]] as $values) {
            $manual = array_combine(['tds', 'salary_advance', 'lwf', 'penalty'], array_slice($values, 0, 4));
            $sync->invoke($controller, $payroll, $data + ['items' => [$manual + ['user_id' => $item->user_id,
                'selected_components' => [1, 2, 3], 'unauthorized_leaves' => 0]]]);
            $saved = $item->fresh();
            $this->assertSame('433.33', $saved->employer_esi);
            $this->assertSame(433.33, \App\Support\WageRegister::row($saved, 0)[61]);
            $this->assertEquals($values[4], $saved->total_deduction);
            $this->assertEquals($values[5], $saved->net_total);
            $this->assertSame($saved->net_total, $saved->net_salary);
            $row = (new \ReflectionMethod($controller, 'storedRow'))->invoke($controller, $saved);
            foreach ($manual as $field => $amount) $this->assertEquals(round($amount), $row[$field]);
            $html = view('salary-processing.partials.rows', ['rows' => [$row]])->render();
            foreach ($manual as $field => $amount) {
                $this->assertStringContainsString('name="items[0]['.$field.']" value="'.number_format($amount, 0, '.', '').'"', $html);
            }
        }
        foreach ([[['esi' => 223.45], 223.0], [[], 223.0], [['esi' => 0], 0.0], [['esi' => null], 0.0], [['esi' => ''], 0.0], [['esi' => 999999, 'recalculate_esi' => true, 'total_earned' => 999999, 'basic_salary' => 999999], 100.0]] as [$input, $expectedEsi]) {
            $sync->invoke($controller, $payroll, $data + ['items' => [$input + ['user_id' => $item->user_id,
                'selected_components' => [1, 2, 3], 'unauthorized_leaves' => 0]]]);
            $saved = $item->fresh();
            $this->assertEquals($expectedEsi, $saved->esi);
            $this->assertEquals(round(880 + $expectedEsi, 2), $saved->total_deduction);
            $this->assertEquals(round(12453.33 - $expectedEsi, 2), $saved->net_total);
            $this->assertSame($saved->net_total, $saved->net_salary);
            $row = (new \ReflectionMethod($controller, 'storedRow'))->invoke($controller, $saved);
            $this->assertSame($expectedEsi, $row['esi']);
            $html = view('salary-processing.partials.rows', ['rows' => [$row]])->render();
            $this->assertStringContainsString('name="items[0][esi]" value="'.number_format($expectedEsi, 0, '.', '').'"', $html);
            $register = \App\Support\WageRegister::row($saved, 0);
            $this->assertSame($expectedEsi, $register[52]);
        }
        // A new employee row from a legacy payload still receives automatic ESI.
        $item->delete();
        Schema::create('salary_components', function (Blueprint $t) { $t->id(); $t->string('component_name'); $t->string('type'); });
        Schema::create('salary_template_items', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('salary_template_id'); $t->unsignedBigInteger('salary_component_id'); $t->decimal('amount');
        });
        DB::table('salary_templates')->insert(['id' => 1, 'role_id' => Role::where('name', 'Staff')->value('id')]);
        DB::table('salary_components')->insert(['id' => 1, 'component_name' => 'Basic', 'type' => 'earning']);
        DB::table('salary_template_items')->insert(['salary_template_id' => 1, 'salary_component_id' => 1, 'amount' => 20000]);
        $sync->invoke($controller, $payroll, $data + ['items' => [['user_id' => $item->user_id, 'selected_components' => [1]]]]);
        $this->assertSame('100.00', $payroll->items()->first()->esi);
        $saved = $payroll->items()->first();
        $this->assertEquals(1600, $saved->pf);
        DB::table('staff_profiles')->where('user_id', $item->user_id)->update(['uan' => null]);
        $this->assertEquals(1600, $saved->fresh()->pf);
        $controller = new \App\Http\Controllers\SalaryProcessingController;
        $editRow = (new \ReflectionMethod($controller, 'storedRow'))->invoke($controller, $saved->fresh());
        $this->assertFalse($editRow['pf_applicable']);
        $preview = \App\Support\SalaryCalculation::wholeRupeeDeductions($editRow);
        $this->assertSame(0.0, $preview['pf']);
        $this->assertEquals((float) $saved->total_deduction - 1600, $preview['total_deduction']);
        $sync->invoke($controller, $payroll, $data + ['items' => [['user_id' => $item->user_id, 'selected_components' => [1]]]]);
        $saved = $payroll->items()->first()->fresh();
        $this->assertSame('0.00', $saved->pf);
        $this->assertSame('100.00', $saved->total_deduction);
        $this->assertSame('13233.33', $saved->net_total);
        $this->assertSame('433.33', $saved->employer_esi);
        $this->getJson('/salary-reports/'.$saved->id)->assertOk()->assertJsonPath('register.row.61', 433.33);
        $response = $this->get('/salary-reports/export?'.http_build_query($this->filters()))->assertOk();
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertEquals(433.33, $book->getActiveSheet()->getCell('BJ5')->getValue());
        $book->disconnectWorksheets();

        $register = \App\Support\WageRegister::row($saved, 0);
        $this->assertSame(0.0, $register[50]);
        $this->assertSame(0.0, $register[60]);
        DB::table('staff_profiles')->where('user_id', $item->user_id)->update(['uan' => 'RESTORED']);
        $register = \App\Support\WageRegister::row($saved->fresh(), 0);
        $this->assertSame(0.0, $register[50]);
        $this->assertSame(0.0, $register[60]);

    }

    public function test_completed_payroll_is_blocked_on_every_report_endpoint(): void
    {
        $payroll = $this->payroll('Completed');
        $query = http_build_query($this->filters());
        $this->getJson('/salary-reports?'.$query.'&generate=1', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('success', false);
        $this->getJson('/salary-reports/'.$payroll->items()->first()->id)->assertNotFound();
        $this->getJson('/salary-reports/export?'.$query)->assertNotFound();
        $this->getJson('/salary-reports/pdf?'.$query)->assertNotFound();
        $this->postJson('/salary-reports/send-mail', $this->filters())->assertNotFound();
        Mail::assertNothingSent();
    }

    public function test_excel_reconciliation_notes_keep_their_spacer_and_do_not_merge_employee_rows(): void
    {
        $payroll = $this->payroll();
        $payroll->items()->firstOrFail()->update(['total_deduction' => 1000]);
        $response = $this->get('/salary-reports/export?'.http_build_query($this->filters()))->assertOk();
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $book->getActiveSheet();
        $this->assertSame(array_fill(0, 63, null), $sheet->rangeToArray('A10:BK10', null, true, false)[0]);
        $this->assertStringStartsWith('Reconciliation notes', $sheet->getCell('A11')->getValue());
        $this->assertStringStartsWith('Employee ', $sheet->getCell('A12')->getValue());
        $this->assertContains('A12:BK12', $sheet->getMergeCells());
        $this->assertEquals(5, $sheet->getCell('A9')->getValue());
        $book->disconnectWorksheets();
    }

    public function test_approved_report_never_reads_changed_live_templates_or_attendance(): void
    {
        $this->payroll();
        // These source tables intentionally do not exist: approved snapshots must suffice.
        $this->getJson('/salary-reports?'.http_build_query($this->filters() + ['generate' => 1]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('html', fn ($html) => str_contains($html, '13,333') && str_contains($html, '650'));
    }
}
