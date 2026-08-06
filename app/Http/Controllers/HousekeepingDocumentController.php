<?php
namespace App\Http\Controllers;
use App\Models\HousekeepingDocument;
use App\Models\HrmsDocumentType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;
class HousekeepingDocumentController extends Controller implements HasMiddleware {
 public static function middleware(): array { return ['auth',new Middleware(PermissionMiddleware::using('housekeeping-management.view'),['index','preview','download']),new Middleware(PermissionMiddleware::using('housekeeping-management.edit'),['store','destroy'])]; }
 public function index(Request $request, User $housekeeping) { abort_unless($housekeeping->hasRole('Housekeeping'),404);$housekeeping->load(['housekeepingProfile.depot','housekeepingProfile.branchLocation']);$documentTypes=$this->types();if($request->ajax()){return DataTables::of(HousekeepingDocument::with('documentType')->where('user_id',$housekeeping->id)->latest())->addIndexColumn()->addColumn('type',fn($r)=>$r->documentType?->name??'-')->addColumn('expiry_date',fn($r)=>$r->expiry_date?->format('d-m-Y')??'-')->addColumn('status',fn($r)=>$r->is_verified?'<span class="status-green">Verified</span>':'<span class="status-red">Not Verified</span>')->addColumn('action',fn($r)=>'<div class="action-btns justify-content-center"><button class="btn-edit view-document" data-bs-toggle="modal" data-bs-target="#viewDoc" data-preview="'.route('housekeeping-documents.preview',$r).'" data-download="'.route('housekeeping-documents.download',$r).'"><i class="fa-solid fa-eye"></i></button>'.(auth()->user()->can('housekeeping-management.edit')?'<button class="btn-delete" onclick="deleteDocument('.$r->id.')"><i class="fa-solid fa-trash"></i></button>':'').'</div>')->rawColumns(['status','action'])->make(true);}return view('housekeeping-management.documents.index',compact('housekeeping','documentTypes')); }
 public function store(Request $request,User $housekeeping){abort_unless($housekeeping->hasRole('Housekeeping'),404);$data=$request->validate(['hrms_document_type_id'=>['required',Rule::in($this->types()->pluck('id')->all())],'expiry_date'=>['nullable','date'],'document_file'=>['required','file','mimes:pdf,jpg,jpeg,png,doc,docx','max:5120'],'is_verified'=>['nullable','boolean']]);$type=HrmsDocumentType::findOrFail($data['hrms_document_type_id']);if($type->is_expiry_required&&empty($data['expiry_date']))throw ValidationException::withMessages(['expiry_date'=>'Expiry date is required.']);$file=$request->file('document_file');$this->validateAllowedFileType($type,$file->getClientOriginalExtension());$doc=HousekeepingDocument::create(['user_id'=>$housekeeping->id,'hrms_document_type_id'=>$type->id,'expiry_date'=>$data['expiry_date']??null,'file_path'=>$file->store('housekeeping-documents/'.$housekeeping->id,'public'),'original_name'=>$file->getClientOriginalName(),'is_verified'=>(bool)($data['is_verified']??false)]);return response()->json(['success'=>true,'message'=>'Document added successfully.','data'=>$doc],201);}
 public function preview(HousekeepingDocument $housekeepingDocument){$this->fileGuard($housekeepingDocument);return response()->file(Storage::disk('public')->path($housekeepingDocument->file_path));}
 public function download(HousekeepingDocument $housekeepingDocument){$this->fileGuard($housekeepingDocument);return Storage::disk('public')->download($housekeepingDocument->file_path,$housekeepingDocument->original_name?:basename($housekeepingDocument->file_path));}
 public function destroy(HousekeepingDocument $housekeepingDocument){abort_unless($housekeepingDocument->housekeeping?->hasRole('Housekeeping'),404);Storage::disk('public')->delete($housekeepingDocument->file_path);$housekeepingDocument->delete();return response()->json(['success'=>true,'message'=>'Document deleted successfully.']);}
 private function types(){return HrmsDocumentType::where('is_active',true)->where('applicable_for','all')->orderBy('name')->get();}
 private function validateAllowedFileType(HrmsDocumentType $type,string $extension):void{$allowed=collect($type->allowed_file_types)->map(fn($value)=>strtolower(ltrim($value,'.')))->filter();if($allowed->isNotEmpty()&&!$allowed->contains(strtolower($extension)))throw ValidationException::withMessages(['document_file'=>'The selected file type is not allowed for this document.']);}
 private function fileGuard(HousekeepingDocument $doc):void{abort_unless($doc->housekeeping?->hasRole('Housekeeping')&&Storage::disk('public')->exists($doc->file_path),404);}
}
