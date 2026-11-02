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
                $t->string('ifsc_code')->nullable();
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
            DB::table($table)->insert(['user_id' => $user->id, 'depot_id' => 1, 'ifsc_code' => ' sbin0001234 ']);
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
