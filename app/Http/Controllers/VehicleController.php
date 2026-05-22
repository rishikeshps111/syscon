<?php

namespace App\Http\Controllers;

use App\Exports\VehicleExport;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\BranchLocation;
use App\Models\Depot;
use App\Models\Oem;
use App\Models\State;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class VehicleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('vehicles.view'), ['index', 'show', 'export', 'downloadPdf']),
            new Middleware(PermissionMiddleware::using('vehicles.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('vehicles.edit'), ['edit', 'update', 'changeStatus']),
            new Middleware(PermissionMiddleware::using('vehicles.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of($this->filteredQuery())
                ->addIndexColumn()
                ->addColumn('checkbox', fn (Vehicle $row) => '<input type="checkbox" class="row-check" value="' . $row->id . '">')
                ->addColumn('type', fn (Vehicle $row) => Vehicle::TYPES[$row->vehicle_type] ?? $row->vehicle_type)
                ->addColumn('fuel', fn (Vehicle $row) => Vehicle::FUEL_TYPES[$row->fuel_type] ?? $row->fuel_type)
                ->addColumn('oem_name', fn (Vehicle $row) => $row->oem?->oem_name ?? '-')
                ->addColumn('capacity', fn (Vehicle $row) => $this->capacityText($row))
                ->addColumn('insurance_expiry_badge', fn (Vehicle $row) => $this->expiryBadge($row->insurance_expiry))
                ->addColumn('fitness_expiry_badge', fn (Vehicle $row) => $this->expiryBadge($row->fitness_expiry))
                ->addColumn('gps_status', fn (Vehicle $row) => $row->gps_enabled
                    ? '<span class="status-green">Enabled</span>'
                    : '<span class="status-red">Disabled</span>')
                ->addColumn('status_badge', fn (Vehicle $row) => $this->statusBadge($row->status))
                ->addColumn('action', fn (Vehicle $row) => view('vehicle.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'insurance_expiry_badge', 'fitness_expiry_badge', 'gps_status', 'status_badge', 'action'])
                ->make(true);
        }

        return view('vehicle.index', $this->indexData());
    }

    public function create()
    {
        return view('vehicle.form', array_merge($this->formData(), [
            'generatedCode' => $this->generateVehicleCode(((int) Vehicle::max('id')) + 1),
        ]));
    }

    public function store(StoreVehicleRequest $request)
    {
        $vehicle = Vehicle::create($this->vehicleData($request->validated()) + [
            'vehicle_code' => null,
            'is_verified' => false,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $vehicle->vehicle_code = $this->generateVehicleCode($vehicle->id);
        $vehicle->save();

        return redirect()->route('vehicles.index')->with('success', 'Vehicle created successfully.');
    }

    public function show(Vehicle $vehicle)
    {
        return view('vehicle.show', [
            'record' => $this->vehicleRecord($vehicle),
        ]);
    }

    public function downloadPdf(Vehicle $vehicle)
    {
        $record = $this->vehicleRecord($vehicle);
        $pdf = $this->buildVehiclePdf($record);
        $fileName = ($record->vehicle_code ?: $record->vehicle_no ?: 'vehicle') . '-profile.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function edit(Vehicle $vehicle)
    {
        return view('vehicle.form', array_merge($this->formData(), [
            'record' => $vehicle,
        ]));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $vehicle->update($this->vehicleData($request->validated()) + [
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully.',
        ]);
    }

    public function changeStatus(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Vehicle::STATUSES))],
        ]);

        $vehicle->update($validated + [
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle status updated successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $query = $this->filteredQuery();
        $ids = $request->input('ids', []);

        if (! empty($ids)) {
            $query->whereIn('vehicles.id', $ids);
        }

        return Excel::download(new VehicleExport($query), 'vehicles.xlsx');
    }

    private function filteredQuery()
    {
        $query = Vehicle::with(['state', 'oem', 'depot', 'branch'])->select('vehicles.*');

        if (request()->filled('search_text')) {
            $search = request('search_text');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('vehicle_code', 'like', '%' . $search . '%')
                    ->orWhere('vehicle_no', 'like', '%' . $search . '%')
                    ->orWhere('engine_no', 'like', '%' . $search . '%')
                    ->orWhere('chassis_no', 'like', '%' . $search . '%');
            });
        }

        foreach (['state_id', 'oem_id', 'vehicle_type', 'fuel_type', 'status'] as $field) {
            if (request()->filled($field)) {
                $query->where($field, request($field));
            }
        }

        if (request()->filled('gps_enabled') && in_array(request('gps_enabled'), ['0', '1'], true)) {
            $query->where('gps_enabled', request('gps_enabled'));
        }

        return $query->orderBy('vehicles.updated_at', 'desc');
    }

    private function indexData(): array
    {
        return [
            'states' => State::orderBy('name')->get(['id', 'name']),
            'oems' => Oem::orderBy('oem_name')->get(['id', 'oem_name']),
            'vehicleTypes' => Vehicle::TYPES,
            'fuelTypes' => Vehicle::FUEL_TYPES,
            'statuses' => Vehicle::STATUSES,
        ];
    }

    private function formData(): array
    {
        return $this->indexData() + [
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'branches' => BranchLocation::orderBy('name')->get(['id', 'name']),
            'categories' => Vehicle::CATEGORIES,
        ];
    }

    private function generateVehicleCode(int $id): string
    {
        return generate_code('Vehicle Module', $id, 3, 'VEH');
    }

    private function vehicleRecord(Vehicle $vehicle): Vehicle
    {
        return $vehicle->load([
            'state',
            'oem',
            'depot',
            'branch',
            'documents.documentType',
            'assignments.driver',
            'assignments.route',
            'maintenanceLogs',
            'fuelLogs',
        ]);
    }

    private function vehicleData(array $data): array
    {
        if (($data['fuel_type'] ?? null) !== 'ELECTRIC') {
            $data['battery_capacity'] = null;
            $data['range_km'] = null;
        }

        if (empty($data['gps_enabled'])) {
            $data['gps_imei'] = null;
        }

        return $data;
    }

    private function capacityText(Vehicle $vehicle): string
    {
        $parts = [];

        if ($vehicle->capacity_seating !== null) {
            $parts[] = $vehicle->capacity_seating . ' Seats';
        }

        if ($vehicle->capacity_load !== null) {
            $parts[] = $vehicle->capacity_load . ' Load';
        }

        return $parts ? implode(' / ', $parts) : '-';
    }

    private function expiryBadge($date): string
    {
        if (! $date) {
            return '<span class="text-muted">-</span>';
        }

        $label = $date->format('d-m-Y');

        if ($date->lt(today())) {
            return '<span class="status-red">' . $label . ' - Expired</span>';
        }

        if ($date->lte(now()->addDays(30))) {
            return '<span class="status-orange">' . $label . ' - Expiring Soon</span>';
        }

        return '<span class="status-green">' . $label . ' - Active</span>';
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'Active' => '<span class="status-green">Active</span>',
            'Under Maintenance' => '<span class="status-orange">Under Maintenance</span>',
            'Scrap' => '<span class="status-red">Scrap</span>',
            default => '<span class="status-red">Inactive</span>',
        };
    }

    private function buildVehiclePdf(Vehicle $record): string
    {
        $content = '';
        $this->pdfFill($content, 0.96, 0.97, 0.99, 0, 0, 595, 842);
        $this->pdfText($content, 'SYSCON', 50, 795, 18, 'F2');
        $this->pdfText($content, 'Vehicle Profile', 50, 770, 22, 'F2');
        $this->pdfText($content, 'Generated on ' . now()->format('d-m-Y'), 430, 795, 10);
        $this->pdfStatus($content, $record->status ?: 'Inactive', 440, 765, $record->status === 'Active');

        $this->pdfCard($content, 40, 600, 515, 140);
        $this->pdfText($content, $record->vehicle_no ?: '-', 60, 710, 18, 'F2');
        $this->pdfText($content, 'Vehicle Code: ' . ($record->vehicle_code ?: '-'), 60, 688, 10);
        $this->pdfText($content, 'Type: ' . ($record->vehicle_type ?: '-'), 60, 670, 10);
        $this->pdfText($content, 'Fuel: ' . ($record->fuel_type ?: '-'), 60, 652, 10);
        $this->pdfText($content, 'OEM: ' . ($record->oem?->oem_name ?: '-'), 285, 688, 10);
        $this->pdfText($content, 'State: ' . ($record->state?->name ?: '-'), 285, 670, 10);
        $this->pdfText($content, 'Depot: ' . ($record->depot?->name ?: '-'), 285, 652, 10);
        $this->pdfText($content, 'Branch: ' . ($record->branch?->name ?: '-'), 285, 634, 10);

        $this->pdfSection($content, 'Vehicle Details', 40, 420, 250, [
            'Category' => $record->vehicle_category ?: '-',
            'Make' => $record->make ?: '-',
            'Model' => $record->model ?: '-',
            'Variant' => $record->variant ?: '-',
            'Capacity' => $this->capacityText($record),
        ]);

        $this->pdfSection($content, 'Identification', 305, 420, 250, [
            'Engine No' => $record->engine_no ?: '-',
            'Chassis No' => $record->chassis_no ?: '-',
            'GPS Enabled' => $record->gps_enabled ? 'Yes' : 'No',
            'GPS IMEI' => $record->gps_imei ?: '-',
            'Verified' => $record->is_verified ? 'Yes' : 'No',
        ]);

        $this->pdfSection($content, 'Registration & Compliance', 40, 210, 250, [
            'Registration Date' => $record->registration_date?->format('d-m-Y') ?: '-',
            'RC Validity' => $record->registration_valid_upto?->format('d-m-Y') ?: '-',
            'Fitness Expiry' => $record->fitness_expiry?->format('d-m-Y') ?: '-',
            'Permit Expiry' => $record->permit_expiry?->format('d-m-Y') ?: '-',
            'Insurance Expiry' => $record->insurance_expiry?->format('d-m-Y') ?: '-',
            'Pollution Expiry' => $record->pollution_expiry?->format('d-m-Y') ?: '-',
        ], 175);

        $this->pdfSection($content, 'Latest Activity', 305, 210, 250, [
            'Documents' => (string) $record->documents->count(),
            'Assignments' => (string) $record->assignments->count(),
            'Maintenance Logs' => (string) $record->maintenanceLogs->count(),
            'Fuel Logs' => (string) $record->fuelLogs->count(),
            'Remarks' => $record->remarks ?: '-',
        ], 175);

        $details = '';
        $this->pdfFill($details, 0.96, 0.97, 0.99, 0, 0, 595, 842);
        $this->pdfText($details, 'Vehicle Additional Details', 50, 790, 20, 'F2');
        $y = 735;

        foreach ([
            'Documents' => $record->documents->map(fn ($item) => ($item->documentType?->name ?: 'Document') . ' | ' . ($item->is_verified ? 'Verified' : 'Not Verified') . ($item->expiry_date ? ' | Expiry ' . $item->expiry_date->format('d-m-Y') : '')),
            'Assignments' => $record->assignments->take(8)->map(fn ($item) => ($item->driver?->name ?: '-') . ' | ' . ($item->route?->name ?: '-') . ' | ' . $item->status),
            'Maintenance' => $record->maintenanceLogs->take(8)->map(fn ($item) => $item->maintenance_type . ' | ' . ($item->service_date?->format('d-m-Y') ?: '-') . ' | ' . $item->status),
            'Fuel Logs' => $record->fuelLogs->take(8)->map(fn ($item) => $item->fuel_type . ' | ' . $item->quantity . ' | ' . ($item->date?->format('d-m-Y') ?: '-')),
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

    private function pdfSection(string &$content, string $title, int $x, int $y, int $width, array $items, int $height = 155): void
    {
        $this->pdfCard($content, $x, $y, $width, $height);
        $this->pdfText($content, $title, $x + 14, $y + $height - 26, 13, 'F2');
        $lineY = $y + $height - 50;

        foreach ($items as $label => $value) {
            $this->pdfText($content, $label . ':', $x + 14, $lineY, 9, 'F2');
            $this->pdfText($content, (string) $value, $x + 112, $lineY, 9);
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
        $this->pdfFill($content, $positive ? 0.88 : 1.00, $positive ? 0.97 : 0.90, $positive ? 0.91 : 0.90, $x, $y, 112, 24);
        $content .= $positive ? "0.13 0.55 0.27 rg\n" : "0.78 0.16 0.16 rg\n";
        $content .= "BT\n/F2 10 Tf\n" . ($x + 12) . ' ' . ($y + 8) . " Td\n(" . $this->escapePdfText($text) . ") Tj\nET\n";
    }

    private function escapePdfText(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
