<?php

namespace App\Http\Controllers;

use App\Exports\ComplaintExport;
use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\UpdateComplaintRequest;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class ComplaintController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('complaints.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('complaints.view|complaints.create|complaints.edit'), ['usersByRole']),
            new Middleware(PermissionMiddleware::using('complaints.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('complaints.edit'), ['edit', 'update', 'changeStatus', 'assignAction']),
            new Middleware(PermissionMiddleware::using('complaints.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        $activeRole = $this->activeReportedByRole();

        if (request()->ajax()) {
            return DataTables::of($this->filteredQuery($activeRole))
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('complaint_date_display', fn ($row) => $row->complaint_date?->format('d-m-Y') ?? '-')
                ->addColumn('reported_by', fn ($row) => $this->userLabel($row->reportedBy))
                ->addColumn('against', fn ($row) => $this->userLabel($row->againstUser) . '<br><small>' . $row->against_role_label . '</small>')
                ->addColumn('category', fn ($row) => $row->category?->name ?? '-')
                ->addColumn('severity_badge', fn ($row) => $this->severityBadge($row->severity))
                ->addColumn('status_badge', fn ($row) => $this->statusBadge($row->status))
                ->addColumn('action', fn ($row) => view('complaint.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'against', 'severity_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('complaint.index', array_merge($this->formData(), compact('activeRole')));
    }

    public function create()
    {
        return view('complaint.form', array_merge($this->formData(), [
            'generatedCode' => $this->generateComplaintCode(((int) Complaint::max('id')) + 1),
        ]));
    }

    public function store(StoreComplaintRequest $request)
    {
        $data = $request->validated();
        $complaint = Complaint::create($this->complaintData($data));
        $complaint->code = $this->generateComplaintCode($complaint->id);
        $complaint->save();
        $this->storeAttachment($request, $complaint);

        return redirect()->route('complaints.index', ['reported_by_role' => $complaint->reported_by_role])
            ->with('success', 'Complaint created successfully.');
    }

    public function show(Complaint $complaint)
    {
        return view('complaint.show', [
            'record' => $complaint->load(['reportedBy', 'againstUser', 'category']),
        ]);
    }

    public function edit(Complaint $complaint)
    {
        return view('complaint.form', array_merge($this->formData(), [
            'record' => $complaint->load(['reportedBy', 'againstUser', 'category']),
        ]));
    }

    public function update(UpdateComplaintRequest $request, Complaint $complaint)
    {
        $data = $request->validated();
        $complaint->update($this->complaintData($data));
        $this->storeAttachment($request, $complaint);

        return redirect()->route('complaints.index', ['reported_by_role' => $complaint->reported_by_role])
            ->with('success', 'Complaint updated successfully.');
    }

    public function destroy(Complaint $complaint)
    {
        if ($complaint->attachment_paths) {
            Storage::disk('public')->delete($complaint->attachment_paths);
        }

        $complaint->delete();

        return response()->json(['success' => true, 'message' => 'Complaint deleted successfully.']);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = $this->filteredQuery($this->activeReportedByRole());

        if (! empty($ids)) {
            $query->whereIn('complaints.id', $ids);
        }

        return Excel::download(new ComplaintExport($query), 'complaints.xlsx');
    }

    public function changeStatus(Request $request, Complaint $complaint)
    {
        $request->validate([
            'status' => ['required', 'in:pending,in_review,action_taken,closed,rejected'],
        ]);

        $complaint->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Complaint status updated successfully.']);
    }

    public function assignAction(Request $request, Complaint $complaint)
    {
        $request->validate([
            'assigned_to' => ['required', 'in:admin,hr,manager'],
            'action_taken' => ['nullable', 'in:warning,suspension,fine'],
            'action_date' => ['nullable', 'date'],
        ]);

        $complaint->update($request->only(['assigned_to', 'action_taken', 'action_date']));

        return response()->json(['success' => true, 'message' => 'Complaint action assigned successfully.']);
    }

    public function usersByRole(Request $request)
    {
        $request->validate([
            'role' => ['required', 'in:driver,controller,supervisor'],
        ]);

        return response()->json(
            User::role(ucfirst($request->role))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'text' => $this->userLabel($user),
                ])
        );
    }

    private function filteredQuery(string $reportedByRole)
    {
        $query = Complaint::with(['reportedBy', 'againstUser', 'category'])
            ->where('reported_by_role', $reportedByRole)
            ->select('complaints.*');

        if (request()->filled('search_text')) {
            $search = request('search_text');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('complaints.code', 'like', '%' . $search . '%')
                    ->orWhereHas('reportedBy', fn ($userQuery) => $userQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%'))
                    ->orWhereHas('againstUser', fn ($userQuery) => $userQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%'));
            });
        }

        foreach (['against_role', 'complaint_category_id', 'severity', 'status'] as $field) {
            if (request()->filled($field)) {
                $query->where($field, request($field));
            }
        }

        if (request()->filled('date_from')) {
            $query->whereDate('complaint_date', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $query->whereDate('complaint_date', '<=', request('date_to'));
        }

        return $query->orderBy('complaints.created_at', 'desc');
    }

    private function formData(): array
    {
        return [
            'reportedByRoles' => Complaint::REPORTED_BY_ROLES,
            'againstRoles' => Complaint::AGAINST_ROLES,
            'severities' => Complaint::SEVERITIES,
            'statuses' => Complaint::STATUSES,
            'assignedToOptions' => Complaint::ASSIGNED_TO_OPTIONS,
            'actionTakenOptions' => Complaint::ACTION_TAKEN_OPTIONS,
            'categories' => ComplaintCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'controllers' => User::role('Controller')->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'supervisors' => User::role('Supervisor')->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'drivers' => User::role('Driver')->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ];
    }

    private function complaintData(array $data): array
    {
        return collect($data)->only([
            'complaint_date',
            'reported_by_role',
            'reported_by_user_id',
            'against_role',
            'against_user_id',
            'complaint_category_id',
            'description',
            'severity',
            'remarks',
        ])->all();
    }

    private function activeReportedByRole(): string
    {
        return in_array(request('reported_by_role'), ['controller', 'supervisor'], true)
            ? request('reported_by_role')
            : 'supervisor';
    }

    private function generateComplaintCode(int $id): string
    {
        return generate_code('Complaint Module', $id, 3, 'CMP');
    }

    private function storeAttachment(Request $request, Complaint $complaint): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        $newAttachmentPaths = collect($request->file('attachments'))
            ->filter()
            ->map(fn ($file) => $file->store('complaints', 'public'))
            ->values()
            ->all();

        $complaint->attachment_paths = array_values(array_merge($complaint->attachment_paths ?? [], $newAttachmentPaths));
        $complaint->save();
    }

    private function userLabel(?User $user): string
    {
        if (! $user) {
            return '-';
        }

        return trim(($user->code ? $user->code . ' - ' : '') . $user->name);
    }

    private function severityBadge(?string $severity): string
    {
        return match ($severity) {
            'high' => '<span class="status-red">High</span>',
            'medium' => '<span class="status-orange">Medium</span>',
            default => '<span class="status-green">Low</span>',
        };
    }

    private function statusBadge(?string $status): string
    {
        return match ($status) {
            'in_review' => '<span class="status-orange">In Review</span>',
            'action_taken' => '<span class="status-green">Action Taken</span>',
            'closed' => '<span class="status-red">Closed</span>',
            'rejected' => '<span class="status-red">Rejected</span>',
            default => '<span class="status-orange">Pending</span>',
        };
    }
}
