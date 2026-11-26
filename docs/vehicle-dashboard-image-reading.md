# Start/end dashboard image reading

`POST /api/v1/read-vehicle-dashboard-image` reads the required dashboard values from one uploaded photo using OpenAI's Responses API. This is a public endpoint, like the existing `/api/v1/read-vehicle-image` API. The existing API remains unchanged.

Send multipart form data with `photo` (JPG, JPEG, PNG, or WebP; maximum 5 MB) and required `type` (`start` or `end`). The server uses `OPENAI_API_KEY` and `OPENAI_VISION_MODEL`; clients must not send an OpenAI key.

```powershell
curl.exe -X POST "https://your-domain/api/v1/read-vehicle-dashboard-image" -H "Accept: application/json" -F "photo=@C:/photos/dashboard.jpg" -F "type=start"
```

`start` returns the total odometer and current battery state-of-charge percentage:

```json
{"success":true,"type":"start","odometer":160648,"battery_percentage":97}
```

`end` also returns DP (Driver Performance) and VP1 (Vehicle Performance 1) as `vp`:

```json
{"success":true,"type":"end","odometer":160648.5,"battery_percentage":42,"dp":37.5,"vp":1643}
```

Odometer, DP, and VP may be nonnegative integers or decimals. Battery percentage must be an integer from 0 to 100. Values are returned as displayed, without unit conversion or scaling. The odometer must be the total distance counter rather than a trip meter, and VP must be VP1 rather than VP2. Unclear values are never guessed.

All requested readings must be clear. If any one is missing, ambiguous, or outside its valid range, the entire request returns HTTP 422:

```json
{"success":false,"message":"Value not detected clearly"}
```

Invalid uploads or types return Laravel's HTTP 422 validation response. Missing server configuration and provider authentication, rate-limit, or server failures return 503; other provider failures or invalid output return 502; connection failures return 504; unexpected internal failures return 500. Provider payloads and credentials are not returned to clients. The image is sent directly to OpenAI with `store:false` and is not saved locally.
