<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VehicleImageReadController extends Controller
{
    public function read(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'type' => 'required|in:battery,odometer',
        ]);

        try {
            $photo = $request->file('photo');

            $response = Http::attach(
                'file',
                file_get_contents($photo->getRealPath()),
                $photo->getClientOriginalName()
            )->post('https://api.ocr.space/parse/image', [
                'apikey' => env('OCR_SPACE_API_KEY'),
                'language' => 'eng',
                'isOverlayRequired' => 'false',
                'OCREngine' => '2',

                // Improve OCR accuracy
                'scale' => 'true',
                'detectOrientation' => 'true',
                'isTable' => 'false',
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OCR API request failed',
                    'error' => $response->body(),
                ], 500);
            }

            $data = $response->json();

            if (!empty($data['IsErroredOnProcessing'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'OCR processing failed',
                    'error' => $data['ErrorMessage'] ?? 'Unknown OCR error',
                    'data' => $data,
                ], 500);
            }

            $ocrText = $data['ParsedResults'][0]['ParsedText'] ?? '';

            if ($request->type === 'battery') {
                $value = $this->extractBatteryPercentage($ocrText);
            } else {
                $value = $this->extractOdometerValue($ocrText);
            }

            if ($value === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Value not detected clearly',
                    'data' => [
                        'type' => $request->type,
                        'value' => null,
                        'ocr_text' => $ocrText,
                    ],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'type' => $request->type,

                // Battery example: 97
                // Odometer example: 160648
                'value' => $value,

                'ocr_text' => $ocrText,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image reading failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function extractBatteryPercentage(string $text): ?int
    {
        if (!$text) {
            return null;
        }

        $cleanText = strtoupper($text);

        /*
            Possible OCR texts:
            97%
            BATTERY 97%
            CHARGE 97 %
            SOC 97%
            BATTERY 97 PERCENT
        */

        // Case 1: 97%
        preg_match('/\b(100|[1-9]?\d)\s*%/', $cleanText, $match);

        if (!empty($match[1])) {
            return (int) $match[1];
        }

        // Case 2: 97 PERCENT / 97 PERCENTAGE
        preg_match('/\b(100|[1-9]?\d)\s*(PERCENT|PERCENTAGE)/', $cleanText, $match);

        if (!empty($match[1])) {
            return (int) $match[1];
        }

        // Case 3: BATTERY 97 / CHARGE 97 / SOC 97
        preg_match('/(BATTERY|CHARGE|SOC)\D{0,20}(100|[1-9]?\d)/', $cleanText, $match);

        if (!empty($match[2])) {
            return (int) $match[2];
        }

        // Case 4: fallback - find any number from 0 to 100
        preg_match_all('/\b\d{1,3}\b/', $cleanText, $matches);

        if (empty($matches[0])) {
            return null;
        }

        foreach ($matches[0] as $number) {
            $value = (int) $number;

            if ($value >= 0 && $value <= 100) {
                return $value;
            }
        }

        return null;
    }

    private function extractOdometerValue(string $text): ?int
    {
        if (!$text) {
            return null;
        }

        $cleanText = strtoupper($text);

        /*
            Common OCR mistakes:
            O/Q = 0
            I/L/| = 1
            S = 5
            B = 8
        */
        $cleanText = str_replace(['O', 'Q'], '0', $cleanText);
        $cleanText = str_replace(['I', 'L', '|'], '1', $cleanText);
        $cleanText = str_replace('S', '5', $cleanText);
        $cleanText = str_replace('B', '8', $cleanText);

        // Remove common separators
        $cleanText = str_replace([',', ' ', "\n", "\r", "\t"], '', $cleanText);

        /*
            Find odometer-like numbers.
            Usually odometer can be 4 to 8 digits.
            Example: 160648
        */
        preg_match_all('/\d{4,8}/', $cleanText, $matches);

        if (empty($matches[0])) {
            return null;
        }

        $numbers = array_map('intval', $matches[0]);

        // Usually odometer value is the largest detected number
        return max($numbers);
    }
}
