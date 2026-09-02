@section('title')
    {{ isset($record) ? 'Edit Complaint' : 'Add Complaint' }}
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ isset($record) ? 'Edit Complaint' : 'Add Complaint' }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('complaints.index') }}">Complaints</a></li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit' : 'Add' }}</li>
                </ol>
            </nav>
        </div>

        <form id="complaintForm" method="POST" enctype="multipart/form-data"
            action="{{ isset($record) ? route('complaints.update', $record->id) : route('complaints.store') }}">
            @csrf
            @if(isset($record))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-xl-12">
                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Basic Information</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-3 o-f-inp mb-3">
                                <label for="code">Complaint ID <span class="text-danger">*</span></label>
                                <input type="text" id="code" class="form-control shadow-none"
                                    value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
                            </div>
                            <div class="col-lg-3 o-f-inp mb-3">
                                <label for="complaint_date">Complaint Date <span class="text-danger">*</span></label>
                                <input type="date" id="complaint_date" name="complaint_date"
                                    class="form-control shadow-none @error('complaint_date') is-invalid @enderror"
                                    value="{{ old('complaint_date', isset($record) ? $record->complaint_date?->format('Y-m-d') : now()->format('Y-m-d')) }}">
                                @error('complaint_date')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-3 o-f-inp mb-3">
                                <label for="reported_by_role">Reported By Role <span
                                        class="text-danger">*</span></label>
                                <select id="reported_by_role" name="reported_by_role"
                                    class="form-select shadow-none @error('reported_by_role') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($reportedByRoles as $value => $label)
                                        <option value="{{ $value }}" {{ old('reported_by_role', $record->reported_by_role ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('reported_by_role')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-3 o-f-inp mb-3">
                                <label for="reported_by_user_id">Reported By (User ID / Name)<span
                                        class="text-danger">*</span></label>
                                <select id="reported_by_user_id" name="reported_by_user_id"
                                    class="form-select shadow-none select2 @error('reported_by_user_id') is-invalid @enderror"
                                    data-selected="{{ old('reported_by_user_id', $record->reported_by_user_id ?? '') }}">
                                    <option value="">---Select---</option>
                                </select>
                                @error('reported_by_user_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Against Whom</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="against_role">Against Role<span class="text-danger">*</span></label>
                                <select id="against_role" name="against_role"
                                    class="form-select shadow-none @error('against_role') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($againstRoles as $value => $label)
                                        <option value="{{ $value }}" {{ old('against_role', $record->against_role ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('against_role')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="against_user_id">Employee Name / ID<span
                                        class="text-danger">*</span></label>
                                <select id="against_user_id" name="against_user_id"
                                    class="form-select shadow-none select2 @error('against_user_id') is-invalid @enderror"
                                    data-selected="{{ old('against_user_id', $record->against_user_id ?? '') }}">
                                    <option value="">---Select---</option>
                                </select>
                                @error('against_user_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Complaint Details</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="complaint_category_id">Complaint Category<span
                                        class="text-danger">*</span></label>
                                <select id="complaint_category_id" name="complaint_category_id"
                                    class="form-select shadow-none select2 @error('complaint_category_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" {{ (int) old('complaint_category_id', $record->complaint_category_id ?? 0) === $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('complaint_category_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="severity">Severity Level<span class="text-danger">*</span></label>
                                <select id="severity" name="severity"
                                    class="form-select shadow-none @error('severity') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach ($severities as $value => $label)
                                        <option value="{{ $value }}" {{ old('severity', $record->severity ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('severity')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-12 o-f-inp mb-3">
                                <label for="description">Description <span class="text-danger">*</span></label>
                                <textarea id="description" name="description"
                                    class="form-control shadow-none @error('description') is-invalid @enderror"
                                    rows="4">{{ old('description', $record->description ?? '') }}</textarea>
                                @error('description')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>

                            <div class="col-lg-6 o-f-inp file-input mb-3">
                                <label>Attachment</label>
                                <div id="attachmentRows">
                                    <div class="complaint-attachment-row d-flex gap-2 mb-2">
                                        <input type="file" name="attachments[]"
                                            class="form-control shadow-none complaint-attachment-input @error('attachments.*') is-invalid @enderror"
                                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                        <button type="button" class="btn btn-primary add-attachment-row"
                                            title="Add attachment">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                @if(isset($record) && count($record->attachment_urls))
                                    <div class="mt-1">
                                        @foreach ($record->attachment_urls as $index => $url)
                                            <a href="{{ $url }}" target="_blank" class="d-inline-block me-2">Attachment
                                                {{ $index + 1 }}</a>
                                        @endforeach
                                    </div>
                                @endif
                                <div id="attachmentPreview" class="complaint-attachment-preview mt-2"></div>
                                @error('attachments')<span class="text-danger">{{ $message }}</span>@enderror
                                @error('attachments.*')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="main-table-container mb-3">
                        <h5 class="title-w-sec">Additional</h5>
                        <hr>
                        <div class="row">
                            <div class="col-lg-12 o-f-inp mb-3">
                                <label for="remarks">Remarks</label>
                                <textarea id="remarks" name="remarks"
                                    class="form-control shadow-none @error('remarks') is-invalid @enderror"
                                    rows="4">{{ old('remarks', $record->remarks ?? '') }}</textarea>
                                @error('remarks')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12 ">
                        <div class="modal-btns-last">
                            <a href="{{ route('complaints.index', ['reported_by_role' => old('reported_by_role', $record->reported_by_role ?? 'supervisor')]) }}"
                                class="modal-btn-1">Cancel</a>
                            <button type="submit" class="modal-btn-2 js-loading-submit"
                                data-loading-text="Loading...">{{ isset($record) ? 'Update' : 'Submit' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>

    @section('scripts')
        <script>
            $(function () {
                $('.select2').select2({
                    placeholder: '---Select---',
                    allowClear: true,
                    width: '100%'
                });

                function resetUserSelect(selectId, placeholder) {
                    $(selectId)
                        .html('<option value="">' + placeholder + '</option>')
                        .val('')
                        .trigger('change.select2');
                }

                function loadUsersByRole(role, selectId, selectedId) {
                    var select = $(selectId);
                    resetUserSelect(selectId, role ? 'Loading...' : '---Select---');

                    if (!role) {
                        return;
                    }

                    $.ajax({
                        url: "{{ route('complaints.users-by-role') }}",
                        type: 'GET',
                        data: { role: role },
                        success: function (users) {
                            select.empty().append(new Option('---Select---', ''));

                            users.forEach(function (user) {
                                select.append(new Option(user.text, user.id));
                            });

                            select.val(selectedId || '').trigger('change.select2');
                        },
                        error: function () {
                            resetUserSelect(selectId, '---Select---');
                            showToast('error', 'Unable to load users.');
                        }
                    });
                }

                function syncReportedBy() {
                    loadUsersByRole(
                        $('#reported_by_role').val(),
                        '#reported_by_user_id',
                        $('#reported_by_user_id').data('selected')
                    );
                    $('#reported_by_user_id').data('selected', '');
                    syncAgainstRoleOptions();
                }

                function syncAgainstRoleOptions() {
                    var reportedByRole = $('#reported_by_role').val();
                    var againstRole = $('#against_role');

                    againstRole.find('option[value="controller"]').prop('hidden', reportedByRole === 'controller');

                    if (reportedByRole === 'controller' && againstRole.val() === 'controller') {
                        againstRole.val('driver');
                    }

                    loadUsersByRole(
                        againstRole.val(),
                        '#against_user_id',
                        $('#against_user_id').data('selected')
                    );
                    $('#against_user_id').data('selected', '');
                }

                $('#reported_by_role').on('change', syncReportedBy);
                $('#against_role').on('change', syncAgainstRoleOptions);
                $(document).on('click', '.add-attachment-row', function () {
                    $('#attachmentRows').append(`
                                        <div class="complaint-attachment-row d-flex gap-2 mb-2">
                                            <input type="file" name="attachments[]" class="form-control shadow-none complaint-attachment-input" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                            <button type="button" class="btn btn-danger remove-attachment-row" title="Remove attachment">
                                                <i class="fa-solid fa-minus"></i>
                                            </button>
                                        </div>
                                    `);
                });

                $(document).on('click', '.remove-attachment-row', function () {
                    $(this).closest('.complaint-attachment-row').remove();
                    renderAttachmentPreview();
                });

                $(document).on('change', '.complaint-attachment-input', renderAttachmentPreview);

                $('#complaintForm').on('submit', function () {
                    var submitButton = $(this).find('.js-loading-submit');
                    submitButton.prop('disabled', true);
                    submitButton.html(submitButton.data('loading-text') || 'Loading...');
                });

                syncReportedBy();

                function renderAttachmentPreview() {
                    var preview = $('#attachmentPreview');
                    preview.empty();

                    $('.complaint-attachment-input').each(function () {
                        var file = this.files && this.files[0] ? this.files[0] : null;

                        if (!file) {
                            return;
                        }

                        var item = $('<div class="complaint-preview-item"></div>');
                        item.append($('<span></span>').text(file.name));

                        if (file.type.startsWith('image/')) {
                            var img = $('<img alt="Attachment preview">');
                            img.attr('src', URL.createObjectURL(file));
                            item.prepend(img);
                        } else {
                            item.prepend('<i class="fa-solid fa-file-lines"></i>');
                        }

                        preview.append(item);
                    });
                }
            });
        </script>
        <style>
            .complaint-attachment-row .btn {
                min-width: 42px;
            }

            .complaint-attachment-preview {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .complaint-preview-item {
                align-items: center;
                background: #f8f9fb;
                border: 1px solid #e2e6ea;
                border-radius: 6px;
                display: flex;
                gap: 8px;
                max-width: 240px;
                padding: 8px;
            }

            .complaint-preview-item img {
                border-radius: 4px;
                height: 42px;
                object-fit: cover;
                width: 42px;
            }

            .complaint-preview-item span {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        </style>
    @endsection
</x-app-layout>
