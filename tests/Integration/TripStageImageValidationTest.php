<?php

namespace Tests\Integration;

use App\Http\Controllers\TripController as AdminTripController;
use App\Http\Controllers\Api\V1\TripController as ApiTripController;
use App\Http\Resources\TripResource;
use App\Models\TripSheetEntryDor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\Support\TripAssignmentTestCase;

class TripStageImageValidationTest extends TripAssignmentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        DB::table('depots')->insert([['id' => 1, 'name' => 'Departure'], ['id' => 2, 'name' => 'Destination']]);
        Schema::create('trip_sheet_entry_dors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_sheet_entry_id');
            $table->string('start_image')->nullable();
            $table->string('end_image')->nullable();
            $table->string('odometer_start_image_path')->nullable();
            $table->timestamps();
        });
    }

    public function test_initial_verification_requires_start_photo_but_not_old_metric_photos(): void
    {
        $payload = [
            'trip_id' => $this->entry->id,
            'actual_start_time' => '08:00',
            'odometer_start_reading' => 1234,
            'route_start_soc_percent' => 80,
            'is_vehicle_verified' => true,
        ];
        $this->postJson('/api/v1/trips/start-verification', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors('start_image')->assertJsonMissingValidationErrors([
                'odometer_start_image_path', 'route_start_soc_percent_image',
            ]);

        $this->postJson('/api/v1/trips/start-verification', $payload + [
            'start_image' => UploadedFile::fake()->createWithContent('bad.txt', 'not-an-image'),
        ])->assertUnprocessable()->assertJsonValidationErrors('start_image');
    }

    public function test_final_verification_requires_end_photo_but_not_old_metric_photos(): void
    {
        $this->entry->update(['status' => 'initial_verification_completed']);
        $payload = [
            'trip_id' => $this->entry->id,
            'actual_end_time' => '10:00',
            'odometer_end_reading' => 1280,
            'route_end_soc_percent' => 40,
            'vp1' => 1643,
            'dp' => 37.5,
        ];
        $this->postJson('/api/v1/trips/start-verification', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors('end_image')->assertJsonMissingValidationErrors([
                'odometer_end_image_path', 'route_end_soc_percent_image', 'vp1_image', 'dp_image',
            ]);
    }

    public function test_admin_dor_exposes_only_start_and_end_photo_controls(): void
    {
        $fields = (new \ReflectionMethod(AdminTripController::class, 'dorFields'))
            ->invoke(new AdminTripController, $this->entry->fresh());
        $uploads = array_values(array_map(
            fn (array $field) => $field['name'],
            array_filter($fields, fn (array $field) => $field['type'] === 'file')
        ));

        $this->assertSame(['start_image', 'end_image'], $uploads);
    }

    public function test_stage_photos_store_and_admin_replacement_preserves_legacy_photo(): void
    {
        Storage::fake('public');
        $dor = TripSheetEntryDor::create([
            'trip_sheet_entry_id' => $this->entry->id,
            'odometer_start_image_path' => 'legacy/odometer.jpg',
        ]);
        $request = Request::create('/', 'POST', [], [], [
            'start_image' => UploadedFile::fake()->image('start.jpg'),
        ]);
        $payload = (new \ReflectionMethod(ApiTripController::class, 'apiDorImagePayload'))
            ->invoke(new ApiTripController, $request, $this->entry, $dor, ['start_image']);
        $dor->update($payload);
        Storage::disk('public')->assertExists($dor->fresh()->start_image);

        $request = Request::create('/', 'POST', [], [], [
            'start_image' => UploadedFile::fake()->image('replacement.jpg'),
            'end_image' => UploadedFile::fake()->image('end.jpg'),
        ]);
        $replacement = (new \ReflectionMethod(AdminTripController::class, 'dorImagePayload'))
            ->invoke(new AdminTripController, $request, $this->entry, $dor->fresh());
        $oldPath = $dor->start_image;
        $dor->update($replacement);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($dor->fresh()->start_image);
        Storage::disk('public')->assertExists($dor->fresh()->end_image);
        $this->assertSame('legacy/odometer.jpg', $dor->fresh()->odometer_start_image_path);

        $details = (new \ReflectionMethod(TripResource::class, 'dorDetails'))
            ->invoke(new TripResource($this->entry->fresh()->load('dor')));
        $this->assertSame(Storage::disk('public')->url($dor->fresh()->start_image), $details['start_image_url']);
        $this->assertSame(Storage::disk('public')->url($dor->fresh()->end_image), $details['end_image_url']);
    }

    public function test_dor_preview_shows_only_available_stage_photos(): void
    {
        View::share('errors', new ViewErrorBag);
        $render = fn (array $images) => view('trip.dor-preview', [
            'record' => $this->entry->sheet->trip,
            'entry' => $this->entry,
            'dor' => (object) ['is_completed' => false],
            'groups' => [],
            'odometerImages' => $images,
        ])->render();

        $both = $render(['start_image' => '/start.jpg', 'end_image' => '/end.jpg', 'dp_image' => '/legacy.jpg']);
        $this->assertStringContainsString('Start Trip Image', $both);
        $this->assertStringContainsString('End Trip Image', $both);
        $this->assertStringContainsString('/start.jpg', $both);
        $this->assertStringContainsString('/end.jpg', $both);
        $this->assertStringNotContainsString('/legacy.jpg', $both);

        $one = $render(['start_image' => '/start.jpg', 'end_image' => null]);
        $this->assertStringContainsString('Start Trip Image', $one);
        $this->assertStringNotContainsString('End Trip Image', $one);

        $none = $render(['start_image' => null, 'end_image' => null]);
        $this->assertStringNotContainsString('Verification Images', $none);
    }
}
