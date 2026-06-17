@php
    $componentValues = $componentValues ?? collect();
@endphp

<div class="row">
    @forelse ($salaryComponents as $salaryComponent)
        @php
            $fieldName = "salary_components[{$salaryComponent->id}]";
            $oldKey = "salary_components.{$salaryComponent->id}";
            $value = old($oldKey, $componentValues[$salaryComponent->id] ?? $salaryComponent->default_value);
            $isRequired = $salaryComponent->is_mandatory || $salaryComponent->type === 'earning';
        @endphp
        <div class="col-lg-4 o-f-inp mb-3">
            <label for="salary_component_{{ $salaryComponent->id }}">
                {{ $salaryComponent->component_name }}
                <small class="text-muted">({{ ucfirst($salaryComponent->type) }})</small>
                @if ($isRequired)
                    <span class="text-danger">*</span>
                @endif
            </label>
            <input type="number" step="0.01" min="0" id="salary_component_{{ $salaryComponent->id }}"
                name="{{ $fieldName }}" class="form-control shadow-none js-dynamic-salary-field"
                data-type="{{ $salaryComponent->type }}"
                value="{{ $value }}"
                @if ($isRequired) required @endif
                @if (! $salaryComponent->is_editable_in_payroll) readonly @endif>
            @error($oldKey)
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    @empty
        <div class="col-lg-12">
            <div class="alert alert-warning mb-0">
                No salary components are assigned for this role yet.
            </div>
        </div>
    @endforelse

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="gross_salary_preview">Gross Salary</label>
        <input type="number" step="0.01" min="0" id="gross_salary_preview"
            class="form-control shadow-none" readonly>
    </div>
</div>
