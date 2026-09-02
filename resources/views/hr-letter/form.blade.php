@section('title', 'Generate HR Letter')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Generate Letter - {{ $user->name }}</h3>
        </div>
        <div class="main-table-container">
            <form id="generateLetterForm" method="POST" action="{{ route('hr-letters.store', $user) }}">@csrf
                <div class="row">
                    <div class="col-md-12 mb-3"><label>Template <span class="text-danger">*</span></label><select
                            id="template_id" class="form-select select2" name="template_id" required>
                            <option value="">--- Select ---</option>@foreach($templates as $template)<option
                                value="{{ $template->id }}" data-entity="{{ $template->entity_type }}">
                                {{ \App\Models\HrLetterTemplate::ENTITY_TYPES[$template->entity_type] }} -
                                {{ $template->language }} - {{ $template->template_name }}
                            </option>@endforeach
                        </select></div>
                    <div id="warning_fields" class="col-12 d-none">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label>Warning Reason</label><textarea class="form-control"
                                    name="warning_reason" rows="3">{{ old('warning_reason') }}</textarea></div>
                            <div class="col-md-3 mb-3"><label>Incident Date</label><input type="date"
                                    class="form-control" name="incident_date" value="{{ old('incident_date') }}"></div>
                            <div class="col-md-3 mb-3"><label>Response Due Date</label><input type="date"
                                    class="form-control" name="response_due_date"
                                    value="{{ old('response_due_date') }}"></div>
                        </div>
                    </div>
                    <div class="col-12 ">
                        <div class="modal-btns-last">
                            <a class="modal-btn-1" href="{{ url()->previous() }}">Cancel</a> <button type="submit" class="modal-btn-2 js-loading-submit"
                                data-loading-text="Generating...">Generate and
                                Preview</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
    @section('scripts')
        <script>
            $(function () {
                $('#template_id').select2({
                    width: '100%',
                    placeholder: '--- Select Template ---',
                    allowClear: true
                });

                function toggleWarning() {
                    var warning = $('#template_id option:selected').data('entity') === 'warning_letter';
                    $('#warning_fields').toggleClass('d-none', !warning);
                    $('[name=warning_reason]').prop('required', warning);
                }

                $('#template_id').on('change', toggleWarning);
                toggleWarning();

                $('#generateLetterForm').on('submit', function () {
                    var button = $(this).find('.js-loading-submit');
                    button.prop('disabled', true).html(button.data('loading-text'));
                });
            });
        </script>
    @endsection
</x-app-layout>