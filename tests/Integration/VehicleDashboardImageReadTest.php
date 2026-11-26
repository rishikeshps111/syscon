<?php

namespace Tests\Integration;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VehicleDashboardImageReadTest extends TestCase
{
    private const URL = '/api/v1/read-vehicle-dashboard-image';

    private const PROVIDER = 'https://api.openai.com/v1/responses';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openai.api_key' => 'test-openai-key', 'services.openai.vision_model' => 'test-vision-model']);
        Http::preventStrayRequests();
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->image('dashboard.png', 10, 10);
    }

    private function provider(array $reading): array
    {
        return ['status' => 'completed', 'output' => [
            ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($reading)]]],
        ]];
    }

    private function reading(string $type): array
    {
        $reading = ['odometer' => 321797.6, 'battery_percentage' => 97];
        if ($type === 'end') {
            $reading += ['dp' => 37.5, 'vp' => 1643];
        }

        return $reading + ['odometer_display_type' => 'digital'];
    }

    public static function tripTypes(): array
    {
        return [['start'], ['end']];
    }

    #[DataProvider('tripTypes')]
    public function test_start_and_end_return_only_requested_readings_from_one_provider_call(string $type): void
    {
        $reading = $this->reading($type);
        Http::fake([self::PROVIDER => Http::response($this->provider($reading))]);
        $photo = $this->image();
        $this->postJson(self::URL, ['photo' => $photo, 'type' => $type])->assertOk()
            ->assertExactJson(['success' => true, 'type' => $type] + array_diff_key($reading, ['odometer_display_type' => true]));
        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($photo, $type, $reading) {
            $body = $request->data();
            $this->assertTrue($request->hasHeader('Authorization', 'Bearer test-openai-key'));
            $this->assertSame('test-vision-model', $body['model']);
            $this->assertFalse($body['store']);
            $this->assertSame('data:image/png;base64,'.base64_encode(file_get_contents($photo->getRealPath())), $body['input'][0]['content'][1]['image_url']);
            $this->assertSame('high', $body['input'][0]['content'][1]['detail']);
            $this->assertSame('json_schema', $body['text']['format']['type']);
            $this->assertTrue($body['text']['format']['strict']);
            $this->assertFalse($body['text']['format']['schema']['additionalProperties']);
            $this->assertSame(array_keys($reading), $body['text']['format']['schema']['required']);
            $this->assertStringContainsString($type === 'end' ? 'VP1' : 'total odometer', $body['input'][0]['content'][0]['text']);

            return true;
        });
    }

    public function test_any_unreadable_or_out_of_range_reading_fails_the_whole_request(): void
    {
        $cases = [
            ['start', 'odometer', null], ['start', 'odometer', -1],
            ['start', 'battery_percentage', null], ['start', 'battery_percentage', 101],
            ['end', 'dp', null], ['end', 'dp', -0.5],
            ['end', 'vp', null], ['end', 'vp', -1],
        ];
        $sequence = Http::sequence();
        foreach ($cases as [$type, $field, $value]) {
            $reading = $this->reading($type);
            $reading[$field] = $value;
            $sequence->push($this->provider($reading));
        }
        $sequence->push($this->provider(['odometer' => 1234, 'battery_percentage' => 50, 'odometer_display_type' => 'unknown']));
        Http::fake([self::PROVIDER => $sequence]);
        foreach ($cases as [$type]) {
            $this->postJson(self::URL, ['photo' => $this->image(), 'type' => $type])->assertUnprocessable()
                ->assertExactJson(['success' => false, 'message' => 'Value not detected clearly']);
        }
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'start'])->assertUnprocessable();
    }

    public function test_invalid_upload_and_type_do_not_call_provider(): void
    {
        Http::fake();
        $this->postJson(self::URL)->assertUnprocessable()->assertJsonValidationErrors(['photo', 'type']);
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'battery'])->assertJsonValidationErrors('type');
        $this->postJson(self::URL, ['photo' => $this->image()->size(5121), 'type' => 'start'])->assertJsonValidationErrors('photo');
        Http::assertNothingSent();
    }

    public function test_malformed_provider_output_and_invalid_value_types_return_502(): void
    {
        $invalid = $this->reading('end');
        $invalid['battery_percentage'] = 97.5;
        $missing = $this->reading('end');
        unset($missing['vp']);
        Http::fake([self::PROVIDER => Http::sequence()
            ->push($this->provider($invalid))->push($this->provider($missing))
            ->push(['status' => 'incomplete', 'output' => []])
            ->push(['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => '{broken']]]]])]);
        for ($i = 0; $i < 4; $i++) {
            $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'end'])->assertStatus(502)
                ->assertJsonPath('success', false);
        }
    }

    public function test_provider_refusal_and_failures_have_safe_responses(): void
    {
        Http::fake([self::PROVIDER => Http::sequence()
            ->push(['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'refusal']]]]])
            ->push(['error' => ['message' => 'private']], 429)]);
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'start'])->assertUnprocessable();
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'start'])->assertStatus(503)
            ->assertExactJson(['success' => false, 'message' => 'Image reading service is unavailable. Please try again later.']);
    }

    public function test_connection_failure_returns_504(): void
    {
        Http::fake(fn () => throw new ConnectionException('private'));
        $this->postJson(self::URL, ['photo' => $this->image(), 'type' => 'start'])->assertStatus(504)
            ->assertJsonPath('success', false);
    }
}
