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
    <section class="section dashboard ">
        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-green">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>120</h3>
                        <p>Total OEM</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-purple">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-warehouse"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>45</h3>
                        <p>Total Depots</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-pink">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-bus"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>210</h3>
                        <p>Active Vehicles</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-purple">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>123</h3>
                        <p>Active Drivers</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-orange">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>75</h3>
                        <p>Total Controllers</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-teal">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>70</h3>
                        <p>Total Supervisor</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-orange">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-route"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>95</h3>
                        <p>Trips Today</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-green">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>80</h3>
                        <p>Completed Trips</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-red">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>10</h3>
                        <p>Delayed Trips</p>
                    </div>
                </div>

            </div>
            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-dark">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>5</h3>
                        <p>Cancelled Trips</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-red">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>12</h3>
                        <p>Complaints Open</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card-dashboard-widget card-orange">
                    <div class="card-dashboard-widget-icon">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div class="card-dashboard-widget-info">
                        <h3>7</h3>
                        <p>License Expiry Alerts</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>