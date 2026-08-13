<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:35px 45px}
@if (is_file(resource_path('fonts/NotoSansDevanagari-Regular.ttf')))
@font-face{font-family:"Noto Sans Devanagari";font-style:normal;font-weight:400;src:url("file:///{{ str_replace('\\', '/', resource_path('fonts/NotoSansDevanagari-Regular.ttf')) }}") format("truetype");}
@endif
@if (is_file(resource_path('fonts/NotoSansDevanagari-Bold.ttf')))
@font-face{font-family:"Noto Sans Devanagari";font-style:normal;font-weight:700;src:url("file:///{{ str_replace('\\', '/', resource_path('fonts/NotoSansDevanagari-Bold.ttf')) }}") format("truetype");}
@endif
body{font-family:"Noto Sans Devanagari","DejaVu Sans",sans-serif;font-size:12px;line-height:1.55;color:#111}
table{width:100%;border-collapse:collapse}th,td{border:1px solid #777;padding:6px}img{max-width:100%}
</style></head><body>@include('hr-letter.partials.document', ['letter' => $hrLetter, 'pdfMode' => true])</body></html>
