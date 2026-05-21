<?php

namespace App\Http\Controllers;

use App\Models\Oem;
use App\Models\OemStateMapping;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class OemStateMappingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('oems.view'), ['index']),
            new Middleware(PermissionMiddleware::using('oems.edit'), ['store', 'update', 'makePrimary', 'destroy']),
        ];
    }

    public function index(Request $request, Oem $oem)
    {
        $oem->load(['state', 'primaryContact']);

        if ($request->ajax()) {
            $query = OemStateMapping::with('state')
                ->where('oem_id', $oem->id)
                ->orderByDesc('id');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('state', fn ($row) => $row->state?->name ?? '-')
                ->addColumn('is_primary', fn ($row) => $row->is_primary
                    ? '<span class="status-green">Primary</span>'
                    : '<span class="status-orange">Secondary</span>')
                ->addColumn('status', fn ($row) => $row->status
                    ? '<span class="status-green">Active</span>'
                    : '<span class="status-red">Inactive</span>')
                ->addColumn('action', function ($row) {
                    if (! auth()->user()->can('oems.edit')) {
                        return '<span class="text-muted">No Access</span>';
                    }

                    $payload = e(json_encode([
                        'id' => $row->id,
                        'state_id' => $row->state_id,
                        'gst_number' => $row->gst_number,
                        'status' => $row->status,
                    ]));

                    $primaryToggle = '<input type="checkbox" class="toggle-btn togglePrimary" data-id="' . $row->id . '" data-status="' . ($row->is_primary ? 1 : 0) . '" ' . ($row->is_primary ? 'checked' : '') . ' title="Make Primary">';

                    return '<div class="action-btns justify-content-center">'
                        . $primaryToggle
                        . '<button type="button" class="btn-edit edit-state-mapping" data-mapping="' . $payload . '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>'
                        . '<button type="button" class="btn-delete" onclick="deleteStateMapping(' . $row->id . ')" title="Delete"><i class="fa-solid fa-trash"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['is_primary', 'status', 'action'])
                ->make(true);
        }

        return view('oem.state-mappings.index', [
            'oem' => $oem,
            'states' => State::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, Oem $oem)
    {
        $validated = $this->validatedData($request, $oem);

        $mapping = DB::transaction(function () use ($validated, $oem) {
            $isPrimary = ! $oem->stateMappings()->exists();

            if ($isPrimary) {
                $oem->stateMappings()->update(['is_primary' => false]);
            }

            return $oem->stateMappings()->create(array_merge($validated, [
                'is_primary' => $isPrimary,
            ]));
        });

        return response()->json([
            'success' => true,
            'message' => 'State mapping added successfully.',
            'data' => $mapping,
        ], 201);
    }

    public function update(Request $request, OemStateMapping $oemStateMapping)
    {
        $validated = $this->validatedData($request, $oemStateMapping->oem, $oemStateMapping);

        $oemStateMapping->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'State mapping updated successfully.',
            'data' => $oemStateMapping->fresh(),
        ]);
    }

    public function makePrimary(OemStateMapping $oemStateMapping)
    {
        DB::transaction(function () use ($oemStateMapping) {
            $oemStateMapping->oem->stateMappings()->update(['is_primary' => false]);
            $oemStateMapping->update(['is_primary' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'State mapping marked as primary successfully.',
            'data' => $oemStateMapping->fresh(),
        ]);
    }

    public function destroy(OemStateMapping $oemStateMapping)
    {
        DB::transaction(function () use ($oemStateMapping) {
            $oem = $oemStateMapping->oem;
            $wasPrimary = $oemStateMapping->is_primary;

            $oemStateMapping->delete();

            if ($wasPrimary) {
                $nextMapping = $oem->stateMappings()->orderByDesc('id')->first();
                $nextMapping?->update(['is_primary' => true]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'State mapping deleted successfully.',
        ]);
    }

    private function validatedData(Request $request, Oem $oem, ?OemStateMapping $mapping = null): array
    {
        return $request->validate([
            'state_id' => [
                'required',
                'integer',
                'exists:states,id',
                Rule::unique('oem_state_mappings', 'state_id')
                    ->where('oem_id', $oem->id)
                    ->ignore($mapping?->id),
            ],
            'gst_number' => ['required', 'string', 'max:30'],
            'status' => ['required', 'boolean'],
        ]);
    }
}
