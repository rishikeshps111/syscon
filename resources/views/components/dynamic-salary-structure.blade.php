@php
    $componentValues = $componentValues ?? collect();
@endphp

<div class="row">
    @forelse ($salaryComponents as $salaryComponent)
        @php
            $fieldName = "salary_components[{$salaryComponent->id}]";
            $oldKey = "salary_components.{$salaryComponent->id}";
            $value = old($oldKey, $componentValues[$salaryComponent->id] ?? $salaryComponent->template_default_amount ?? 0);
            $isRequired = true;
            $templateDefaults = $salaryComponent->template_defaults ?? [];
            $designationIds = collect(array_keys($templateDefaults))->reject(fn ($id) => (int) $id === 0)->implode(',');
            $hasExplicitValue = session()->hasOldInput($oldKey) || $componentValues->has($salaryComponent->id);
        @endphp
        <div class="col-lg-4 o-f-inp mb-3 js-salary-component-item"
            data-designation-ids="{{ $designationIds }}">
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
                data-required="{{ $isRequired ? '1' : '0' }}"
                data-template-defaults='@json($templateDefaults)'
                data-has-explicit-value="{{ $hasExplicitValue ? '1' : '0' }}"
                value="{{ $value }}"
                @if ($isRequired) required @endif
                >
            @error($oldKey)
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    @empty
        <div class="col-lg-12">
            <div class="alert alert-warning mb-0">
                No salary template is assigned for this role{{ request('role') === 'Staff' ? ' and designation' : '' }}. Salary values will remain zero until a salary template is assigned.
            </div>
        </div>
    @endforelse

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="gross_salary_preview">Gross Salary</label>
        <input type="number" step="0.01" min="0" id="gross_salary_preview"
            class="form-control shadow-none" readonly>
    </div>
</div>
