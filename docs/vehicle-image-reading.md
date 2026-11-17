# Vehicle dashboard image reading with OpenAI

`POST /api/v1/read-vehicle-image` reads either battery SOC or the total odometer from an uploaded dashboard photo. It now uses OpenAI's Responses API instead of OCR.space. The endpoint remains public and does not require a login token, matching the existing callers.

## Server configuration

Set these values in the server's `.env` file:

```dotenv
OPENAI_API_KEY=your-server-side-key
OPENAI_VISION_MODEL=gpt-5.6-terra
```

Use an OpenAI API key with access to the configured model. Do not put the key in mobile/browser code, request fields, source control or logs. Rebuild Laravel's configuration cache after changing the server environment:

```powershell
php artisan config:cache
```

The model setting can be changed to another model supporting image input, the Responses API and structured JSON output. The integration has a 10-second connection timeout and 45-second request timeout. No database migration or new package is required. OCR_SPACE_API_KEY is no longer used by this endpoint.

## Request

Send multipart form data with `photo` (JPG, JPEG, PNG or WebP; maximum 5 MB) and `type` (`battery` or `odometer`). For example, in PowerShell:

```powershell
curl.exe -X POST "https://your-domain/api/v1/read-vehicle-image" -H "Accept: application/json" -F "photo=@C:/photos/dashboard.jpg" -F "type=battery"
```

Use `type=odometer` to request the total odometer instead. No OpenAI credential is included in the client request.

Successful response:

```json
{"success":true,"type":"battery","value":97,"ocr_text":"SOC 97%"}
```

Battery values are integers from 0 through 100. Odometer values are nonnegative integers in the displayed units, with no unit conversion or decimal rounding. The model is instructed to ignore trip meters, speed and remaining range, and to return null rather than guess unclear readings. `ocr_text` contains relevant visible display text rather than an unrestricted transcription of the entire image.

For odometers, the provider must classify the counter internally as `digital`, `analog` (mechanical numbered wheels/drums), or `unknown`. Classification refers to the distance counter, so a digital odometer inside an analog speedometer cluster is still digital. Needle-only dials cannot supply total distance. Unknown displays are rejected even if the provider also proposes a number. This internal classification does not change the public response fields.

The mechanical-counter prompt rejects ambiguous rollover wheels and prevents a differently colored fractional wheel from being appended to the whole-distance number. Fractional counters return null under the existing integer-only contract. The battery prompt requires a clearly readable numeric SOC, and rejects estimates from bars, arcs or needles, battery-health percentages and charge-limit settings.

Unclear or invalid-range readings return HTTP 422 using the existing failure shape:

```json
{"success":false,"message":"Value not detected clearly","data":{"type":"battery","value":null,"ocr_text":""}}
```

Invalid uploads/types also return 422 using Laravel's validation error response. Missing configuration and provider authentication/rate-limit/server failures return 503; other provider failures or malformed/incomplete output return 502; connection failures/timeouts return 504; unexpected internal failures return 500. Errors use `success:false` and a safe message without raw provider payloads or exception details.

Images are sent directly as Base64 image inputs; no local database record or permanent file is created. Responses are requested with `store:false`. This setting controls Responses API storage, not all provider data-retention policies.

## Verification

```powershell
php artisan test tests/Integration/VehicleImageReadTest.php
```

The automated tests mock all provider requests and require no real key or credits. Live accuracy checks still require the configured server key and representative dashboard photos, especially images containing trip meters, range readings or multiple displays. Mocked tests verify request/response handling, not a model's ability to interpret real photos.

References: [OpenAI image inputs](https://developers.openai.com/api/docs/guides/images-vision), [structured output](https://developers.openai.com/api/docs/guides/structured-outputs), [GPT-5.6 Terra](https://developers.openai.com/api/docs/models/gpt-5.6-terra).
