@extends('layouts.app')

@section('title', __('businessintelligence::lang.business_intelligence'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        @yield('page_title', __('businessintelligence::lang.business_intelligence'))
        <small>@yield('page_subtitle', '')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @yield('bi_content')
</section>
<!-- /.content -->

@endsection

@push('css')
<style>
    .bi-kpi-card {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    
    .bi-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .bi-kpi-icon {
        font-size: 2.5rem;
        opacity: 0.8;
    }
    
    .bi-kpi-value {
        font-size: 2rem;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .bi-kpi-label {
        color: #666;
        font-size: 0.9rem;
        text-transform: uppercase;
    }
    
    .bi-trend-up {
        color: #28a745;
    }
    
    .bi-trend-down {
        color: #dc3545;
    }
    
    .bi-insight-card {
        border-left: 4px solid;
        margin-bottom: 15px;
        padding: 15px;
        background: #fff;
        border-radius: 4px;
    }
    
    .bi-insight-critical {
        border-left-color: #dc3545;
    }
    
    .bi-insight-high {
        border-left-color: #fd7e14;
    }
    
    .bi-insight-medium {
        border-left-color: #007bff;
    }
    
    .bi-insight-low {
        border-left-color: #6c757d;
    }
    
    .bi-chart-container {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>
@endpush

@push('js')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@stack('bi_scripts')
@endpush

