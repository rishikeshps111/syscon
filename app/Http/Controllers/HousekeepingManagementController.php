<?php

namespace App\Http\Controllers;

use App\Exports\HousekeepingManagementExport;
use App\Http\Requests\StoreHousekeepingManagementRequest;
use App\Http\Requests\UpdateHousekeepingManagementRequest;
use App\Models\BranchLocation;
use App\Models\Depot;
use App\Models\District;
use App\Models\HousekeepingProfile;
use App\Models\Location;
use App\Models\State;
use App\Models\User;
use App\Support\SalaryComponents;
use App\Support\UserCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class HousekeepingManagementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', new Middleware(PermissionMiddleware::using('housekeeping-management.view'), ['index', 'show', 'downloadPdf', 'export', 'districtsByState', 'locationsByDistrict']), new Middleware(PermissionMiddleware::using('housekeeping-management.create'), ['create', 'store']), new Middleware(PermissionMiddleware::using('housekeeping-management.edit'), ['edit', 'update', 'status']), new Middleware(PermissionMiddleware::using('housekeeping-management.delete'), ['destroy'])];
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of($this->query())->addIndexColumn()
                ->addColumn('checkbox', fn ($r) => '<input type="checkbox" class="row-checkbox" value="'.$r->id.'">')
                ->addColumn('phone_number', fn ($r) => $r->full_phone ?: '-')
                ->addColumn('depot', fn ($r) => $r->housekeepingProfile?->depot?->name ?? '-')
                ->addColumn('employment_type', fn ($r) => $r->housekeepingProfile?->employment_type_label ?? '-')
                ->addColumn('joining_date', fn ($r) => $r->housekeepingProfile?->joining_date?->format('d-m-Y') ?? '-')
                ->addColumn('verification_status', fn ($r) => match ($r->housekeepingProfile?->verification_status) { 'verified' => '<span class="status-green">Verified</span>', 'rejected' => '<span class="status-red">Rejected</span>', default => '<span class="status-orange">Pending</span>' })
                ->addColumn('status', fn ($r) => $r->is_active ? '<span class="status-green">Active</span>' : '<span class="status-red">Inactive</span>')
                ->addColumn('action', fn ($row) => view('housekeeping-management.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'verification_status', 'status', 'action'])->make(true);
        }
        return view('housekeeping-management.index', $this->formData());
    }

    public function create() { return view('housekeeping-management.form', array_merge($this->formData(), ['districts' => collect(), 'locations' => collect()])); }

    public function store(StoreHousekeepingManagementRequest $request)
    {
        $data = $request->validated();
        $user = User::create(['code' => null, 'name' => $data['name'], 'email' => $data['email'], 'country_code' => $data['country_code'], 'phone' => $data['phone'], 'password' => Str::random(40), 'is_active' => $data['is_active']]);
        $user->update(['code' => UserCodeGenerator::generate('Housekeeping', (int) $data['depot_id'], $user->id)]);
        $this->avatar($request, $user); $user->assignRole('Housekeeping');
        $user->housekeepingProfile()->create($this->profileData($data)); SalaryComponents::sync($user, $data['salary_components'] ?? []);
        return redirect()->route('housekeeping-management.index')->with('success', 'Housekeeping user created successfully.');
    }

    public function show(User $housekeeping_management) { $this->guard($housekeeping_management); return view('housekeeping-management.show', ['record' => $housekeeping_management->load(['roles', 'housekeepingProfile.state', 'housekeepingProfile.district', 'housekeepingProfile.location', 'housekeepingProfile.depot', 'housekeepingProfile.branchLocation', 'housekeepingDocuments.documentType', 'salaryComponentValues.salaryComponent'])]); }

    public function downloadPdf(User $housekeeping_management)
    {
        $this->guard($housekeeping_management);
        $record = $housekeeping_management->load(['housekeepingProfile.depot', 'housekeepingProfile.branchLocation']);
        $profile = $record->housekeepingProfile;
        $lines = ['SYSCON - Housekeeping Profile', 'Code: '.($record->code ?: '-'), 'Name: '.$record->name, 'Email: '.$record->email, 'Phone: '.$record->full_phone, 'Aadhaar: '.($profile?->aadhaar_number ?: '-'), 'Employment: '.($profile?->employment_type_label ?: '-'), 'Depot: '.($profile?->depot?->name ?: '-'), 'Branch: '.($profile?->branchLocation?->name ?: '-'), 'Joining Date: '.($profile?->joining_date?->format('d-m-Y') ?: '-'), 'Address: '.($profile?->address ?: '-')];
        $stream = "BT\n/F1 11 Tf\n50 790 Td\n"; foreach ($lines as $i => $line) $stream .= ($i ? "0 -20 Td\n" : '').'('.str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$line).") Tj\n"; $stream .= 'ET';
        $pdf = "%PDF-1.4\n"; $objects = ['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>','<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>','<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream"]; $offsets=[0]; foreach($objects as $i=>$o){$offsets[]=strlen($pdf);$pdf.=($i+1)." 0 obj\n{$o}\nendobj\n";} $xref=strlen($pdf);$pdf.="xref\n0 6\n0000000000 65535 f \n";foreach(array_slice($offsets,1) as $o)$pdf.=sprintf("%010d 00000 n \n",$o);$pdf.="trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return response($pdf, 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.($record->code ?: 'housekeeping').'-profile.pdf"']);
    }

    public function edit(User $housekeeping_management)
    {
        $this->guard($housekeeping_management); $record = $housekeeping_management->load('housekeepingProfile'); $p = $record->housekeepingProfile;
        $districts = District::where('state_id', $p?->state_id)->orderBy('name')->get(['id', 'name']);
        $locations = Location::where('state_id', $p?->state_id)->where('district_id', $p?->district_id)->orderBy('name')->get(['id', 'name', 'pincode']);
        return view('housekeeping-management.form', array_merge($this->formData(), compact('record', 'districts', 'locations')));
    }

    public function update(UpdateHousekeepingManagementRequest $request, User $housekeeping_management)
    {
        $this->guard($housekeeping_management); $data = $request->validated();
        $housekeeping_management->update(collect($data)->only(['name', 'email', 'country_code', 'phone', 'is_active'])->all());
        $this->avatar($request, $housekeeping_management); $housekeeping_management->syncRoles(['Housekeeping']);
        $housekeeping_management->housekeepingProfile()->updateOrCreate(['user_id' => $housekeeping_management->id], $this->profileData($data));
        SalaryComponents::sync($housekeeping_management, $data['salary_components'] ?? []);
        return redirect()->route('housekeeping-management.index')->with('success', 'Housekeeping user updated successfully.');
    }

    public function destroy(User $housekeeping_management) { $this->guard($housekeeping_management); if ($housekeeping_management->avatar) Storage::disk('public')->delete($housekeeping_management->avatar); $housekeeping_management->delete(); return response()->json(['success' => true]); }
    public function status(Request $request) { $data = $request->validate(['id' => ['required', 'exists:users,id'], 'status' => ['required', 'boolean']]); User::role('Housekeeping')->findOrFail($data['id'])->update(['is_active' => $data['status']]); return response()->json(['success' => true]); }
    public function export(Request $request) { $q = $this->query(); if ($request->filled('ids')) $q->whereIn('users.id', $request->ids); return Excel::download(new HousekeepingManagementExport($q), 'housekeeping-management.xlsx'); }
    public function districtsByState(Request $r) { return response()->json(District::where('state_id', $r->integer('state_id'))->orderBy('name')->get(['id', 'name'])); }
    public function locationsByDistrict(Request $r) { return response()->json(Location::where('state_id', $r->integer('state_id'))->where('district_id', $r->integer('district_id'))->orderBy('name')->get(['id', 'name', 'pincode'])); }

    private function query()
    {
        return User::role('Housekeeping')->with(['housekeepingProfile.depot'])->select('users.*')
            ->when(request('search_text'), fn ($q, $v) => $q->where(fn ($s) => $s->where('users.code', 'like', "%{$v}%")->orWhere('users.name', 'like', "%{$v}%")))
            ->when(request()->filled('depot_id'), fn ($q) => $q->whereHas('housekeepingProfile', fn ($p) => $p->where('depot_id', request('depot_id'))))
            ->when(request()->filled('state_id'), fn ($q) => $q->whereHas('housekeepingProfile', fn ($p) => $p->where('state_id', request('state_id'))))
            ->when(request()->filled('employment_type'), fn ($q) => $q->whereHas('housekeepingProfile', fn ($p) => $p->where('employment_type', request('employment_type'))))
            ->when(request()->filled('verification_status'), fn ($q) => $q->whereHas('housekeepingProfile', fn ($p) => $p->where('verification_status', request('verification_status'))))
            ->when(request('expiry_filter') === 'medical_expiring', fn ($q) => $q->whereHas('housekeepingProfile', fn ($p) => $p->whereBetween('medical_fitness_expiry', [now()->toDateString(), now()->addMonth()->toDateString()])))
            ->when(request()->filled('status'), fn ($q) => $q->where('users.is_active', request('status')))->latest('users.created_at');
    }

    private function formData(): array { return ['depots' => Depot::orderBy('name')->get(['id', 'name']), 'branches' => BranchLocation::orderBy('name')->get(['id', 'name']), 'states' => State::orderBy('name')->get(['id', 'name']), 'districts' => District::orderBy('name')->get(['id', 'name']), 'locations' => Location::orderBy('name')->get(['id', 'name', 'pincode']), 'countries' => ['India'], 'employmentTypes' => HousekeepingProfile::EMPLOYMENT_TYPES, 'verificationStatuses' => HousekeepingProfile::VERIFICATION_STATUSES, 'salaryComponents' => SalaryComponents::forRole('Housekeeping'), 'salaryComponentValues' => SalaryComponents::valuesFor(request()->route('housekeeping_management'))]; }
    private function profileData(array $data): array { return collect($data)->only(['alternate_country_code', 'alternate_phone', 'aadhaar_number', 'country', 'state_id', 'district_id', 'location_id', 'pincode', 'address', 'employment_type', 'joining_date', 'depot_id', 'branch_location_id', 'account_number', 'ifsc_code', 'emergency_contact_name', 'emergency_country_code', 'emergency_contact_no', 'medical_fitness_expiry', 'police_verification_status', 'verification_status'])->merge(['salary' => SalaryComponents::legacyProfileSalaryData($data['salary_components'] ?? [])['salary']])->all(); }
    private function guard(User $u): void { abort_unless($u->hasRole('Housekeeping'), 404); }
    private function avatar(Request $r, User $u): void { if (!$r->hasFile('avatar')) return; if ($u->avatar) Storage::disk('public')->delete($u->avatar); $u->update(['avatar' => $r->file('avatar')->store('avatars', 'public')]); }
}
