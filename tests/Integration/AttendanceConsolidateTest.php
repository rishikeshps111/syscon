<?php

namespace Tests\Integration;

use App\Http\Middleware\TrackStaffUserLog;
use App\Models\AttendanceConsolidateImport;
use App\Models\AttendanceConsolidateRow;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendanceConsolidateTest extends TestCase
{
    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'activitylog.enabled' => false]);
        DB::purge('sqlite');
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_05_12_031408_create_permission_tables.php'))->up();
        Schema::table('users', fn (Blueprint $table) => $table->string('ref_code')->nullable());
        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        DB::table('depots')->insert(['id' => 1, 'name' => 'Warangal']);
        foreach (['staff_profiles', 'driver_profiles', 'housekeeping_profiles', 'controller_profiles', 'supervisor_profiles'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('depot_id');
            });
        }
        (require database_path('migrations/2026_09_09_000001_create_attendance_consolidate_tables.php'))->up();
        Schema::create('salary_processings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_consolidate_import_id')->nullable()->constrained('attendance_consolidate_imports')->nullOnDelete();
            $table->decimal('net_total', 12, 2)->default(0);
        });
        (require database_path('migrations/2026_10_26_000001_make_attendance_consolidate_period_unique.php'))->up();
        Storage::set('local', Storage::build(['driver' => 'local', 'root' => sys_get_temp_dir().'/consolidate-'.Str::uuid()]));
        $this->withoutMiddleware(TrackStaffUserLog::class);
        $this->operator = User::factory()->create(['name' => 'Importer']);
        foreach (['view', 'create'] as $action) {
            $this->operator->givePermissionTo(Permission::create(['name' => 'attendance-management.'.$action, 'guard_name' => 'web', 'group_name' => 'HRMS']));
        }
        $employee = User::factory()->create(['ref_code' => '0012', 'name' => 'Employee']);
        DB::table('staff_profiles')->insert(['user_id' => $employee->id, 'depot_id' => 1]);
        $this->actingAs($this->operator);
    }

    private function preview(string $line = '0012,Employee,31,4,2,35', array $extra = [])
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->fromArray(['Emp Id', 'Name Of The Employee', 'P', 'W/O', 'A', 'Total'], null, 'A1');
        foreach (explode("\n", $line) as $index => $record) {
            $values = str_getcsv($record);
            $sheet->fromArray($values, null, 'A'.($index + 2), true);
            $sheet->setCellValueExplicit('A'.($index + 2), $values[0], DataType::TYPE_STRING);
        }
        $temporary = tmpfile();
        try {
            $path = stream_get_meta_data($temporary)['uri'];
            IOFactory::createWriter($book, 'Xlsx')->save($path);
            return $this->from('/attendance-consolidate/import')->post('/attendance-consolidate/preview', array_merge([
                'year' => 2026, 'month' => 8, 'depot_id' => 1,
                'csv_file' => UploadedFile::fake()->createWithContent('attendance.xlsx', file_get_contents($path)),
            ], $extra));
        } finally {
            fclose($temporary);
            $book->disconnectWorksheets();
        }
    }

    private function confirm(array $extra = [])
    {
        return $this->post('/attendance-consolidate/import', array_merge(['token' => session('attendance_consolidate_preview.token')], $extra));
    }

    public function test_excel_formats_import_and_download_saved_values(): void
    {
        foreach (['Xlsx' => 'xlsx', 'Xls' => 'xls'] as $writerType => $extension) {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->fromArray(['Emp Id', 'Name Of The Employee', 'P', 'W/O', 'A', 'Total'], null, 'A1');
            $sheet->setCellValueExplicit('A2', '0012', DataType::TYPE_STRING);
            $sheet->fromArray(['Employee', 20.5, 3, 0, 23.5], null, 'B2', true);
            $temporary = tmpfile();
            try {
                $path = stream_get_meta_data($temporary)['uri'];
                IOFactory::createWriter($book, $writerType)->save($path);
                $bytes = file_get_contents($path);
                $this->preview(extra: ['import_now' => 1, 'csv_file' => UploadedFile::fake()->createWithContent('attendance.'.$extension, $bytes)])->assertRedirect('/attendance-consolidate');
                $import = AttendanceConsolidateImport::latest('id')->firstOrFail();
                $this->assertSame('0012', $import->rows()->first()->employee_ref_code);
                $this->assertSame('23.50', $import->rows()->first()->total_days);
                $download = $this->readDownload('/attendance-consolidate/'.$import->id.'/download');
                $this->assertEquals(23.5, $download->getActiveSheet()->getCell('F3')->getValue());
                $download->disconnectWorksheets();
            } finally {
                fclose($temporary);
                $book->disconnectWorksheets();
            }
        }
    }

    public function test_downloaded_sample_can_be_filled_and_imported_directly(): void
    {
        $book = $this->readDownload('/attendance-consolidate/sample-csv?depot_id=1');
        $book->getActiveSheet()->fromArray([20, 3, 0, 23], null, 'C3', true);
        $book->createSheet()->setTitle('Notes')->setCellValue('A1', 'Other sheet');
        $temporary = tmpfile();
        try {
            $path = stream_get_meta_data($temporary)['uri'];
            IOFactory::createWriter($book, 'Xlsx')->save($path);
            $this->preview(extra: ['import_now' => 1, 'csv_file' => UploadedFile::fake()->createWithContent('sample.xlsx', file_get_contents($path))])->assertRedirect('/attendance-consolidate');
            $this->assertSame(3, AttendanceConsolidateRow::first()->source_row);
        } finally {
            fclose($temporary);
            $book->disconnectWorksheets();
        }
    }

    public function test_invalid_excel_upload_does_not_create_a_batch(): void
    {
        $this->preview(extra: ['import_now' => 1, 'csv_file' => UploadedFile::fake()->createWithContent('broken.xlsx', 'invalid workbook')])->assertSessionHasErrors('csv_file');
        $this->assertSame(0, AttendanceConsolidateImport::count());
    }

    public function test_import_button_saves_valid_excel_without_review(): void
    {
        $this->preview(extra: ['import_now' => 1])->assertRedirect('/attendance-consolidate');
        $this->assertSame(1, AttendanceConsolidateImport::count());
        $this->preview('0012,Historical Name,20,3,0,23', ['import_now' => 1])->assertOk()->assertSee('Name differs');
        $this->assertSame(1, AttendanceConsolidateImport::count());
    }

    private function readDownload(string $url): Spreadsheet
    {
        $content = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')->streamedContent();
        $path = tempnam(sys_get_temp_dir(), 'consolidate-test-');
        try {
            file_put_contents($path, $content);

            return IOFactory::load($path);
        } finally {
            unlink($path);
        }
    }

    public function test_depot_sample_contains_only_depot_employees_with_reference_colors_and_text_ids(): void
    {
        $other = User::factory()->create(['ref_code' => 'OTHER', 'name' => 'Other Employee']);
        DB::table('staff_profiles')->insert(['user_id' => $other->id, 'depot_id' => 2]);
        $employeeId = User::where('ref_code', '0012')->value('id');
        DB::table('driver_profiles')->insert(['user_id' => $employeeId, 'depot_id' => 1]);
        DB::table('users')->where('id', $employeeId)->update(['name' => '=1+1']);
        $book = $this->readDownload('/attendance-consolidate/sample-csv?depot_id=1');
        try {
            $sheet = $book->getActiveSheet();
            $this->assertSame(3, $sheet->getHighestDataRow());
            $this->assertSame('0012', $sheet->getCell('A3')->getValue());
            $this->assertSame('s', $sheet->getCell('A3')->getDataType());
            $this->assertSame('=1+1', $sheet->getCell('B3')->getValue());
            $this->assertSame('s', $sheet->getCell('B3')->getDataType());
            $this->assertNull($sheet->getCell('C3')->getValue());
            $this->assertSame('92D050', $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB());
            foreach (range('A', 'F') as $column) {
                $this->assertSame('FFFF00', $sheet->getStyle($column.'2')->getFill()->getStartColor()->getRGB());
            }
        } finally {
            $book->disconnectWorksheets();
        }
        $this->getJson('/attendance-consolidate/sample-csv')->assertUnprocessable()->assertJsonValidationErrors('depot_id');
        $this->getJson('/attendance-consolidate/sample-csv?depot_id=999')->assertUnprocessable();
    }

    public function test_listing_download_contains_saved_values_and_colors(): void
    {
        $this->preview(extra: ['import_now' => 1])->assertRedirect();
        DB::table('users')->where('ref_code', '0012')->update(['name' => 'Changed']);
        $book = $this->readDownload('/attendance-consolidate/'.AttendanceConsolidateImport::firstOrFail()->id.'/download');
        try {
            $sheet = $book->getActiveSheet();
            $this->assertSame('Employee', $sheet->getCell('B3')->getValue());
            $this->assertEquals(35, $sheet->getCell('F3')->getValue());
            $this->assertEquals(2, $sheet->getCell('E3')->getValue());
            $this->assertSame('FFFF00', $sheet->getStyle('F2')->getFill()->getStartColor()->getRGB());
        } finally {
            $book->disconnectWorksheets();
        }
        $this->getJson('/attendance-consolidate', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('data.0.imported_details', fn ($value) => str_contains($value, 'Importer') && str_contains($value, '<small>'));
    }

    public function test_import_renders_pages_keeps_existing_users_and_downloads_saved_attendance(): void
    {
        $before = DB::table('users')->get()->toJson();
        $this->get('/attendance-consolidate')->assertOk()->assertSee('Attendance Consolidate');
        $this->get('/attendance-consolidate/import')->assertOk()->assertSee('Import Excel');
        $this->preview()->assertOk()->assertSee('Import CSV');
        $this->assertSame(0, AttendanceConsolidateImport::count());
        $this->confirm()->assertRedirect('/attendance-consolidate');
        $import = AttendanceConsolidateImport::firstOrFail();
        $this->assertSame('Importer', $import->imported_by_name);
        $this->assertNotNull($import->imported_at);
        $this->assertSame('0012', $import->rows()->first()->employee_ref_code);
        $this->assertSame('35.00', $import->rows()->first()->total_days);
        $this->assertSame($before, DB::table('users')->get()->toJson());
        $this->get('/attendance-consolidate/'.$import->id)->assertOk()->assertSee('Employee');
        $download = $this->readDownload('/attendance-consolidate/'.$import->id.'/download');
        $this->assertEquals(35, $download->getActiveSheet()->getCell('F3')->getValue());
        $download->disconnectWorksheets();
        $this->getJson('/attendance-consolidate', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonMissingPath('data.0.file_path');
    }

    public function test_invalid_rows_reject_the_entire_file(): void
    {
        foreach (['999,Employee,20,3,0,23', "0012,Employee,20,3,0,23\n0012,Employee,20,3,0,23", '0012,Employee,20,3,2,25', '0012,Employee,-1,3,0,2', '0012,Employee,1.234,3,0,4.234'] as $line) {
            $this->preview($line)->assertSessionHasErrors('csv_file');
            $this->assertSame(0, AttendanceConsolidateImport::count());
        }
    }

    public function test_ambiguous_reference_is_rejected(): void
    {
        User::factory()->create(['ref_code' => '0012']);
        $this->preview()->assertSessionHasErrors('csv_file');
    }

    public function test_warnings_require_acknowledgement_and_fractional_values_are_preserved(): void
    {
        $this->preview('0012,Old Name,20.5,3.25,0,23.75')->assertOk()->assertSee('Name differs');
        $this->confirm()->assertOk()->assertSee('Please acknowledge');
        $this->assertSame(0, AttendanceConsolidateImport::count());
        $this->confirm(['acknowledge_mismatches' => 1])->assertRedirect('/attendance-consolidate');
        $this->assertSame('23.75', AttendanceConsolidateRow::first()->total_days);
    }

    public function test_identical_and_corrected_imports_replace_the_batch_in_place(): void
    {
        $this->preview()->assertOk();
        $this->confirm()->assertRedirect();
        $id = AttendanceConsolidateImport::firstOrFail()->id;
        $this->preview()->assertOk();
        $this->confirm()->assertRedirect();
        $this->preview('0012,Employee,30,4,3,34')->assertOk();
        $this->confirm()->assertRedirect();
        $this->assertSame(1, AttendanceConsolidateImport::count());
        $this->assertSame($id, AttendanceConsolidateImport::firstOrFail()->id);
        $this->assertSame(1, AttendanceConsolidateRow::count());
        $this->assertSame('34.00', AttendanceConsolidateRow::firstOrFail()->total_days);
    }

    public function test_changed_employee_mapping_requires_fresh_review(): void
    {
        $this->preview()->assertOk();
        DB::table('users')->where('ref_code', '0012')->update(['name' => 'New Name']);
        $this->confirm(['acknowledge_mismatches' => 1])->assertOk()->assertSee('Employee records changed');
        $this->assertSame(0, AttendanceConsolidateImport::count());
        $this->confirm(['acknowledge_mismatches' => 1])->assertRedirect();
    }

    public function test_delete_removes_attendance_and_preserves_saved_payroll(): void
    {
        $this->operator->givePermissionTo(Permission::create([
            'name' => 'attendance-management.delete', 'guard_name' => 'web', 'group_name' => 'HRMS',
        ]));
        $this->preview(extra: ['import_now' => 1])->assertRedirect();
        $import = AttendanceConsolidateImport::firstOrFail();
        DB::table('salary_processings')->insert(['attendance_consolidate_import_id' => $import->id, 'net_total' => 12345]);
        $this->getJson('/attendance-consolidate', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('data.0.action', fn ($html) => str_contains($html, 'delete-consolidated-attendance'));
        $this->deleteJson('/attendance-consolidate/'.$import->id)->assertOk()->assertJson(['success' => true]);
        $this->assertSame(0, AttendanceConsolidateImport::count());
        $this->assertSame(0, AttendanceConsolidateRow::count());
        $this->assertNull(DB::table('salary_processings')->value('attendance_consolidate_import_id'));
        $this->assertEquals(12345, DB::table('salary_processings')->value('net_total'));
        $this->deleteJson('/attendance-consolidate/'.$import->id)->assertNotFound();
        $this->preview(extra: ['import_now' => 1])->assertRedirect();
        $this->assertSame(1, AttendanceConsolidateImport::count());
    }

    public function test_delete_requires_permission_and_hides_the_action(): void
    {
        $this->preview(extra: ['import_now' => 1])->assertRedirect();
        $import = AttendanceConsolidateImport::firstOrFail();
        $this->getJson('/attendance-consolidate', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('data.0.action', fn ($html) => !str_contains($html, 'delete-consolidated-attendance'));
        $this->deleteJson('/attendance-consolidate/'.$import->id)->assertForbidden();
        $this->assertSame(1, AttendanceConsolidateImport::count());
        $this->assertSame(1, AttendanceConsolidateRow::count());
    }

    public function test_replacement_removes_missing_employees_and_preserves_payroll_reference(): void
    {
        $employee = User::factory()->create(['ref_code' => '0013', 'name' => 'Second Employee']);
        DB::table('staff_profiles')->insert(['user_id' => $employee->id, 'depot_id' => 1]);
        $this->preview("0012,Employee,20,3,0,23\n0013,Second Employee,20,3,0,23")->assertOk();
        $this->confirm()->assertRedirect();
        $import = AttendanceConsolidateImport::firstOrFail();
        DB::table('salary_processings')->insert(['attendance_consolidate_import_id' => $import->id, 'net_total' => 12345]);
        $checksum = $import->file_checksum;
        $this->travel(1)->minutes();
        $this->preview('0012,Employee,19,3,0,22')->assertOk();
        $this->confirm()->assertRedirect();
        $import->refresh();
        $this->assertSame(1, AttendanceConsolidateImport::count());
        $this->assertSame(1, $import->employee_count);
        $this->assertNotSame($checksum, $import->file_checksum);
        $this->assertSame(now()->timestamp, $import->imported_at->timestamp);
        $this->assertSame(['0012'], $import->rows()->pluck('employee_ref_code')->all());
        $this->assertEquals($import->id, DB::table('salary_processings')->value('attendance_consolidate_import_id'));
        $this->assertEquals(12345, DB::table('salary_processings')->value('net_total'));
    }

    public function test_invalid_or_failed_replacement_preserves_existing_attendance(): void
    {
        $this->preview(extra: ['import_now' => 1])->assertRedirect();
        $before = AttendanceConsolidateImport::firstOrFail()->getAttributes();
        $rows = AttendanceConsolidateRow::all()->toArray();
        $this->preview('0012,Employee,-1,3,0,2')->assertSessionHasErrors('csv_file');
        $this->preview('0012,Employee,20,3,0,23')->assertOk();
        AttendanceConsolidateRow::creating(fn () => throw new \RuntimeException('Simulated replacement failure'));
        try {
            $this->confirm()->assertStatus(500);
            $this->assertSame($before, AttendanceConsolidateImport::firstOrFail()->getAttributes());
            $this->assertSame($rows, AttendanceConsolidateRow::all()->toArray());
        } finally {
            AttendanceConsolidateRow::flushEventListeners();
        }
    }

    public function test_period_unique_migration_keeps_newest_batch_and_repoints_payroll(): void
    {
        $migration = require database_path('migrations/2026_10_26_000001_make_attendance_consolidate_period_unique.php');
        $migration->down();
        $this->preview(extra: ['import_now' => 1])->assertRedirect();
        $old = AttendanceConsolidateImport::firstOrFail();
        $new = $old->replicate();
        $new->file_checksum = str_repeat('a', 64);
        $new->save();
        $new->rows()->create(array_merge($old->rows()->firstOrFail()->replicate()->getAttributes(), [
            'present_days' => 20, 'week_off_days' => 4, 'total_days' => 24,
        ]));
        DB::table('salary_processings')->insert(['attendance_consolidate_import_id' => $old->id, 'net_total' => 4321]);
        $migration->up();
        $this->assertSame([$new->id], AttendanceConsolidateImport::pluck('id')->all());
        $this->assertSame(1, AttendanceConsolidateRow::count());
        $this->assertSame('24.00', AttendanceConsolidateRow::firstOrFail()->total_days);
        $this->assertEquals($new->id, DB::table('salary_processings')->value('attendance_consolidate_import_id'));
        $this->assertEquals(4321, DB::table('salary_processings')->value('net_total'));
        $duplicate = $new->replicate();
        $duplicate->file_checksum = str_repeat('b', 64);
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $duplicate->save();
    }

    public function test_expired_preview_cannot_import(): void
    {
        $this->preview()->assertOk();
        $this->travel(31)->minutes();
        $this->confirm()->assertRedirect('/attendance-consolidate/import')->assertSessionHasErrors('csv_file');
        $this->assertSame(0, AttendanceConsolidateImport::count());
    }

    public function test_permission_checks_cover_import_and_download(): void
    {
        $this->preview()->assertOk();
        $this->confirm()->assertRedirect();
        $this->operator->revokePermissionTo('attendance-management.create');
        $this->get('/attendance-consolidate/import')->assertForbidden();
        $this->post('/attendance-consolidate/preview')->assertForbidden();
        $this->post('/attendance-consolidate/import')->assertForbidden();
        $this->get('/attendance-consolidate/1/download')->assertOk();
        $this->operator->revokePermissionTo('attendance-management.view');
        $this->get('/attendance-consolidate')->assertForbidden();
        $this->get('/attendance-consolidate/1/download')->assertForbidden();
    }

    public function test_failed_row_insert_rolls_back_batch_and_removes_original_copy(): void
    {
        $this->preview()->assertOk();
        AttendanceConsolidateRow::creating(function () {
            throw new \RuntimeException('Simulated row failure');
        });
        try {
            $this->post('/attendance-consolidate/import', ['token' => session('attendance_consolidate_preview.token')])->assertStatus(500);
            $this->assertSame(0, AttendanceConsolidateImport::count());
            $this->assertSame([], Storage::disk('local')->files('attendance-consolidate/originals'));
        } finally {
            AttendanceConsolidateRow::flushEventListeners();
        }
    }

    public function test_same_csv_is_allowed_for_a_different_period(): void
    {
        $this->preview()->assertOk();
        $this->confirm()->assertRedirect();
        $this->preview(extra: ['month' => 9])->assertOk();
        $this->confirm()->assertRedirect();
        $this->assertSame(2, AttendanceConsolidateImport::count());
        $this->preview(extra: ['year' => 2025])->assertOk();
        $this->confirm()->assertRedirect();
        DB::table('depots')->insert(['id' => 2, 'name' => 'Other Depot']);
        $this->preview(extra: ['depot_id' => 2])->assertOk();
        $this->confirm(['acknowledge_mismatches' => 1])->assertRedirect();
        $this->assertSame(4, AttendanceConsolidateImport::count());
    }

    public function test_cleanup_only_removes_old_pending_files(): void
    {
        $disk = Storage::disk('local');
        $disk->put('attendance-consolidate/pending/old.csv', 'old');
        $disk->put('attendance-consolidate/pending/new.csv', 'new');
        $disk->put('attendance-consolidate/originals/old.csv', 'original');
        touch($disk->path('attendance-consolidate/pending/old.csv'), now()->subDays(2)->timestamp);
        touch($disk->path('attendance-consolidate/originals/old.csv'), now()->subDays(2)->timestamp);
        $this->artisan('attendance-consolidate:cleanup-previews')->assertSuccessful();
        $disk->assertMissing('attendance-consolidate/pending/old.csv');
        $disk->assertExists('attendance-consolidate/pending/new.csv');
        $disk->assertExists('attendance-consolidate/originals/old.csv');
    }
}
