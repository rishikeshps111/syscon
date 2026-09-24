@section('title', 'Salary Archive Details')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Salary Archive Details</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">HRMS</li>
                    <li class="breadcrumb-item">Payroll</li>
                    <li class="breadcrumb-item"><a href="{{ route('salary-archives.index') }}">Salary Archive</a></li>
                    <li class="breadcrumb-item active">View</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container">
            <div class="bet-gap-cs mb-4">
                <div>
                    <h5 class="title-w-sec mb-1">Approved Salary Processing</h5>
                    <span class="status-green mt-2">Approved</span>
                </div>
                <div class="btns-group-container"><a href="{{ route('salary-archives.index') }}" class="bk-btn">Back</a></div>
                
            </div>

            @include('salary-archive.partials.details')
        </div>
    </section>
</x-app-layout>
