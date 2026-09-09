<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class AttendanceConsolidateCsv
{
    public const HEADERS = ['Emp Id', 'Name Of The Employee', 'P', 'W/O', 'A', 'Total'];

    public function read(string $path, int $depotId, ?string $extension = null): array
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, ['xlsx', 'xls'], true)) {
            return $this->readExcel($path, $depotId, $extension);
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $this->invalid(['Unable to read the CSV file.']);
        }
        $rows = [];
        $errors = [];
        $seen = [];
        $headers = null;
        $line = 0;
        try {
            while (($cells = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $line++;
                $cells = array_map(fn ($cell) => trim((string) $cell), $cells);
                if ($line === 1) {
                    $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cells[0]);
                }
                if (! array_filter($cells, fn ($cell) => $cell !== '')) {
                    continue;
                }
                if (! $headers) {
                    // Excel's consolidated sheet has an optional title above its six headers.
                    if ($line === 1 && count(array_filter($cells, fn ($cell) => $cell !== '')) === 1) {
                        continue;
                    }
                    $headers = array_map(fn ($cell) => $this->header($cell), $cells);
                    if (count($headers) !== 6 || in_array(null, $headers, true) || count(array_unique($headers)) !== 6) {
                        $this->invalid(['The file must have these six columns: '.implode(', ', self::HEADERS).'.']);
                    }

                    continue;
                }
                if (count($rows) >= 10000) {
                    $this->invalid(['A file may contain at most 10,000 employees.']);
                }
                if (count($cells) !== 6 || ! mb_check_encoding(implode('', $cells), 'UTF-8')) {
                    $errors[] = "Row {$line}: use six columns and UTF-8 text.";

                    continue;
                }
                $row = array_combine($headers, $cells);
                $ref = $row['employee_ref_code'];
                if ($ref === '' || mb_strlen($ref) > 100) {
                    $errors[] = "Row {$line}: Emp Id is required and must not exceed 100 characters.";
                }
                if ($row['employee_name'] === '' || mb_strlen($row['employee_name']) > 255) {
                    $errors[] = "Row {$line}: employee name is required and must not exceed 255 characters.";
                }
                if (isset($seen[$ref])) {
                    $errors[] = "Row {$line}: duplicate Emp Id; first found on row {$seen[$ref]}.";
                }
                $seen[$ref] = $line;
                $numbers = [];
                foreach (['present_days', 'week_off_days', 'absent_days', 'total_days'] as $column) {
                    if (! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $row[$column])) {
                        $errors[] = "Row {$line}: {$column} must be a non-negative number with at most two decimal places.";

                        continue;
                    }
                    [$whole, $fraction] = array_pad(explode('.', $row[$column]), 2, '');
                    $numbers[$column] = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
                    $row[$column] = $whole.'.'.str_pad($fraction, 2, '0');
                }
                if (count($numbers) === 4 && $numbers['total_days'] !== $numbers['present_days'] + $numbers['week_off_days']) {
                    $errors[] = "Row {$line}: Total must equal P + W/O; absent days are excluded.";
                }
                $row['source_row'] = $line;
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }
        if (! $rows) {
            $errors[] = 'The file contains no employee rows.';
        }
        if ($errors) {
            $this->invalid($errors);
        }

        $users = collect();
        foreach (array_chunk(array_column($rows, 'employee_ref_code'), 500) as $refs) {
            $users = $users->concat(User::query()->whereIn('ref_code', $refs)->select(['id', 'ref_code', 'name'])
                ->with(['staffProfile:id,user_id,depot_id', 'driverProfile:id,user_id,depot_id',
                    'housekeepingProfile:id,user_id,depot_id', 'controllerProfile:id,user_id,depot_id', 'supervisorProfile:id,user_id,depot_id'])->get());
        }
        $users = $users->unique('id')->groupBy('ref_code');
        foreach ($rows as &$row) {
            $matches = $users->get($row['employee_ref_code'], collect());
            if ($matches->count() !== 1) {
                $errors[] = "Row {$row['source_row']}: Emp Id {$row['employee_ref_code']} ".($matches->isEmpty() ? 'does not match users.ref_code.' : 'matches multiple users; resolve the duplicate reference first.');

                continue;
            }
            $user = $matches->first();
            $warnings = [];
            if ($this->name($row['employee_name']) !== $this->name($user->name)) {
                $warnings[] = "Name differs from the user record ({$user->name}). The uploaded name will be retained.";
            }
            $depots = collect(['staffProfile', 'driverProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile'])
                ->map(fn ($profile) => $user->{$profile}?->depot_id)->filter()->map(fn ($id) => (int) $id)->unique();
            if ($depots->isEmpty()) {
                $warnings[] = 'The user has no recorded depot; review the selected depot.';
            } elseif (! $depots->contains($depotId)) {
                $warnings[] = 'The selected depot differs from the user’s current depot. Review before importing historical attendance.';
            }
            $row['user_id'] = $user->id;
            $row['warnings'] = $warnings;
        }
        unset($row);
        if ($errors) {
            $this->invalid($errors);
        }

        return $rows;
    }

    private function header(string $value): ?string
    {
        return match (preg_replace('/[^a-z0-9]/', '', strtolower($value))) {
            'empid', 'employeeid', 'refcode' => 'employee_ref_code',
            'nameoftheemployee', 'name', 'fullname', 'employeename' => 'employee_name',
            'p', 'presentdays' => 'present_days',
            'wo', 'weekoff', 'weekoffdays' => 'week_off_days',
            'a', 'absentdays' => 'absent_days',
            'total', 'totaldays' => 'total_days',
            default => null,
        };
    }

    private function readExcel(string $path, int $depotId, string $extension): array
    {
        $book = null;
        $temporary = tmpfile();
        try {
            $reader = IOFactory::createReader($extension === 'xlsx' ? 'Xlsx' : 'Xls');
            if (! $reader->canRead($path)) {
                $this->invalid(['The file is not a valid Excel workbook.']);
            }
            $candidates = [];
            foreach ($reader->listWorksheetInfo($path) as $info) {
                if ($info['totalRows'] <= 10002 && $info['totalColumns'] === 6) {
                    $candidates[] = $info['worksheetName'];
                }
            }
            if (! $candidates) {
                $this->invalid(['Excel must contain a six-column consolidated attendance sheet with at most 10,000 employees.']);
            }
            $reader->setLoadSheetsOnly($candidates);
            $book = $reader->load($path);
            $matches = [];
            foreach ($book->getAllSheets() as $sheet) {
                foreach ([1, 2] as $line) {
                    $headers = [];
                    foreach (range('A', 'F') as $column) {
                        $headers[] = $this->header(trim((string) $sheet->getCell($column.$line)->getValue()));
                    }
                    if (! in_array(null, $headers, true) && count(array_unique($headers)) === 6) {
                        $matches[] = [$sheet, $line, $headers];
                        break;
                    }
                }
            }
            if (count($matches) !== 1) {
                $this->invalid(['Excel must contain exactly one matching attendance sheet, with the six column headers on row 1 or 2.']);
            }
            [$sheet, $headerLine, $headers] = $matches[0];
            // Keep source row numbers aligned with the uploaded workbook.
            if ($headerLine === 2) {
                fputcsv($temporary, ['Attendance'], ',', '"', '');
            }
            for ($line = $headerLine; $line <= $sheet->getHighestDataRow(); $line++) {
                $values = [];
                foreach (range('A', 'F') as $index => $column) {
                    $cell = $sheet->getCell($column.$line);
                    $value = $cell->getValue();
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        $value = $cell->getOldCalculatedValue();
                        if ($value === null) {
                            $this->invalid(["Row {$line}: save the workbook in Excel to calculate formulas before uploading."]);
                        }
                    } elseif ($line > $headerLine && $headers[$index] === 'employee_ref_code' && is_numeric($value)) {
                        $value = $cell->getFormattedValue();
                    }
                    $values[] = (string) $value;
                }
                fputcsv($temporary, $values, ',', '"', '');
            }
            fflush($temporary);

            return $this->read(stream_get_meta_data($temporary)['uri'], $depotId, 'csv');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->invalid(['Unable to read the Excel workbook. Upload an unencrypted .xlsx or .xls file with the required columns.']);
        } finally {
            $book?->disconnectWorksheets();
            if (is_resource($temporary)) {
                fclose($temporary);
            }
        }
    }

    private function name(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)));
    }

    private function invalid(array $errors): never
    {
        throw ValidationException::withMessages(['csv_file' => array_slice($errors, 0, 100)]);
    }
}
