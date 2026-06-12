<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Oem;
use App\Models\OemDepot;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class OemDepotController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('oems.view'), ['index', 'branchesByDepot']),
            new Middleware(PermissionMiddleware::using('oems.edit'), ['store', 'update', 'status', 'destroy']),
        ];
    }

    public function index(Request $request, Oem $oem)
    {
        $oem->load(['state', 'primaryContact']);

        if ($request->ajax()) {
            $query = OemDepot::with(['depot', 'branchLocation'])
                ->where('oem_id', $oem->id)
                ->orderByDesc('id');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('depot', fn (OemDepot $row) => $row->depot?->name ?? '-')
                ->addColumn('branch', fn (OemDepot $row) => $row->branchLocation?->name ?? '-')
                ->addColumn('status', fn (OemDepot $row) => $row->status
                    ? '<span class="status-green">Active</span>'
                    : '<span class="status-red">Inactive</span>')
                ->addColumn('action', function (OemDepot $row) {
                    if (! auth()->user()->can('oems.edit')) {
                        return '<span class="text-muted">No Access</span>';
                    }

                    $payload = e(json_encode([
                        'id' => $row->id,
                        'depot_id' => $row->depot_id,
                        'branch_location_id' => $row->branch_location_id,
                        'status' => $row->status,
                    ]));

                    $statusToggle = '<input type="checkbox" class="toggle-btn toggleDepotStatus" data-id="' . $row->id . '" data-status="' . ($row->status ? 1 : 0) . '" ' . ($row->status ? 'checked' : '') . ' title="Change Status">';

                    return '<div class="action-btns justify-content-center">'
                        . $statusToggle
                        . '<button type="button" class="btn-edit edit-oem-depot" data-depot="' . $payload . '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>'
                        . '<button type="button" class="btn-delete" onclick="deleteOemDepot(' . $row->id . ')" title="Delete"><i class="fa-solid fa-trash"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('oem.depots.index', [
            'oem' => $oem,
            'depots' => Depot::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, Oem $oem)
    {
        $validated = $this->validatedData($request, $oem);

        $oem->depots()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'OEM depot added successfully.',
        ], 201);
    }

    public function update(Request $request, OemDepot $oemDepot)
    {
        $validated = $this->validatedData($request, $oemDepot->oem, $oemDepot);

        $oemDepot->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'OEM depot updated successfully.',
            'data' => $oemDepot->fresh(),
        ]);
    }

    public function status(Request $request, OemDepot $oemDepot)
    {
        $validated = $request->validate([
            'status' => ['required', 'boolean'],
        ]);

        $oemDepot->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'OEM depot status updated successfully.',
        ]);
    }

    public function destroy(OemDepot $oemDepot)
    {
        $oemDepot->delete();

        return response()->json([
            'success' => true,
            'message' => 'OEM depot deleted successfully.',
        ]);
    }

    public function branchesByDepot(Depot $depot)
    {
        return response()->json(
            $depot->branchLocations()
                ->orderBy('branch_locations.name')
                ->get(['branch_locations.id', 'branch_locations.name'])
        );
    }

    private function validatedData(Request $request, Oem $oem, ?OemDepot $oemDepot = null): array
    {
        return $request->validate([
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'branch_location_id' => [
                'required',
                'integer',
                Rule::exists('depot_branch_location', 'branch_location_id')
                    ->where(fn ($query) => $query->where('depot_id', $request->input('depot_id'))),
                Rule::unique('oem_depots', 'branch_location_id')
                    ->where(fn ($query) => $query
                        ->where('oem_id', $oem->id)
                        ->where('depot_id', $request->input('depot_id')))
                    ->ignore($oemDepot?->id),
            ],
            'status' => ['required', 'boolean'],
        ]);
    }
}
