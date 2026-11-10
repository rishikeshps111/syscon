<?php

namespace Tests\Integration;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class RosterOptionalOemMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('oems', fn (Blueprint $table) => $table->id());
        Schema::create('rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oem_id')->constrained('oems')->restrictOnDelete();
            $table->index('oem_id');
        });
        DB::table('oems')->insert(['id' => 1]);
        DB::table('rosters')->insert(['id' => 1, 'oem_id' => 1]);
    }

    public function test_nullable_migration_preserves_existing_oem_and_foreign_key(): void
    {
        $migration = require database_path('migrations/2026_11_02_000001_make_roster_oem_optional.php');
        $migration->up();
        DB::table('rosters')->insert(['id' => 2, 'oem_id' => null]);
        $this->assertSame(1, (int) DB::table('rosters')->where('id', 1)->value('oem_id'));
        $this->assertNull(DB::table('rosters')->where('id', 2)->value('oem_id'));
        $this->expectException(QueryException::class);
        DB::table('rosters')->insert(['id' => 3, 'oem_id' => 999]);
    }

    public function test_rollback_refuses_null_rosters_and_restores_requirement_when_resolved(): void
    {
        $migration = require database_path('migrations/2026_11_02_000001_make_roster_oem_optional.php');
        $migration->up();
        DB::table('rosters')->insert(['id' => 2, 'oem_id' => null]);
        try {
            $migration->down();
            $this->fail('Rollback must not discard driver-only rosters.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Assign an OEM before rolling back', $exception->getMessage());
        }
        $this->assertSame(2, DB::table('rosters')->count());
        $this->assertNull(DB::table('rosters')->where('id', 2)->value('oem_id'));
        DB::table('rosters')->where('id', 2)->update(['oem_id' => 1]);
        $migration->down();
        $this->expectException(QueryException::class);
        DB::table('rosters')->insert(['id' => 3, 'oem_id' => null]);
    }
}
