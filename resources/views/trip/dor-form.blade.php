@section('title')
    DOR
@endsection
@php
    $dorReadOnly = (bool) ($dorReadOnly ?? false);
    $canCompleteDor = (bool) ($canCompleteDor ?? false);
@endphp
<x-app-layout>
    <style>
        .fw-semibold {
            color: red !important;
        }
        input::file-selector-button {
            background-color: #025187 !important;
            height: 41px;
            color: #fff !important;
        }
        .dor-delay-highlight {
            border: 2px solid #dc3545 !important;
            box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.15);
        }

    </style>
    <div class="page-title">
        <h3>DOR</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trip Management</a></li>
                <li class="breadcrumb-item"><a href="{{ route('trips.sheet', $record->id) }}">Manage Trip Sheet</a>
                </li>
                <li class="breadcrumb-item active">DOR</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        @if($dorReadOnly)
            <div class="alert alert-info">
                This DOR is marked as complete and can only be edited by a Super Admin.
            </div>
        @endif

        <form method="POST" action="{{ route('trips.sheet.entries.dor.store', [$record->id, $entry->id]) }}"
            id="dorForm" enctype="multipart/form-data">
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
                                        $disabled = $dorReadOnly || (bool) ($field['disabled'] ?? false);
                                        $type = $field['type'] ?? 'text';
                                        $fieldClass = !empty($field['highlight'] ?? false) ? 'dor-delay-highlight' : '';
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $loop->iteration }}</td>
                                        <td class="{{ !empty($field['manual_formula'] ?? false) ? '' : 'text-muted' }}">
                                            {{ $field['label'] }}
                                        </td>
                                        <td>
                                            @if($disabled)
                                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                            @endif

                                            @if($type === 'select')
                                                <select name="{{ $name }}" class="form-select" @disabled($disabled)>
                                                    <option value="">Select</option>
                                                    @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($type === 'account_responsible')
                                                <select name="{{ $name }}" id="{{ $name }}"
                                                    class="form-select @error($name) is-invalid @enderror" @disabled($disabled)>
                                                    <option value="">Select</option>
                                                    @foreach($accountResponsibles as $account)
                                                        <option value="{{ $account->id }}" @selected((string) $value === (string) $account->id)>{{ $account->name }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($type === 'kilometer_loss_reason')
                                                <select name="{{ $name }}" id="{{ $name }}"
                                                    class="form-select @error($name) is-invalid @enderror" @disabled($disabled)>
                                                    <option value="">Select</option>
                                                    @foreach($kilometerLossReasons as $reason)
                                                        <option value="{{ $reason->id }}"
                                                            data-account="{{ $reason->dor_account_responsible_id }}"
                                                            @selected((string) $value === (string) $reason->id)>{{ $reason->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @elseif($type === 'file')
                                                <input type="file" name="{{ $name }}" id="{{ $name }}"
                                                    class="form-control odometer-image-input @error($name) is-invalid @enderror"
                                                    accept="image/*" data-preview="{{ $name }}_preview"
                                                    data-target="{{ $field['target'] ?? '' }}" @disabled($disabled)>
                                                <div class="mt-2 d-flex align-items-start gap-3 flex-wrap">
                                                    <img id="{{ $name }}_preview" src="{{ $field['image_url'] ?? '' }}"
                                                        alt="{{ $field['label'] }} preview" class="odometer-preview"
                                                        style="{{ empty($field['image_url'] ?? null) ? 'display:none;' : '' }} max-width: 220px; max-height: 140px; border-radius: 6px; border: 1px solid #d9dee3; object-fit: cover;">
                                                    @if(!empty($field['image_url'] ?? null))
                                                        <a href="{{ $field['image_url'] }}" target="_blank"
                                                            class="btn btn-sm btn-outline-primary">View saved image</a>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block mt-2">Upload the meter photo for verification.
                                                    If reading detection is not available, enter the reading manually.</small>
                                            @elseif($type === 'textarea')
                                                <textarea name="{{ $name }}" id="{{ $name }}" class="form-control" rows="3"
                                                    @disabled($disabled)>{{ $value }}</textarea>
                                            @else
                                                <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}"
                                                    class="form-control {{ $fieldClass }}" value="{{ $value }}" step="any" @disabled($disabled)>
                                            @endif

                                            @error($name)
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                                @if($canCompleteDor)
                                    @php
                                        $completedValue = old('is_completed', $dor?->is_completed ? '1' : '0');
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ count($fields) + 1 }}</td>
                                        <td class="text-muted">Mark As Complete</td>
                                        <td>
                                            <select name="is_completed" id="is_completed"
                                                class="form-select @error('is_completed') is-invalid @enderror">
                                                <option value="0" @selected((string) $completedValue === '0')>No</option>
                                                <option value="1" @selected((string) $completedValue === '1')>Yes</option>
                                            </select>
                                            @error('is_completed')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-12 mt-3">
                <div class="modal-btns-last" >
                    <a href="{{ route('trips.sheet', $record->id) }}" class="modal-btn-1">Back</a>
                    @if($dor)
                        <a href="{{ route('trips.sheet.entries.dor.preview', [$record->id, $entry->id]) }}"
                            class="modal-btn-2 m-0">Preview</a>
                    @endif
                    @unless($dorReadOnly)
                        <button type="submit" class="modal-btn-2 m-0 js-loading-submit"
                            data-loading-text="<i class='fa-solid fa-spinner fa-spin me-1'></i> Saving">
                            {{ $dor ? 'Update DOR' : 'Create DOR' }}
                        </button>
                    @endunless
                </div>
            </div>
        </form>
    </section>

    @section('scripts')
        <script>
            $(function () {
                function toNumber(selector) {
                    var value = parseFloat($(selector).val());

                    return Number.isNaN(value) ? null : value;
                }

                function setNumber(selector, value, decimals) {
                    if (value === null || Number.isNaN(value)) {
                        $(selector).val('');
                        $('input[type="hidden"][name="' + selector.replace('#', '') + '"]').val('');
                        return;
                    }

                    var formatted = Number(value).toFixed(decimals);
                    $(selector).val(formatted);
                    $('input[type="hidden"][name="' + selector.replace('#', '') + '"]').val(formatted);
                }

                function refreshCalculatedFields() {
                    var scheduleKm = toNumber('#schedule_km');
                    var routeKmLoss = toNumber('#route_km_loss');
                    var actualRouteKm = null;

                    if (scheduleKm !== null && routeKmLoss !== null) {
                        actualRouteKm = Math.max(0, scheduleKm - routeKmLoss);
                        setNumber('#actual_route_km', actualRouteKm, 2);
                    } else {
                        actualRouteKm = toNumber('#actual_route_km');
                    }

                    var scheduleTrip = toNumber('#schedule_trip');
                    var actualTrip = toNumber('#actual_trip');

                    if (scheduleTrip !== null && actualTrip !== null) {
                        setNumber('#miss_trip', Math.max(0, scheduleTrip - actualTrip), 0);
                    }

                    var start = parseFloat($('#odometer_start_reading').val());
                    var end = parseFloat($('#odometer_end_reading').val());
                    var odometerDiff = null;

                    if (!Number.isNaN(start) && !Number.isNaN(end) && end >= start) {
                        odometerDiff = end - start;
                        setNumber('#odometer_diff_km', odometerDiff, 2);
                    } else {
                        odometerDiff = toNumber('#odometer_diff_km');
                    }

                    if (actualRouteKm !== null && odometerDiff !== null) {
                        setNumber('#difference', actualRouteKm - odometerDiff, 2);
                    }

                    var startSoc = toNumber('#route_start_soc_percent');
                    var endSoc = toNumber('#route_end_soc_percent');
                    var socConsumption = null;

                    if (startSoc !== null && endSoc !== null) {
                        socConsumption = Math.max(0, startSoc - endSoc);
                        setNumber('#soc_consumption_on_route_percent', socConsumption, 2);
                    } else {
                        socConsumption = toNumber('#soc_consumption_on_route_percent');
                    }

                    if (socConsumption !== null && actualRouteKm !== null && actualRouteKm > 0) {
                        setNumber('#soc_per_km', socConsumption / actualRouteKm, 4);
                    }

                    if (socConsumption !== null && socConsumption > 0 && actualRouteKm !== null) {
                        setNumber('#run_kilometer_per_soc', actualRouteKm / socConsumption, 4);
                    }

                    var dcrChargedSoc = toNumber('#dcr_charged_soc');
                    var dcrKwh = toNumber('#dcr_kwh');
                    var batterySizeKwh = toNumber('#battery_size_kwh');

                    if (dcrChargedSoc !== null && socConsumption !== null && odometerDiff !== null && odometerDiff > 0) {
                        setNumber('#dor_kwh_per_km_odo', (dcrChargedSoc * socConsumption) / odometerDiff / 100, 4);
                    } else {
                        setNumber('#dor_kwh_per_km_odo', null, 4);
                    }

                    if (dcrKwh !== null && actualRouteKm !== null && actualRouteKm > 0) {
                        setNumber('#dor_kwh_per_km_act', dcrKwh / actualRouteKm, 4);
                    } else {
                        setNumber('#dor_kwh_per_km_act', null, 4);
                    }

                    if (socConsumption !== null && batterySizeKwh !== null) {
                        setNumber('#dor_kwh', (socConsumption * batterySizeKwh) / 100, 2);
                    } else {
                        setNumber('#dor_kwh', null, 2);
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
                            refreshCalculatedFields();
                        }
                    }
                });

                function filterKilometerReasons() {
                    var accountId = $('#dor_account_responsible_id').val();
                    var reason = $('#dor_kilometer_loss_reason_id');

                    reason.find('option').each(function () {
                        var option = $(this);

                        if (!option.val()) {
                            option.show();
                            return;
                        }

                        option.toggle(!accountId || option.data('account').toString() === accountId);
                    });

                    if (reason.find('option:selected').is(':hidden')) {
                        reason.val('');
                    }
                }

                $('#schedule_km, #route_km_loss, #schedule_trip, #actual_trip, #odometer_start_reading, #odometer_end_reading, #route_start_soc_percent, #route_end_soc_percent, #dcr_charged_soc, #dcr_kwh, #battery_size_kwh').on('input', refreshCalculatedFields);
                $('#dor_account_responsible_id').on('change', filterKilometerReasons);
                filterKilometerReasons();
                refreshCalculatedFields();

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
