<?php

namespace App\Http\Controllers;

use App\Exports\OemExport;
use App\Http\Requests\StoreOemRequest;
use App\Http\Requests\UpdateOemRequest;
use App\Models\District;
use App\Models\Location;
use App\Models\Oem;
use App\Models\OemType;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class OemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('oems.view'), ['index', 'show', 'export', 'downloadPdf']),
            new Middleware(PermissionMiddleware::using('oems.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('oems.edit'), ['edit', 'update', 'verify', 'changeStatus']),
            new Middleware(PermissionMiddleware::using('oems.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of($this->filteredQuery())
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('type', fn($row) => $row->oem_type)
                ->addColumn('state', fn($row) => $row->state?->name ?? '-')
                ->addColumn('verification_status', fn($row) => $row->is_verified
                    ? '<span class="status-green">Verified</span>'
                    : '<span class="status-orange">Pending</span>')
                ->addColumn('last_updated', fn($row) => $row->updated_at?->format('d-m-Y') ?? '-')
                ->addColumn('status', fn($row) => $this->statusBadge($row->status))
                ->addColumn('action', fn($row) => view('oem.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'verification_status', 'status', 'action'])
                ->make(true);
        }

        return view('oem.index', [
            'states' => State::orderBy('name')->get(['id', 'name']),
            'oemTypes' => OemType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => Oem::STATUSES,
        ]);
    }

    public function create()
    {
        return view('oem.form', array_merge($this->formData(), [
            'generatedCode' => $this->generateOemCode(((int) Oem::max('id')) + 1),
        ]));
    }

    public function store(StoreOemRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $oem = Oem::create($this->oemData($data) + [
                'oem_code' => null,
                'status' => 'Active',
                'is_verified' => false,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $oem->oem_code = $this->generateOemCode($oem->id);
            $oem->save();

            $this->syncContacts($oem, $data['contacts']);
            $this->syncAddresses($oem, $data['addresses']);
        });

        return redirect()->route('oems.index')->with('success', 'OEM created successfully.');
    }

    public function edit(Oem $oem)
    {
        return view('oem.form', array_merge($this->formData(), [
            'record' => $oem->load(['contacts', 'addresses']),
        ]));
    }

    public function show(Oem $oem)
    {
        return view('oem.show', [
            'record' => $this->oemRecord($oem),
        ]);
    }

    public function downloadPdf(Oem $oem)
    {
        $record = $this->oemRecord($oem);
        $pdf = $this->buildOemPdf($record);
        $fileName = ($record->oem_code ?: 'OEM') . '-profile.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function update(UpdateOemRequest $request, Oem $oem)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $oem) {
            $oem->update($this->oemData($data) + [
                'updated_by' => auth()->id(),
            ]);

            $this->syncContacts($oem, $data['contacts']);
            $this->syncAddresses($oem, $data['addresses']);
        });

        return redirect()->route('oems.index')->with('success', 'OEM updated successfully.');
    }

    public function destroy(Oem $oem)
    {
        $oem->delete();

        return response()->json([
            'success' => true,
            'message' => 'OEM deleted successfully.',
        ]);
    }

    public function verify(Oem $oem)
    {
        $oem->update([
            'is_verified' => true,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OEM verified successfully.',
        ]);
    }

    public function changeStatus(Request $request, Oem $oem)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Oem::STATUSES))],
        ]);

        $oem->update($validated + [
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OEM status updated successfully.',
        ]);
    }

    private function formData(): array
    {
        return [
            'states' => State::orderBy('name')->get(['id', 'name']),
            'districts' => District::orderBy('name')->get(['id', 'state_id', 'name']),
            'locations' => Location::orderBy('name')->get(['id', 'state_id', 'district_id', 'name', 'pincode']),
            'oemTypes' => OemType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'registrationTypes' => Oem::REGISTRATION_TYPES,
            'addressTypes' => \App\Models\OemAddress::ADDRESS_TYPES,
        ];
    }

    private function oemData(array $data): array
    {
        return collect($data)->only([
            'state_id',
            'oem_name',
            'short_name',
            'oem_type',
            'registration_type',
            'gst_number',
            'pan_number',
            'cin_number',
            'remarks',
        ])->all();
    }

    private function syncContacts(Oem $oem, array $contacts): void
    {
        $oem->contacts()->delete();

        foreach ($contacts as $index => $contact) {
            $oem->contacts()->create($contact + [
                'is_primary' => $index === 0 && ! collect($contacts)->contains('is_primary', true),
            ]);
        }
    }

    private function syncAddresses(Oem $oem, array $addresses): void
    {
        $oem->addresses()->delete();

        foreach ($addresses as $address) {
            $oem->addresses()->create($address);
        }
    }

    private function generateOemCode(int $id): string
    {
        return generate_code('OEM Module', $id, 3, 'OEM');
    }

    private function oemRecord(Oem $oem): Oem
    {
        return $oem->load([
            'state',
            'contacts',
            'addresses.state',
            'addresses.district',
            'addresses.city',
            'documents.documentType',
            'bankDetails',
            'stateMappings.state',
            'verifiedBy',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = $this->filteredQuery();

        if (! empty($ids)) {
            $query->whereIn('oems.id', $ids);
        }

        return Excel::download(new OemExport($query), 'oems.xlsx');
    }

    private function filteredQuery()
    {
        $query = Oem::with('state')->select('oems.*');

        if (request()->filled('search_text')) {
            $search = request('search_text');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('oems.oem_code', 'like', '%' . $search . '%')
                    ->orWhere('oems.oem_name', 'like', '%' . $search . '%')
                    ->orWhere('oems.gst_number', 'like', '%' . $search . '%')
                    ->orWhere('oems.pan_number', 'like', '%' . $search . '%');
            });
        }

        if (request()->filled('state_id')) {
            $query->where('state_id', request('state_id'));
        }

        if (request()->filled('oem_type')) {
            $query->where('oem_type', request('oem_type'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        if (request()->filled('date_from')) {
            $query->whereDate('updated_at', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $query->whereDate('updated_at', '<=', request('date_to'));
        }

        return $query->orderBy('updated_at', 'desc');
    }

    private function statusBadge(?string $status): string
    {
        return match ($status) {
            'Active' => '<span class="status-green">Active</span>',
            'Blocked' => '<span class="status-red">Blocked</span>',
            default => '<span class="status-orange">Inactive</span>',
        };
    }

    private function buildOemPdf(Oem $record): string
    {
        $content = '';
        $this->pdfFill($content, 0.96, 0.97, 0.99, 0, 0, 595, 842);
        $this->pdfText($content, 'SYSCON', 50, 795, 18, 'F2');
        $this->pdfText($content, 'OEM Profile', 50, 770, 22, 'F2');
        $this->pdfText($content, 'Generated on ' . now()->format('d-m-Y'), 430, 795, 10);
        $this->pdfStatus($content, $record->status ?: 'Inactive', 465, 765, $record->status === 'Active');

        $this->pdfCard($content, 40, 615, 515, 125);
        $this->pdfText($content, $record->oem_name ?: '-', 60, 708, 18, 'F2');
        $this->pdfText($content, 'OEM Code: ' . ($record->oem_code ?: '-'), 60, 686, 11);
        $this->pdfText($content, 'Short Name: ' . ($record->short_name ?: '-'), 60, 668, 10);
        $this->pdfText($content, 'Type: ' . ($record->oem_type ?: '-'), 60, 650, 10);
        $this->pdfText($content, 'Registration: ' . ($record->registration_type ?: '-'), 310, 686, 10);
        $this->pdfText($content, 'State: ' . ($record->state?->name ?: '-'), 310, 668, 10);
        $this->pdfText($content, 'Verification: ' . ($record->is_verified ? 'Verified' : 'Pending'), 310, 650, 10);

        $this->pdfSection($content, 'Business Details', 40, 445, 250, [
            'GST Number' => $record->gst_number ?: '-',
            'PAN Number' => $record->pan_number ?: '-',
            'CIN Number' => $record->cin_number ?: '-',
            'Remarks' => $record->remarks ?: '-',
        ]);

        $primaryContact = $record->contacts->firstWhere('is_primary', true) ?: $record->contacts->first();
        $this->pdfSection($content, 'Primary Contact', 305, 445, 250, [
            'Name' => $primaryContact?->contact_person ?: '-',
            'Designation' => $primaryContact?->designation ?: '-',
            'Phone' => $primaryContact?->full_phone ?: '-',
            'Email' => $primaryContact?->email ?: '-',
        ]);

        $primaryBank = $record->bankDetails->firstWhere('is_primary', true) ?: $record->bankDetails->first();
        $this->pdfSection($content, 'Primary Bank', 40, 270, 250, [
            'Account Name' => $primaryBank?->account_name ?: '-',
            'Account Number' => $primaryBank?->account_number ?: '-',
            'Bank Name' => $primaryBank?->bank_name ?: '-',
            'IFSC Code' => $primaryBank?->ifsc_code ?: '-',
        ]);

        $primaryMapping = $record->stateMappings->firstWhere('is_primary', true) ?: $record->stateMappings->first();
        $this->pdfSection($content, 'Primary State Mapping', 305, 270, 250, [
            'State' => $primaryMapping?->state?->name ?: '-',
            'GST Number' => $primaryMapping?->gst_number ?: '-',
            'Status' => $primaryMapping?->status ? 'Active' : 'Inactive',
        ]);

        $details = '';
        $this->pdfFill($details, 0.96, 0.97, 0.99, 0, 0, 595, 842);
        $this->pdfText($details, 'OEM Additional Details', 50, 790, 20, 'F2');
        $y = 735;

        foreach ([
            'Contacts' => $record->contacts->map(fn ($item) => ($item->contact_person ?: '-') . ' | ' . ($item->full_phone ?: '-') . ' | ' . ($item->email ?: '-')),
            'Addresses' => $record->addresses->map(fn ($item) => ($item->address_type ?: '-') . ' | ' . ($item->full_address ?: '-')),
            'Documents' => $record->documents->map(fn ($item) => ($item->documentType?->name ?: 'Document') . ' | ' . ($item->is_verified ? 'Verified' : 'Not Verified')),
        ] as $title => $rows) {
            $this->pdfText($details, $title, 50, $y, 14, 'F2');
            $y -= 24;

            if ($rows->isEmpty()) {
                $this->pdfText($details, 'No records found.', 65, $y, 10);
                $y -= 22;
            }

            foreach ($rows as $row) {
                if ($y < 70) {
                    break;
                }
                $this->pdfText($details, '- ' . $row, 65, $y, 9);
                $y -= 18;
            }

            $y -= 14;
        }

        return $this->pdfDocument([$content, $details]);
    }

    private function pdfDocument(array $contents): string
    {
        $pageCount = count($contents);
        $fontObject = 3 + ($pageCount * 2);
        $boldFontObject = $fontObject + 1;
        $objects = [];
        $pageObjectNumbers = [];

        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        foreach ($contents as $index => $content) {
            $pageObject = 3 + ($index * 2);
            $contentObject = $pageObject + 1;
            $pageObjectNumbers[] = $pageObject . ' 0 R';
            $objects[$pageObject] = $pageObject . " 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 " . $fontObject . " 0 R /F2 " . $boldFontObject . " 0 R >> >> /Contents " . $contentObject . " 0 R >>\nendobj\n";
            $objects[$contentObject] = $contentObject . " 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";
        }

        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [" . implode(' ', $pageObjectNumbers) . '] /Count ' . count($pageObjectNumbers) . " >>\nendobj\n";
        $objects[$fontObject] = $fontObject . " 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[$boldFontObject] = $boldFontObject . " 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
    }

    private function pdfSection(string &$content, string $title, int $x, int $y, int $width, array $items, int $height = 140): void
    {
        $this->pdfCard($content, $x, $y, $width, $height);
        $this->pdfText($content, $title, $x + 14, $y + $height - 26, 13, 'F2');
        $lineY = $y + $height - 50;

        foreach ($items as $label => $value) {
            $this->pdfText($content, $label . ':', $x + 14, $lineY, 9, 'F2');
            $this->pdfText($content, (string) $value, $x + 125, $lineY, 9);
            $lineY -= 17;
        }
    }

    private function pdfCard(string &$content, int $x, int $y, int $width, int $height): void
    {
        $this->pdfFill($content, 1, 1, 1, $x, $y, $width, $height);
        $content .= "0.84 0.86 0.90 RG\n";
        $content .= $x . ' ' . $y . ' ' . $width . ' ' . $height . " re S\n";
    }

    private function pdfFill(string &$content, float $r, float $g, float $b, int $x, int $y, int $width, int $height): void
    {
        $content .= sprintf("%.2f %.2f %.2f rg\n%d %d %d %d re f\n", $r, $g, $b, $x, $y, $width, $height);
    }

    private function pdfText(string &$content, string $text, int $x, int $y, int $size = 10, string $font = 'F1'): void
    {
        $content .= "0.08 0.10 0.14 rg\n";
        $content .= "BT\n/" . $font . ' ' . $size . " Tf\n" . $x . ' ' . $y . " Td\n(" . $this->escapePdfText(substr($text, 0, 78)) . ") Tj\nET\n";
    }

    private function pdfStatus(string &$content, string $text, int $x, int $y, bool $positive): void
    {
        if ($positive) {
            $this->pdfFill($content, 0.88, 0.97, 0.91, $x, $y, 82, 24);
            $content .= "0.13 0.55 0.27 rg\n";
        } else {
            $this->pdfFill($content, 1.00, 0.90, 0.90, $x, $y, 82, 24);
            $content .= "0.78 0.16 0.16 rg\n";
        }

        $content .= "BT\n/F2 10 Tf\n" . ($x + 14) . ' ' . ($y + 8) . " Td\n(" . $this->escapePdfText($text) . ") Tj\nET\n";
    }

    private function escapePdfText(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
