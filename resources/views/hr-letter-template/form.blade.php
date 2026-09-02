@section('title', $record ? 'Edit Letter Template' : 'Create Letter Template')
<style>
    .btn-light{
            background: #fcf5e0 !important;
    color: #ad8406 !important;
    border:1px solid #fcf5e0 !important;
    }
    .btn-light:hover{
            background: #ad8406 !important;
    color: #fff !important;
    border:1px solid #ad8406 !important;
    }
</style>
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ $record ? 'Edit' : 'Create' }} Letter Template</h3>
        </div>
        <div class="main-table-container">
            <form id="letterTemplateForm" method="POST" enctype="multipart/form-data"
                action="{{ $record ? route('hr-letter-templates.update', $record) : route('hr-letter-templates.store') }}">
                @csrf @if ($record)
                    @method('PUT')
                @endif
                <div class="row">
                    <div class="col-lg-4 mb-3"><label>Entity <span class="text-danger">*</span></label><select
                            class="form-select select2 shadow-none" name="entity_type" required>
                            <option value="">--- Select ---</option>
                            @foreach ($entityTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('entity_type', $record?->entity_type) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('entity_type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 mb-3"><label>Language <span class="text-danger">*</span></label><select
                            class="form-select select2 shadow-none" name="language" required>
                            <option value="">--- Select ---</option>
                            @foreach ($languages as $language)
                                <option value="{{ $language }}" @selected(old('language', $record?->language) === $language)>
                                    {{ $language }}
                                </option>
                            @endforeach
                        </select>
                        @error('language')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 mb-3 o-f-inp"><label>Template Name <span class="text-danger">*</span></label><input
                            class="form-control shadow-none" name="template_name"
                            value="{{ old('template_name', $record?->template_name) }}" required></div>
                    <div class="col-12 mb-3 o-f-inp"><label>Subject Line <span class="text-danger">*</span></label><input
                            class="form-control shadow-none" name="subject" value="{{ old('subject', $record?->subject) }}"
                            required></div>
                    <div class="col-12 mb-2 o-f-inp"><label>Available Variables</label>
                        <div>
                            @foreach ($placeholders as $placeholder)
                                <button type="button" class="btn btn-sm btn-light border mb-1 js-placeholder"
                                    data-value="{{ $placeholder }}">{{ $placeholder }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12 mb-3 o-f-inp"><label>Letter Content <span class="text-danger">*</span></label>
                        <textarea id="letter_content" class="form-control shadow-none"
                            name="content">{{ old('content', $record?->content) }}</textarea>
                        @error('content')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3 o-f-inp"><label>Header Address</label>
                        <textarea class="form-control shadow-none" name="header_address"
                            rows="3">{{ old('header_address', $record?->header_address) }}</textarea>
                    </div>
                    <div class="col-md-6 mb-3 o-f-inp"><label>Footer Data</label>
                        <textarea class="form-control shadow-none" name="footer_content"
                            rows="3">{{ old('footer_content', $record?->footer_content) }}</textarea>
                    </div>
                    <div class="col-md-4 mb-3 o-f-inp file-input"><label for="header_logo">Header Logo</label><input type="file"
                            id="header_logo" class="form-control shadow-none" name="header_logo" accept="image/*">
                        <div class="mt-2"><img id="header_logo_preview"
                                style="max-height:90px;max-width:100%;{{ $record?->header_logo ? '' : 'display:none;' }}"
                                src="{{ $record?->header_logo ? asset('storage/' . $record->header_logo) : '' }}"
                                alt="Header logo preview"></div>
                        @error('header_logo')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3 o-f-inp"><label>Status</label><select class="form-select select2 shadow-none"
                            name="is_active">
                            <option value="1" @selected((string) old('is_active', $record?->is_active ?? 1) === '1')>
                                Active
                            </option>
                            <option value="0" @selected((string) old('is_active', $record?->is_active ?? 1) === '0')>
                                Inactive</option>
                        </select></div>
                    <div class="col-12  mt-3">
                        <div class="modal-btns-last">
                            <a class="reset-btn me-2 modal-btn-1" href="{{ route('hr-letter-templates.index') }}">Cancel</a><button
                                type="submit" class="modal-btn-2 js-loading-submit"
                                data-loading-text="Loading...">{{ $record ? 'Update' : 'Submit' }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
    @section('scripts')
        <script>
            $(function () {
                $('.select2').select2({
                    width: '100%'
                });

                $('#header_logo').on('change', function () {
                    var file = this.files && this.files[0];
                    var preview = document.getElementById('header_logo_preview');
                    if (!file) {
                        preview.style.display = 'none';
                        preview.removeAttribute('src');
                        return;
                    }
                    var reader = new FileReader();
                    reader.onload = function (event) {
                        preview.src = event.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                });

                $('#letterTemplateForm').on('submit', function () {
                    tinymce.triggerSave();
                    var button = $(this).find('.js-loading-submit');
                    button.prop('disabled', true).html(button.data('loading-text'));
                });
            });
            tinymce.init({
                selector: '#letter_content',
                height: 420,
                menubar: false,
                plugins: 'lists link table code',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | table link | code'
            });
            document.querySelectorAll('.js-placeholder').forEach(function (button) {
                button.addEventListener('click', function () {
                    tinymce.get('letter_content').insertContent(this.dataset.value);
                });
            });
        </script>
    @endsection
</x-app-layout>