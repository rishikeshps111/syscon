@section('title')
    DOR
@endsection
<x-app-layout>
    <div class="page-title">
        <h3>DOR</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trip Management</a></li>
                <li class="breadcrumb-item"><a href="{{ route('trips.sheet.view', $record->id) }}">View Trip Sheet</a></li>
                <li class="breadcrumb-item active">DOR</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <form method="POST" action="{{ route('trips.sheet.entries.dor.store', [$record->id, $entry->id]) }}" id="dorForm">
            @csrf

            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="table-over field-table field-table-cs mt-3">
                        <table class="align-middle mb-0 table tble-cstm bg-transparent">
                            <thead>
                                <tr class="payroll-table">
                                    <th class="nowrap">Sl No</th>
                                    <th class="nowrap">Title</th>
                                    <th>#</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fields as $field)
                                    @php
                                        $name = $field['name'];
                                        $value = old($name, $field['value'] ?? '');
                                        $disabled = (bool) ($field['disabled'] ?? false);
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $loop->iteration }}</td>
                                        <td class="text-muted">{{ $field['label'] }}</td>
                                        <td>
                                            @if(($field['type'] ?? 'text') === 'select')
                                                <select name="{{ $name }}" class="form-select" @disabled($disabled)>
                                                    <option value="">Select</option>
                                                    @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input
                                                    type="{{ $field['type'] ?? 'text' }}"
                                                    name="{{ $name }}"
                                                    class="form-control"
                                                    value="{{ $value }}"
                                                    step="any"
                                                    @disabled($disabled)
                                                >
                                            @endif

                                            @error($name)
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-12 mt-3">
                <div class="btn-flex" style="justify-content: center;">
                    <a href="{{ route('trips.sheet.view', $record->id) }}" class="add-btn bg-filter">Back</a>
                    @if($dor)
                        <a href="{{ route('trips.sheet.entries.dor.preview', [$record->id, $entry->id]) }}" class="add-btn">Preview</a>
                    @endif
                    <button type="submit" class="add-btn js-loading-submit"
                        data-loading-text="<i class='fa-solid fa-spinner fa-spin me-1'></i> Saving">
                        {{ $dor ? 'Update DOR' : 'Create DOR' }}
                    </button>
                </div>
            </div>
        </form>
    </section>

    @section('scripts')
        <script>
            $(function () {
                $('#dorForm').on('submit', function () {
                    var button = $(this).find('.js-loading-submit');

                    if (button.prop('disabled')) {
                        return false;
                    }

                    button.prop('disabled', true).html(button.data('loading-text') || 'Loading...');
                });
            });
        </script>
    @endsection
</x-app-layout>
