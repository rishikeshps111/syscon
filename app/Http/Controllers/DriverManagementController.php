<?php

namespace App\Http\Controllers;

use App\Exports\DriverManagementExport;
use App\Http\Requests\StoreDriverManagementRequest;
use App\Http\Requests\UpdateDriverManagementRequest;
use App\Models\BranchLocation;
use App\Models\Depot;
use App\Models\District;
use App\Models\DriverProfile;
use App\Models\Location;
use App\Models\State;
use App\Models\User;
use App\Support\SalaryComponents;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class DriverManagementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('driver-management.view'), ['index', 'show', 'export', 'downloadPdf', 'districtsByState', 'locationsByDistrict']),
            new Middleware(PermissionMiddleware::using('driver-management.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('driver-management.edit'), ['edit', 'update', 'status', 'regeneratePasscode']),
            new Middleware(PermissionMiddleware::using('driver-management.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of($this->filteredQuery())
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('phone_number', fn($row) => $row->full_phone ?: '-')
                ->addColumn('license_type', fn($row) => $row->driverProfile?->license_type_label ?: '-')
                ->addColumn('license_expiry', fn($row) => $this->licenseExpiryBadge($row->driverProfile?->expiry_date))
                ->addColumn('verification_status', fn($row) => $this->verificationBadge($row->driverProfile?->verification_status))
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', fn($row) => view('driver-management.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'license_expiry', 'verification_status', 'status', 'action'])
                ->make(true);
        }

        return view('driver-management.index', $this->formData());
    }

    public function create()
    {
        return view('driver-management.form', array_merge($this->formData(), [
            'districts' => collect(),
            'locations' => collect(),
            'generatedCode' => $this->generateDriverCode(((int) User::max('id')) + 1),
        ]));
    }

    public function store(StoreDriverManagementRequest $request)
    {
        $data = $request->validated();
        $user = User::create([
            'code' => null,
            'name' => $data['name'],
            'email' => $data['email'],
            'country_code' => $data['country_code'],
            'phone' => $data['phone'],
            'password' => $data['passcode'],
            'is_active' => $data['is_active'],
        ]);
        $user->code = $this->generateDriverCode($user->id);
        $user->save();
        $this->storeAvatar($request, $user);
        $user->assignRole('Driver');
        $user->driverProfile()->create($this->profileData($data));
        SalaryComponents::sync($user, $data['salary_components'] ?? []);

        return redirect()->route('driver-management.index')->with('success', 'Driver created successfully.');
    }

    public function show(User $driver_management)
    {
        abort_unless($driver_management->hasRole('Driver'), 404);

        $record = $this->driverRecord($driver_management);

        return view('driver-management.show', compact('record'));
    }

    public function downloadPdf(User $driver_management)
    {
        abort_unless($driver_management->hasRole('Driver'), 404);

        $record = $this->driverRecord($driver_management);
        $pdf = $this->buildDriverPdf($record);
        $fileName = ($record->code ?: 'driver') . '-profile.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function edit(User $driver_management)
    {
        abort_unless($driver_management->hasRole('Driver'), 404);

        $record = $driver_management->load('driverProfile');
        $profile = $record->driverProfile;
        $districts = $profile
            ? District::where('state_id', $profile->state_id)->orderBy('name')->get(['id', 'name'])
            : collect();
        $locations = $profile
            ? Location::where('state_id', $profile->state_id)->where('district_id', $profile->district_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('driver-management.form', array_merge($this->formData(), compact('record', 'districts', 'locations')));
    }

    public function update(UpdateDriverManagementRequest $request, User $driver_management)
    {
        abort_unless($driver_management->hasRole('Driver'), 404);

        $data = $request->validated();
        $driver_management->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'country_code' => $data['country_code'],
            'phone' => $data['phone'],
            'is_active' => $data['is_active'],
        ] + (! empty($data['passcode']) ? ['password' => $data['passcode']] : []));
        $this->storeAvatar($request, $driver_management);
        $driver_management->syncRoles(['Driver']);
        $driver_management->driverProfile()->updateOrCreate(
            ['user_id' => $driver_management->id],
            $this->profileData($data)
        );
        SalaryComponents::sync($driver_management, $data['salary_components'] ?? []);

        return redirect()->route('driver-management.index')->with('success', 'Driver updated successfully.');
    }

    public function destroy(User $driver_management)
    {
        abort_unless($driver_management->hasRole('Driver'), 404);

        if ($driver_management->avatar) {
            Storage::disk('public')->delete($driver_management->avatar);
        }

        $driver_management->delete();

        return response()->json([
            'success' => true,
            'message' => 'Driver deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = $this->filteredQuery();

        if (! empty($ids)) {
            $query->whereIn('users.id', $ids);
        }

        return Excel::download(new DriverManagementExport($query), 'driver-management.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', 'boolean'],
        ]);

        $driver = User::role('Driver')->findOrFail($request->id);
        $driver->is_active = $request->status;
        $driver->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    public function regeneratePasscode(User $driver_management)
    {
        abort_unless($driver_management->hasRole('Driver'), 404);

        $passcode = $this->generatePasscode();

        $driver_management->forceFill([
            'password' => $passcode,
            'failed_login_attempts' => 0,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Passcode regenerated successfully.',
            'passcode' => $passcode,
        ]);
    }

    public function districtsByState(Request $request)
    {
        $request->validate([
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
        ]);

        if (! $request->filled('state_id')) {
            return response()->json([]);
        }

        return response()->json(
            District::where('state_id', $request->state_id)->orderBy('name')->get(['id', 'name'])
        );
    }

    public function locationsByDistrict(Request $request)
    {
        $request->validate([
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
        ]);

        if (! $request->filled('state_id') || ! $request->filled('district_id')) {
            return response()->json([]);
        }

        return response()->json(
            Location::where('state_id', $request->state_id)
                ->where('district_id', $request->district_id)
                ->orderBy('name')
                ->get(['id', 'name', 'pincode'])
        );
    }

    private function filteredQuery()
    {
        $query = User::role('Driver')
            ->with(['roles', 'driverProfile.state', 'driverProfile.district', 'driverProfile.location', 'driverProfile.depot', 'driverProfile.branchLocation'])
            ->select('users.*');

        if (request()->filled('search_text')) {
            $search = request('search_text');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('users.code', 'like', '%' . $search . '%')
                    ->orWhere('users.name', 'like', '%' . $search . '%')
                    ->orWhere('users.phone', 'like', '%' . $search . '%');
            });
        }

        foreach (['state_id', 'employment_type', 'license_type', 'verification_status'] as $field) {
            if (request()->filled($field)) {
                $query->whereHas('driverProfile', fn($profileQuery) => $profileQuery->where($field, request($field)));
            }
        }

        if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
            $query->where('users.is_active', request('status'));
        }

        if (request('expiry_filter') === 'license_expiring') {
            $query->whereHas('driverProfile', fn($profileQuery) => $profileQuery
                ->whereDate('expiry_date', '>=', now()->toDateString())
                ->whereDate('expiry_date', '<=', now()->addMonth()->toDateString()));
        }

        if (request('expiry_filter') === 'medical_expiring') {
            $query->whereHas('driverProfile', fn($profileQuery) => $profileQuery
                ->whereDate('medical_fitness_expiry', '>=', now()->toDateString())
                ->whereDate('medical_fitness_expiry', '<=', now()->addMonth()->toDateString()));
        }

        return $query->orderBy('users.created_at', 'desc');
    }

    private function formData(): array
    {
        return [
            'licenseTypes' => DriverProfile::LICENSE_TYPES,
            'employmentTypes' => DriverProfile::EMPLOYMENT_TYPES,
            'verificationStatuses' => DriverProfile::VERIFICATION_STATUSES,
            'states' => State::orderBy('name')->get(['id', 'name']),
            'districts' => District::orderBy('name')->get(['id', 'name']),
            'locations' => Location::orderBy('name')->get(['id', 'name', 'pincode']),
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'branches' => BranchLocation::orderBy('name')->get(['id', 'name']),
            'countries' => ['India'],
            'salaryComponents' => SalaryComponents::forRole('Driver'),
            'salaryComponentValues' => SalaryComponents::valuesFor(request()->route('driver_management')),
        ];
    }

    private function profileData(array $data): array
    {
        return collect($data)->only([
            'alternate_country_code',
            'alternate_phone',
            'aadhaar_number',
            'country',
            'state_id',
            'district_id',
            'location_id',
            'pincode',
            'address',
            'license_number',
            'license_type',
            'issue_date',
            'expiry_date',
            'badge_number',
            'badge_expiry_date',
            'employment_type',
            'joining_date',
            'depot_id',
            'branch_location_id',
            'account_number',
            'ifsc_code',
            'emergency_contact_name',
            'emergency_country_code',
            'emergency_contact_no',
            'medical_fitness_expiry',
            'police_verification_status',
            'verification_status',
        ])->merge([
            'salary' => SalaryComponents::legacyProfileSalaryData($data['salary_components'] ?? [])['salary'],
        ])->all();
    }

    private function generateDriverCode(int $id): string
    {
        return generate_code('Driver Management Module', $id, 3, 'DRV');
    }

    private function generatePasscode(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function storeAvatar(Request $request, User $user): void
    {
        if (! $request->hasFile('avatar')) {
            return;
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->avatar = $request->file('avatar')->store('avatars', 'public');
        $user->save();
    }

    private function licenseExpiryBadge($expiryDate): string
    {
        if (! $expiryDate) {
            return '<span class="status-red">-</span>';
        }

        $date = $expiryDate->copy()->startOfDay();
        $label = $date->format('d-m-Y');

        if ($date->lt(now()->startOfDay())) {
            return '<span class="driver-license-badge driver-license-expired">Expired License<br><small>' . $label . '</small></span>';
        }

        if ($date->lte(now()->addMonth()->startOfDay())) {
            return '<span class="driver-license-badge driver-license-warning">Expiring Soon<br><small>' . $label . '</small></span>';
        }

        return '<span class="driver-license-badge driver-license-active">Active<br><small>' . $label . '</small></span>';
    }

    private function verificationBadge(?string $status): string
    {
        return match ($status) {
            'verified' => '<span class="status-green">Verified</span>',
            'rejected' => '<span class="status-red">Rejected</span>',
            default => '<span class="driver-verification-pending">Pending</span>',
        };
    }

    private function driverRecord(User $driver): User
    {
        return $driver->load([
            'roles',
            'driverProfile.state',
            'driverProfile.district',
            'driverProfile.location',
            'driverProfile.depot',
            'driverProfile.branchLocation',
            'driverDocuments.documentType',
        ]);
    }

    private function buildDriverPdf(User $record): string
    {
        $profile = $record->driverProfile;
        $date = fn ($value) => $value ? $value->format('d-m-Y') : '-';
        $money = fn ($value) => filled($value) ? number_format((float) $value, 2) : '-';
        $verification = fn ($value) => DriverProfile::VERIFICATION_STATUSES[$value] ?? '-';
        $alternatePhone = trim(($profile?->alternate_country_code ?? '') . ' ' . ($profile?->alternate_phone ?? '')) ?: '-';
        $emergencyPhone = trim(($profile?->emergency_country_code ?? '') . ' ' . ($profile?->emergency_contact_no ?? '')) ?: '-';

        $content = '';
        $this->pdfFill($content, 0.96, 0.97, 0.99, 0, 0, 595, 842);
        $this->pdfText($content, 'SYSCON', 50, 795, 18, 'F2');
        $this->pdfText($content, 'Driver Profile', 50, 770, 22, 'F2');
        $this->pdfText($content, 'Generated on ' . now()->format('d-m-Y'), 430, 795, 10);
        $this->pdfStatus($content, $record->is_active ? 'Active' : 'Inactive', 465, 765, $record->is_active);

        $this->pdfCard($content, 40, 600, 515, 140);
        $this->pdfFill($content, 0.90, 0.94, 1.00, 58, 645, 82, 72);
        $this->pdfText($content, 'PHOTO', 82, 678, 11, 'F2');
        $this->pdfText($content, $record->name ?: '-', 160, 708, 18, 'F2');
        $this->pdfText($content, 'Driver Code: ' . ($record->code ?: '-'), 160, 686, 11);
        $this->pdfText($content, 'Email: ' . ($record->email ?: '-'), 160, 668, 10);
        $this->pdfText($content, 'Phone: ' . ($record->full_phone ?: '-'), 160, 650, 10);
        $this->pdfText($content, 'Alt Phone: ' . $alternatePhone, 160, 632, 10);
        $this->pdfText($content, 'License: ' . ($profile?->license_type_label ?: '-'), 340, 686, 10);
        $this->pdfText($content, 'Expiry: ' . $date($profile?->expiry_date), 340, 668, 10);
        $this->pdfText($content, 'Verification: ' . $verification($profile?->verification_status), 340, 650, 10);

        $this->pdfSection($content, 'Identity Details', 40, 440, 250, [
            'Aadhaar Number' => $profile?->aadhaar_number ?: '-',
            'Country' => $profile?->country ?: '-',
            'State' => $profile?->state?->name ?: '-',
            'District' => $profile?->district?->name ?: '-',
            'City' => $profile?->location?->name ?: '-',
            'Pincode' => $profile?->pincode ?: '-',
        ], 170);

        $this->pdfSection($content, 'License Details', 305, 440, 250, [
            'License Number' => $profile?->license_number ?: '-',
            'License Type' => $profile?->license_type_label ?: '-',
            'Issue Date' => $date($profile?->issue_date),
            'Expiry Date' => $date($profile?->expiry_date),
            'Badge Number' => $profile?->badge_number ?: '-',
            'Badge Expiry' => $date($profile?->badge_expiry_date),
        ], 170);

        $this->pdfSection($content, 'Employment Details', 40, 235, 250, [
            'Employment Type' => $profile?->employment_type_label ?: '-',
            'Joining Date' => $date($profile?->joining_date),
            'Salary' => $money($profile?->salary),
            'Depot' => $profile?->depot?->name ?: '-',
            'Branch' => $profile?->branchLocation?->name ?: '-',
        ], 155);

        $this->pdfSection($content, 'Emergency / Bank', 305, 235, 250, [
            'Emergency Name' => $profile?->emergency_contact_name ?: '-',
            'Emergency No' => $emergencyPhone,
            'Medical Expiry' => $date($profile?->medical_fitness_expiry),
            'Account Number' => $profile?->account_number ?: '-',
            'IFSC Code' => $profile?->ifsc_code ?: '-',
        ], 155);

        $this->pdfSection($content, 'Status & Verification', 40, 95, 515, [
            'Police Verification' => $verification($profile?->police_verification_status),
            'Verification Status' => $verification($profile?->verification_status),
            'Address' => $profile?->address ?: '-',
        ], 95);

        $pages = [$content];
        $documentContent = '';
        $this->pdfFill($documentContent, 0.96, 0.97, 0.99, 0, 0, 595, 842);
        $this->pdfText($documentContent, 'Driver Documents', 50, 790, 20, 'F2');
        $y = 735;

        if ($record->driverDocuments->isEmpty()) {
            $this->pdfCard($documentContent, 40, 665, 515, 50);
            $this->pdfText($documentContent, 'No documents uploaded.', 60, 692, 11);
        }

        foreach ($record->driverDocuments as $document) {
            if ($y < 90) {
                $pages[] = $documentContent;
                $documentContent = '';
                $this->pdfFill($documentContent, 0.96, 0.97, 0.99, 0, 0, 595, 842);
                $this->pdfText($documentContent, 'Driver Documents', 50, 790, 20, 'F2');
                $y = 735;
            }

            $this->pdfCard($documentContent, 40, $y - 55, 515, 55);
            $this->pdfText($documentContent, $document->documentType?->name ?: 'Document', 60, $y - 22, 12, 'F2');
            $this->pdfText($documentContent, 'Expiry: ' . ($document->expiry_date?->format('d-m-Y') ?: '-'), 60, $y - 40, 10);
            $this->pdfStatus($documentContent, $document->is_verified ? 'Verified' : 'Not Verified', 430, $y - 32, $document->is_verified);
            $y -= 70;
        }

        $pages[] = $documentContent;

        return $this->pdfDocument($pages);
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

        foreach ($objects as $objectNumber => $object) {
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= $object;
        }

        ksort($offsets);
        $xref = strlen($pdf);
        $maxObject = max(array_keys($offsets));
        $pdf .= "xref\n0 " . ($maxObject + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= $maxObject; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }

        $pdf .= "trailer\n<< /Size " . ($maxObject + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }

    private function pdfSection(string &$content, string $title, int $x, int $y, int $width, array $items, int $height = 140): void
    {
        $this->pdfCard($content, $x, $y, $width, $height);
        $this->pdfText($content, $title, $x + 14, $y + $height - 26, 13, 'F2');
        $lineY = $y + $height - 50;

        foreach ($items as $label => $value) {
            $this->pdfText($content, $label . ':', $x + 14, $lineY, 9, 'F2');
            $this->pdfText($content, (string) $value, $x + 150, $lineY, 9);
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
