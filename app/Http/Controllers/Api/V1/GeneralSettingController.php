<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Http\JsonResponse;

class GeneralSettingController extends Controller
{
    public function freeNo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Free no fetched successfully.',
            'data' => [
                'free_no' => GeneralSetting::query()->value('free_no'),
            ],
        ]);
    }
}
