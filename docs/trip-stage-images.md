# Trip verification stage photos

`POST /api/v1/trips/start-verification` accepts multipart form data. During initial verification, include a required `start_image` with the existing start time, odometer reading, battery percentage, and vehicle verification fields. During final verification, include a required `end_image` with the existing end time, odometer reading, battery percentage, VP1, and DP fields.

The two photos must be JPG, JPEG, PNG, or WebP, up to 4 MB each. Separate `odometer_start_image_path`, `route_start_soc_percent_image`, `odometer_end_image_path`, `route_end_soc_percent_image`, `vp1_image`, and `dp_image` uploads remain accepted but are optional. Existing numeric fields remain required as before.

Trip responses expose `start_image_url` and `end_image_url`. The admin DOR form shows only Start Trip Image and End Trip Image for upload, replacement, and viewing. Existing separate metric photos are retained in storage and their legacy API URL fields remain available. Old trips are not backfilled into the new photo fields.
