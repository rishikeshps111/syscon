<?php

namespace App\Http\Controllers;

use App\Models\GeneratedHrLetter;
use App\Models\HrLetterTemplate;
use App\Models\User;
use App\Support\HrLetterRenderer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class HrLetterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', new Middleware(PermissionMiddleware::using('hr-letters.view'), ['index', 'show', 'pdf']), new Middleware(PermissionMiddleware::using('hr-letters.generate'), ['create', 'store'])];
    }

    public function index(User $user)
    {
        if (request()->ajax()) {
            $query = GeneratedHrLetter::where('user_id', $user->id)->select(['id', 'letter_number', 'entity_type', 'language', 'subject', 'generated_at']);
            return DataTables::of($query)->addIndexColumn()
                ->addColumn('entity_label', fn ($row) => HrLetterTemplate::ENTITY_TYPES[$row->entity_type] ?? $row->entity_type)
                ->editColumn('generated_at', fn ($row) => $row->generated_at?->format('d-m-Y H:i'))
                ->addColumn('action', fn ($row) => view('hr-letter.partials.action', compact('row'))->render())
                ->rawColumns(['action'])->make(true);
        }
        return view('hr-letter.index', compact('user'));
    }

    public function create(User $user)
    {
        $templates = HrLetterTemplate::where('is_active', true)->orderBy('entity_type')->orderBy('language')->get();
        return view('hr-letter.form', compact('user', 'templates'));
    }

    public function store(Request $request, User $user, HrLetterRenderer $renderer)
    {
        $data = $request->validate([
            'template_id' => ['required', Rule::exists('hr_letter_templates', 'id')->where('is_active', true)],
            'warning_reason' => ['nullable', 'string'],
            'incident_date' => ['nullable', 'date'],
            'response_due_date' => ['nullable', 'date'],
        ]);
        $template = HrLetterTemplate::findOrFail($data['template_id']);

        if ($template->entity_type === 'warning_letter' && blank($data['warning_reason'] ?? null)) {
            return back()->withErrors(['warning_reason' => 'The warning reason is required for a warning letter.'])->withInput();
        }
        $rendered = $renderer->render($template, $user, $data);
        $letter = GeneratedHrLetter::create([
            'letter_number' => 'HRL-' . now()->format('Ymd') . '-' . str_pad((string) (((int) GeneratedHrLetter::max('id')) + 1), 5, '0', STR_PAD_LEFT),
            'template_id' => $template->id, 'user_id' => $user->id, 'entity_type' => $template->entity_type,
            'language' => $template->language, 'subject' => $rendered['subject'], 'content' => $rendered['content'],
            'header_logo' => $template->header_logo, 'header_address' => $rendered['header_address'],
            'footer_content' => $rendered['footer_content'], 'additional_data' => $data,
            'generated_by' => auth()->id(), 'generated_at' => now(),
        ]);
        return redirect()->route('hr-letters.show', $letter)->with('success', 'Letter generated successfully.');
    }

    public function show(GeneratedHrLetter $hrLetter) { return view('hr-letter.show', compact('hrLetter')); }

    public function pdf(GeneratedHrLetter $hrLetter)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $chroot = [base_path(), storage_path(), resource_path('fonts')];
        $options->setChroot($chroot);
        $dompdf = new Dompdf($options); $dompdf->loadHtml(view('hr-letter.pdf', compact('hrLetter'))->render()); $dompdf->setPaper('A4'); $dompdf->render();
        return response($dompdf->output())->header('Content-Type', 'application/pdf')->header('Content-Disposition', 'attachment; filename="' . $hrLetter->letter_number . '.pdf"');
    }
}
