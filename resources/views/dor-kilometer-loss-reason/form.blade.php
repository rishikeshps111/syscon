<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('dor-kilometer-loss-reasons.update', $record->id) : route('dor-kilometer-loss-reasons.store') }}">
    @csrf
    @if(isset($record))
        @method('PUT')
    @endif

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="code" class="form-label m-0">Code</label>
        <input type="text" class="form-control shadow-none" id="code"
            value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="dor_account_responsible_id" class="form-label m-0">Account Responsible <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" id="dor_account_responsible_id" name="dor_account_responsible_id" style="height: 45px;">
            <option value="">Select</option>
            @foreach($accounts as $account)
                <option value="{{ $account->id }}" @selected(old('dor_account_responsible_id', $record->dor_account_responsible_id ?? '') == $account->id)>{{ $account->name }}</option>
            @endforeach
        </select>
        <span class="text-danger error-text dor_account_responsible_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="name" class="form-label m-0">Reason <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" id="name" name="name"
            value="{{ old('name', $record->name ?? '') }}">
        <span class="text-danger error-text name_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="is_active" class="form-label m-0">Status <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" id="is_active" name="is_active" style="height: 45px;">
            <option value="1" @selected(old('is_active', $record->is_active ?? 1) == 1)>Active</option>
            <option value="0" @selected(old('is_active', $record->is_active ?? 1) == 0)>Inactive</option>
        </select>
        <span class="text-danger error-text is_active_error"></span>
    </div>

    <div class="col-lg-12 mt-3 text-center">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">{{ isset($record) ? 'Update' : 'Add' }}</button>
    </div>
</form>
