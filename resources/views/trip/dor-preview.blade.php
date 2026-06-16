@section('title')
    DOR Preview
@endsection
<x-app-layout>
    <div class="page-title">
        <h3>Trip Management</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trip Management</a></li>
                <li class="breadcrumb-item"><a href="{{ route('trips.sheet.view', $record->id) }}">View Trip Sheet</a></li>
                <li class="breadcrumb-item active">DOR Preview</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="dor-wrap">
            <div class="btn-flex">
                <a href="{{ route('trips.sheet.entries.dor', [$record->id, $entry->id]) }}" class="add-btn mb-3" style="background-color: #6c757d; border-color: #6c757d;">Back</a>
                <a href="#!" class="add-btn mb-3" style="background-color: #b23939; border-color: #b23939;" onclick="window.print(); return false;">Print</a>
            </div>

            @if($dor->is_completed)
                <div class="alert alert-success">DOR marked as complete.</div>
            @endif

            @if(! empty($odometerImages['odometer_start_image']) || ! empty($odometerImages['odometer_end_image']))
                <div class="dor-section">
                    <div class="dor-section-title">Odometer Images</div>
                    <div class="dor-grid">
                        @foreach([
                            'odometer_start_image' => 'Odometer Start Image',
                            'odometer_end_image' => 'Odometer End Image',
                        ] as $imageName => $imageLabel)
                            @if(! empty($odometerImages[$imageName]))
                                <div class="dor-card">
                                    <div class="dor-label">{{ $imageLabel }}</div>
                                    <a href="{{ $odometerImages[$imageName] }}" target="_blank">
                                        <img src="{{ $odometerImages[$imageName] }}" alt="{{ $imageLabel }}" style="max-width: 100%; max-height: 180px; border-radius: 6px; border: 1px solid #d9dee3; object-fit: cover;">
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach($groups as $title => $items)
                <div class="dor-section">
                    <div class="dor-section-title">{{ $title }}</div>
                    <div class="dor-grid">
                        @foreach($items as $label => $value)
                            <div class="dor-card {{ $label === 'Remarks' ? 'dor-wide' : '' }}">
                                <div class="dor-label">{{ $label }}</div>
                                <div class="dor-value">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
