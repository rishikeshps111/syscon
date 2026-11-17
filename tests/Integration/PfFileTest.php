<?php

namespace Tests\Integration;

use App\Http\Middleware\TrackStaffUserLog;
use App\Models\SalaryProcessing;
use App\Models\User;
use App\Support\PfFileData;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PfFileTest extends TestCase
{
    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'activitylog.enabled' => false]);
        DB::purge('sqlite');
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_05_12_031408_create_permission_tables.php'))->up();
        Schema::table('users', fn (Blueprint $t) => $t->string('ref_code')->nullable());
        Schema::create('depots', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('short_name')->nullable(); $t->boolean('is_active')->default(true);
        });
        DB::table('depots')->insert([['id' => 1, 'name' => 'Main Depot'], ['id' => 2, 'name' => 'Other Depot']]);
        foreach (['staff_profiles', 'driver_profiles', 'housekeeping_profiles', 'controller_profiles', 'supervisor_profiles'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('depot_id'); $t->string('uan')->nullable();
            });
        }
        Schema::create('salary_processings', function (Blueprint $t) {
            $t->id(); $t->integer('year'); $t->integer('month'); $t->unsignedBigInteger('depot_id');
            $t->unsignedBigInteger('attendance_consolidate_import_id')->nullable(); $t->string('status'); $t->timestamps();
        });
        (require database_path('migrations/2026_06_17_000002_create_salary_processing_items_table.php'))->up();
        (require database_path('migrations/2026_09_24_000002_add_consolidated_attendance_to_salary_items.php'))->up();
        (require database_path('migrations/2026_09_28_000006_add_statutory_salary_fields_to_salary_processing_items.php'))->up();
        $this->operator = User::factory()->create();
        $this->operator->givePermissionTo(Permission::create(['name' => 'pf-files.view', 'guard_name' => 'web', 'group_name' => 'PF Files']));
        $this->withoutMiddleware(TrackStaffUserLog::class);
        $this->actingAs($this->operator);
    }

    private function payroll(string $status = 'Approved'): SalaryProcessing
    {
        $processing = SalaryProcessing::create(['year' => 2026, 'month' => 9, 'depot_id' => 1, 'status' => $status]);
        foreach (['Driver', 'Housekeeping', 'Controller', 'Supervisor', 'Staff'] as $i => $role) {
            $user = User::factory()->create(['name' => $role.' Employee', 'ref_code' => 'REF'.$i]);
            $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
            DB::table((new ('App\\Models\\'.$role.'Profile'))->getTable())->insert([
                'user_id' => $user->id, 'depot_id' => 1, 'uan' => str_pad((string) ($i + 1), 12, '0', STR_PAD_LEFT),
            ]);
            $processing->items()->create([
                'user_id' => $user->id, 'total_attendance_days' => 20, 'present_days' => 16, 'week_off_days' => 4,
                'pf' => 880, 'total_earned' => 13333.33, 'total_deduction' => 880, 'net_total' => 12453.33,
                'salary_split' => [
                    ['name' => 'Basic', 'type' => 'earning', 'amount' => 10000],
                    ['name' => 'VDA', 'type' => 'earning', 'amount' => 1000],
                    ['name' => 'Allowance', 'type' => 'earning', 'amount' => 9000],
                ],
            ]);
        }

        return $processing;
    }

    private function filters(array $extra = []): array
    {
        return array_replace(['year' => 2026, 'month' => 9, 'depot_id' => 1, 'role_id' => 'all'], $extra);
    }

    private function generate(array $extra = [])
    {
        return $this->getJson('/pf-files?'.http_build_query($this->filters($extra) + ['generate' => 1]), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    private function export(array $extra = [])
    {
        return $this->get('/pf-files/export?'.http_build_query($this->filters($extra)));
    }

    public function test_pf_file_page_and_preview_include_all_roles_and_filter_by_role_depot_and_period(): void
    {
        $this->payroll();
        $this->get('/pf-files')->assertOk()->assertSee('PF File')->assertSee('Generate Report');
        $response = $this->generate()->assertOk()->assertJsonPath('success', true);
        foreach (['Driver', 'Housekeeping', 'Controller', 'Supervisor', 'Staff'] as $role) {
            $this->assertStringContainsString($role.' Employee', $response->json('html'));
            $html = $this->generate(['role_id' => Role::where('name', $role)->value('id')])->assertOk()->assertJsonPath('success', true)->json('html');
            $this->assertStringContainsString($role.' Employee', $html);
            foreach (['Driver', 'Housekeeping', 'Controller', 'Supervisor', 'Staff'] as $other) {
                if ($role !== $other) $this->assertStringNotContainsString($other.' Employee', $html);
            }
        }
        foreach ([['year' => 2025], ['month' => 8], ['depot_id' => 2]] as $filters) {
            $this->generate($filters)->assertOk()->assertJsonPath('success', false)->assertJsonPath('download_excel_url', null);
        }
    }

    public function test_pf_excel_matches_six_column_template_and_preserves_uan_text_and_saved_amounts(): void
    {
        $processing = $this->payroll();
        $processing->items()->whereHas('user.roles', fn ($q) => $q->where('name', 'Staff'))->update(['pf' => 999.45]);
        $response = $this->export()->assertOk();
        Cell::setValueBinder(new DefaultValueBinder);
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        try {
            $sheet = $book->getActiveSheet();
            $this->assertSame('F', $sheet->getHighestDataColumn());
            $this->assertSame(6, $sheet->getHighestDataRow());
            $this->assertSame(PfFileData::HEADINGS, $sheet->rangeToArray('A1:F1')[0]);
            $this->assertSame('A1:F6', $sheet->getAutoFilter()->getRange());
            $this->assertSame('000000000003', $sheet->getCell('A2')->getValue());
            $this->assertSame('s', $sheet->getCell('A2')->getDataType());
            $this->assertSame('Controller Employee', $sheet->getCell('B2')->getValue());
            $this->assertEquals(13333.33, $sheet->getCell('C2')->getValue());
            $this->assertEquals(7333.33, $sheet->getCell('D2')->getValue());
            $this->assertEquals(880, $sheet->getCell('E2')->getValue());
            $this->assertEquals(20, $sheet->getCell('F2')->getValue());
            $this->assertSame('n', $sheet->getCell('C2')->getDataType());
            $this->assertSame('20', $sheet->getCell('F2')->getFormattedValue());
            $this->assertEquals(999.45, $sheet->getCell('E5')->getValue());
            $this->assertSame('999', $sheet->getCell('E5')->getFormattedValue());
        } finally { $book->disconnectWorksheets(); }
    }

    public function test_pf_report_excludes_blank_uan_and_zero_pf_and_disables_empty_download(): void
    {
        $processing = $this->payroll();
        $processing->items()->whereHas('user.roles', fn ($q) => $q->where('name', 'Driver'))->update(['pf' => 0]);
        DB::table('staff_profiles')->update(['uan' => '   ']);
        $response = $this->generate()->assertOk()->assertJsonPath('success', true);
        $this->assertStringNotContainsString('Driver Employee', $response->json('html'));
        $this->assertStringNotContainsString('Staff Employee', $response->json('html'));
        $this->assertStringContainsString('Housekeeping Employee', $response->json('html'));
        $processing->items()->update(['pf' => 0]);
        $this->generate()->assertJsonPath('success', false)->assertJsonPath('download_excel_url', null);
        $this->export()->assertNotFound();
    }

    public function test_pf_preview_and_direct_export_require_approved_processing_even_after_preview(): void
    {
        $processing = $this->payroll('Completed');
        foreach (['Completed', 'Draft', 'Rejected'] as $status) {
            $processing->update(['status' => $status]);
            $this->generate()->assertOk()->assertJsonPath('success', false)->assertJsonPath('download_excel_url', null);
            $this->export()->assertNotFound();
        }
        $processing->update(['status' => 'Approved']);
        $url = $this->generate()->assertJsonPath('success', true)->json('download_excel_url');
        $processing->update(['status' => 'Completed']);
        $this->get($url)->assertNotFound();
    }

    public function test_pf_report_caps_calendar_days_and_formats_fractional_worked_shifts(): void
    {
        $processing = $this->payroll();
        foreach ([[2026, 9, 33.75, '30'], [2026, 12, 33.75, '31'], [2027, 2, 31.5, '28'], [2028, 2, 31.5, '29'], [2026, 9, 26.25, '26.25']] as [$year, $month, $worked, $display]) {
            $processing->update(['year' => $year, 'month' => $month]);
            $processing->items()->update(['total_attendance_days' => $worked]);
            $this->generate(['year' => $year, 'month' => $month])->assertJsonPath('success', true)
                ->assertJsonPath('html', fn ($html) => str_contains($html, '>'.$display.'</td>'));
            $response = $this->export(['year' => $year, 'month' => $month])->assertOk();
            Cell::setValueBinder(new DefaultValueBinder);
            $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
            try {
                $this->assertSame($display, $book->getActiveSheet()->getCell('F2')->getFormattedValue());
                $this->assertEquals((float) $display, $book->getActiveSheet()->getCell('F2')->getValue());
            } finally { $book->disconnectWorksheets(); }
        }
    }

    public function test_pf_unavailable_history_shows_blank_wages_with_warning_instead_of_recalculating(): void
    {
        $processing = $this->payroll();
        $processing->items()->update(['salary_split' => []]);
        $this->generate()->assertJsonPath('success', true)->assertSee('unavailable in the approved snapshot');
        $response = $this->export()->assertOk();
        Cell::setValueBinder(new DefaultValueBinder);
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        try {
            $sheet = $book->getActiveSheet();
            $this->assertNull($sheet->getCell('D2')->getValue());
            $this->assertEquals(880, $sheet->getCell('E2')->getValue());
            $this->assertStringContainsString('Missing historical values', $sheet->getComment('D2')->getText()->getPlainText());
        } finally { $book->disconnectWorksheets(); }
    }

    public function test_pf_permissions_guard_page_generation_export_and_menu_order(): void
    {
        $this->operator->givePermissionTo(Permission::create(['name' => 'salary-reports.view', 'guard_name' => 'web', 'group_name' => 'Salary Reports']));
        $html = $this->get('/pf-files')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, route('pf-files.index')), strpos($html, route('salary-reports.index')));
        $this->operator->revokePermissionTo('pf-files.view');
        $this->get('/pf-files')->assertForbidden();
        $this->generate()->assertForbidden();
        $this->export()->assertForbidden();
    }

    public function test_pf_invalid_and_incomplete_filters_are_rejected(): void
    {
        $this->getJson('/pf-files/export')->assertUnprocessable();
        foreach ([['month' => 13], ['year' => 1999], ['depot_id' => 999], ['role_id' => 999], ['role_id' => 'invalid']] as $filters) {
            $this->generate($filters)->assertUnprocessable();
            $this->getJson('/pf-files/export?'.http_build_query($this->filters($filters)))->assertUnprocessable();
        }
    }

    public function test_pf_permission_migration_is_idempotent_and_grants_only_default_finance_roles(): void
    {
        $admin = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $finance = Role::create(['name' => 'Finance Officer', 'guard_name' => 'web']);
        $other = Role::create(['name' => 'Unrelated', 'guard_name' => 'web']);
        $migration = require database_path('migrations/2026_11_16_000002_add_pf_file_permission.php');
        $migration->up(); $migration->up();
        $this->assertTrue($admin->fresh()->hasPermissionTo('pf-files.view'));
        $this->assertTrue($finance->fresh()->hasPermissionTo('pf-files.view'));
        $this->assertFalse($other->fresh()->hasPermissionTo('pf-files.view'));
        $this->assertSame(1, Permission::where('name', 'pf-files.view')->count());
        $migration->down();
        $this->assertSame(0, Permission::where('name', 'pf-files.view')->count());
    }

    public function test_pf_report_reads_approved_snapshots_after_live_template_and_attendance_changes(): void
    {
        $processing = $this->payroll();
        (require database_path('migrations/2026_09_09_000001_create_attendance_consolidate_tables.php'))->up();
        $import = \App\Models\AttendanceConsolidateImport::create(['year' => 2026, 'month' => 9, 'depot_id' => 1,
            'depot_name' => 'Main Depot', 'original_filename' => 'attendance.xlsx', 'file_path' => '',
            'file_checksum' => str_repeat('c', 64), 'employee_count' => 5, 'imported_by_name' => 'Test', 'imported_at' => now()]);
        $processing->update(['attendance_consolidate_import_id' => $import->id]);
        foreach ($processing->items as $item) {
            $import->rows()->create(['user_id' => $item->user_id, 'employee_ref_code' => $item->user->ref_code,
                'employee_name' => $item->user->name, 'present_days' => 16, 'week_off_days' => 4, 'absent_days' => 0, 'total_days' => 20, 'source_row' => $item->id + 1]);
        }
        Schema::create('salary_templates', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('role_id'); });
        Schema::create('salary_components', function (Blueprint $t) { $t->id(); $t->string('component_name'); $t->string('type'); });
        Schema::create('salary_template_items', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('salary_template_id'); $t->unsignedBigInteger('salary_component_id'); $t->decimal('amount');
        });
        DB::table('salary_templates')->insert(['id' => 1, 'role_id' => Role::where('name', 'Staff')->value('id')]);
        DB::table('salary_components')->insert(['id' => 1, 'component_name' => 'Basic', 'type' => 'earning']);
        DB::table('salary_template_items')->insert(['salary_template_id' => 1, 'salary_component_id' => 1, 'amount' => 10000]);
        $before = $this->generate()->assertOk()->json('html');
        $saved = $processing->items()->get()->map->getAttributes()->all();
        $import->rows()->update(['present_days' => 31, 'week_off_days' => 0, 'total_days' => 31]);
        DB::table('salary_template_items')->update(['amount' => 999999]);
        $this->assertSame($before, $this->generate()->assertOk()->json('html'));
        $response = $this->export()->assertOk();
        Cell::setValueBinder(new DefaultValueBinder);
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        try {
            $sheet = $book->getActiveSheet();
            $this->assertEquals(13333.33, $sheet->getCell('C2')->getValue());
            $this->assertEquals(7333.33, $sheet->getCell('D2')->getValue());
            $this->assertEquals(880, $sheet->getCell('E2')->getValue());
            $this->assertEquals(20, $sheet->getCell('F2')->getValue());
        } finally { $book->disconnectWorksheets(); }
        $this->assertSame($saved, $processing->items()->get()->map->getAttributes()->all());
    }

    public function test_pf_report_does_not_mix_unapproved_rows_into_approved_payroll(): void
    {
        $this->payroll();
        $pending = $this->payroll('Completed');
        foreach ($pending->items as $item) $item->user->update(['name' => 'Pending '.$item->user->name]);
        $html = $this->generate()->assertOk()->assertJsonPath('success', true)->json('html');
        $this->assertStringNotContainsString('Pending ', $html);
        $response = $this->export()->assertOk();
        Cell::setValueBinder(new DefaultValueBinder);
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame(6, $book->getActiveSheet()->getHighestDataRow());
        $book->disconnectWorksheets();
    }

    public function test_pf_excel_preserves_numeric_zero_and_treats_employee_names_as_text(): void
    {
        $processing = $this->payroll();
        $processing->items()->update(['total_attendance_days' => 0, 'total_earned' => 0, 'net_total' => 0, 'net_salary' => 0]);
        $processing->items()->whereHas('user.roles', fn ($q) => $q->where('name', 'Controller'))->first()->user->update(['name' => '=1+1']);
        $response = $this->export()->assertOk();
        Cell::setValueBinder(new DefaultValueBinder);
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        try {
            $sheet = $book->getActiveSheet();
            $this->assertSame('=1+1', $sheet->getCell('B2')->getValue());
            $this->assertSame('s', $sheet->getCell('B2')->getDataType());
            foreach (['C2', 'D2', 'F2'] as $coordinate) {
                $this->assertSame('n', $sheet->getCell($coordinate)->getDataType());
                $this->assertEquals(0, $sheet->getCell($coordinate)->getValue());
                $this->assertSame('0', $sheet->getCell($coordinate)->getFormattedValue());
            }
        } finally { $book->disconnectWorksheets(); }
    }
}
