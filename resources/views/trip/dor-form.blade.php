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
        <form method="POST" action="{{ route('trips.sheet.entries.dor.store', [$record->id, $entry->id]) }}" id="dorForm" enctype="multipart/form-data">
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
                                            @elseif(($field['type'] ?? 'text') === 'file')
                                                <input
                                                    type="file"
                                                    name="{{ $name }}"
                                                    id="{{ $name }}"
                                                    class="form-control odometer-image-input @error($name) is-invalid @enderror"
                                                    accept="image/*"
                                                    data-preview="{{ $name }}_preview"
                                                    data-target="{{ $field['target'] ?? '' }}"
                                                    @disabled($disabled)
                                                >
                                                <div class="mt-2 d-flex align-items-start gap-3 flex-wrap">
                                                    <img id="{{ $name }}_preview"
                                                        src="{{ $field['image_url'] ?? '' }}"
                                                        alt="{{ $field['label'] }} preview"
                                                        class="odometer-preview"
                                                        style="{{ empty($field['image_url'] ?? null) ? 'display:none;' : '' }} max-width: 220px; max-height: 140px; border-radius: 6px; border: 1px solid #d9dee3; object-fit: cover;">
                                                    @if(! empty($field['image_url'] ?? null))
                                                        <a href="{{ $field['image_url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">View saved image</a>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block mt-2">Upload the meter photo for verification. If reading detection is not available, enter the reading manually.</small>
                                            @else
                                                <input
                                                    type="{{ $field['type'] ?? 'text' }}"
                                                    name="{{ $name }}"
                                                    id="{{ $name }}"
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
                function refreshOdometerDiff() {
                    var start = parseFloat($('#odometer_start_reading').val());
                    var end = parseFloat($('#odometer_end_reading').val());

                    if (!Number.isNaN(start) && !Number.isNaN(end) && end >= start) {
                        $('#odometer_diff_km').val((end - start).toFixed(2));
                    }
                }

                function readingFromFileName(fileName) {
                    var baseName = (fileName || '').replace(/\.[^.]+$/, '');
                    var matches = baseName.match(/\d+(?:[._-]\d+)?/g);

                    if (!matches || !matches.length) {
                        return '';
                    }

                    return matches[matches.length - 1].replace(/[._-]/g, '.');
                }

                $('.odometer-image-input').on('change', function () {
                    var input = this;
                    var file = input.files && input.files[0] ? input.files[0] : null;
                    var preview = document.getElementById($(input).data('preview'));
                    var target = $('#' + $(input).data('target'));

                    if (!file) {
                        return;
                    }

                    if (preview) {
                        preview.src = URL.createObjectURL(file);
                        preview.style.display = 'block';
                        preview.onload = function () {
                            URL.revokeObjectURL(preview.src);
                        };
                    }

                    if (target.length && !target.val()) {
                        var reading = readingFromFileName(file.name);

                        if (reading) {
                            target.val(reading);
                            refreshOdometerDiff();
                        }
                    }
                });

                $('#odometer_start_reading, #odometer_end_reading').on('input', refreshOdometerDiff);

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
