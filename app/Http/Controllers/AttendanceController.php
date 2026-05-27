<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('attendance-management.view'), ['index', 'print', 'export', 'downloadPdf', 'usersByRole']),
            new Middleware(PermissionMiddleware::using('attendance-management.create'), ['create', 'store', 'importForm', 'import', 'sampleCsv']),
            new Middleware(PermissionMiddleware::using('attendance-management.edit'), ['manage', 'update']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Attendance::query()
                ->select('month', 'year', 'user_type')
                ->groupBy('year', 'month', 'user_type')
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->orderBy('user_type');

            if ($request->filled('year')) {
                $query->where('year', $request->integer('year'));
            }

            if ($request->filled('month')) {
                $query->where('month', $request->integer('month'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('month_name', fn ($row) => Carbon::create($row->year, $row->month, 1)->format('F'))
                ->addColumn('user_type_display', fn ($row) => $row->user_type ?: '-')
                ->addColumn('action', fn ($row) => view('attendance-management.partials.action', compact('row'))->render())
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('attendance-management.index', $this->commonData());
    }

    public function create(Request $request)
    {
        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('n'));
        $selectedRole = $this->selectedRole($request->input('user_type'));
        $date = $request->filled('attendance_date')
            ? $this->validDate($year, $month, $request->input('attendance_date'))
            : null;

        return view('attendance-management.form', $this->formData($date, $selectedRole) + [
            'mode' => 'create',
            'year' => $year,
            'month' => $month,
            'attendanceDate' => $date,
            'selectedRole' => $selectedRole,
            'recordedDates' => $selectedRole ? $this->recordedDates($year, $month, $selectedRole) : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'attendance_date' => ['required', 'date'],
            'user_type' => ['required', Rule::in(array_keys(Attendance::ROLES))],
        ]);

        $date = $this->validDate((int) $request->year, (int) $request->month, $request->attendance_date);

        if (Attendance::whereDate('attendance_date', $date)->where('user_type', $request->user_type)->exists()) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Attendance already marked.',
            ]);
        }

        $this->saveAttendance($request);

        return redirect()->route('attendance-management.index')->with('success', 'Attendance added successfully.');
    }

    public function manage(Request $request, int $year, int $month)
    {
        $selectedRole = $this->selectedRole($request->input('user_type')) ?? 'Staff';
        $recordedDates = $this->recordedDates($year, $month, $selectedRole);
        $date = $this->manageDate($request, $year, $month, $recordedDates);

        return view('attendance-management.form', $this->formData($date, $selectedRole) + [
            'mode' => 'manage',
            'year' => (int) $date->format('Y'),
            'month' => (int) $date->format('n'),
            'attendanceDate' => $date,
            'selectedRole' => $selectedRole,
            'recordedDates' => $recordedDates,
        ]);
    }

    public function update(Request $request)
    {
        $this->saveAttendance($request);

        return back()->with('success', 'Attendance updated successfully.');
    }

    public function importForm()
    {
        return view('attendance-management.import', $this->commonData() + [
            'headers' => $this->csvHeaders(),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        [$rows, $errors] = $this->readAttendanceCsv($request->file('csv_file')->getRealPath());

        if ($errors) {
            throw ValidationException::withMessages(['csv_file' => $errors]);
        }

        $validatedRows = $this->validateCsvRows($rows);

        DB::transaction(function () use ($validatedRows) {
            foreach ($validatedRows as $row) {
                Attendance::updateOrCreate(
                    [
                        'attendance_date' => $row['attendance_date']->toDateString(),
                        'user_id' => $row['user']->id,
                    ],
                    [
                        'month' => (int) $row['attendance_date']->format('n'),
                        'year' => (int) $row['attendance_date']->format('Y'),
                        'user_type' => $row['user_type'],
                        'status' => $row['status'],
                        'half_day_period' => $row['status'] === 'half_day' ? $row['half_day_period'] : null,
                        'shift' => $row['user_type'] === 'Driver' ? $row['shift'] : null,
                        'leave_id' => in_array($row['status'], ['absent', 'half_day'], true) ? $row['leave_id'] : null,
                        'remarks' => $row['remarks'],
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );
            }
        });

        return redirect()
            ->route('attendance-management.index')
            ->with('success', count($validatedRows) . ' attendance row(s) imported successfully.');
    }

    public function sampleCsv()
    {
        $sampleUser = $this->attendanceUsers('Staff')->first() ?? $this->attendanceUsers()->first();

        return response()->streamDownload(function () use ($sampleUser) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->csvHeaders());
            fputcsv($handle, [
                now()->format('d-m-Y'),
                $sampleUser && $sampleUser->hasRole('Driver') ? 'Driver' : 'Staff',
                $sampleUser?->code ?: '',
                $sampleUser?->name ?: '',
                'present',
                '',
                '',
                '',
                'Sample present attendance',
            ]);
            fputcsv($handle, [
                now()->format('d-m-Y'),
                $sampleUser && $sampleUser->hasRole('Driver') ? 'Driver' : 'Staff',
                $sampleUser?->code ?: '',
                $sampleUser?->name ?: '',
                'half_day',
                'morning',
                $sampleUser && $sampleUser->hasRole('Driver') ? 'Morning' : '',
                '',
                'Sample half day attendance',
            ]);
            fclose($handle);
        }, 'attendance-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    public function print(Request $request, int $year, int $month)
    {
        $query = $this->printQuery($request, $year, $month);

        return view('attendance-management.print', $this->commonData() + [
            'year' => $year,
            'month' => $month,
            'monthName' => Carbon::create($year, $month, 1)->format('F'),
            'records' => $query->get(),
            'selectedRole' => $request->input('role'),
            'selectedUser' => $request->input('user_id'),
            'selectedStatus' => $request->input('status'),
        ]);
    }

    public function export(Request $request, int $year, int $month)
    {
        return Excel::download(new AttendanceExport($this->printQuery($request, $year, $month)), 'attendance-management.xlsx');
    }

    public function downloadPdf(Request $request, int $year, int $month)
    {
        $records = $this->printQuery($request, $year, $month)->get();
        $content = "%PDF-1.4\n";
        $objects = [];
        $lines = [
            'Attendance Management',
            Carbon::create($year, $month, 1)->format('F') . ' ' . $year,
            '',
        ];

        foreach ($records as $record) {
            $lines[] = implode(' | ', [
                $record->attendance_date?->format('d-m-Y'),
                $this->userLabel($record->user),
                $record->user?->roles?->pluck('name')->implode(', '),
                Attendance::STATUSES[$record->status] ?? $record->status,
                $record->half_day_period ? (Attendance::HALF_DAY_PERIODS[$record->half_day_period] ?? $record->half_day_period) : '-',
                $record->shift ?: '-',
                $record->leave ? ($record->leave->code ?: '#' . $record->leave->id) : '-',
                $record->remarks ?: '-',
            ]);
        }

        $stream = "BT\n/F1 10 Tf\n50 790 Td\n";
        foreach (array_slice($lines, 0, 45) as $index => $line) {
            $stream .= ($index ? "0 -16 Td\n" : '') . '(' . $this->pdfEscape($line) . ") Tj\n";
        }
        $stream .= "ET";

        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";

        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[] = strlen($content);
            $content .= ($number + 1) . " 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($content);
        $content .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $content .= sprintf("%010d 00000 n \n", $offset);
        }
        $content .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="attendance-management.pdf"',
        ]);
    }

    public function usersByRole(Request $request)
    {
        $request->validate(['role' => ['required', Rule::in(array_keys(Attendance::ROLES))]]);

        return response()->json(
            User::role($request->role)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn ($user) => ['id' => $user->id, 'text' => $this->userLabel($user)])
        );
    }

    private function saveAttendance(Request $request): void
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'user_type' => ['required', Rule::in(array_keys(Attendance::ROLES))],
            'attendance_date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'attendance.*.status' => ['required', Rule::in(array_keys(Attendance::STATUSES))],
            'attendance.*.half_day_period' => ['nullable', Rule::in(array_keys(Attendance::HALF_DAY_PERIODS))],
            'attendance.*.shift' => ['nullable', Rule::in(array_keys(Attendance::SHIFTS))],
            'attendance.*.leave_id' => ['nullable', 'integer', 'exists:leaves,id'],
            'attendance.*.remarks' => ['nullable', 'string'],
        ]);

        $date = $this->validDate((int) $validated['year'], (int) $validated['month'], $validated['attendance_date']);
        $users = $this->attendanceUsers($validated['user_type'])->keyBy('id');

        DB::transaction(function () use ($validated, $date, $users) {
            foreach ($validated['attendance'] as $row) {
                if (! $users->has((int) $row['user_id'])) {
                    continue;
                }

                $isDriver = $users->get((int) $row['user_id'])->hasRole('Driver');
                $status = $row['status'];
                $fieldPrefix = 'attendance.' . $row['user_id'];

                if ($status === 'half_day' && empty($row['half_day_period'])) {
                    throw ValidationException::withMessages([
                        $fieldPrefix . '.half_day_period' => 'Please select morning or afternoon for half day attendance.',
                    ]);
                }

                if ($isDriver && empty($row['shift'])) {
                    throw ValidationException::withMessages([
                        $fieldPrefix . '.shift' => 'Please select a shift for driver attendance.',
                    ]);
                }

                if (! empty($row['leave_id']) && ! $this->leaveBelongsToDate((int) $row['leave_id'], (int) $row['user_id'], $date)) {
                    throw ValidationException::withMessages([
                        $fieldPrefix . '.leave_id' => 'Please select a valid leave application for this user and date.',
                    ]);
                }

                Attendance::updateOrCreate(
                    [
                        'attendance_date' => $date->toDateString(),
                        'user_id' => $row['user_id'],
                    ],
                    [
                        'month' => (int) $date->format('n'),
                        'year' => (int) $date->format('Y'),
                        'user_type' => $validated['user_type'],
                        'status' => $status,
                        'half_day_period' => $status === 'half_day' ? ($row['half_day_period'] ?? null) : null,
                        'shift' => $isDriver ? ($row['shift'] ?? null) : null,
                        'leave_id' => in_array($status, ['absent', 'half_day'], true) ? ($row['leave_id'] ?? null) : null,
                        'remarks' => $row['remarks'] ?? null,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );
            }
        });
    }

    private function formData(?Carbon $date, ?string $selectedRole = null): array
    {
        $attendances = Attendance::with('leave')
            ->when($date, fn ($query) => $query->whereDate('attendance_date', $date), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedRole, fn ($query) => $query->where('user_type', $selectedRole))
            ->get()
            ->keyBy('user_id');

        $users = $this->attendanceUsers($selectedRole)->groupBy(function (User $user) {
            return collect(array_keys(Attendance::ROLES))
                ->first(fn ($role) => $user->hasRole($role), 'Staff');
        });

        $leaves = Leave::with('leaveType')
            ->when($date, fn ($query) => $query->where(function ($query) use ($date) {
                $query->whereDate('leave_date', $date)
                    ->orWhere(function ($subQuery) use ($date) {
                        $subQuery->whereDate('from_date', '<=', $date)
                            ->whereDate('to_date', '>=', $date);
                    });
            }), fn ($query) => $query->whereRaw('1 = 0'))
            ->whereIn('status', ['Pending', 'Approved'])
            ->get()
            ->groupBy('user_id');

        return $this->commonData() + [
            'usersByRole' => $users,
            'attendances' => $attendances,
            'leavesByUser' => $leaves,
        ];
    }

    private function commonData(): array
    {
        return [
            'months' => collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => Carbon::create((int) date('Y'), $month, 1)->format('F')])->all(),
            'years' => range((int) date('Y') - 5, (int) date('Y') + 1),
            'roles' => Attendance::ROLES,
            'statuses' => Attendance::STATUSES,
            'shifts' => Attendance::SHIFTS,
            'halfDayPeriods' => Attendance::HALF_DAY_PERIODS,
        ];
    }

    private function attendanceUsers(?string $role = null)
    {
        return User::role($role ?: array_keys(Attendance::ROLES))
            ->where('is_active', true)
            ->with('roles')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    private function printQuery(Request $request, int $year, int $month)
    {
        $query = Attendance::with(['user.roles', 'leave.leaveType'])
            ->where('year', $year)
            ->where('month', $month)
            ->select('attendances.*')
            ->orderBy('attendance_date')
            ->orderBy('user_id');

        if ($request->filled('role') && array_key_exists($request->role, Attendance::ROLES)) {
            $query->whereHas('user.roles', fn ($roleQuery) => $roleQuery->where('name', $request->role));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('status') && array_key_exists($request->status, Attendance::STATUSES)) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    private function leaveBelongsToDate(int $leaveId, int $userId, Carbon $date): bool
    {
        return Leave::query()
            ->whereKey($leaveId)
            ->where('user_id', $userId)
            ->whereIn('status', ['Pending', 'Approved'])
            ->where(function ($query) use ($date) {
                $query->whereDate('leave_date', $date)
                    ->orWhere(function ($subQuery) use ($date) {
                        $subQuery->whereDate('from_date', '<=', $date)
                            ->whereDate('to_date', '>=', $date);
                    });
            })
            ->exists();
    }

    private function csvHeaders(): array
    {
        return [
            'attendance_date',
            'user_type',
            'usercode',
            'name',
            'status',
            'half_day_period',
            'shift',
            'leave_code',
            'remarks',
        ];
    }

    private function readAttendanceCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            return [[], ['Unable to read the uploaded CSV file.']];
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);
            return [[], ['CSV file is empty.']];
        }

        $header = array_map(fn ($value) => Str::of((string) $value)->trim()->lower()->replace(' ', '_')->toString(), $header);
        $missingHeaders = array_diff(['attendance_date', 'user_type', 'status'], $header);

        if ($missingHeaders) {
            fclose($handle);
            return [[], ['Missing required column(s): ' . implode(', ', $missingHeaders) . '.']];
        }

        if (! in_array('usercode', $header, true) && ! in_array('name', $header, true)) {
            fclose($handle);
            return [[], ['CSV must include either usercode or name column.']];
        }

        $rows = [];
        $errors = [];
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->isEmptyCsvRow($data)) {
                continue;
            }

            if (count($data) > count($header)) {
                $errors[] = "Row {$line}: too many columns.";
                continue;
            }

            $data = array_pad($data, count($header), '');
            $rows[] = [
                'line' => $line,
                'data' => array_combine($header, $data),
            ];
        }

        fclose($handle);

        if (! $rows && ! $errors) {
            $errors[] = 'CSV file does not contain any attendance rows.';
        }

        return [$rows, $errors];
    }

    private function validateCsvRows(array $rows): array
    {
        $errors = [];
        $validatedRows = [];
        $seen = [];

        foreach ($rows as $row) {
            $line = $row['line'];
            $data = collect($row['data'])
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->all();

            $date = $this->csvDate($data['attendance_date'] ?? null);
            $userType = $data['user_type'] ?? '';
            $status = $data['status'] ?? '';
            $halfDayPeriod = $data['half_day_period'] ?? null;
            $shift = $data['shift'] ?? null;
            $remarks = $data['remarks'] ?? null;

            if (! $date) {
                $errors[] = "Row {$line}: attendance_date must be a valid date in DD-MM-YYYY format.";
            }

            if (! array_key_exists($userType, Attendance::ROLES)) {
                $errors[] = "Row {$line}: user_type must be one of " . implode(', ', array_keys(Attendance::ROLES)) . '.';
            }

            if (! array_key_exists($status, Attendance::STATUSES)) {
                $errors[] = "Row {$line}: status must be one of " . implode(', ', array_keys(Attendance::STATUSES)) . '.';
            }

            [$user, $userError] = $this->csvUser($data['usercode'] ?? null, $data['name'] ?? null);

            if ($userError) {
                $errors[] = "Row {$line}: {$userError}";
            } elseif ($userType && ! $user->hasRole($userType)) {
                $errors[] = "Row {$line}: selected user does not have the {$userType} role.";
            }

            if ($status === 'half_day' && ! array_key_exists((string) $halfDayPeriod, Attendance::HALF_DAY_PERIODS)) {
                $errors[] = "Row {$line}: half_day_period is required for half_day status and must be morning or afternoon.";
            }

            if ($userType === 'Driver' && ! array_key_exists((string) $shift, Attendance::SHIFTS)) {
                $errors[] = "Row {$line}: shift is required for Driver attendance and must be Morning, Evening, or Night.";
            }

            $leaveId = $this->csvLeaveId($data['leave_code'] ?? null);

            if ($data['leave_code'] ?? null) {
                if (! $leaveId) {
                    $errors[] = "Row {$line}: leave application not found.";
                } elseif ($user && $date && ! $this->leaveBelongsToDate($leaveId, $user->id, $date)) {
                    $errors[] = "Row {$line}: leave application does not belong to this user/date or is not Pending/Approved.";
                }
            }

            if ($user && $date) {
                $key = $date->toDateString() . ':' . $user->id;

                if (isset($seen[$key])) {
                    $errors[] = "Row {$line}: duplicate attendance for this user/date; first seen on row {$seen[$key]}.";
                }

                $seen[$key] = $line;
            }

            $validatedRows[] = [
                'attendance_date' => $date,
                'user_type' => $userType,
                'user' => $user,
                'status' => $status,
                'half_day_period' => $status === 'half_day' ? $halfDayPeriod : null,
                'shift' => $userType === 'Driver' ? $shift : null,
                'leave_id' => $leaveId,
                'remarks' => $remarks ?: null,
            ];
        }

        if ($errors) {
            throw ValidationException::withMessages(['csv_file' => array_slice($errors, 0, 20)]);
        }

        return $validatedRows;
    }

    private function csvDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('d-m-Y', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->format('d-m-Y') === $value ? $date : null;
    }

    private function csvUser(?string $userCode, ?string $name): array
    {
        if (! $userCode && ! $name) {
            return [null, 'usercode or name is required.'];
        }

        $query = User::with('roles')->where('is_active', true);

        if ($userCode) {
            $user = (clone $query)->where('code', $userCode)->first();

            return $user
                ? [$user, null]
                : [null, 'active user not found for the supplied usercode.'];
        }

        $users = $query->where('name', $name)->limit(2)->get();

        if ($users->count() > 1) {
            return [null, 'multiple active users found with this name; use usercode instead.'];
        }

        return $users->isNotEmpty()
            ? [$users->first(), null]
            : [null, 'active user not found for the supplied name.'];
    }

    private function csvLeaveId(?string $leaveCode): ?int
    {
        if (! $leaveCode) {
            return null;
        }

        return Leave::query()
            ->where('code', $leaveCode)
            ->value('id');
    }

    private function isEmptyCsvRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    private function validDate(int $year, int $month, ?string $date): Carbon
    {
        $fallback = Carbon::create($year, $month, 1);
        $selected = $date ? Carbon::parse($date) : $fallback;

        if ((int) $selected->format('Y') !== $year || (int) $selected->format('n') !== $month) {
            return $fallback;
        }

        return $selected;
    }

    private function recordedDates(int $year, int $month, string $userType)
    {
        return Attendance::query()
            ->where('year', $year)
            ->where('month', $month)
            ->where('user_type', $userType)
            ->orderBy('attendance_date')
            ->distinct()
            ->pluck('attendance_date')
            ->map(fn ($item) => Carbon::parse($item)->format('Y-m-d'));
    }

    private function manageDate(Request $request, int $year, int $month, $recordedDates): Carbon
    {
        if ($request->filled('attendance_date')) {
            $date = $this->validDate($year, $month, $request->input('attendance_date'));

            if ($recordedDates->contains($date->format('Y-m-d'))) {
                return $date;
            }
        }

        return $recordedDates->isNotEmpty()
            ? Carbon::parse($recordedDates->first())
            : Carbon::create($year, $month, 1);
    }

    private function selectedRole(?string $role): ?string
    {
        return $role && array_key_exists($role, Attendance::ROLES) ? $role : null;
    }

    private function userLabel(?User $user): string
    {
        if (! $user) {
            return '-';
        }

        return trim(($user->code ? $user->code . ' - ' : '') . $user->name);
    }

    private function pdfEscape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $value);
    }
}
