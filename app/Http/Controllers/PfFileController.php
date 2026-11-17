<?php

namespace App\Http\Controllers;

use App\Exports\PfFileExport;
use App\Models\Attendance;
use App\Models\Depot;
use App\Models\SalaryProcessing;
use App\Support\PfFileData;
use App\Support\WageRegister;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;

class PfFileController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', new Middleware(PermissionMiddleware::using('pf-files.view'))];
    }

    public function index(Request $request)
    {
        $filters = $this->filters($request, $request->boolean('generate'));
        $report = $filters['month'] && $filters['depot_id'] && $filters['role_id'] !== null
            ? $this->report($filters) : null;
        if ($request->ajax() && $request->boolean('generate')) {
            $success = $report && $report['rows'] !== [];

            return response()->json([
                'success' => (bool) $success,
                'html' => view('pf-files.partials.report', compact('report'))->render(),
                'message' => $success ? 'PF file generated successfully.' : 'No approved PF contributors found for the selected filters.',
                'download_excel_url' => $success ? route('pf-files.export', $filters) : null,
                'filters' => $filters,
            ]);
        }

        return view('pf-files.index', [
            'filters' => $filters, 'report' => $report,
            'years' => range(now()->year, now()->year - 5),
            'months' => collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => Carbon::create(null, $month, 1)->format('F')])->all(),
            'depots' => Depot::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => Role::whereIn('name', array_keys(Attendance::ROLES))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(Request $request)
    {
        // Query approval again; a previously generated preview does not authorize a later download.
        $filters = $this->filters($request, true);
        $report = $this->report($filters);
        abort_if($report['rows'] === [], 404, 'No approved PF contributors found for the selected filters.');

        return Excel::download(new PfFileExport($report), $this->fileName($report));
    }

    private function filters(Request $request, bool $required): array
    {
        $presence = $required ? 'required' : 'nullable';
        $validated = $request->validate([
            'year' => [$presence, 'integer', 'between:2000,2100'],
            'month' => [$presence, 'integer', 'between:1,12'],
            'depot_id' => [$presence, 'integer', 'exists:depots,id'],
            'role_id' => [$presence, Rule::when($request->input('role_id') !== 'all', ['integer', 'exists:roles,id'], ['in:all'])],
        ]);

        return [
            'year' => isset($validated['year']) ? (int) $validated['year'] : now()->year,
            'month' => isset($validated['month']) ? (int) $validated['month'] : null,
            'depot_id' => isset($validated['depot_id']) ? (int) $validated['depot_id'] : null,
            'role_id' => isset($validated['role_id']) ? ($validated['role_id'] === 'all' ? 'all' : (int) $validated['role_id']) : null,
        ];
    }

    private function report(array $filters): array
    {
        $relations = ['depot', 'items' => fn ($q) => $q->where('pf', '>', 0), 'items.user.roles'];
        foreach (['staffProfile', 'driverProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile'] as $profile) {
            $relations[] = 'items.user.'.$profile;
        }
        $processings = SalaryProcessing::with($relations)->where('year', $filters['year'])
            ->where('month', $filters['month'])->where('depot_id', $filters['depot_id'])
            ->whereRaw('LOWER(status) = ?', ['approved'])->get();
        $role = $filters['role_id'] === 'all' ? null : Role::find($filters['role_id']);
        $items = $processings->flatMap(function ($processing) {
            return $processing->items->each(fn ($item) => $item->setRelation('salaryProcessing', $processing));
        })->filter(fn ($item) => PfFileData::uan($item) !== ''
            && ($filters['role_id'] === 'all' || WageRegister::role($item) === $role?->name))
            ->sortBy(fn ($item) => $item->user?->name ?? '')->values();
        $rows = $items->map(fn ($item) => PfFileData::row($item))->all();
        $warnings = [];
        foreach ($rows as $row) {
            $missing = [];
            foreach ([2 => 'Gross Salary', 3 => 'EPF Wages', 5 => 'Worked Shifts'] as $column => $label) {
                if ($row[$column] === null) $missing[] = $label;
            }
            if ($missing) $warnings[] = $row[1].' (UAN '.$row[0].'): '.implode(', ', $missing).' unavailable in the approved snapshot; shown blank.';
        }

        return [
            'rows' => $rows, 'warnings' => $warnings, 'headings' => PfFileData::HEADINGS,
            'depot' => $processings->first()?->depot ?: Depot::find($filters['depot_id']),
            'monthName' => Carbon::create(null, $filters['month'], 1)->format('F'),
            'year' => $filters['year'], 'roleLabel' => $filters['role_id'] === 'all' ? 'All' : ($role?->name ?? ''),
        ];
    }

    private function fileName(array $report): string
    {
        $depot = $report['depot']?->short_name ?: $report['depot']?->name ?: 'Depot';
        $name = preg_replace('/[^A-Za-z0-9]/', '', $depot).'_'.$report['monthName'].'_'.$report['year'].'_PF_'.$report['roleLabel'];

        return preg_replace('/[^A-Za-z0-9_-]/', '', $name).'.xlsx';
    }
}
