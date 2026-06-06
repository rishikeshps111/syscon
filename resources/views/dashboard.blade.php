@section('title')
    Dashboard
@endsection
<x-app-layout>
    <div class="page-title">
        <h3>Dashboard</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>
    <section class="section dashboard">
        <div class="row">
            @forelse($cards as $card)
                <div class="col-lg-4 mb-3">
                    <a href="{{ route($card['route']) }}" class="card-dashboard-widget {{ $card['class'] }} d-flex text-decoration-none">
                        <div class="card-dashboard-widget-icon">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                        <div class="card-dashboard-widget-info">
                            <h3>{{ number_format($card['value']) }}</h3>
                            <p>{{ $card['label'] }}</p>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-lg-12">
                    <div class="main-table-container text-center">
                        <p class="mb-0">No dashboard widgets are available for your permissions.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
