@section('title') Import {{ $config['label'] }} @endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Import {{ $config['label'] }}</h3>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item"><a href="{{ route($config['index_route']) }}">{{ $config['label'] }}</a></li><li class="breadcrumb-item active">Import</li></ol></nav>
        </div>
        <div class="main-table-container">
            <div class="mb-4">
                <p>Upload a CSV containing the same fields available on the add/edit form. Relationship columns use names, never database IDs. Names must exactly identify an existing record.</p>
                <p class="mb-0"><strong>Dates:</strong> YYYY-MM-DD &nbsp; <strong>Boolean values:</strong> yes/no, true/false, active/inactive, or 1/0.</p>
            </div>
            @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form class="js-loading-form" method="POST" action="{{ route('bulk-import.store', $module) }}" enctype="multipart/form-data">
                @csrf
                <div class="o-f-inp mb-3"><label for="csv_file">CSV file</label><input class="form-control shadow-none @error('csv_file') is-invalid @enderror" type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required></div>
                <div class="d-flex gap-2"><button class="btn btn-primary js-loading-submit" type="submit">Import</button><a class="btn btn-light" href="{{ route($config['index_route']) }}">Cancel</a></div>
            </form>
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">CSV Instructions</h5>
                    <a href="{{ route('bulk-import.sample', $module) }}" class="btn btn-outline-primary">Download Sample CSV</a>
                </div>
                <p class="mb-2">Create a CSV file with this exact header row:</p>
                <code class="d-block mb-3">{{ implode(',', $config['headers']) }}</code>
                <div class="table-over mb-3">
                    <table class="align-middle mb-0 table tble-cstm" style="width:100%;">
                        <thead><tr><th>Column</th><th>Required</th><th>Instruction</th></tr></thead>
                        <tbody>
                            @foreach ($config['instructions'] as $instruction)
                                <tr>
                                    <td><code>{{ $instruction['column'] }}</code></td>
                                    <td>{{ $instruction['required'] }}</td>
                                    <td>{{ $instruction['instruction'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p>The complete file is validated before any record is saved. If any row has an error, nothing is imported.</p>
            </div>
            @if (in_array($module, ['drivers', 'controllers', 'supervisors', 'staff'], true))<div class="alert alert-info mb-0">Salary structure is intentionally excluded. Imported profiles start with the existing zero/default salary values.</div>@endif
        </div>
    </section>
</x-app-layout>
