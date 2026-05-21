<?php

namespace App\Http\Controllers;

use App\Models\Oem;
use App\Models\OemBankDetail;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class OemBankDetailController extends Controller implements HasMiddleware
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
            $query = OemBankDetail::where('oem_id', $oem->id)->orderByDesc('id');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('is_primary', fn ($row) => $row->is_primary
                    ? '<span class="status-green">Primary</span>'
                    : '<span class="status-orange">Secondary</span>')
                ->addColumn('action', function ($row) {
                    if (! auth()->user()->can('oems.edit')) {
                        return '<span class="text-muted">No Access</span>';
                    }

                    $payload = e(json_encode([
                        'id' => $row->id,
                        'account_name' => $row->account_name,
                        'account_number' => $row->account_number,
                        'bank_name' => $row->bank_name,
                        'branch' => $row->branch,
                        'ifsc_code' => $row->ifsc_code,
                    ]));

                    $primaryToggle = '<input type="checkbox" class="toggle-btn toggleStatus" data-id="' . $row->id . '" data-status="' . ($row->is_primary ? 1 : 0) . '" ' . ($row->is_primary ? 'checked' : '') . '>';

                    return '<div class="action-btns justify-content-center">'
                        . $primaryToggle
                        . '<button type="button" class="btn-edit edit-bank-detail" data-detail="' . $payload . '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>'
                        . '<button type="button" class="btn-delete" onclick="deleteBankDetail(' . $row->id . ')" title="Delete"><i class="fa-solid fa-trash"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['is_primary', 'action'])
                ->make(true);
        }

        return view('oem.bank-details.index', compact('oem'));
    }

    public function store(Request $request, Oem $oem)
    {
        $validated = $this->validatedData($request, $oem);

        $bankDetail = DB::transaction(function () use ($validated, $oem) {
            $isPrimary = ! $oem->bankDetails()->exists();

            if ($isPrimary) {
                $oem->bankDetails()->update(['is_primary' => false]);
            }

            return $oem->bankDetails()->create(array_merge($validated, [
                'is_primary' => $isPrimary,
            ]));
        });

        return response()->json([
            'success' => true,
            'message' => 'Bank detail added successfully.',
            'data' => $bankDetail,
        ], 201);
    }

    public function update(Request $request, OemBankDetail $oemBankDetail)
    {
        $validated = $this->validatedData($request, $oemBankDetail->oem, $oemBankDetail);

        $oemBankDetail->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bank detail updated successfully.',
            'data' => $oemBankDetail->fresh(),
        ]);
    }

    public function makePrimary(OemBankDetail $oemBankDetail)
    {
        DB::transaction(function () use ($oemBankDetail) {
            $oemBankDetail->oem->bankDetails()->update(['is_primary' => false]);
            $oemBankDetail->update(['is_primary' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Bank detail marked as primary successfully.',
            'data' => $oemBankDetail->fresh(),
        ]);
    }

    public function destroy(OemBankDetail $oemBankDetail)
    {
        DB::transaction(function () use ($oemBankDetail) {
            $oem = $oemBankDetail->oem;
            $wasPrimary = $oemBankDetail->is_primary;

            $oemBankDetail->delete();

            if ($wasPrimary) {
                $nextBankDetail = $oem->bankDetails()->orderByDesc('id')->first();
                $nextBankDetail?->update(['is_primary' => true]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Bank detail deleted successfully.',
        ]);
    }

    private function validatedData(Request $request, Oem $oem, ?OemBankDetail $bankDetail = null): array
    {
        return $request->validate([
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('oem_bank_details', 'account_number')
                    ->where('oem_id', $oem->id)
                    ->ignore($bankDetail?->id),
            ],
            'bank_name' => ['required', 'string', 'max:255'],
            'branch' => ['required', 'string', 'max:255'],
            'ifsc_code' => ['required', 'string', 'max:20'],
        ]);
    }
}
