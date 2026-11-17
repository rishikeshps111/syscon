<?php

namespace Tests\Integration;

use App\Http\Middleware\TrackStaffUserLog;
use App\Models\{Trip, TripSheet, TripSheetEntry, Vehicle};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\Support\TripAssignmentTestCase;

class TripRosterPaginationTest extends TripAssignmentTestCase
{
    private Trip $trip;

    protected function createSchema(): void
    {
        parent::createSchema();
        Schema::table('trips', function (Blueprint $table) {
            $table->string('code')->nullable();
            $table->unsignedBigInteger('vehicle_classification_id')->nullable();
        });
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->string('code')->nullable();
            $table->string('service_code')->nullable();
            $table->integer('trip_order_sequence_no')->nullable();
        });
        Schema::table('vehicles', fn (Blueprint $table) => $table->unsignedBigInteger('vehicle_classification_id')->nullable());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TrackStaffUserLog::class);
        $permission = Permission::create(['name' => 'rosters.create', 'guard_name' => 'web', 'group_name' => 'Roster']);
        $this->supervisor->givePermissionTo($permission);
        $this->actingAs($this->supervisor, 'web');
        $this->trip = $this->entry->sheet->trip;
        $this->trip->update(['code' => 'TRP2026#0056', 'vehicle_classification_id' => 3]);
        $this->entry->update(['code' => 'TRP2026#0056-0001', 'service_code' => 'ES01', 'trip_order_sequence_no' => null]);
    }

    private function urlFor(string $suffix = 'rows', array $query = [], ?Trip $trip = null): string
    {
        return '/trips/'.($trip ?? $this->trip)->id.'/roster/'.$suffix.($query ? '?'.http_build_query($query) : '');
    }

    private function entryOn(string $date, ?int $sequence, string $service = 'ES01', ?Trip $trip = null): TripSheetEntry
    {
        $trip ??= $this->trip;
        $sheet = $trip->sheets()->firstOrCreate(['date' => $date], ['status' => 'pending']);
        return TripSheetEntry::create(['trip_sheet_id' => $sheet->id, 'status' => 'pending',
            'code' => 'ENTRY-'.(TripSheetEntry::max('id') + 1), 'service_code' => $service, 'trip_order_sequence_no' => $sequence]);
    }

    public function test_rows_keyset_pagination_preserves_null_sequence_ties_and_trip_scope(): void
    {
        $expected = [['id' => $this->entry->id, 'date' => '2026-09-08', 'sequence' => 0]];
        for ($index = 0; $index < 62; $index++) {
            $date = $index < 31 ? '2026-09-09' : '2026-09-08';
            $sequence = $index % 3 === 0 ? null : $index % 4;
            $entry = $this->entryOn($date, $sequence);
            $expected[] = ['id' => $entry->id, 'date' => $date, 'sequence' => $sequence ?? 0];
        }
        $otherTrip = $this->trip->replicate();
        $otherTrip->save();
        $this->entryOn('2026-09-10', null, 'ES01', $otherTrip);
        usort($expected, fn ($left, $right) => strcmp($right['date'], $left['date'])
            ?: ($left['sequence'] <=> $right['sequence']) ?: ($left['id'] <=> $right['id']));
        $ids = [];
        $cursor = null;
        $sizes = [];
        $seenCursors = [];
        do {
            $data = $this->getJson($this->urlFor(query: $cursor ? ['cursor' => $cursor] : []))->assertOk()->json();
            $sizes[] = count($data['rows']);
            $ids = array_merge($ids, array_column($data['rows'], 'id'));
            $cursor = $data['next_cursor'];
            $this->assertLessThanOrEqual(count($expected), count($ids));
            if ($cursor) {
                $this->assertNotContains($cursor, $seenCursors);
                $seenCursors[] = $cursor;
            }
        } while ($cursor);
        $this->assertSame([25, 25, 13], $sizes);
        $this->assertSame(array_column($expected, 'id'), $ids);
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_rows_cursor_reaches_newly_added_backdated_duties_without_repeating_prior_rows(): void
    {
        for ($index = 0; $index < 30; $index++) $this->entryOn('2026-09-10', $index);
        $first = $this->getJson($this->urlFor())->assertOk()->json();
        $added = $this->entryOn('2026-09-01', null);
        $second = $this->getJson($this->urlFor(query: ['cursor' => $first['next_cursor']]))->assertOk()->json();
        $this->assertContains($added->id, array_column($second['rows'], 'id'));
        $this->assertSame([], array_values(array_intersect(array_column($first['rows'], 'id'), array_column($second['rows'], 'id'))));
    }

    public function test_rows_filters_bind_cursor_to_trip_and_filter_values(): void
    {
        for ($index = 0; $index < 30; $index++) $this->entryOn('2026-09-10', $index, 'EX02');
        $this->entryOn('2026-09-11', 1, 'EX02');
        $this->entryOn('2026-09-10', 1, 'ES01');
        $filter = ['date_from' => '2026-09-10', 'date_to' => '2026-09-10', 'ser' => 'EX'];
        $data = $this->getJson($this->urlFor(query: $filter))->assertOk()->json();
        $this->assertCount(25, $data['rows']);
        $next = $this->getJson($this->urlFor(query: $filter + ['cursor' => $data['next_cursor']]))->assertOk()->json();
        $this->assertCount(5, $next['rows']);
        $this->assertNull($next['next_cursor']);
        $this->getJson($this->urlFor(query: ['cursor' => $data['next_cursor'], 'ser' => 'ES']))->assertUnprocessable();
        $otherTrip = $this->trip->replicate();
        $otherTrip->save();
        $this->getJson($this->urlFor(query: $filter + ['cursor' => $data['next_cursor']], trip: $otherTrip))->assertUnprocessable();
        $this->getJson($this->urlFor(query: ['cursor' => 'not-a-valid-cursor']))->assertUnprocessable();
    }

    public function test_row_and_lookup_endpoints_require_permission_and_active_user(): void
    {
        $this->supervisor->revokePermissionTo('rosters.create');
        foreach (['rows', 'drivers', 'vehicles'] as $suffix) $this->getJson($this->urlFor($suffix))->assertForbidden();
        $this->supervisor->givePermissionTo('rosters.create');
        $this->supervisor->update(['is_active' => false]);
        foreach (['rows', 'drivers', 'vehicles'] as $suffix) $this->getJson($this->urlFor($suffix))->assertForbidden();
    }

    public function test_driver_lookup_pages_searches_and_excludes_other_depots_and_inactive_users(): void
    {
        $ids = [];
        for ($index = 1; $index <= 53; $index++) {
            $driver = $this->driver();
            $driver->user->update(['name' => 'Eligible Driver '.$index, 'code' => 'DR'.str_pad((string) $index, 3, '0', STR_PAD_LEFT)]);
            $ids[] = $driver->id;
        }
        $outside = $this->driver(['depot_id' => 2]);
        $outside->user->update(['name' => 'Eligible Other depot']);
        $inactive = $this->driver();
        $inactive->user->update(['name' => 'Eligible Inactive', 'is_active' => false]);
        $actual = [];
        for ($page = 1; $page <= 3; $page++) {
            $data = $this->getJson($this->urlFor('drivers', ['q' => 'Eligible', 'page' => $page]))->assertOk()->json();
            $this->assertSame($page < 3, $data['pagination']['more']);
            $this->assertCount($page < 3 ? 25 : 3, $data['results']);
            $actual = array_merge($actual, array_map('intval', array_column($data['results'], 'id')));
        }
        $this->assertSame($ids, $actual);
        $searched = $this->getJson($this->urlFor('drivers', ['q' => 'DR053']))->assertOk()->json('results');
        $this->assertSame([$ids[52]], array_map('intval', array_column($searched, 'id')));
        $this->assertStringContainsString('DR053', $searched[0]['text']);
        $this->assertStringContainsString('Eligible Driver 53', $searched[0]['text']);
    }

    public function test_vehicle_lookup_pages_in_vehicle_number_order_and_filters_depot_class_and_status(): void
    {
        $ids = [];
        for ($index = 53; $index >= 1; $index--) {
            $vehicle = Vehicle::create(['vehicle_code' => 'V'.$index, 'vehicle_no' => 'BUS'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'depot_id' => 1, 'vehicle_classification_id' => 3, 'status' => 'Active']);
            array_unshift($ids, $vehicle->id);
        }
        foreach ([['depot_id' => 2], ['vehicle_classification_id' => 4], ['status' => 'Inactive']] as $index => $attributes) {
            Vehicle::create($attributes + ['vehicle_code' => 'OUT'.$index, 'vehicle_no' => 'BUS000',
                'depot_id' => 1, 'vehicle_classification_id' => 3, 'status' => 'Active']);
        }
        $actual = [];
        for ($page = 1; $page <= 3; $page++) {
            $data = $this->getJson($this->urlFor('vehicles', ['q' => 'BUS', 'page' => $page]))->assertOk()->json();
            $this->assertSame($page < 3, $data['pagination']['more']);
            $this->assertCount($page < 3 ? 25 : 3, $data['results']);
            $actual = array_merge($actual, array_map('intval', array_column($data['results'], 'id')));
        }
        $this->assertSame($ids, $actual);
        $this->getJson($this->urlFor('vehicles', ['q' => 'BUS053']))->assertOk()->assertJsonPath('results.0.text', 'BUS053')->assertJsonCount(1, 'results');
    }

    public function test_rows_show_entry_assignments_before_roster_fallback_without_catalogs(): void
    {
        $this->oldDriver->user->update(['code' => 'D1', 'name' => 'Roster Driver']);
        $this->freeDriver->user->update(['code' => 'D2', 'name' => 'Entry Driver']);
        $rosterVehicle = Vehicle::create(['vehicle_code' => 'VR', 'vehicle_no' => 'ROSTER BUS', 'depot_id' => 1,
            'vehicle_classification_id' => 3, 'status' => 'Active']);
        $entryVehicle = Vehicle::create(['vehicle_code' => 'VE', 'vehicle_no' => 'ENTRY BUS', 'depot_id' => 1,
            'vehicle_classification_id' => 3, 'status' => 'Active']);
        $this->roster->update(['vehicle_id' => $rosterVehicle->id]);
        $this->entry->update(['driver_profile_id' => $this->freeDriver->id, 'vehicle_id' => $entryVehicle->id]);
        $row = $this->getJson($this->urlFor())->assertOk()->json('rows.0');
        $this->assertSame(['id' => $this->freeDriver->id, 'text' => 'D2 - Entry Driver'], $row['driver']);
        $this->assertSame(['id' => $entryVehicle->id, 'text' => 'ENTRY BUS'], $row['vehicle']);
        $this->assertArrayNotHasKey('drivers', $row);
        $this->assertArrayNotHasKey('vehicles', $row);
        $this->entry->update(['driver_profile_id' => null, 'vehicle_id' => null]);
        $fallback = $this->getJson($this->urlFor())->assertOk()->json('rows.0');
        $this->assertSame(['id' => $this->oldDriver->id, 'text' => 'D1 - Roster Driver'], $fallback['driver']);
        $this->assertSame(['id' => $rosterVehicle->id, 'text' => 'ROSTER BUS'], $fallback['vehicle']);
    }

    public function test_vehicle_lookup_uses_id_to_break_duplicate_vehicle_number_ties(): void
    {
        $expected = [];
        for ($index = 1; $index <= 28; $index++) {
            $expected[] = Vehicle::create(['vehicle_code' => 'DUP'.$index, 'vehicle_no' => 'SAME BUS',
                'depot_id' => 1, 'vehicle_classification_id' => 3, 'status' => 'Active'])->id;
        }
        $first = $this->getJson($this->urlFor('vehicles', ['q' => 'SAME', 'page' => 1]))->assertOk()->json();
        $second = $this->getJson($this->urlFor('vehicles', ['q' => 'SAME', 'page' => 2]))->assertOk()->json();
        $this->assertTrue($first['pagination']['more']);
        $this->assertFalse($second['pagination']['more']);
        $this->assertSame($expected, array_map('intval', array_merge(array_column($first['results'], 'id'), array_column($second['results'], 'id'))));
    }

    public function test_rows_support_single_ended_date_filters_and_reject_reversed_range(): void
    {
        $newer = $this->entryOn('2026-09-10', 1);
        $older = $this->entryOn('2026-09-01', 1);
        $this->getJson($this->urlFor(query: ['date_from' => '2026-09-10']))->assertOk()->assertJsonPath('rows.0.id', $newer->id)->assertJsonCount(1, 'rows');
        $this->getJson($this->urlFor(query: ['date_to' => '2026-09-01']))->assertOk()->assertJsonPath('rows.0.id', $older->id)->assertJsonCount(1, 'rows');
        $this->getJson($this->urlFor(query: ['date_from' => '2026-09-10', 'date_to' => '2026-09-01']))->assertUnprocessable()->assertJsonValidationErrors('date_to');
    }

    public function test_lookup_query_and_page_validation_are_bounded(): void
    {
        foreach (['drivers', 'vehicles'] as $suffix) {
            $this->getJson($this->urlFor($suffix, ['page' => 0]))->assertUnprocessable();
            $this->getJson($this->urlFor($suffix, ['q' => str_repeat('x', 101)]))->assertUnprocessable();
        }
    }
}
