<?php

namespace App\Http\Controllers;

use App\Models\{Trip, TripSheetEntry};
use App\Services\{MonthlyRosterImport, TripRosterAssignment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MonthlyRosterImportController extends Controller
{
    public function template(Request $request, Trip $trip, TripRosterAssignment $assignments)
    {
        $assignments->authorize($request->user(), $trip);
        $entries = TripSheetEntry::whereHas('sheet', fn ($query) => $query->where('trip_id', $trip->id))
            ->with(['sheet', 'driverProfile.user', 'vehicle', 'rosters.driverProfile.user', 'rosters.vehicle'])->get()
            ->sortBy(fn ($entry) => $entry->sheet->date->toDateString().'|'.$entry->service_code.'|'.$entry->code);
        if ($entries->isEmpty()) throw ValidationException::withMessages(['roster_file' => 'No trip-sheet entries exist for this trip.']);
        return response()->streamDownload(function () use ($entries) {
            $book = new Spreadsheet;
            try {
                $sheet = $book->getActiveSheet();
                $sheet->setTitle('Trip Drivers');
                $sheet->fromArray(MonthlyRosterImport::HEADERS, null, 'A1');
                $line = 2;
                foreach ($entries as $entry) {
                    $roster = $entry->rosters->sortByDesc('id')->first();
                    $values = [$entry->code, $entry->sheet->date->format('d-m-Y'), $entry->service_code,
                        $entry->driverProfile?->user?->name ?? $roster?->driverProfile?->user?->name ?? '',
                        $entry->vehicle?->vehicle_no ?? $roster?->vehicle?->vehicle_no ?? ''];
                    foreach ($values as $index => $value) $sheet->setCellValueExplicit([$index + 1, $line], (string) $value, DataType::TYPE_STRING);
                    $line++;
                }
                $sheet->getStyle('A1:E1')->getFont()->setBold(true);
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:E'.($line - 1));
                foreach (['A', 'B', 'C', 'D', 'E'] as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'trip-'.$trip->id.'-drivers.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function import(Request $request, Trip $trip, TripRosterAssignment $assignments, MonthlyRosterImport $importer)
    {
        $assignments->authorize($request->user(), $trip);
        $request->validate(['step' => ['required', Rule::in(['prepare', 'preview', 'rows', 'confirm', 'save', 'discard'])]]);
        $cache = Cache::store('file');
        $activeKey = 'monthly-roster-active.'.$request->user()->id.'.'.$trip->id;
        $lockKey = 'monthly-roster-lock.'.$request->user()->id.'.'.$trip->id;
        if ($request->step === 'prepare') {
            $request->validate(['roster_file' => ['required', 'file', 'mimes:xlsx,xls', 'extensions:xlsx,xls', 'max:5120']]);
            $rows = $importer->read($request->file('roster_file')->getRealPath());
            $token = (string) Str::uuid();
            $state = ['format' => 2, 'user_id' => $request->user()->id, 'trip_id' => $trip->id, 'rows' => $rows,
                'phase' => 'preview', 'previews' => [], 'reservations' => [], 'previewed' => 0,
                'processed' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
            $cache->lock($lockKey, 120)->block(5, function () use ($cache, $activeKey, $token, $state) {
                if ($old = $cache->get($activeKey)) $cache->forget('monthly-roster.'.$old);
                $cache->put('monthly-roster.'.$token, $state, 3600);
                $cache->put($activeKey, $token, 3600);
            });
            return response()->json($this->progress($state) + ['token' => $token]);
        }
        $request->validate(['token' => ['required', 'uuid']]);
        return $cache->lock($lockKey, 120)->block(5, function () use ($request, $trip, $importer, $cache, $activeKey) {
            $key = 'monthly-roster.'.$request->token;
            $state = $cache->get($key);
            abort_unless($state, 410, 'Import expired. Please upload the file again.');
            abort_unless($state['user_id'] === $request->user()->id && $state['trip_id'] === $trip->id, 403);
            abort_unless(($state['format'] ?? null) === 2, 410, 'Download a fresh sample and upload it again; this preview uses the previous format.');
            abort_unless($cache->get($activeKey) === $request->token, 410, 'This preview was replaced. Upload the file again.');
            $cache->put($activeKey, $request->token, 3600);
            if ($request->step === 'discard') {
                $cache->forget($key);
                $cache->forget($activeKey);
                return response()->json(['phase' => 'discarded']);
            }
            if ($request->step === 'rows') {
                $request->validate(['page' => ['required', 'integer', 'min:1']]);
                $page = (int) $request->page;
                return response()->json($this->progress($state) + ['page' => $page, 'pages' => max(1, (int) ceil(count($state['previews']) / 25)),
                    'rows' => array_map(fn ($row) => array_intersect_key($row, array_flip([
                        'line', 'trip_code', 'display_date', 'service', 'driver_name', 'current_driver', 'proposed_driver',
                        'vehicle', 'current_vehicle', 'proposed_vehicle', 'status', 'reason', 'outcome', 'conflicts',
                    ])), array_slice($state['previews'], ($page - 1) * 25, 25))]);
            }
            if ($request->step === 'confirm') {
                $request->validate(['confirmed' => ['required', 'accepted']]);
                abort_unless($state['phase'] === 'ready' && $state['previewed'] === count($state['rows'])
                    && $this->progress($state)['summary']['eligible'] > 0, 409, 'Complete the preview and resolve issues before confirmation.');
                $state['phase'] = 'saving';
                $cache->put($key, $state, 3600);
                return response()->json($this->progress($state));
            }
            $request->validate(['offset' => ['required', 'integer', 'min:0']]);
            $preview = $request->step === 'preview';
            abort_unless($state['phase'] === ($preview ? 'preview' : 'saving'), 409, 'Complete preview and explicitly confirm eligible rows before saving.');
            $offset = $preview ? $state['previewed'] : $state['processed'];
            abort_unless((int) $request->offset === $offset, 409, 'Import progress changed. Reload the page before retrying.');
            foreach (array_slice($state['rows'], $offset, 25) as $index => $row) {
                $position = $offset + $index;
                if ($preview) {
                    $state['previews'][$position] = $importer->preview($request->user(), $trip, $row);
                    $importer->flagUploadedConflicts($state['previews'], $state['reservations'], $position);
                    $state['previewed']++;
                } else {
                    $checked = $state['previews'][$position];
                    $result = in_array($checked['status'], ['ready_create', 'ready_update'], true)
                        ? $importer->save($request->user(), $trip, $checked)
                        : ['status' => $checked['status'] === 'unchanged' ? 'unchanged' : 'skipped', 'reason' => $checked['reason']];
                    $state[$result['status']]++;
                    $state['processed']++;
                    $state['previews'][$position]['outcome'] = $result['status'];
                    $state['previews'][$position]['reason'] = $result['reason'];
                    if (isset($result['conflicts'])) $state['previews'][$position]['conflicts'] = $result['conflicts'];
                }
                $cache->put($key, $state, 3600);
            }
            if ($preview && $state['previewed'] === count($state['rows'])) $state['phase'] = 'ready';
            if (! $preview && $state['processed'] === count($state['rows'])) {
                $state['phase'] = 'done';
                $request->session()->flash('success', 'Roster import completed: '.$state['created'].' created, '.$state['updated']
                    .' updated, '.$state['unchanged'].' unchanged, '.$state['skipped'].' skipped.');
                $summary = array_intersect_key($state, array_flip(['created', 'updated', 'unchanged', 'skipped']));
                $summary['reasons'] = [];
                foreach ($state['previews'] as $row) {
                    if (($row['outcome'] ?? '') === 'skipped' && $row['status'] !== 'blank') {
                        $summary['reasons'][] = 'Row '.$row['line'].' ('.$row['trip_code'].'): '.$row['reason'];
                    }
                }
                $request->session()->flash('roster_update_summary', $summary);
            }
            $cache->put($key, $state, 3600);
            return response()->json($this->progress($state));
        });
    }

    private function progress(array $state): array
    {
        $summary = ['ready_create' => 0, 'ready_update' => 0, 'unchanged' => 0, 'blank' => 0, 'issue' => 0];
        foreach ($state['previews'] as $row) $summary[$row['status']]++;
        $summary['eligible'] = $summary['ready_create'] + $summary['ready_update'];
        return array_intersect_key($state, array_flip(['phase', 'previewed', 'processed', 'created', 'updated', 'unchanged', 'skipped']))
            + ['total' => count($state['rows']), 'summary' => $summary];
    }
}
