<?php

namespace App\Http\Controllers;

use App\Models\HrLetterTemplate;
use App\Support\HrLetterRenderer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class HrLetterTemplateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('hr-letter-templates.view'), ['index', 'show']),
            new Middleware(PermissionMiddleware::using('hr-letter-templates.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('hr-letter-templates.edit'), ['edit', 'update']),
            new Middleware(PermissionMiddleware::using('hr-letter-templates.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = HrLetterTemplate::query()->select(['id', 'entity_type', 'language', 'template_name', 'is_active', 'created_at'])->latest();

            if (request()->filled('entity_type')) $query->where('entity_type', request('entity_type'));
            if (request()->filled('language')) $query->where('language', request('language'));
            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) $query->where('is_active', request('status'));

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('entity_label', fn ($row) => HrLetterTemplate::ENTITY_TYPES[$row->entity_type] ?? $row->entity_type)
                ->addColumn('status_label', fn ($row) => $row->is_active ? '<span class="status-green">Active</span>' : '<span class="status-red">Inactive</span>')
                ->addColumn('action', fn ($row) => view('hr-letter-template.partials.action', compact('row'))->render())
                ->rawColumns(['status_label', 'action'])->make(true);
        }

        return view('hr-letter-template.index', ['entityTypes' => HrLetterTemplate::ENTITY_TYPES, 'languages' => HrLetterTemplate::LANGUAGES]);
    }

    public function create()
    {
        return view('hr-letter-template.form', $this->formData());
    }

    public function show(HrLetterTemplate $hrLetterTemplate)
    {
        return view('hr-letter-template.show', ['template' => $hrLetterTemplate]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        HrLetterTemplate::create($data);

        return redirect()->route('hr-letter-templates.index')->with('success', 'Letter template created successfully.');
    }

    public function edit(HrLetterTemplate $hrLetterTemplate)
    {
        return view('hr-letter-template.form', $this->formData($hrLetterTemplate));
    }

    public function update(Request $request, HrLetterTemplate $hrLetterTemplate)
    {
        $data = $this->validated($request, $hrLetterTemplate);
        $data['updated_by'] = auth()->id();

        if ($request->hasFile('header_logo') && $hrLetterTemplate->header_logo) {
            Storage::disk('public')->delete($hrLetterTemplate->header_logo);
        }

        $hrLetterTemplate->update($data);
        return redirect()->route('hr-letter-templates.index')->with('success', 'Letter template updated successfully.');
    }

    public function destroy(HrLetterTemplate $hrLetterTemplate)
    {
        if ($hrLetterTemplate->header_logo) Storage::disk('public')->delete($hrLetterTemplate->header_logo);
        $hrLetterTemplate->delete();
        return response()->json(['success' => true, 'message' => 'Letter template deleted successfully.']);
    }

    private function validated(Request $request, ?HrLetterTemplate $template = null): array
    {
        $data = $request->validate([
            'entity_type' => ['required', Rule::in(array_keys(HrLetterTemplate::ENTITY_TYPES))],
            'language' => ['required', Rule::in(HrLetterTemplate::LANGUAGES)],
            'template_name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'header_logo' => ['nullable', 'image', 'max:2048'],
            'header_address' => ['nullable', 'string'],
            'footer_content' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($request->hasFile('header_logo')) {
            $data['header_logo'] = $request->file('header_logo')->store('hr-letter-templates', 'public');
        } else {
            unset($data['header_logo']);
        }

        $data['content'] = $this->sanitizeHtml($data['content']);

        return $data;
    }

    private function sanitizeHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><div><span><strong><b><em><i><u><ol><ul><li><table><thead><tbody><tr><th><td><h1><h2><h3><h4><blockquote><a>');
        $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        return preg_replace('/(href\s*=\s*["\'])\s*javascript:/i', '$1#', $html) ?? $html;
    }

    private function formData(?HrLetterTemplate $record = null): array
    {
        return ['record' => $record, 'entityTypes' => HrLetterTemplate::ENTITY_TYPES, 'languages' => HrLetterTemplate::LANGUAGES, 'placeholders' => HrLetterRenderer::PLACEHOLDERS];
    }
}
