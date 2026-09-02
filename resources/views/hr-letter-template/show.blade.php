@section('title', $template->template_name)
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ $template->template_name }} Preview</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-letter-templates.index') }}">Letter Templates</a></li>
                    <li class="breadcrumb-item active">Preview</li>
                </ol>
            </nav>
        </div>

       

        <div class="main-table-containers">
             <div class="btns-print-group">
            <a class="back-show" href="{{ route('hr-letter-templates.index') }}">Back</a>
            @can('hr-letter-templates.edit')
                <a class="edit-temp" href="{{ route('hr-letter-templates.edit', $template) }}">Edit Template</a>
            @endcan
        </div>
            <div class="letter-tem-doc">
                @include('hr-letter.partials.document', ['letter' => $template])
                
            </div>
        </div>
    </section>
</x-app-layout>
