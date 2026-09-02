@section('title', 'Letter Templates')
<x-app-layout>
<section class="section dashboard section-top-padding">
    <div class="page-title"><h3>Manage Letter Templates</h3><nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item active">HRMS</li><li class="breadcrumb-item active">Settings</li><li class="breadcrumb-item active">Letter Templates</li></ol></nav></div>
    <div class="row"><div class="col-lg-12 mb-3"><div class="main-table-container">
        <div class="row mb-4">
            <div class="col-lg-4 o-f-inp mb-2"><label for="entityFilter">Filter by Entity</label><select id="entityFilter" class="form-select shadow-none select2"><option value="">--- Select ---</option>@foreach($entityTypes as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-lg-4 o-f-inp mb-2"><label for="languageFilter">Filter by Language</label><select id="languageFilter" class="form-select shadow-none select2"><option value="">--- Select ---</option>@foreach($languages as $language)<option value="{{ $language }}">{{ $language }}</option>@endforeach</select></div>
            <div class="col-lg-4 o-f-inp mb-2"><label for="statusFilter">Filter by Status</label><select id="statusFilter" class="form-select shadow-none select2"><option value="">--- Select ---</option><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div class="col-lg-4 ms-auto d-flex align-items-end btns-group-container" style="align-items:flex-end !important; "><button type="button" id="resetFilters" class="btn btn-secondary ">Reset</button> @can('hr-letter-templates.create')<a href="{{ route('hr-letter-templates.create') }}" class="add-btn form-btn text-decoration-none m-0">Create Template</a>@endcan</div>
        </div>
      
        <div class="row"><div class="col-lg-12"><div class="mt-3 table-container table-over-cs"><table id="table" class="table align-middle mb-0 tble-cstm mt-3" style="width:100%"><thead><tr><th class="text-center">Sl No</th><th class="text-center">Entity</th><th class="text-center">Template</th><th class="text-center">Language</th><th class="text-center">Status</th><th class="text-center">Action</th></tr></thead><tbody></tbody></table></div></div></div>
    </div></div></div>
</section>
@section('scripts') @include('hr-letter-template.partials.js') @endsection
</x-app-layout>
