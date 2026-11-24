# Vehicle dashboard image reading with OpenAI

`POST /api/v1/read-vehicle-image` reads battery SOC, the total odometer, DP (Driver Performance) or VP1 (Vehicle Performance) from an uploaded dashboard photo. It uses OpenAI's Responses API. The endpoint remains public and does not require a login token, matching the existing callers.

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

Send multipart form data with `photo` (JPG, JPEG, PNG or WebP; maximum 5 MB) and one of these `type` values:

| type | Reading | Numeric value |
|---|---|---|
| `battery` | Battery SOC | Integer, 0–100 |
| `odometer` | Total odometer | Nonnegative integer or decimal |
| `dp` | DP / Driver Performance | Nonnegative integer or decimal |
| `vp` | VP1 / Vehicle Performance | Nonnegative integer or decimal |

For example, in PowerShell:

```powershell
curl.exe -X POST "https://your-domain/api/v1/read-vehicle-image" -H "Accept: application/json" -F "photo=@C:/photos/dashboard.jpg" -F "type=battery"
```

Change the type field to `odometer`, `dp` or `vp` to select the reading. `vp1` is a dashboard label, not an accepted request type. No OpenAI credential is included in the client request.

Successful response:

```json
{"success":true,"type":"battery","value":97,"ocr_text":"SOC 97%"}
```

Battery values are integers from 0 through 100. Odometer values are nonnegative integers or decimals in the displayed units, with no unit conversion, rounding or truncation. For example, visible `km 321797.6` returns numeric `321797.6`, not `321797` or `3217976`. Existing whole-number readings remain integers; clients must also accept fractional JSON numbers. The model is instructed to ignore trip meters, speed and remaining range, and to return null rather than guess unclear readings. `ocr_text` contains relevant visible display text rather than an unrestricted transcription of the entire image.

For odometers, the provider must classify the counter internally as `digital`, `analog` (mechanical numbered wheels/drums), or `unknown`. Classification refers to the distance counter, so a digital odometer inside an analog speedometer cluster is still digital. Needle-only dials cannot supply total distance. Unknown displays are rejected even if the provider also proposes a number. This internal classification does not change the public response fields.

The mechanical-counter prompt rejects ambiguous rollover wheels and prevents a fractional wheel from being appended to the whole-distance number. Clearly marked fractional wheels are included with their visible decimal position and scale; a different wheel color alone is insufficient to infer that scale. Ambiguous fractions return null. The battery prompt requires a clearly readable numeric SOC, and rejects estimates from bars, arcs or needles, battery-health percentages and charge-limit settings.

DP and VP1 are read only when their own label clearly identifies the associated number. DP is not substituted with SOC or another percentage; VP1 is not substituted with VP2. Handwritten annotations, added numbering, arrows and underlines are ignored. Missing or ambiguous performance labels/readings return the same HTTP 422/null response as other unreadable values.

For these two types, `value` preserves the displayed numeric magnitude without rounding, scaling or inserting a decimal point: `DP 37.50%` gives numeric `37.5`, and `VP1: 1643` gives `1643`, not `1.643`. The original visible decimals and units remain in `ocr_text`; numeric JSON does not guarantee trailing zeroes. There is no artificial 100-point cap on VP1. Battery remains integer-only.

```powershell
curl.exe -X POST "https://your-domain/api/v1/read-vehicle-image" -H "Accept: application/json" -F "photo=@C:/photos/dashboard.jpg" -F "type=dp"
curl.exe -X POST "https://your-domain/api/v1/read-vehicle-image" -H "Accept: application/json" -F "photo=@C:/photos/dashboard.jpg" -F "type=vp"
```

Example performance responses:

```json
{"success":true,"type":"dp","value":37.5,"ocr_text":"DP 37.50%"}
{"success":true,"type":"vp","value":1643,"ocr_text":"VP1: 1643"}
```

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
