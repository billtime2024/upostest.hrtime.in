@extends('businessintelligence::layouts.app')

@section('page_title', __('businessintelligence::lang.sales_analytics'))
@section('page_subtitle', 'Visual Sales Performance Dashboard')

@push('css')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container--default .select2-selection--multiple {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    min-height: 38px;
}
.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #0ea5e9;
}

/* Mobile responsive styles */
@media (max-width: 768px) {
    .analytics-header .row > div {
        margin-bottom: 15px;
    }

    .analytics-header .col-md-6 {
        text-align: left !important;
    }

    .filter-select {
        width: 100% !important;
        margin-bottom: 10px;
    }

    .metric-card {
        margin-bottom: 20px;
    }

    .chart-card {
        margin-bottom: 20px;
    }
}
</style>
@endpush

@push('js')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush

@section('bi_content')

<style>
/* Modern Analytics Dashboard Styles */
.analytics-header {
    background: linear-gradient(135deg, #5f7592ff 0%, #0ea5e9 100%);
    padding: 30px;
    border-radius: 15px;
    color: white;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.analytics-header h2 {
    margin: 0 0 10px 0;
    font-size: 28px;
    font-weight: 600;
}

.analytics-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.metric-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
}

.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.metric-card.revenue::before { background: linear-gradient(135deg, #166534 0%, #15803d 100%); }
.metric-card.transactions::before { background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); }
.metric-card.average::before { background: linear-gradient(135deg, #9a3412 0%, #c2410c 100%); }
.metric-card.growth::before { background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 100%); }

.metric-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 15px;
}

.metric-card.revenue .metric-icon {
    background: linear-gradient(135deg, #166534 0%, #15803d 100%);
    color: white;
}

.metric-card.transactions .metric-icon {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    color: white;
}

.metric-card.average .metric-icon {
    background: linear-gradient(135deg, #9a3412 0%, #c2410c 100%);
    color: white;
}

.metric-card.growth .metric-icon {
    background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 100%);
    color: white;
}

.metric-value {
    font-size: 32px;
    font-weight: 700;
    color: #2d3748;
    margin: 10px 0;
}

.metric-label {
    font-size: 14px;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.metric-change {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    margin-top: 10px;
    font-weight: 600;
}

.metric-change.positive {
    background: #d4f8e8;
    color: #27ae60;
}

.metric-change.negative {
    background: #ffe5e5;
    color: #e74c3c;
}

.chart-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    border-bottom: 2px solid #f7fafc;
    padding-bottom: 15px;
}

.chart-title {
    font-size: 20px;
    font-weight: 600;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 10px;
}

.chart-title i {
    color: #0ea5e9;
}

.filter-select {
    padding: 8px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    color: #4a5568;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-select:focus {
    outline: none;
    border-color: #0ea5e9;
}

.product-list {
    max-height: 500px;
    overflow-y: auto;
}

.product-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.product-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.product-rank {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    margin-right: 15px;
}

.product-rank.rank-1 { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #333; }
.product-rank.rank-2 { background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%); color: #333; }
.product-rank.rank-3 { background: linear-gradient(135deg, #cd7f32 0%, #e39a4f 100%); color: white; }
.product-rank.rank-other { background: linear-gradient(135deg, #0ea5e9 0%, #5f7592ff 100%); color: white; }

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 15px;
    margin-bottom: 5px;
}

.product-stats {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: #718096;
}

.product-revenue {
    font-size: 18px;
    font-weight: 700;
    color: #667eea;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 64px;
    color: #cbd5e0;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #4a5568;
    font-size: 20px;
    margin-bottom: 10px;
}

.empty-state p {
    color: #718096;
    font-size: 15px;
}

/* Custom Scrollbar */
.product-list::-webkit-scrollbar {
    width: 8px;
}

.product-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.product-list::-webkit-scrollbar-thumb {
    background: #0ea5e9;
    border-radius: 10px;
}

.product-list::-webkit-scrollbar-thumb:hover {
    background: #5f7592ff;
}
</style>

<!-- Header -->
<div class="analytics-header">
    <div class="row">
        <div class="col-md-6">
            <h2><i class="fas fa-chart-line"></i> Sales Analytics Dashboard</h2>
            <p>Comprehensive visual analysis of your sales performance</p>
        </div>
        <div class="col-md-6 text-right">
            <div class="row">
                <div class="col-md-6">
                    <label for="location_filter" style="display: block; color: white; font-size: 12px; margin-bottom: 5px;">Business Location</label>
                    @if($businessLocations->count() > 0)
                        <select class="filter-select" id="location_filter" multiple="multiple" style="width: 100%;">
                            @foreach($businessLocations as $id => $name)
                                <option value="{{ $id }}" {{ in_array($id, $locationIds) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    @else
                        <div class="alert alert-warning" style="margin: 0; padding: 8px; font-size: 12px;">
                            <i class="fas fa-exclamation-triangle"></i> No business locations available
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label for="date_range_filter" style="display: block; color: white; font-size: 12px; margin-bottom: 5px;">Date Range</label>
                    <select class="filter-select" id="date_range_filter">
                        <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ $dateRange == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="last_7_days" {{ $dateRange == 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="last_30_days" {{ $dateRange == 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="this_month" {{ $dateRange == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ $dateRange == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_month_last_year" {{ $dateRange == 'this_month_last_year' ? 'selected' : '' }}>This Month Last Year</option>
                        <option value="this_year" {{ $dateRange == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="last_year" {{ $dateRange == 'last_year' ? 'selected' : '' }}>Last Year</option>
                        <option value="current_financial_year" {{ $dateRange == 'current_financial_year' ? 'selected' : '' }}>Current Financial Year</option>
                        <option value="last_financial_year" {{ $dateRange == 'last_financial_year' ? 'selected' : '' }}>Last Financial Year</option>
                        <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                    <!-- Custom Date Range Inputs -->
                    <div id="custom_date_inputs" style="display: none; margin-top: 10px;">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="start_date" style="display: block; color: white; font-size: 10px; margin-bottom: 2px;">Start Date</label>
                                <input type="date" class="filter-select" id="start_date" value="{{ request('start_date', \Carbon\Carbon::now()->subDays(29)->format('Y-m-d')) }}" style="width: 100%; padding: 6px 10px; font-size: 12px;">
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" style="display: block; color: white; font-size: 10px; margin-bottom: 2px;">End Date</label>
                                <input type="date" class="filter-select" id="end_date" value="{{ request('end_date', \Carbon\Carbon::now()->format('Y-m-d')) }}" style="width: 100%; padding: 6px 10px; font-size: 12px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Metrics Row -->
<div class="row">
    <div class="col-md-3">
        <div class="metric-card revenue">
            <div class="metric-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="metric-value">
                @php
                    $formatted_sales = number_format($totalSales, session('business.currency_precision', 2), session('currency.decimal_separator', '.'), session('currency.thousand_separator', ','));
                    $currency_symbol = session('currency.symbol', '৳');
                    $symbol_placement = session('business.currency_symbol_placement', 'before');
                @endphp
                @if($symbol_placement == 'before')
                    {{ $currency_symbol }}{{ $formatted_sales }}
                @else
                    {{ $formatted_sales }} {{ $currency_symbol }}
                @endif
            </div>
            <div class="metric-label">Total Sales</div>
            <div class="metric-change {{ $salesChangePercent >= 0 ? 'positive' : 'negative' }}">
                <i class="fas fa-arrow-{{ $salesChangePercent >= 0 ? 'up' : 'down' }}"></i> {{ abs($salesChangePercent) }}% vs last period
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card transactions">
            <div class="metric-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="metric-value">{{ number_format($totalTransactions) }}</div>
            <div class="metric-label">Total Invoices</div>
            <div class="metric-change {{ $transactionsChangePercent >= 0 ? 'positive' : 'negative' }}">
                <i class="fas fa-arrow-{{ $transactionsChangePercent >= 0 ? 'up' : 'down' }}"></i> {{ abs($transactionsChangePercent) }}% vs last period
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card average">
            <div class="metric-icon">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="metric-value">
                @php
                    $formatted_avg = number_format($averageSale, session('business.currency_precision', 2), session('currency.decimal_separator', '.'), session('currency.thousand_separator', ','));
                    $currency_symbol = session('currency.symbol', '৳');
                    $symbol_placement = session('business.currency_symbol_placement', 'before');
                @endphp
                @if($symbol_placement == 'before')
                    {{ $currency_symbol }}{{ $formatted_avg }}
                @else
                    {{ $formatted_avg }} {{ $currency_symbol }}
                @endif
            </div>
            <div class="metric-label">Average Sale</div>
            <div class="metric-change {{ $averageSaleChangePercent >= 0 ? 'positive' : 'negative' }}">
                <i class="fas fa-arrow-{{ $averageSaleChangePercent >= 0 ? 'up' : 'down' }}"></i> {{ abs($averageSaleChangePercent) }}% vs last period
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card growth">
            <div class="metric-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="metric-value">{{ $salesChangePercent }}%</div>
            <div class="metric-label">Growth Rate</div>
            <div class="metric-change {{ $salesChangePercent >= 0 ? 'positive' : 'negative' }}">
                <i class="fas fa-arrow-{{ $salesChangePercent >= 0 ? 'up' : 'down' }}"></i> {{ abs($salesChangePercent) }}% vs last period
            </div>
        </div>
    </div>
</div>

<!-- AI Insights for Sales -->
<div class="row">
    <div class="col-md-12">
        <div class="chart-card" style="background: linear-gradient(135deg, #166534 0%, #1e3a8a 100%); color: white;">
            <div class="chart-header" style="border-bottom-color: rgba(255,255,255,0.2);">
                <div class="chart-title" style="color: white;">
                    <i class="fas fa-brain"></i>
                    AI Sales Insights
                </div>
            </div>
            <div class="row" id="ai_sales_insights">
                <div class="col-md-3">
                    <div style="padding: 20px; background: rgba(255,255,255,0.1); border-radius: 10px; text-align: center;">
                        <i class="fas fa-trophy" style="font-size: 32px; margin-bottom: 10px;"></i>
                        <h4 style="margin: 10px 0;">Best Day</h4>
                        <p style="font-size: 18px; font-weight: 600;" id="best_day">Loading...</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="padding: 20px; background: rgba(255,255,255,0.1); border-radius: 10px; text-align: center;">
                        <i class="fas fa-chart-line" style="font-size: 32px; margin-bottom: 10px;"></i>
                        <h4 style="margin: 10px 0;">Trend</h4>
                        <p style="font-size: 18px; font-weight: 600;" id="sales_trend_text">Loading...</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="padding: 20px; background: rgba(255,255,255,0.1); border-radius: 10px; text-align: center;">
                        <i class="fas fa-users" style="font-size: 32px; margin-bottom: 10px;"></i>
                        <h4 style="margin: 10px 0;">Avg Customers/Day</h4>
                        <p style="font-size: 18px; font-weight: 600;" id="avg_customers">Loading...</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="padding: 20px; background: rgba(255,255,255,0.1); border-radius: 10px; text-align: center;">
                        <i class="fas fa-star" style="font-size: 32px; margin-bottom: 10px;"></i>
                        <h4 style="margin: 10px 0;">Top Product</h4>
                        <p style="font-size: 18px; font-weight: 600;" id="best_seller">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-md-8">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-chart-area"></i>
                    Sales Trend Over Time
                </div>
                <select class="filter-select" id="chart_type_filter">
                    <option value="area">Area Chart</option>
                    <option value="line">Line Chart</option>
                    <option value="bar">Bar Chart</option>
                </select>
            </div>
            <div id="sales_trend_chart" style="height: 400px;">
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #0ea5e9;"></i>
                    <p style="margin-top: 20px; color: #718096;">Loading sales data...</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-pie-chart"></i>
                    Sales by Category
                </div>
            </div>
            <div id="category_chart" style="height: 400px;">
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #0ea5e9;"></i>
                    <p style="margin-top: 20px; color: #718096;">Loading categories...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Products Row -->
<div class="row">
    <div class="col-md-4">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-tags"></i>
                    Top Categories Selling
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label for="top_categories_sort" style="margin: 0; font-size: 14px; color: #4a5568; font-weight: 500;">Sort by:</label>
                        <select class="filter-select" id="top_categories_sort" style="padding: 6px 12px; font-size: 13px;">
                            <option value="revenue" selected>Price</option>
                            <option value="quantity">Qty</option>
                        </select>
                    </div>
                    <span class="badge" id="categories_count_badge" style="background: linear-gradient(135deg, #9c88ff 0%, #6c5ce7 100%); color: white; padding: 8px 15px; border-radius: 20px;">
                        {{ count($topCategories) }} Categories
                    </span>
                </div>
            </div>

            <div id="top_categories_container">
                @if(count($topCategories) > 0)
                <div class="product-list">
                    @foreach($topCategories as $index => $category)
                    <div class="product-item">
                        <div class="product-rank rank-{{ $index < 3 ? $index + 1 : 'other' }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="product-info">
                            <div class="product-name">{{ $category->category_name ?? 'Uncategorized' }}</div>
                            <div class="product-stats">
                                <span><i class="fas fa-box"></i> {{ number_format($category->total_quantity ?? 0) }} Units Sold</span>
                                <span><i class="fas fa-cube"></i> {{ number_format($category->product_count ?? 0) }} Products</span>
                                <span><i class="fas fa-percent"></i> {{ number_format((($category->total_revenue ?? 0) / max($totalSales, 1)) * 100, 1) }}% of Total</span>
                            </div>
                            @if(isset($category->trend_percent))
                            <div class="metric-change {{ $category->trend_percent >= 0 ? 'positive' : 'negative' }}" style="margin-top: 5px; padding: 2px 8px; font-size: 11px;">
                                <i class="fas fa-arrow-{{ $category->trend_percent >= 0 ? 'up' : 'down' }}"></i> {{ abs($category->trend_percent) }}% vs last period
                            </div>
                            @endif
                        </div>
                        <div class="product-revenue">
                            @php
                                $category_revenue = $category->total_revenue ?? 0;
                                $formatted_revenue = number_format($category_revenue, session('business.currency_precision', 2), session('currency.decimal_separator', '.'), session('currency.thousand_separator', ','));
                                $currency_symbol = session('currency.symbol', '৳');
                                $symbol_placement = session('business.currency_symbol_placement', 'before');
                            @endphp
                            @if($symbol_placement == 'before')
                                {{ $currency_symbol }}{{ $formatted_revenue }}
                            @else
                                {{ $formatted_revenue }} {{ $currency_symbol }}
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Category Data Available</h3>
                    <p>Start making sales to see your top categories here!</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-trophy"></i>
                    Top Selling Products
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label for="top_products_sort" style="margin: 0; font-size: 14px; color: #4a5568; font-weight: 500;">Sort by:</label>
                        <select class="filter-select" id="top_products_sort" style="padding: 6px 12px; font-size: 13px;">
                            <option value="revenue" selected>Price</option>
                            <option value="quantity">Qty</option>
                        </select>
                    </div>
                    <span class="badge" id="products_count_badge" style="background: linear-gradient(135deg, #0ea5e9 0%, #5f7592ff 100%); color: white; padding: 8px 15px; border-radius: 20px;">
                        {{ count($topProducts) }} Products
                    </span>
                </div>
            </div>

            <div id="top_products_container">
                @if(count($topProducts) > 0)
                <div class="product-list">
                    @foreach($topProducts as $index => $product)
                    <div class="product-item">
                        <div class="product-rank rank-{{ $index < 3 ? $index + 1 : 'other' }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="product-info">
                            <div class="product-name">{{ $product->product_name ?? $product->name ?? 'Unknown Product' }}</div>
                        <div class="product-stats">
                            <span><i class="fas fa-box"></i> {{ number_format($product->total_quantity ?? $product->total_sold ?? $product->qty_sold ?? 0) }} Units Sold</span>
                            <span><i class="fas fa-percent"></i> {{ number_format((($product->total_revenue ?? 0) / max($totalSales, 1)) * 100, 1) }}% of Total</span>
                        </div>
                        @if(isset($product->trend_percent))
                        <div class="metric-change {{ $product->trend_percent >= 0 ? 'positive' : 'negative' }}" style="margin-top: 5px; padding: 2px 8px; font-size: 11px;">
                            <i class="fas fa-arrow-{{ $product->trend_percent >= 0 ? 'up' : 'down' }}"></i> {{ abs($product->trend_percent) }}% vs last period
                        </div>
                        @endif
                    </div>
                        <div class="product-revenue">
                            @php
                                $product_revenue = $product->total_revenue ?? 0;
                                $formatted_revenue = number_format($product_revenue, session('business.currency_precision', 2), session('currency.decimal_separator', '.'), session('currency.thousand_separator', ','));
                                $currency_symbol = session('currency.symbol', '৳');
                                $symbol_placement = session('business.currency_symbol_placement', 'before');
                            @endphp
                            @if($symbol_placement == 'before')
                                {{ $currency_symbol }}{{ $formatted_revenue }}
                            @else
                                {{ $formatted_revenue }} {{ $currency_symbol }}
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No Product Data Available</h3>
                    <p>Start making sales to see your top products here!</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-award"></i>
                    Top Brand Selling
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label for="top_brands_sort" style="margin: 0; font-size: 14px; color: #4a5568; font-weight: 500;">Sort by:</label>
                        <select class="filter-select" id="top_brands_sort" style="padding: 6px 12px; font-size: 13px;">
                            <option value="revenue" selected>Price</option>
                            <option value="quantity">Quantity</option>
                        </select>
                    </div>
                    <span class="badge" id="brands_count_badge" style="background: linear-gradient(135deg, #f5576c 0%, #e74c3c 100%); color: white; padding: 8px 15px; border-radius: 20px;">
                        {{ count($topBrands) }} Brands
                    </span>
                </div>
            </div>

            <div id="top_brands_container">
                @if(count($topBrands) > 0)
                <div class="product-list">
                    @foreach($topBrands as $index => $brand)
                    <div class="product-item">
                        <div class="product-rank rank-{{ $index < 3 ? $index + 1 : 'other' }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="product-info">
                            <div class="product-name">{{ $brand->brand_name ?? 'Unbranded' }}</div>
                            <div class="product-stats">
                                <span><i class="fas fa-box"></i> {{ number_format($brand->total_quantity ?? 0) }} Units Sold</span>
                                <span><i class="fas fa-cube"></i> {{ number_format($brand->product_count ?? 0) }} Products</span>
                                <span><i class="fas fa-percent"></i> {{ number_format((($brand->total_revenue ?? 0) / max($totalSales, 1)) * 100, 1) }}% of Total</span>
                            </div>
                            @if(isset($brand->trend_percent))
                            <div class="metric-change {{ $brand->trend_percent >= 0 ? 'positive' : 'negative' }}" style="margin-top: 5px; padding: 2px 8px; font-size: 11px;">
                                <i class="fas fa-arrow-{{ $brand->trend_percent >= 0 ? 'up' : 'down' }}"></i> {{ abs($brand->trend_percent) }}% vs last period
                            </div>
                            @endif
                        </div>
                        <div class="product-revenue">
                            @php
                                $brand_revenue = $brand->total_revenue ?? 0;
                                $formatted_revenue = number_format($brand_revenue, session('business.currency_precision', 2), session('currency.decimal_separator', '.'), session('currency.thousand_separator', ','));
                                $currency_symbol = session('currency.symbol', '৳');
                                $symbol_placement = session('business.currency_symbol_placement', 'before');
                            @endphp
                            @if($symbol_placement == 'before')
                                {{ $currency_symbol }}{{ $formatted_revenue }}
                            @else
                                {{ $formatted_revenue }} {{ $currency_symbol }}
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h3>No Brand Data Available</h3>
                    <p>Start making sales to see your top brands here!</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Top Brands Row -->
<div class="row">

</div>

<!-- Sales Performance Breakdown -->
<div class="row">
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-calendar-alt"></i>
                    Daily Performance (Last 7 Days)
                </div>
            </div>
            <div id="daily_performance_chart" style="height: 300px;">
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 36px; color: #0ea5e9;"></i>
                    <p style="margin-top: 15px; color: #718096;">Loading daily data...</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-clock"></i>
                    Peak Sales Hours
                </div>
            </div>
            <div id="hourly_sales_chart" style="height: 300px;">
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 36px; color: #0ea5e9;"></i>
                    <p style="margin-top: 15px; color: #718096;">Analyzing hours...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Sales Analytics -->
<div class="row">
    <div class="col-md-4">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-percentage"></i>
                    Conversion Rate
                </div>
            </div>
            <div id="conversion_chart" style="height: 250px;"></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Payment Methods
                </div>
            </div>
            <div id="payment_methods_chart" style="height: 250px;"></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-user-tag"></i>
                    Customer Types
                </div>
            </div>
            <div id="customer_types_chart" style="height: 250px;"></div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
$(document).ready(function() {

    // Initialize Select2 for location filter (only if it exists)
    if ($('#location_filter').length > 0) {
        $('#location_filter').select2({
            placeholder: 'Select locations...',
            allowClear: true,
            width: '100%'
        });
    }

    // Function to update data dynamically without page reload
    function updateFilters() {
        const dateRange = $('#date_range_filter').val();
        const selectedLocations = $('#location_filter').val() || [];

        // Show loading indicator
        $('.metric-card').addClass('loading');
        $('.metric-card').append('<div class="loading-overlay-card" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 10; display: flex; align-items: center; justify-content: center; border-radius: 15px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #0ea5e9;"></i></div>');

        // Update metrics via AJAX
        loadSalesMetrics();

        // Update charts and lists
        loadSalesTrendChart();
        loadCategoryChart();
        loadDailyPerformanceChart();
        loadHourlySalesChart();
        loadAISalesInsights();
        loadConversionChart();
        loadPaymentMethodsChart();
        loadCustomerTypesChart();
        loadTopProducts();
        loadTopCategories();
        loadTopBrands();
    }

    // Date range filter
    $('#date_range_filter').on('change', function() {
        const selectedValue = $(this).val();

        // Show/hide custom date inputs
        if (selectedValue === 'custom') {
            $('#custom_date_inputs').show();
        } else {
            $('#custom_date_inputs').hide();
        }

        updateFilters();
        // Also reload top products and brands with current sort
        const productsSortBy = $('#top_products_sort').val();
        const categoriesSortBy = $('#top_categories_sort').val();
        const brandsSortBy = $('#top_brands_sort').val();
        loadTopProducts(productsSortBy);
        loadTopCategories(categoriesSortBy);
        loadTopBrands(brandsSortBy);
    });

    // Custom date inputs change
    $('#start_date, #end_date').on('change', function() {
        const dateRange = $('#date_range_filter').val();
        if (dateRange === 'custom') {
            updateFilters();
            // Also reload top products, categories and brands with current sort
            const productsSortBy = $('#top_products_sort').val();
            const categoriesSortBy = $('#top_categories_sort').val();
            const brandsSortBy = $('#top_brands_sort').val();
            loadTopProducts(productsSortBy);
            loadTopCategories(categoriesSortBy);
            loadTopBrands(brandsSortBy);
        }
    });

    // Initialize custom date inputs visibility on page load
    if ($('#date_range_filter').val() === 'custom') {
        $('#custom_date_inputs').show();
    }

    // Location filter
    if ($('#location_filter').length > 0) {
        $('#location_filter').on('change', function() {
            updateFilters();
            // Also reload top products, categories and brands with current sort
            const productsSortBy = $('#top_products_sort').val();
            const categoriesSortBy = $('#top_categories_sort').val();
            const brandsSortBy = $('#top_brands_sort').val();
            loadTopProducts(productsSortBy);
            loadTopCategories(categoriesSortBy);
            loadTopBrands(brandsSortBy);
        });
    }
    
    // Load all data
    loadSalesMetrics();
    loadSalesTrendChart();
    loadCategoryChart();
    loadDailyPerformanceChart();
    loadHourlySalesChart();
    loadAISalesInsights();
    loadConversionChart();
    loadPaymentMethodsChart();
    loadCustomerTypesChart();
    loadTopProducts();
    loadTopCategories();
    loadTopBrands();
    
    /**
     * Load Sales Metrics
     */
    function loadSalesMetrics() {
        console.log('Loading sales metrics...');

        const dateRange = $('#date_range_filter').val();
        const selectedLocations = $('#location_filter').val() || [];

        let params = {
            date_range: dateRange,
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        };

        // Add custom date parameters if custom range is selected
        if (dateRange === 'custom') {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            if (startDate) params.start_date = startDate;
            if (endDate) params.end_date = endDate;
        }

        $.get('{{ route("businessintelligence.analytics.sales.data") }}', params, function(response) {
            console.log('Sales metrics response:', response);

            if (response.success && response.data) {
                const data = response.data;

                // Update Total Sales metric
                $('.metric-card.revenue .metric-value').text(data.total_sales_formatted);
                const salesChangeClass = data.sales_change_percent >= 0 ? 'positive' : 'negative';
                const salesChangeIcon = data.sales_change_percent >= 0 ? 'up' : 'down';
                $('.metric-card.revenue .metric-change').removeClass('positive negative').addClass(salesChangeClass);
                $('.metric-card.revenue .metric-change i').removeClass('fa-arrow-up fa-arrow-down').addClass('fa-arrow-' + salesChangeIcon);
                $('.metric-card.revenue .metric-change').html('<i class="fas fa-arrow-' + salesChangeIcon + '"></i> ' + Math.abs(data.sales_change_percent) + '% vs last period');

                // Update Total Transactions metric
                $('.metric-card.transactions .metric-value').text(new Intl.NumberFormat().format(data.total_transactions));
                const transactionsChangeClass = data.transactions_change_percent >= 0 ? 'positive' : 'negative';
                const transactionsChangeIcon = data.transactions_change_percent >= 0 ? 'up' : 'down';
                $('.metric-card.transactions .metric-change').removeClass('positive negative').addClass(transactionsChangeClass);
                $('.metric-card.transactions .metric-change i').removeClass('fa-arrow-up fa-arrow-down').addClass('fa-arrow-' + transactionsChangeIcon);
                $('.metric-card.transactions .metric-change').html('<i class="fas fa-arrow-' + transactionsChangeIcon + '"></i> ' + Math.abs(data.transactions_change_percent) + '% vs last period');

                // Update Average Sale metric
                $('.metric-card.average .metric-value').text(data.average_sale_formatted);
                const averageChangeClass = data.average_sale_change_percent >= 0 ? 'positive' : 'negative';
                const averageChangeIcon = data.average_sale_change_percent >= 0 ? 'up' : 'down';
                $('.metric-card.average .metric-change').removeClass('positive negative').addClass(averageChangeClass);
                $('.metric-card.average .metric-change i').removeClass('fa-arrow-up fa-arrow-down').addClass('fa-arrow-' + averageChangeIcon);
                $('.metric-card.average .metric-change').html('<i class="fas fa-arrow-' + averageChangeIcon + '"></i> ' + Math.abs(data.average_sale_change_percent) + '% vs last period');

                // Update Growth Rate metric (using sales change as growth rate)
                $('.metric-card.growth .metric-value').text(data.sales_change_percent + '%');
                const growthChangeClass = data.sales_change_percent >= 0 ? 'positive' : 'negative';
                const growthChangeIcon = data.sales_change_percent >= 0 ? 'up' : 'down';
                $('.metric-card.growth .metric-change').removeClass('positive negative').addClass(growthChangeClass);
                $('.metric-card.growth .metric-change i').removeClass('fa-arrow-up fa-arrow-down').addClass('fa-arrow-' + growthChangeIcon);
                $('.metric-card.growth .metric-change').html('<i class="fas fa-arrow-' + growthChangeIcon + '"></i> ' + Math.abs(data.sales_change_percent) + '% vs last period');

                // Remove loading indicators
                $('.loading-overlay-card').remove();
                $('.metric-card').removeClass('loading');

                console.log('Sales metrics updated successfully');
            } else {
                console.error('Failed to load sales metrics');
                $('.loading-overlay-card').remove();
                $('.metric-card').removeClass('loading');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading sales metrics:', error);
            $('.loading-overlay-card').remove();
            $('.metric-card').removeClass('loading');
        });
    }

    /**
     * Load AI Sales Insights
     */
    function loadAISalesInsights() {
        console.log('Loading AI Sales Insights...');
        
        const selectedLocations = $('#location_filter').val() || [];

        $.get('{{ route("businessintelligence.dashboard.chart-data") }}', {
            chart_type: 'sales_trend',
            date_range: '{{ $dateRange }}',
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        }, function(response) {
            console.log('AI Insights response:', response);
            
            if (response.success && response.data) {
                const sales = response.data.series || [];
                const categories = response.data.categories || [];
                
                console.log('Sales data:', sales);
                console.log('Categories:', categories);
                
                // Find best day
                let maxSales = 0;
                let bestDayIndex = -1;
                let bestDay = 'No Data';
                
                sales.forEach((value, index) => {
                    if (value > maxSales) {
                        maxSales = value;
                        bestDayIndex = index;
                        bestDay = categories[index] || 'Unknown';
                    }
                });
                
                console.log('Best day:', bestDay, 'Sales:', maxSales);
                
                // Calculate trend
                let trendText = 'Stable';
                if (sales.length >= 14) {
                    const recentSales = sales.slice(-7);
                    const previousSales = sales.slice(-14, -7);
                    const recentAvg = recentSales.reduce((a, b) => a + b, 0) / recentSales.length;
                    const previousAvg = previousSales.reduce((a, b) => a + b, 0) / previousSales.length;
                    
                    if (previousAvg > 0) {
                        const trend = (((recentAvg - previousAvg) / previousAvg) * 100).toFixed(1);
                        trendText = trend > 0 ? '↑ ' + trend + '% Up' : trend < 0 ? '↓ ' + Math.abs(trend) + '% Down' : 'Stable';
                    }
                } else if (sales.length >= 2) {
                    // If less than 14 days, compare first half vs second half
                    const midPoint = Math.floor(sales.length / 2);
                    const firstHalf = sales.slice(0, midPoint);
                    const secondHalf = sales.slice(midPoint);
                    const firstAvg = firstHalf.reduce((a, b) => a + b, 0) / firstHalf.length;
                    const secondAvg = secondHalf.reduce((a, b) => a + b, 0) / secondHalf.length;
                    
                    if (firstAvg > 0) {
                        const trend = (((secondAvg - firstAvg) / firstAvg) * 100).toFixed(1);
                        trendText = trend > 0 ? '↑ ' + trend + '% Up' : trend < 0 ? '↓ ' + Math.abs(trend) + '% Down' : 'Stable';
                    }
                }
                
                console.log('Trend:', trendText);
                
                // Calculate avg customers
                const totalTransactions = {{ $totalTransactions }};
                const daysInRange = {{ $daysInRange }};
                const avgCustomers = totalTransactions > 0 ? Math.floor(totalTransactions / daysInRange) : 0;
                
                console.log('Avg customers:', avgCustomers, 'Total:', totalTransactions, 'Days:', daysInRange);
                
                // Best seller from products
                const topProducts = @json($topProducts);
                let bestSeller = 'No Sales';
                
                if (topProducts && topProducts.length > 0) {
                    const firstProduct = topProducts[0];
                    bestSeller = firstProduct.product_name || firstProduct.name || 'Unknown';
                    if (bestSeller.length > 15) {
                        bestSeller = bestSeller.substring(0, 15) + '...';
                    }
                }
                
                console.log('Best seller:', bestSeller);
                
                // Update UI
                const currencySymbol = '{{ session("currency.symbol", "৳") }}';
                const symbolPlacement = '{{ session("business.currency_symbol_placement", "before") }}';
                const formattedAmount = maxSales > 0 ? maxSales.toFixed(2) : '0.00';
                const displayAmount = symbolPlacement === 'before' ?
                    currencySymbol + formattedAmount :
                    formattedAmount + ' ' + currencySymbol;
                $('#best_day').html(maxSales > 0 ? '<strong>' + bestDay + '</strong><br>' + displayAmount : 'No sales yet');
                $('#sales_trend_text').html('<strong>' + trendText + '</strong>');
                $('#avg_customers').html('<strong>' + avgCustomers + '</strong> per day');
                $('#best_seller').html('<strong>' + bestSeller + '</strong>');
                
                console.log('AI Insights updated successfully');
            } else {
                console.error('Failed to load AI insights');
                $('#best_day').html('Data unavailable');
                $('#sales_trend_text').html('Data unavailable');
                $('#avg_customers').html('Data unavailable');
                $('#best_seller').html('Data unavailable');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading AI insights:', error);
            $('#best_day').html('Error loading');
            $('#sales_trend_text').html('Error loading');
            $('#avg_customers').html('Error loading');
            $('#best_seller').html('Error loading');
        });
    }
    
    /**
     * Load Sales Trend Chart
     */
    function loadSalesTrendChart() {
        console.log('Loading sales trend chart...');

        const selectedLocations = $('#location_filter').val() || [];

        $.get('{{ route("businessintelligence.dashboard.chart-data") }}', {
            chart_type: 'sales_trend',
            date_range: '{{ $dateRange }}',
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        }, function(response) {
            console.log('Sales trend response:', response);
            
            if (response.success && response.data) {
                // Clear loading spinner
                $('#sales_trend_chart').html('');
                
                const options = {
                    series: [{
                        name: 'Sales',
                        data: response.data.series || []
                    }],
                    chart: {
                        type: 'area',
                        height: 400,
                        toolbar: { show: true },
                        animations: { enabled: true }
                    },
                    dataLabels: { enabled: false },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.3,
                            stops: [0, 90, 100]
                        }
                    },
                    colors: ['#667eea'],
                    xaxis: {
                        categories: response.data.categories || [],
                        labels: { style: { fontSize: '12px' } }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(val) {
                                const currencySymbol = '{{ session("currency.symbol", "৳") }}';
                                const symbolPlacement = '{{ session("business.currency_symbol_placement", "before") }}';
                                const formatted = val.toFixed(0);
                                return symbolPlacement === 'before' ? currencySymbol + formatted : formatted + ' ' + currencySymbol;
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                const currencySymbol = '{{ session("currency.symbol", "৳") }}';
                                const symbolPlacement = '{{ session("business.currency_symbol_placement", "before") }}';
                                const formatted = val.toFixed(2);
                                return symbolPlacement === 'before' ? currencySymbol + formatted : formatted + ' ' + currencySymbol;
                            }
                        }
                    }
                };
                
                const chart = new ApexCharts(document.querySelector("#sales_trend_chart"), options);
                chart.render();
                
                console.log('Sales trend chart rendered successfully');
            } else {
                $('#sales_trend_chart').html('<div style="text-align: center; padding: 50px;"><i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f5576c;"></i><p style="margin-top: 20px; color: #718096;">Failed to load sales data</p></div>');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading sales trend:', error);
            $('#sales_trend_chart').html('<div style="text-align: center; padding: 50px;"><i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f5576c;"></i><p style="margin-top: 20px; color: #718096;">Error: ' + error + '</p></div>');
        });
    }
    
    /**
     * Load Category Chart
     */
    function loadCategoryChart() {
        console.log('Loading category chart...');

        const selectedLocations = $('#location_filter').val() || [];

        $.get('{{ route("businessintelligence.dashboard.chart-data") }}', {
            chart_type: 'revenue_sources',
            date_range: '{{ $dateRange }}',
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        }, function(response) {
            console.log('Category chart response:', response);
            
            if (response.success && response.data) {
                // Clear loading spinner
                $('#category_chart').html('');
                const options = {
                    series: response.data.series || [],
                    chart: {
                        type: 'donut',
                        height: 400
                    },
                    labels: response.data.labels || [],
                    colors: ['#0ea5e9', '#f5576c'],
                    legend: {
                        position: 'bottom',
                        fontSize: '14px'
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val) {
                            return val.toFixed(1) + '%';
                        }
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: { width: 300 },
                            legend: { position: 'bottom' }
                        }
                    }]
                };
                
                const chart = new ApexCharts(document.querySelector("#category_chart"), options);
                chart.render();
                
                console.log('Category chart rendered successfully');
            } else {
                $('#category_chart').html('<div style="text-align: center; padding: 50px;"><i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f5576c;"></i><p style="margin-top: 20px; color: #718096;">No category data available</p></div>');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading category chart:', error);
            $('#category_chart').html('<div style="text-align: center; padding: 50px;"><i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f5576c;"></i><p style="margin-top: 20px; color: #718096;">Error loading categories</p></div>');
        });
    }
    
    /**
     * Load Daily Performance Chart
     */
    function loadDailyPerformanceChart() {
        console.log('Loading daily performance chart...');

        const selectedLocations = $('#location_filter').val() || [];

        $.get('{{ route("businessintelligence.dashboard.chart-data") }}', {
            chart_type: 'sales_trend',
            date_range: 'last_7_days',
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        }, function(response) {
            console.log('Daily performance response:', response);
            
            if (response.success && response.data) {
                // Clear loading spinner
                $('#daily_performance_chart').html('');
                const options = {
                    series: [{
                        name: 'Sales',
                        data: response.data.series || []
                    }],
                    chart: {
                        type: 'bar',
                        height: 300,
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 8,
                            columnWidth: '60%',
                            distributed: true
                        }
                    },
                    dataLabels: { enabled: false },
                    colors: ['#0ea5e9', '#f5576c'],
                    xaxis: {
                        categories: response.data.categories || [],
                        labels: { style: { fontSize: '11px' } }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(val) {
                                const currencySymbol = '{{ session("currency.symbol", "৳") }}';
                                const symbolPlacement = '{{ session("business.currency_symbol_placement", "before") }}';
                                const formatted = val.toFixed(0);
                                return symbolPlacement === 'before' ? currencySymbol + formatted : formatted + ' ' + currencySymbol;
                            }
                        }
                    },
                    legend: { show: false }
                };
                
                const chart = new ApexCharts(document.querySelector("#daily_performance_chart"), options);
                chart.render();
                
                console.log('Daily performance chart rendered successfully');
            } else {
                $('#daily_performance_chart').html('<div style="text-align: center; padding: 50px;"><i class="fas fa-info-circle" style="font-size: 36px; color: #cbd5e0;"></i><p style="margin-top: 15px; color: #718096;">No data for last 7 days</p></div>');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading daily performance:', error);
            $('#daily_performance_chart').html('<div style="text-align: center; padding: 50px;"><i class="fas fa-exclamation-triangle" style="font-size: 36px; color: #f5576c;"></i><p style="margin-top: 15px; color: #718096;">Error loading data</p></div>');
        });
    }
    
    /**
     * Load Hourly Sales Chart
     */
    function loadHourlySalesChart() {
        console.log('Loading hourly sales chart...');

        const selectedLocations = $('#location_filter').val() || [];

        $.get('{{ route("businessintelligence.dashboard.chart-data") }}', {
            chart_type: 'hourly_sales',
            date_range: '{{ $dateRange }}',
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        }, function(response) {
            console.log('Hourly sales response:', response);

            if (response.success && response.data) {
                // Clear loading spinner
                $('#hourly_sales_chart').html('');

                const options = {
                    series: [{
                        name: 'Sales',
                        data: response.data.data || []
                    }],
                    chart: {
                        type: 'line',
                        height: 300,
                        toolbar: { show: false }
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    colors: ['#f5576c'],
                    markers: {
                        size: 5,
                        colors: ['#f5576c'],
                        strokeWidth: 2,
                        strokeColors: '#fff',
                        hover: { size: 7 }
                    },
                    xaxis: {
                        categories: response.data.hours || [],
                        labels: { style: { fontSize: '11px' } }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(val) {
                                const currencySymbol = '{{ session("currency.symbol", "৳") }}';
                                const symbolPlacement = '{{ session("business.currency_symbol_placement", "before") }}';
                                const formatted = val.toFixed(0);
                                return symbolPlacement === 'before' ? currencySymbol + formatted : formatted + ' ' + currencySymbol;
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                const currencySymbol = '{{ session("currency.symbol", "৳") }}';
                                const symbolPlacement = '{{ session("business.currency_symbol_placement", "before") }}';
                                const formatted = val.toFixed(2);
                                return symbolPlacement === 'before' ? currencySymbol + formatted : formatted + ' ' + currencySymbol;
                            }
                        }
                    }
                };

                const chart = new ApexCharts(document.querySelector("#hourly_sales_chart"), options);
                chart.render();

                console.log('Hourly sales chart rendered successfully');
            } else {
                $('#hourly_sales_chart').html('<div style="text-align: center; padding: 50px;"><i class="fas fa-exclamation-triangle" style="font-size: 36px; color: #f5576c;"></i><p style="margin-top: 15px; color: #718096;">Failed to load hourly sales data</p></div>');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading hourly sales:', error);
            $('#hourly_sales_chart').html('<div style="text-align: center; padding: 50px;"><i class="fas fa-exclamation-triangle" style="font-size: 36px; color: #f5576c;"></i><p style="margin-top: 15px; color: #718096;">Error loading hourly sales data</p></div>');
        });
    }
    
    /**
     * Load Conversion Rate Chart
     */
    function loadConversionChart() {
        console.log('Loading conversion rate chart...');

        const selectedLocations = $('#location_filter').val() || [];

        $.get('{{ route("businessintelligence.dashboard.chart-data") }}', {
            chart_type: 'conversion_rate',
            date_range: '{{ $dateRange }}',
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        }, function(response) {
            console.log('Conversion rate response:', response);
            
            if (response.success && response.data) {
                const rate = response.data.rate || 0;
                
                const options = {
                    series: [rate],
                    chart: {
                        type: 'radialBar',
                        height: 250
                    },
                    plotOptions: {
                        radialBar: {
                            hollow: {
                                size: '60%'
                            },
                            dataLabels: {
                                name: {
                                    show: true,
                                    fontSize: '14px',
                                    offsetY: -10
                                },
                                value: {
                                    show: true,
                                    fontSize: '28px',
                                    fontWeight: 700,
                                    offsetY: 5,
                                    formatter: function(val) {
                                        return val + '%';
                                    }
                                }
                            }
                        }
                    },
                    colors: ['#43e97b'],
                    labels: ['Conversion Rate']
                };
                
                const chart = new ApexCharts(document.querySelector("#conversion_chart"), options);
                chart.render();
                
                console.log('Conversion rate chart rendered: ' + rate + '%');
            } else {
                console.error('Failed to load conversion rate data');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading conversion rate:', error);
        });
    }
    
    /**
     * Load Payment Methods Chart
     */
    function loadPaymentMethodsChart() {
        console.log('Loading payment methods chart...');

        const selectedLocations = $('#location_filter').val() || [];

        $.get('{{ route("businessintelligence.dashboard.chart-data") }}', {
            chart_type: 'payment_methods',
            date_range: '{{ $dateRange }}',
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        }, function(response) {
            console.log('Payment methods response:', response);
            
            if (response.success && response.data) {
                const options = {
                    series: response.data.series || [],
                    chart: {
                        type: 'pie',
                        height: 250
                    },
                    labels: response.data.labels || [],
                    colors: ['#0ea5e9', '#f5576c'],
                    legend: {
                        position: 'bottom',
                        fontSize: '12px'
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val) {
                            return val.toFixed(1) + '%';
                        }
                    }
                };
                
                const chart = new ApexCharts(document.querySelector("#payment_methods_chart"), options);
                chart.render();
                
                console.log('Payment methods chart rendered');
            } else {
                console.error('Failed to load payment methods data');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading payment methods:', error);
        });
    }
    
    /**
     * Load Customer Types Chart
     */
    function loadCustomerTypesChart() {
        console.log('Loading customer types chart...');

        const selectedLocations = $('#location_filter').val() || [];

        $.get('{{ route("businessintelligence.dashboard.chart-data") }}', {
            chart_type: 'customer_types',
            date_range: '{{ $dateRange }}',
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        }, function(response) {
            console.log('Customer types response:', response);

            if (response.success && response.data) {
                const returningPercent = response.data.returning || 0;
                const newPercent = response.data.new || 0;

                const options = {
                    series: [{
                        name: 'Customers',
                        data: [returningPercent, newPercent]
                    }],
                    chart: {
                        type: 'bar',
                        height: 250,
                        horizontal: true
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 8,
                            distributed: true
                        }
                    },
                    colors: ['#667eea', '#f5576c'],
                    xaxis: {
                        categories: ['Returning', 'New'],
                        labels: {
                            formatter: function(val) {
                                return val + '%';
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                fontSize: '14px',
                                fontWeight: 600
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val) {
                            return val + '%';
                        },
                        style: {
                            colors: ['#fff'],
                            fontSize: '14px',
                            fontWeight: 600
                        }
                    },
                    legend: { show: false }
                };

                const chart = new ApexCharts(document.querySelector("#customer_types_chart"), options);
                chart.render();

                console.log('Customer types chart rendered: Returning ' + returningPercent + '%, New ' + newPercent + '%');
            } else {
                console.error('Failed to load customer types data');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading customer types:', error);
        });
    }

    /**
     * Load Top Products
     */
    function loadTopProducts(sortBy = 'revenue') {
        console.log('Loading top products with sort:', sortBy);

        const selectedLocations = $('#location_filter').val() || [];

        $.get('{{ route("businessintelligence.dashboard.chart-data") }}', {
            chart_type: 'top_products',
            date_range: '{{ $dateRange }}',
            sort_by: sortBy,
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        }, function(response) {
            console.log('Top products response:', response);

            if (response.success && response.data && response.data.products) {
                const products = response.data.products;
                const totalSales = {{ $totalSales }};
                const currencySymbol = '{{ session("currency.symbol", "৳") }}';
                const symbolPlacement = '{{ session("business.currency_symbol_placement", "before") }}';
                const currencyPrecision = {{ session('business.currency_precision', 2) }};
                const decimalSeparator = '{{ session("currency.decimal_separator", ".") }}';
                const thousandSeparator = '{{ session("currency.thousand_separator", ",") }}';

                let html = '';

                if (products.length > 0) {
                    html += '<div class="product-list">';
                    products.forEach(function(product, index) {
                        const rankClass = index < 3 ? 'rank-' + (index + 1) : 'rank-other';
                        const productName = product.product_name || product.name || 'Unknown Product';
                        const totalQuantity = product.total_quantity || 0;
                        const totalRevenue = product.total_revenue || 0;
                        const percentage = totalSales > 0 ? ((totalRevenue / totalSales) * 100).toFixed(1) : 0;

                        // Format revenue
                        const formattedRevenue = new Intl.NumberFormat('en-US', {
                            minimumFractionDigits: currencyPrecision,
                            maximumFractionDigits: currencyPrecision
                        }).format(totalRevenue).replace(/,/g, thousandSeparator).replace(/\./g, decimalSeparator);

                        const displayRevenue = symbolPlacement === 'before' ?
                            currencySymbol + formattedRevenue :
                            formattedRevenue + ' ' + currencySymbol;

                        // Calculate trend data
                        const trendPercent = product.trend_percent !== undefined ? product.trend_percent : 0;
                        const trendDirection = trendPercent > 0 ? 'up' : (trendPercent < 0 ? 'down' : 'stable');
                        const trendClass = trendPercent >= 0 ? 'positive' : 'negative';
                        const trendIcon = trendPercent >= 0 ? 'up' : 'down';
                        const trendDisplay = product.trend_percent !== undefined ?
                            `<div class="metric-change ${trendClass}" style="margin-top: 5px; padding: 2px 8px; font-size: 11px;">
                                <i class="fas fa-arrow-${trendIcon}"></i> ${Math.abs(trendPercent)}% vs last period
                            </div>` : '';

                        html += `
                            <div class="product-item">
                                <div class="product-rank ${rankClass}">
                                    ${index + 1}
                                </div>
                                <div class="product-info">
                                    <div class="product-name">${productName}</div>
                                    <div class="product-stats">
                                        <span><i class="fas fa-box"></i> ${new Intl.NumberFormat().format(totalQuantity)} Units Sold</span>
                                        <span><i class="fas fa-percent"></i> ${percentage}% of Total</span>
                                    </div>
                                    ${trendDisplay}
                                </div>
                                <div class="product-revenue">
                                    ${displayRevenue}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                } else {
                    html = `
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h3>No Product Data Available</h3>
                            <p>Start making sales to see your top products here!</p>
                        </div>
                    `;
                }

                $('#top_products_container').html(html);
                $('#products_count_badge').text(products.length + ' Products');

                console.log('Top products loaded successfully');
            } else {
                console.error('Failed to load top products data');
                $('#top_products_container').html(`
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error Loading Products</h3>
                        <p>Unable to load product data. Please try again.</p>
                    </div>
                `);
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading top products:', error);
            $('#top_products_container').html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error Loading Products</h3>
                    <p>Unable to load product data. Please try again.</p>
                </div>
            `);
        });
    }

    // Handle top products sort change
    $('#top_products_sort').on('change', function() {
        const sortBy = $(this).val();
        loadTopProducts(sortBy);
    });

    /**
     * Load Top Categories
     */
    function loadTopCategories(sortBy = 'revenue') {
        console.log('Loading top categories with sort:', sortBy);

        const selectedLocations = $('#location_filter').val() || [];

        $.get('{{ route("businessintelligence.dashboard.chart-data") }}', {
            chart_type: 'top_categories',
            date_range: '{{ $dateRange }}',
            sort_by: sortBy,
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        }, function(response) {
            console.log('Top categories response:', response);

            if (response.success && response.data && response.data.categories) {
                const categories = response.data.categories;
                const totalSales = {{ $totalSales }};
                const currencySymbol = '{{ session("currency.symbol", "৳") }}';
                const symbolPlacement = '{{ session("business.currency_symbol_placement", "before") }}';
                const currencyPrecision = {{ session('business.currency_precision', 2) }};
                const decimalSeparator = '{{ session("currency.decimal_separator", ".") }}';
                const thousandSeparator = '{{ session("currency.thousand_separator", ",") }}';

                let html = '';

                if (categories.length > 0) {
                    html += '<div class="product-list">';
                    categories.forEach(function(category, index) {
                        const rankClass = index < 3 ? 'rank-' + (index + 1) : 'rank-other';
                        const categoryName = category.category_name || 'Uncategorized';
                        const totalQuantity = category.total_quantity || 0;
                        const totalRevenue = category.total_revenue || 0;
                        const productCount = category.product_count || 0;
                        const percentage = totalSales > 0 ? ((totalRevenue / totalSales) * 100).toFixed(1) : 0;

                        // Format revenue
                        const formattedRevenue = new Intl.NumberFormat('en-US', {
                            minimumFractionDigits: currencyPrecision,
                            maximumFractionDigits: currencyPrecision
                        }).format(totalRevenue).replace(/,/g, thousandSeparator).replace(/\./g, decimalSeparator);

                        const displayRevenue = symbolPlacement === 'before' ?
                            currencySymbol + formattedRevenue :
                            formattedRevenue + ' ' + currencySymbol;

                        // Calculate trend data
                        const trendPercent = category.trend_percent !== undefined ? category.trend_percent : 0;
                        const trendDirection = trendPercent > 0 ? 'up' : (trendPercent < 0 ? 'down' : 'stable');
                        const trendClass = trendPercent >= 0 ? 'positive' : 'negative';
                        const trendIcon = trendPercent >= 0 ? 'up' : 'down';
                        const trendDisplay = category.trend_percent !== undefined ?
                            `<div class="metric-change ${trendClass}" style="margin-top: 5px; padding: 2px 8px; font-size: 11px;">
                                <i class="fas fa-arrow-${trendIcon}"></i> ${Math.abs(trendPercent)}% vs last period
                            </div>` : '';

                        html += `
                            <div class="product-item">
                                <div class="product-rank ${rankClass}">
                                    ${index + 1}
                                </div>
                                <div class="product-info">
                                    <div class="product-name">${categoryName}</div>
                                    <div class="product-stats">
                                        <span><i class="fas fa-box"></i> ${new Intl.NumberFormat().format(totalQuantity)} Units Sold</span>
                                        <span><i class="fas fa-cube"></i> ${new Intl.NumberFormat().format(productCount)} Products</span>
                                        <span><i class="fas fa-percent"></i> ${percentage}% of Total</span>
                                    </div>
                                    ${trendDisplay}
                                </div>
                                <div class="product-revenue">
                                    ${displayRevenue}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                } else {
                    html = `
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h3>No Category Data Available</h3>
                            <p>Start making sales to see your top categories here!</p>
                        </div>
                    `;
                }

                $('#top_categories_container').html(html);
                $('#categories_count_badge').text(categories.length + ' Categories');

                console.log('Top categories loaded successfully');
            } else {
                console.error('Failed to load top categories data');
                $('#top_categories_container').html(`
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error Loading Categories</h3>
                        <p>Unable to load category data. Please try again.</p>
                    </div>
                `);
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading top categories:', error);
            $('#top_categories_container').html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error Loading Categories</h3>
                    <p>Unable to load category data. Please try again.</p>
                </div>
            `);
        });
    }

    // Handle top categories sort change
    $('#top_categories_sort').on('change', function() {
        const sortBy = $(this).val();
        loadTopCategories(sortBy);
    });

    /**
     * Load Top Brands
     */
    function loadTopBrands(sortBy = 'revenue') {
        console.log('Loading top brands with sort:', sortBy);

        const selectedLocations = $('#location_filter').val() || [];

        $.get('{{ route("businessintelligence.dashboard.chart-data") }}', {
            chart_type: 'top_brands',
            date_range: '{{ $dateRange }}',
            sort_by: sortBy,
            location_ids: selectedLocations.length > 0 ? selectedLocations.join(',') : null
        }, function(response) {
            console.log('Top brands response:', response);

            if (response.success && response.data && response.data.brands) {
                const brands = response.data.brands;
                const totalSales = {{ $totalSales }};
                const currencySymbol = '{{ session("currency.symbol", "৳") }}';
                const symbolPlacement = '{{ session("business.currency_symbol_placement", "before") }}';
                const currencyPrecision = {{ session('business.currency_precision', 2) }};
                const decimalSeparator = '{{ session("currency.decimal_separator", ".") }}';
                const thousandSeparator = '{{ session("currency.thousand_separator", ",") }}';

                let html = '';

                if (brands.length > 0) {
                    html += '<div class="product-list">';
                    brands.forEach(function(brand, index) {
                        const rankClass = index < 3 ? 'rank-' + (index + 1) : 'rank-other';
                        const brandName = brand.brand_name || 'Unbranded';
                        const totalQuantity = brand.total_quantity || 0;
                        const totalRevenue = brand.total_revenue || 0;
                        const productCount = brand.product_count || 0;
                        const percentage = totalSales > 0 ? ((totalRevenue / totalSales) * 100).toFixed(1) : 0;

                        // Format revenue
                        const formattedRevenue = new Intl.NumberFormat('en-US', {
                            minimumFractionDigits: currencyPrecision,
                            maximumFractionDigits: currencyPrecision
                        }).format(totalRevenue).replace(/,/g, thousandSeparator).replace(/\./g, decimalSeparator);

                        const displayRevenue = symbolPlacement === 'before' ?
                            currencySymbol + formattedRevenue :
                            formattedRevenue + ' ' + currencySymbol;

                        // Calculate trend data
                        const trendPercent = brand.trend_percent !== undefined ? brand.trend_percent : 0;
                        const trendDirection = trendPercent > 0 ? 'up' : (trendPercent < 0 ? 'down' : 'stable');
                        const trendClass = trendPercent >= 0 ? 'positive' : 'negative';
                        const trendIcon = trendPercent >= 0 ? 'up' : 'down';
                        const trendDisplay = brand.trend_percent !== undefined ?
                            `<div class="metric-change ${trendClass}" style="margin-top: 5px; padding: 2px 8px; font-size: 11px;">
                                <i class="fas fa-arrow-${trendIcon}"></i> ${Math.abs(trendPercent)}% vs last period
                            </div>` : '';

                        html += `
                            <div class="product-item">
                                <div class="product-rank ${rankClass}">
                                    ${index + 1}
                                </div>
                                <div class="product-info">
                                    <div class="product-name">${brandName}</div>
                                    <div class="product-stats">
                                        <span><i class="fas fa-box"></i> ${new Intl.NumberFormat().format(totalQuantity)} Units Sold</span>
                                        <span><i class="fas fa-cube"></i> ${new Intl.NumberFormat().format(productCount)} Products</span>
                                        <span><i class="fas fa-percent"></i> ${percentage}% of Total</span>
                                    </div>
                                    ${trendDisplay}
                                </div>
                                <div class="product-revenue">
                                    ${displayRevenue}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                } else {
                    html = `
                        <div class="empty-state">
                            <i class="fas fa-tags"></i>
                            <h3>No Brand Data Available</h3>
                            <p>Start making sales to see your top brands here!</p>
                        </div>
                    `;
                }

                $('#top_brands_container').html(html);
                $('#brands_count_badge').text(brands.length + ' Brands');

                console.log('Top brands loaded successfully');
            } else {
                console.error('Failed to load top brands data');
                $('#top_brands_container').html(`
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error Loading Brands</h3>
                        <p>Unable to load brand data. Please try again.</p>
                    </div>
                `);
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading top brands:', error);
            $('#top_brands_container').html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error Loading Brands</h3>
                    <p>Unable to load brand data. Please try again.</p>
                </div>
            `);
        });
    }

    // Handle top brands sort change
    $('#top_brands_sort').on('change', function() {
        const sortBy = $(this).val();
        loadTopBrands(sortBy);
    });
});
</script>
@endsection

