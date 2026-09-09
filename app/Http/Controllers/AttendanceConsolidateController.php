<?php

namespace App\Http\Controllers;

use App\Models\AttendanceConsolidateImport;
use App\Models\Depot;
use App\Models\User;
use App\Services\AttendanceConsolidateCsv;
use App\Services\AttendanceConsolidateWorkbook;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class AttendanceConsolidateController extends Controller implements HasMiddleware
{
    private const SESSION_KEY = 'attendance_consolidate_preview';

    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('attendance-management.view')),
            new Middleware(PermissionMiddleware::using('attendance-management.create'), ['importForm', 'preview', 'store', 'sample']),
        ];
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['year' => ['nullable', 'integer', 'between:1900,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'], 'depot_id' => ['nullable', 'integer', 'exists:depots,id']]);
        if ($request->ajax()) {
            $query = AttendanceConsolidateImport::query()->select(['id', 'year', 'month', 'depot_id', 'depot_name', 'original_filename', 'employee_count', 'imported_by_name', 'imported_at']);
            foreach (['year', 'month', 'depot_id'] as $field) {
                if (! empty($filters[$field])) {
                    $query->where($field, $filters[$field]);
                }
            }

            return DataTables::of($query)->addIndexColumn()
                ->addColumn('month_name', fn ($row) => $this->months()[$row->month])
                ->editColumn('imported_at', fn ($row) => $row->imported_at->format('d M Y H:i'))
                ->addColumn('imported_details', fn ($row) => e($row->imported_by_name).'<br><small>'.e($row->imported_at->format('d M Y H:i')).'</small>')
                ->addColumn('action', fn ($row) => view('attendance-consolidate.partials.action', compact('row'))->render())
                ->rawColumns(['action', 'imported_details'])->make(true);
        }

        return view('attendance-consolidate.index', $this->formData());
    }

    public function importForm()
    {
        return view('attendance-consolidate.import', $this->formData());
    }

    public function preview(Request $request, AttendanceConsolidateCsv $csv)
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:1900,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'extensions:csv,xlsx,xls', 'max:2048'],
        ]);
        $file = $request->file('csv_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = $csv->read($file->getRealPath(), (int) $data['depot_id'], $extension);
        $checksum = hash_file('sha256', $file->getRealPath());
        $this->checkDuplicate($data, $checksum);
        $token = (string) Str::uuid();
        $path = $file->storeAs('attendance-consolidate/pending', $token.'.'.$extension, 'local');
        if (! $path) {
            throw ValidationException::withMessages(['csv_file' => 'Unable to store the upload. Please try again.']);
        }
        $previous = $request->session()->get(self::SESSION_KEY);
        if ($previous) {
            Storage::disk('local')->delete($previous['path']);
        }
        $draft = [
            'token' => $token, 'path' => $path, 'checksum' => $checksum,
            'original_filename' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255),
            'year' => (int) $data['year'], 'month' => (int) $data['month'], 'depot_id' => (int) $data['depot_id'],
            'user_id' => $request->user()->id, 'expires_at' => now()->addMinutes(30)->timestamp,
            'review_signature' => hash('sha256', json_encode($rows)),
        ];
        $request->session()->put(self::SESSION_KEY, $draft);

        if ($request->boolean('import_now') && ! collect($rows)->contains(fn ($row) => count($row['warnings']) > 0)) {
            $request->merge(['token' => $token]);

            return $this->store($request, $csv);
        }

        return $this->review($draft, $rows);
    }

    public function store(Request $request, AttendanceConsolidateCsv $csv)
    {
        try {
            return $this->commitImport($request, $csv);
        } catch (ValidationException $exception) {
            return redirect()->route('attendance-consolidate.import.form')->withErrors($exception->errors());
        }
    }

    private function commitImport(Request $request, AttendanceConsolidateCsv $csv)
    {
        $request->validate(['token' => ['required', 'uuid']]);
        $draft = $request->session()->get(self::SESSION_KEY);
        if (! $draft || ! hash_equals($draft['token'], $request->input('token')) || $draft['user_id'] !== $request->user()->id) {
            throw ValidationException::withMessages(['csv_file' => 'Upload and review the file before importing.']);
        }
        $disk = Storage::disk('local');
        if ($draft['expires_at'] < now()->timestamp || ! $disk->exists($draft['path'])) {
            $disk->delete($draft['path']);
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('attendance-consolidate.import.form')->withErrors(['csv_file' => 'The preview expired. Please upload the file again.']);
        }
        $path = $disk->path($draft['path']);
        abort_unless(hash_equals($draft['checksum'], hash_file('sha256', $path)), 422, 'The uploaded file changed. Upload it again.');
        $rows = $csv->read($path, $draft['depot_id']);
        $signature = hash('sha256', json_encode($rows));
        if (! hash_equals($draft['review_signature'], $signature)) {
            $draft['review_signature'] = $signature;
            $request->session()->put(self::SESSION_KEY, $draft);

            return $this->review($draft, $rows, 'Employee records changed. Review the updated matches before confirming.');
        }
        if (collect($rows)->contains(fn ($row) => count($row['warnings']) > 0) && ! $request->boolean('acknowledge_mismatches')) {
            return $this->review($draft, $rows, 'Please acknowledge the highlighted name/depot differences before importing.');
        }
        $this->checkDuplicate($draft, $draft['checksum']);
        try {
            $import = DB::transaction(function () use ($request, $draft, $rows) {
                $import = AttendanceConsolidateImport::create([
                    'year' => $draft['year'], 'month' => $draft['month'], 'depot_id' => $draft['depot_id'],
                    'depot_name' => Depot::findOrFail($draft['depot_id'])->name,
                    'original_filename' => $draft['original_filename'], 'file_path' => '',
                    'file_checksum' => $draft['checksum'], 'employee_count' => count($rows),
                    'imported_by' => $request->user()->id, 'imported_by_name' => $request->user()->name, 'imported_at' => now(),
                ]);
                foreach ($rows as $row) {
                    $import->rows()->create($row);
                }

                return $import;
            });
        } catch (Throwable $exception) {
            if ($exception instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['csv_file' => 'This file has already been imported for the selected year, month and depot.']);
            }
            throw $exception;
        }
        $disk->delete($draft['path']);
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('attendance-consolidate.index')->with('success', "{$import->employee_count} consolidated attendance rows imported successfully.");
    }

    public function show(AttendanceConsolidateImport $import)
    {
        return view('attendance-consolidate.show', ['import' => $import, 'rows' => $import->rows()->orderBy('source_row')->paginate(100), 'months' => $this->months()]);
    }

    public function download(AttendanceConsolidateImport $import, AttendanceConsolidateWorkbook $workbook)
    {
        return $workbook->download($import->rows()->orderBy('source_row')->cursor(),
            $import->depot_name.' ATTENDANCE - '.$this->months()[$import->month].' '.$import->year,
            'attendance-consolidate-'.$import->id.'.xlsx');
    }

    public function sample(Request $request, AttendanceConsolidateWorkbook $workbook)
    {
        $data = $request->validate(['depot_id' => ['required', 'integer', 'exists:depots,id']]);
        $depot = Depot::findOrFail($data['depot_id']);
        $users = User::query()->whereNotNull('ref_code')->where('ref_code', '!=', '')
            ->where(function ($query) use ($depot) {
                foreach (['staffProfile', 'driverProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile'] as $profile) {
                    $query->orWhereHas($profile, fn ($profileQuery) => $profileQuery->where('depot_id', $depot->id));
                }
            })->orderBy('name')->get(['ref_code', 'name']);
        if ($users->isEmpty()) {
            return redirect()->route('attendance-consolidate.import.form')->withErrors(['sample_depot_id' => 'No employees with reference codes were found in this depot.']);
        }

        return $workbook->download($users->map(fn ($user) => ['employee_ref_code' => $user->ref_code, 'employee_name' => $user->name]),
            $depot->name.' ATTENDANCE', 'attendance-consolidate-sample-depot-'.$depot->id.'.xlsx');
    }

    private function review(array $draft, array $rows, ?string $notice = null)
    {
        return view('attendance-consolidate.review', [
            'draft' => $draft, 'rows' => $rows, 'notice' => $notice, 'months' => $this->months(),
            'depot' => Depot::findOrFail($draft['depot_id']),
            'hasWarnings' => collect($rows)->contains(fn ($row) => count($row['warnings']) > 0),
        ]);
    }

    private function checkDuplicate(array $data, string $checksum): void
    {
        if (AttendanceConsolidateImport::where('year', $data['year'])->where('month', $data['month'])
            ->where('depot_id', $data['depot_id'])->where('file_checksum', $checksum)->exists()) {
            throw ValidationException::withMessages(['csv_file' => 'This file has already been imported for the selected year, month and depot.']);
        }
    }

    private function formData(): array
    {
        return ['years' => collect(range(now()->year + 1, now()->year - 10))->merge(AttendanceConsolidateImport::distinct()->pluck('year'))->unique()->sortDesc(),
            'months' => $this->months(), 'depots' => Depot::orderBy('name')->get(['id', 'name'])];
    }

    private function months(): array
    {
        return collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => date('F', mktime(0, 0, 0, $month, 1, 2026))])->all();
    }
}
