<?php

namespace Tests\Integration;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VehicleImageReadTest extends TestCase
{
    private const URL = '/api/v1/read-vehicle-image';

    private const PROVIDER = 'https://api.openai.com/v1/responses';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openai.api_key' => 'test-openai-key', 'services.openai.vision_model' => 'test-vision-model']);
        Http::preventStrayRequests();
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('dashboard.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aVRsAAAAASUVORK5CYII='));
    }

    private function provider(mixed $value, mixed $text = 'SOC 97%', ?string $displayType = null): array
    {
        $reading = ['value' => $value, 'ocr_text' => $text];
        if ($displayType !== null) $reading['display_type'] = $displayType;
        return ['status' => 'completed', 'output' => [
            ['type' => 'reasoning', 'summary' => []],
            ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($reading)]]],
        ]];
    }

    public static function readings(): array
    {
        return [
            'empty_battery' => ['battery', 0, 'SOC 0%'],
            'battery' => ['battery', 97, 'Battery 97%'],
            'full_battery' => ['battery', 100, 'SOC 100%'],
            'zero_odometer' => ['odometer', 0, 'ODO 0 km'],
            'odometer' => ['odometer', 160648, 'ODO 160,648 km'],
        ];
    }

    #[DataProvider('readings')]
    public function test_public_endpoint_preserves_integer_reading_and_response_contract(string $type, int $value, string $text): void
    {
        Http::fake([self::PROVIDER => Http::response($this->provider($value, $text, $type === 'odometer' ? 'digital' : null))]);
        $photo = $this->image();
        $bytes = file_get_contents($photo->getRealPath());
        $this->postJson(self::URL, ['photo' => $photo, 'type' => $type])->assertOk()
            ->assertExactJson(['success' => true, 'type' => $type, 'value' => $value, 'ocr_text' => $text]);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($bytes, $type) {
            $body = $request->data();
            $this->assertSame(self::PROVIDER, $request->url());
            $this->assertTrue($request->hasHeader('Authorization', 'Bearer test-openai-key'));
            $this->assertSame('test-vision-model', $body['model']);
            $this->assertFalse($body['store']);
            $this->assertSame('data:image/png;base64,'.base64_encode($bytes), $body['input'][0]['content'][1]['image_url']);
            $this->assertSame('high', $body['input'][0]['content'][1]['detail']);
            $this->assertSame('json_schema', $body['text']['format']['type']);
            $this->assertTrue($body['text']['format']['strict']);
            $this->assertFalse($body['text']['format']['schema']['additionalProperties']);
            $this->assertStringContainsString($type === 'battery' ? 'state-of-charge' : 'total vehicle odometer', $body['input'][0]['content'][0]['text']);
            $this->assertStringContainsString('Never guess', $body['instructions']);

            return true;
        });
    }

    public static function unreadableReadings(): array
    {
        return [
            'ambiguous_battery' => ['battery', null, 'Two ambiguous battery displays'],
            'trip_meter_only' => ['odometer', null, 'TRIP 1234 km'],
            'above_battery_limit' => ['battery', 101, '101%'],
            'negative_battery' => ['battery', -1, '-1%'],
            'negative_odometer' => ['odometer', -10, '-10 km'],
            'unreadable_photo' => ['odometer', null, ''],
        ];
    }

    #[DataProvider('unreadableReadings')]
    public function test_ambiguous_or_out_of_range_values_return_existing_422_shape(string $type, ?int $value, string $text): void
    {
        Http::fake([self::PROVIDER => Http::response($this->provider($value, $text, $type === 'odometer' ? 'digital' : null))]);
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => $type])->assertStatus(422)
            ->assertExactJson(['success' => false, 'message' => 'Value not detected clearly', 'data' => [
                'type' => $type, 'value' => null, 'ocr_text' => $text,
            ]]);
    }

    public function test_upload_validation_rejects_missing_invalid_unsupported_and_oversized_images_without_provider_calls(): void
    {
        Http::fake();
        $this->postJson(self::URL)->assertUnprocessable()->assertJsonValidationErrors(['photo', 'type']);
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'speed'])->assertUnprocessable()->assertJsonValidationErrors('type');
        $invalid = UploadedFile::fake()->createWithContent('fake.jpg', 'not an image');
        $realUpload = new UploadedFile($invalid->getRealPath(), 'fake.jpg', 'image/jpeg', null, true);
        $this->postJson(self::URL, ['photo' => $realUpload, 'type' => 'battery'])
            ->assertUnprocessable()->assertJsonValidationErrors('photo');
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $this->postJson(self::URL, ['photo' => UploadedFile::fake()->createWithContent('dashboard.gif', $gif), 'type' => 'battery'])
            ->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->postJson(self::URL, ['photo' => $this->image()->size(5121), 'type' => 'battery'])
            ->assertUnprocessable()->assertJsonValidationErrors('photo');
        Http::assertNothingSent();
    }

    public function test_missing_key_or_model_is_a_configuration_error_without_provider_calls(): void
    {
        Http::fake();
        foreach (['api_key', 'vision_model'] as $setting) {
            $previous = config('services.openai.'.$setting);
            config(['services.openai.'.$setting => ' ']);
            $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'battery'])->assertStatus(503)
                ->assertExactJson(['success' => false, 'message' => 'Image reading is not configured.']);
            config(['services.openai.'.$setting => $previous]);
        }
        Http::assertNothingSent();
    }

    public static function imageFormats(): array
    {
        return [['jpg', 'image/jpeg'], ['jpeg', 'image/jpeg'], ['png', 'image/png'], ['webp', 'image/webp']];
    }

    #[DataProvider('imageFormats')]
    public function test_supported_image_formats_are_sent_with_their_image_mime_type(string $extension, string $mime): void
    {
        Http::fake([self::PROVIDER => Http::response($this->provider(97))]);
        $photo = UploadedFile::fake()->image('dashboard.'.$extension, 10, 10);
        $this->postJson(self::URL, ['photo' => $photo, 'type' => 'battery'])->assertOk();
        Http::assertSent(fn ($request) => $request['input'][0]['content'][1]['image_url']
            === 'data:'.$mime.';base64,'.base64_encode(file_get_contents($photo->getRealPath())));
    }

    public function test_exact_upload_size_limit_is_accepted(): void
    {
        Http::fake([self::PROVIDER => Http::response($this->provider(97))]);
        $this->postJson(self::URL, ['photo' => $this->image()->size(5120), 'type' => 'battery'])->assertOk();
        Http::assertSentCount(1);
    }

    public function test_unexpected_failure_does_not_expose_exception_or_image_contents(): void
    {
        Log::spy();
        Http::fake(fn () => throw new \RuntimeException('private-exception test-openai-key'));
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'battery'])->assertStatus(500)
            ->assertExactJson(['success' => false, 'message' => 'Image reading failed. Please try again.']);
        Log::shouldHaveReceived('error')->with('Vehicle image reading failed.', ['exception_type' => \RuntimeException::class])->once();
    }

    public static function providerFailures(): array
    {
        return [[400, 502], [401, 503], [403, 503], [429, 503], [500, 503], [503, 503]];
    }

    #[DataProvider('providerFailures')]
    public function test_provider_failures_do_not_expose_provider_payload_or_credentials(int $upstream, int $status): void
    {
        Log::spy();
        Http::fake([self::PROVIDER => Http::response(['error' => ['message' => 'private-provider-detail test-openai-key']], $upstream)]);
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'battery'])->assertStatus($status)
            ->assertExactJson(['success' => false, 'message' => 'Image reading service is unavailable. Please try again later.']);
        Log::shouldHaveReceived('warning')->with('Vehicle image provider request failed.', ['status' => $upstream])->once();
        Http::assertSentCount(1);
    }

    public function test_connection_failure_returns_safe_timeout_response(): void
    {
        Log::spy();
        Http::fake(fn () => throw new ConnectionException('private-connection-detail test-openai-key'));
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'odometer'])->assertStatus(504)
            ->assertExactJson(['success' => false, 'message' => 'Image reading service did not respond in time. Please try again.']);
        Log::shouldHaveReceived('warning')->with('Vehicle image provider connection timed out or could not be established.')->once();
    }

    public static function invalidProviderOutputs(): array
    {
        return array_map(fn ($reading) => [$reading], [
            'numeric_string' => ['value' => '97', 'ocr_text' => '97%'],
            'fractional_value' => ['value' => 97.5, 'ocr_text' => '97.5%'],
            'boolean_value' => ['value' => true, 'ocr_text' => '97%'],
            'missing_value' => ['ocr_text' => '97%'],
            'invalid_text' => ['value' => 97, 'ocr_text' => []],
            'extra_fields' => ['value' => 97, 'ocr_text' => '97%', 'private' => 'secret'],
        ]);
    }

    #[DataProvider('invalidProviderOutputs')]
    public function test_invalid_structured_output_is_not_coerced_to_a_reading(array $reading): void
    {
        $payload = $this->provider(97);
        $payload['output'][1]['content'][0]['text'] = json_encode($reading);
        Http::fake([self::PROVIDER => Http::response($payload)]);
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'battery'])->assertStatus(502)
            ->assertExactJson(['success' => false, 'message' => 'Image reading service returned an invalid response. Please try again.']);
    }

    public function test_incomplete_malformed_or_empty_provider_responses_do_not_produce_readings(): void
    {
        $broken = $this->provider(97);
        $broken['output'][1]['content'][0]['text'] = '{bad-json';
        $payloads = ['not json', ['status' => 'incomplete', 'output' => []], ['status' => 'completed', 'output' => []], $broken];
        $sequence = Http::sequence();
        foreach ($payloads as $payload) {
            $sequence->push($payload);
        }
        Http::fake([self::PROVIDER => $sequence]);
        foreach ($payloads as $payload) {
            $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'battery'])->assertStatus(502)
                ->assertJsonPath('success', false);
        }
    }

    public function test_refusal_is_unreadable_instead_of_a_guessed_number(): void
    {
        Http::fake([self::PROVIDER => Http::response(['status' => 'completed', 'output' => [
            ['type' => 'message', 'content' => [['type' => 'refusal', 'refusal' => 'private-provider-detail']]],
        ]])]);
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'battery'])->assertStatus(422)
            ->assertJsonPath('data.value', null)->assertJsonPath('data.ocr_text', '');
    }

    public static function odometerDisplays(): array
    {
        return [['digital', 160648, 'ODO 160,648 km'], ['analog', 1234, '001234']];
    }

    #[DataProvider('odometerDisplays')]
    public function test_digital_and_mechanical_analog_odometers_keep_the_public_integer_contract(string $display, int $value, string $text): void
    {
        Http::fake([self::PROVIDER => Http::response($this->provider($value, $text, $display))]);
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'odometer'])->assertOk()
            ->assertExactJson(['success' => true, 'type' => 'odometer', 'value' => $value, 'ocr_text' => $text]);
        Http::assertSent(function ($request) {
            $schema = $request['text']['format']['schema'];
            $this->assertSame(['digital', 'analog', 'unknown'], $schema['properties']['display_type']['enum']);
            $this->assertContains('display_type', $schema['required']);
            $prompt = $request['input'][0]['content'][0]['text'];
            $this->assertStringContainsString('classify the odometer counter itself', $prompt);
            $this->assertStringContainsString('mechanical odometer with numbered rolling wheels', $prompt);
            $this->assertStringContainsString('do not round or truncate', $prompt);
            return true;
        });
    }

    public function test_unknown_display_cannot_return_a_number_even_when_provider_guesses_one(): void
    {
        Http::fake([self::PROVIDER => Http::response($this->provider(120, 'Speedometer 120 km/h', 'unknown'))]);
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'odometer'])->assertUnprocessable()
            ->assertExactJson(['success' => false, 'message' => 'Value not detected clearly', 'data' => [
                'type' => 'odometer', 'value' => null, 'ocr_text' => 'Speedometer 120 km/h',
            ]]);
    }

    public function test_ambiguous_mechanical_counter_returns_null_without_reconstructing_digits(): void
    {
        Http::fake([self::PROVIDER => Http::response($this->provider(null, '001?34', 'analog'))]);
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'odometer'])->assertUnprocessable()
            ->assertJsonPath('data.value', null)->assertJsonPath('data.ocr_text', '001?34');
    }

    public function test_odometer_requires_a_valid_display_classification(): void
    {
        $sequence = Http::sequence()->push($this->provider(1234, 'ODO 1234'))
            ->push($this->provider(1234, 'ODO 1234', 'needle'));
        Http::fake([self::PROVIDER => $sequence]);
        for ($index = 0; $index < 2; $index++) {
            $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'odometer'])->assertStatus(502)
                ->assertJsonPath('success', false);
        }
    }
}
