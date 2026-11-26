@php
    $logoSource = $letter->header_logo ? asset('storage/'.$letter->header_logo) : null;
    if ($pdfMode ?? false) {
        $logoSource = null;
        if ($letter->header_logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($letter->header_logo)) {
            $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($letter->header_logo);
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoSource = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logoPath));
        }
    }
@endphp
@if($logoSource)<div style="text-align:center;margin-bottom:10px"><img src="{{ $logoSource }}" style="max-height:90px;max-width:250px"></div>@endif
@if($letter->header_address)<div style="text-align:center;white-space:pre-line">{{ $letter->header_address }}</div><hr>@endif
<h2 style="text-align:center">{{ $letter->subject }}</h2>
<div>{!! $letter->content !!}</div>
@if($letter->footer_content)<div style="margin-top:50px;border-top:1px solid #aaa;padding-top:10px;white-space:pre-line">{{ $letter->footer_content }}</div>@endif
