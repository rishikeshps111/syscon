<?php

use App\Models\GeneralSetting;

it('fetches the free no from general settings', function () {
    GeneralSetting::query()->create([
        'free_no' => '9876543210',
    ]);

    $this->getJson('/api/v1/free-no')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.free_no', '9876543210');
});

it('returns null free no when settings are not configured', function () {
    $this->getJson('/api/v1/free-no')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.free_no', null);
});
