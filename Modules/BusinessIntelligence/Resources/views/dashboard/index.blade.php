@extends('businessintelligence::layouts.app')

@section('page_title', __('businessintelligence::lang.dashboard'))
@section('page_subtitle', __('Ai Powered Insights'))

@section('bi_content')

<style>
/* Modern Dashboard Styles */
.bi-modern-dashboard {
    background: #f5f7fa;
    padding: 20px;
}

/* Modern KPI Cards with Gradients */
.bi-kpi-modern {
    background: linear-gradient(135deg, #5f7592ff 0%, #0ea5e9 100%);
    border-radius: 15px;
    padding: 20px;
    color: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}

.bi-kpi-modern::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 96px;
    height: 96px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transform: translate(30%, -30%);
}

.bi-kpi-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
}

/* Distinct KPI Card Colors */
.bi-kpi-modern.revenue {
    background: linear-gradient(135deg, #166534 0%, #15803d 100%);
}

.bi-kpi-modern.profit {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
}

.bi-kpi-modern.orders {
    background: linear-gradient(135deg, #9a3412 0%, #c2410c 100%);
}

.bi-kpi-modern.customers {
    background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 100%);
}

.bi-kpi-icon-modern {
    font-size: 34px;
    opacity: 0.9;
    margin-bottom: 10px;
}

.bi-kpi-value-modern {
    font-size: 26px;
    font-weight: 700;
    margin: 10px 0;
}

.bi-kpi-label-modern {
    font-size: 11px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.bi-trend-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    margin-top: 10px;
    background: rgba(255,255,255,0.2);
}

.bi-trend-badge i {
    margin-right: 5px;
}

/* Modern Chart Container */
.bi-chart-modern {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    transition: all 0.3s ease;
}

.bi-chart-modern:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.bi-chart-title {
    font-size: 18px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.bi-chart-subtitle {
    font-size: 13px;
    color: #718096;
    margin-top: -15px;
    margin-bottom: 15px;
}

/* AI Insights Panel */
.bi-insights-panel {
    background: linear-gradient(135deg, #166534 0%, #1e3a8a 100%);
    border-radius: 15px;
    padding: 30px;
    color: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    margin-bottom: 25px;
}

.bi-insights-panel h3 {
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 22px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.bi-insights-panel h3 i {
    font-size: 24px;
}

.bi-insight-item {
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    backdrop-filter: blur(10px);
    border-left: 5px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.bi-insight-item:hover {
    background: rgba(255,255,255,0.15);
    transform: translateX(5px);
}

.bi-insight-item.priority-critical {
    border-left-color: #ff6b6b;
}

.bi-insight-item.priority-high {
    border-left-color: #ffd93d;
}

.bi-insight-item.priority-medium {
    border-left-color: #6bcfff;
}

.bi-insight-item.priority-low {
    border-left-color: #6ae792;
}

.bi-insight-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
    background: rgba(255,255,255,0.2);
}

.bi-insight-icon.icon-critical {
    background: rgba(255, 107, 107, 0.3);
}

.bi-insight-icon.icon-high {
    background: rgba(255, 217, 61, 0.3);
}

.bi-insight-icon.icon-medium {
    background: rgba(107, 207, 255, 0.3);
}

.bi-insight-icon.icon-low {
    background: rgba(106, 231, 146, 0.3);
}

.bi-insight-text {
    flex: 1;
}

.bi-insight-title {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 8px;
    line-height: 1.4;
}

.bi-insight-description {
    font-size: 14px;
    opacity: 0.95;
    line-height: 1.6;
}

/* Statistics Grid */
.bi-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.bi-stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    text-align: center;
    transition: all 0.3s ease;
}

.bi-stat-card:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
}

.bi-stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #0ea5e9;
    margin: 10px 0;
}

.bi-stat-label {
    font-size: 13px;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Filter Section */
.bi-filter-section {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

/* Loading Overlay */
.bi-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.bi-loader {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #0ea5e9;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Date Range Selector Styles */
.bi-date-range-selector {
    position: relative;
}

.custom-date-inputs {
    margin-top: 10px;
}

.custom-date-inputs .form-control {
    font-size: 0.875rem;
}

/* Responsive */
@media (max-width: 768px) {
    .bi-kpi-value-modern {
        font-size: 19px;
    }

    .bi-kpi-icon-modern {
        font-size: 26px;
    }

    .bi-filter-section .col-md-6 {
        margin-bottom: 15px;
    }

    .custom-date-inputs .col-6 {
        padding-left: 5px;
        padding-right: 5px;
    }
}

@media (max-width: 576px) {
    .bi-filter-section .btn-group {
        flex-direction: column;
        width: 100%;
    }

    .bi-filter-section .btn-group .btn {
        margin-bottom: 5px;
        border-radius: 4px !important;
    }
}
</style>

<div class="bi-modern-dashboard">
    <!-- Loading Overlay -->
    <div class="bi-loading-overlay" id="loading_overlay">
        <div class="bi-loader"></div>
    </div>

    <!-- Filter Section -->
    <div class="bi-filter-section">
        <div class="row">
            <div class="col-12 col-md-6 mb-3 mb-md-0">
                <label><i class="fa fa-calendar"></i> Date Range</label>
                <div class="input-group bi-date-range-selector">
                    <input type="text" class="form-control" id="sell_list_filter_date_range" name="sell_list_filter_date_range" placeholder="Select a date range" readonly>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="date_range_info" data-toggle="tooltip" title="Click to see current date range details">
                            <i class="fa fa-info-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3 mb-3 mb-md-0">
                <label><i class="fa fa-map-marker"></i> Business Location</label>
                <select class="form-control" id="location_filter">
                    <option value="">All Locations</option>
                    @if(isset($businessLocations) && $businessLocations->count() > 0)
                        @foreach($businessLocations as $location)
                            <option value="{{ $location->id }}" {{ (isset($selectedLocation) && $selectedLocation == $location->id) ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label>&nbsp;</label><br>
                <div class="btn-group flex-wrap" role="group">
                    <button class="btn btn-primary mb-1 mb-sm-0" id="refresh_dashboard">
                        <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Refresh Data</span>
                    </button>
                    <button class="btn btn-success mb-1 mb-sm-0" id="generate_insights">
                        <i class="fa fa-magic"></i> <span class="d-none d-sm-inline">Generate AI Insights</span>
                    </button>
                    <button class="btn btn-info" id="export_dashboard">
                        <i class="fa fa-download"></i> <span class="d-none d-sm-inline">Export Report</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <small class="text-muted" id="filter_info">
                    @php
                        $dateRangeLabels = [
                            'today' => 'today',
                            'yesterday' => 'yesterday',
                            'last_7_days' => 'last 7 days',
                            'last_30_days' => 'last 30 days',
                            'this_month' => 'current month',
                            'last_month' => 'last month',
                            'this_month_last_year' => 'same month last year',
                            'this_year' => 'current year',
                            'last_year' => 'last year',
                            'current_financial_year' => 'current financial year (April 1 - March 31)',
                            'last_financial_year' => 'last financial year (April 1 - March 31)',
                            'custom' => 'custom date range'
                        ];
                        $displayLabel = $dateRangeLabels[$dateRange] ?? $dateRange;
                    @endphp
                    Showing data for {{ $displayLabel }}
                    @if(isset($selectedLocation) && $selectedLocation)
                        @php
                            $locationName = $businessLocations->where('id', $selectedLocation)->first()->name ?? 'Unknown Location';
                        @endphp
                        for location: {{ $locationName }}
                    @else
                        across all locations
                    @endif
                </small>
            </div>
        </div>
    </div>

    <!-- KPI Cards Row -->
    <div class="row">
        @foreach($kpis as $key => $kpi)
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="bi-kpi-modern {{ $key }}">
                <div class="bi-kpi-icon-modern">
                    <i class="{{ $kpi['icon'] }}"></i>
                </div>
                <div class="bi-kpi-value-modern" id="kpi_{{ $key }}">
                    @if(isset($kpi['percentage']))
                        {{ $kpi['percentage'] }}%
                    @else
                        @php
                            $formatted_value = number_format($kpi['value'], 0, session('currency.decimal_separator', '.'), session('currency.thousand_separator', ','));
                            $currency_symbol = session('currency.symbol', '৳');
                            $symbol_placement = session('business.currency_symbol_placement', 'before');
                        @endphp
                        @if(in_array($key, ['customers', 'orders', 'products']))
                            {{ $formatted_value }}
                        @else
                            @if($symbol_placement == 'before')
                                {{ $currency_symbol }}{{ $formatted_value }}
                            @else
                                {{ $formatted_value }} {{ $currency_symbol }}
                            @endif
                        @endif
                    @endif
                </div>
                <div class="bi-kpi-label-modern">{{ $kpi['label'] }}</div>
                @if(isset($kpi['trends']) && is_array($kpi['trends']))
                    @foreach($kpi['trends'] as $index => $trend)
                        @if($trend && is_array($trend))
                            @php
                                $periodLabels = ['vs prev', 'vs prev²', 'vs prev³'];
                                $periodLabel = $periodLabels[$index] ?? 'vs prev' . ($index + 1);
                            @endphp
                            <div class="bi-trend-badge" style="margin-top: 5px; font-size: 10px; padding: 2px 8px;">
                                <i class="fa fa-arrow-{{ $trend['direction'] == 'right' ? 'minus' : $trend['direction'] }}"></i>
                                {{ number_format($trend['value'], 1) }}% {{ $periodLabel }}
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Charts Row 1: Sales & Revenue -->
    <div class="row">
        <div class="col-md-8">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-line-chart"></i> Sales Trend & Revenue</span>
                    <span class="badge bg-primary">
                        @php
                            $badgeLabels = [
                                'today' => 'Today',
                                'yesterday' => 'Yesterday',
                                'last_7_days' => 'Last 7 Days',
                                'last_30_days' => 'Last 30 Days',
                                'this_month' => 'This Month',
                                'last_month' => 'Last Month',
                                'this_month_last_year' => 'This Month Last Year',
                                'this_year' => 'This Year',
                                'last_year' => 'Last Year',
                                'current_financial_year' => 'Current FY',
                                'last_financial_year' => 'Last FY',
                                'custom' => 'Custom Range'
                            ];
                            $badgeText = $badgeLabels[$dateRange] ?? ucfirst(str_replace('_', ' ', $dateRange));
                        @endphp
                        {{ $badgeText }}
                    </span>
                </div>
                <div class="bi-chart-subtitle">Daily sales performance and revenue trends</div>
                <div id="sales_trend_chart" style="height: 350px;"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-pie-chart"></i> Revenue Sources</span>
                </div>
                <div class="bi-chart-subtitle">Revenue distribution by source</div>
                <div id="revenue_sources_chart" style="height: 350px;"></div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Profit & Expenses -->
    <div class="row">
        <div class="col-md-6">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-bar-chart"></i> Profit vs Expenses</span>
                </div>
                <div class="bi-chart-subtitle">Monthly comparison of profit and expenses</div>
                <div id="profit_expense_chart" style="height: 300px;"></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-money"></i> Cash Flow Analysis</span>
                </div>
                <div class="bi-chart-subtitle">Income vs outgoing cash flow</div>
                <div id="cash_flow_chart" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    <!-- Charts Row 3: Sales, Purchase & Expense Analytics -->
    <div class="row">
        <div class="col-md-12">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-line-chart"></i> Sales, Purchase & Expense Analytics</span>
                    <span class="badge badge-info ml-2">Last 6 Months</span>
                </div>
                <div class="bi-chart-subtitle">Comprehensive monthly analysis of sales, purchases, and expenses</div>
                <div id="sales_purchase_expense_chart" style="height: 350px;"></div>
            </div>
        </div>
    </div>

    <!-- Charts Row 3.5: Comprehensive Profit & Loss Analysis -->
    <div class="row">
        <div class="col-md-8">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-calculator"></i> Profit & Loss Statement</span>
                    <span class="badge badge-success ml-2">Complete Breakdown</span>
                </div>
                <div class="bi-chart-subtitle">Complete financial performance analysis with P&L breakdown</div>
                <div id="profit_loss_complete_chart" style="height: 400px;"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-pie-chart"></i> P&L Components</span>
                </div>
                <div class="bi-chart-subtitle">Revenue, COGS, Expenses & Profit breakdown</div>
                <div id="profit_loss_breakdown_chart" style="height: 400px;"></div>
            </div>
        </div>
    </div>

    <!-- Charts Row 4: Products & Inventory -->
    <div class="row">
        <div class="col-md-6">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-trophy"></i> Top 10 Products</span>
                    <div class="float-right">
                        <select class="form-control form-control-sm" id="top_products_sort_by" style="width: auto; display: inline-block;">
                            <option value="revenue" selected>Price</option>
                            <option value="quantity">Qty</option>
                        </select>
                    </div>
                </div>
                <div class="bi-chart-subtitle" id="top_products_subtitle">Best selling products by total price volume sold</div>
                <div id="top_products_chart" style="height: 400px;"></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-cubes"></i> Inventory Status</span>
                </div>
                <div class="bi-chart-subtitle">Stock levels and inventory health</div>
                <div id="inventory_status_chart" style="height: 400px;"></div>
            </div>
        </div>
    </div>

    <!-- AI Insights & Performance Summary -->
    <div class="row">
        <div class="col-md-8">
            <div class="bi-insights-panel">
                <h3>
                    <i class="fas fa-brain"></i> AI-Powered Insights & Recommendations
                </h3>
                <div id="ai_insights_container">
                    @if(count($insights) > 0)
                        @foreach($insights as $insight)
                        <div class="bi-insight-item priority-{{ $insight->priority }}">
                            <div class="bi-insight-icon icon-{{ $insight->priority }}">
                                @if($insight->priority == 'critical')
                                    <i class="fas fa-exclamation-triangle"></i>
                                @elseif($insight->priority == 'high')
                                    <i class="fas fa-exclamation-circle"></i>
                                @elseif($insight->priority == 'medium')
                                    <i class="fas fa-info-circle"></i>
                                @else
                                    <i class="fas fa-check-circle"></i>
                                @endif
                            </div>
                            <div class="bi-insight-text">
                                <div class="bi-insight-title">{{ $insight->title }}</div>
                                <div class="bi-insight-description">{{ $insight->description }}</div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center" style="padding: 40px 20px;">
                            <i class="fas fa-magic" style="font-size: 56px; opacity: 0.4; margin-bottom: 20px; display: block;"></i>
                            <p style="font-size: 15px; margin-bottom: 20px; opacity: 0.9;">No insights generated yet. Click "Generate AI Insights" to analyze your business data.</p>
                            <button class="btn btn-warning btn-lg" id="generate_first_insights" style="border-radius: 25px; padding: 12px 30px; font-weight: 600;">
                                <i class="fas fa-magic"></i> Generate Insights Now
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-bullseye"></i> Performance Summary</span>
                </div>
                <div style="padding: 15px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding: 15px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <span style="color: #718096; font-size: 14px;">
                                <i class="fa fa-chart-line"></i> Revenue Growth
                            </span>
                            <span style="color: #20a9e4ff; font-weight: 600; font-size: 16px;" id="revenue_growth">
                                {{ isset($kpis['revenue']['trends']) && isset($kpis['revenue']['trends'][0]) ? number_format($kpis['revenue']['trends'][0]['value'], 1) : '0' }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #a0aec0;">vs previous period</div>
                    </div>

                    <div style="border-bottom: 1px solid #e2e8f0; padding: 15px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <span style="color: #718096; font-size: 14px;">
                                <i class="fa fa-money"></i> Profit Margin
                            </span>
                            <span style="color: #f5576c; font-weight: 600; font-size: 16px;" id="profit_margin_pct">
                                {{ number_format($kpis['profit']['percentage'] ?? 0, 1) }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #a0aec0;">current margin rate</div>
                    </div>

                    <div style="border-bottom: 1px solid #e2e8f0; padding: 15px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <span style="color: #718096; font-size: 14px;">
                                <i class="fa fa-shopping-cart"></i> Total Orders
                            </span>
                            <span style="color: rgba(14, 165, 233, 1); font-weight: 600; font-size: 16px;" id="order_count">
                                {{ number_format($kpis['orders']['value'] ?? 0) }}
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #a0aec0;">in selected period</div>
                    </div>

                    <div style="padding: 15px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <span style="color: #718096; font-size: 14px;">
                                <i class="fa fa-users"></i> Active Customers
                            </span>
                            <span style="color: #f5576c; font-weight: 600; font-size: 16px;" id="customer_count">
                                {{ number_format($kpis['customers']['value'] ?? 0) }}
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #a0aec0;">total customer base</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Breakdown & Customer Analytics -->
    <div class="row">
        <div class="col-md-6">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-credit-card"></i> Expense Categories</span>
                </div>
                <div class="bi-chart-subtitle">Breakdown of business expenses</div>
                <div id="expense_breakdown_chart" style="height: 350px;"></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bi-chart-modern">
                <div class="bi-chart-title">
                    <span><i class="fa fa-user-plus"></i> Customer Growth</span>
                </div>
                <div class="bi-chart-subtitle">New customers over time</div>
                <div id="customer_growth_chart" style="height: 350px;"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="{{ asset('modules/businessintelligence/js/bi-dashboard-dynamic.js') }}"></script>
<script type="text/javascript">
// Override with inline version for now

$(document).ready(function() {
    const BiDashboard = {
        selectedLocation: '{{ $selectedLocation ?? "" }}',
        currencySymbol: '{{ $currencySymbol ?? "৳" }}',
        currencyPrecision: {{ $currencyPrecision ?? 2 }},
        currencyDecimalSeparator: '{{ $currencyDecimalSeparator ?? "." }}',
        currencyThousandSeparator: '{{ $currencyThousandSeparator ?? "," }}',
        currencySymbolPlacement: '{{ session("business.currency_symbol_placement", "before") }}',
        charts: {},

        init() {
            this.setupEventListeners();
            this.initializeTooltips();
            this.initializeDateRangePicker();
            this.updateTopProductsSubtitle('revenue'); // Set initial subtitle
            this.loadAllCharts();
            this.updateFilterInfo();
        },

        initializeTooltips() {
            $('[data-toggle="tooltip"]').tooltip();
        },

        initializeDateRangePicker() {
            // Initialize daterangepicker with current values if they exist
            @if(request('start_date') && request('end_date'))
                const startDate = '{{ request("start_date") }}';
                const endDate = '{{ request("end_date") }}';
                $('#sell_list_filter_date_range').val(moment(startDate).format(moment_date_format) + ' ~ ' + moment(endDate).format(moment_date_format));
                $('#sell_list_filter_date_range').data('daterangepicker').setStartDate(moment(startDate));
                $('#sell_list_filter_date_range').data('daterangepicker').setEndDate(moment(endDate));
            @else
                // Set default to last 30 days
                const endDate = moment();
                const startDate = moment().subtract(29, 'days');
                $('#sell_list_filter_date_range').val(startDate.format(moment_date_format) + ' ~ ' + endDate.format(moment_date_format));
                $('#sell_list_filter_date_range').data('daterangepicker').setStartDate(startDate);
                $('#sell_list_filter_date_range').data('daterangepicker').setEndDate(endDate);
            @endif
        },

        setupEventListeners() {
            //Date range as a button
            $('#sell_list_filter_date_range').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                        moment_date_format));
                    this.handleDateRangeChange(start, end);
                }.bind(this)
            );
            $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#sell_list_filter_date_range').val('');
                this.handleDateRangeChange(null, null);
            }.bind(this));

            $('#location_filter').on('change', (e) => {
                const selectedValue = $(e.target).val();
                const dateRangeValue = $('#sell_list_filter_date_range').val();
                let url = '{{ route("businessintelligence.dashboard") }}?location_id=' + selectedValue;

                if (dateRangeValue) {
                    const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    url += '&start_date=' + start + '&end_date=' + end;
                }

                window.location.href = url;
            });

            $('#top_products_sort_by').on('change', (e) => {
                const sortBy = $(e.target).val();
                this.updateTopProductsSubtitle(sortBy);
                this.loadTopProductsChart();
            });

            $('#refresh_dashboard').on('click', () => {
                this.showLoading();
                this.refreshDashboard();
            });

            $('#generate_insights, #generate_first_insights').on('click', () => {
                this.generateInsights();
            });

            $('#export_dashboard').on('click', () => {
                this.exportDashboard();
            });
        },

        showLoading() {
            $('#loading_overlay').css('display', 'flex');
        },

        hideLoading() {
            $('#loading_overlay').hide();
        },

        loadAllCharts() {
            this.loadSalesTrendChart();
            this.loadRevenueSourcesChart();
            this.loadProfitExpenseChart();
            this.loadCashFlowChart();
            this.loadSalesPurchaseExpenseChart();
            this.loadProfitLossCompleteChart();
            this.loadTopProductsChart();
            this.loadInventoryStatusChart();
            this.loadExpenseBreakdownChart();
            this.loadCustomerGrowthChart();
        },

        updateKPIs() {
            this.showLoading();
            const ajaxData = {
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.kpis") }}',
                method: 'GET',
                data: ajaxData,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        const kpis = response.data;

                        // Update KPI values
                        Object.keys(kpis).forEach(key => {
                            const kpi = kpis[key];
                            const kpiElement = $(`#kpi_${key}`);

                            if (kpiElement.length > 0) {
                                let displayValue = '';
                                if (kpi.percentage) {
                                    displayValue = `${kpi.value}%`;
                                } else if (['customers', 'orders', 'products'].includes(key)) {
                                    displayValue = this.formatNumber(kpi.value);
                                } else {
                                    const formattedValue = this.formatCurrency(kpi.value);
                                    displayValue = this.currencySymbolPlacement === 'before'
                                        ? `${this.currencySymbol}${formattedValue}`
                                        : `${formattedValue} ${this.currencySymbol}`;
                                }
                                kpiElement.text(displayValue);
                            }
                        });

                        // Update specific KPIs that need special handling
                        if (kpis.profit) {
                            $('#profit_margin_pct').text(`${kpis.profit.percentage}%`);
                        }
                        if (kpis.orders) {
                            $('#order_count').text(this.formatNumber(kpis.orders.value));
                        }
                        if (kpis.customers) {
                            $('#customer_count').text(this.formatNumber(kpis.customers.value));
                        }
                        if (kpis.revenue && kpis.revenue.trends && kpis.revenue.trends[0]) {
                            $('#revenue_growth').text(`${kpis.revenue.trends[0].value}%`);
                        }
                    }
                },
                error: () => {
                    this.hideLoading();
                    console.error('Failed to update KPIs');
                }
            });
        },

        formatCurrency(value) {
            return value.toLocaleString(undefined, {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        },

        formatNumber(value) {
            return value.toLocaleString();
        },

        loadSalesTrendChart() {
            this.showLoading();
            const ajaxData = {
                chart_type: 'sales_trend',
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.chart-data") }}',
                method: 'GET',
                data: ajaxData,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        const data = response.data;
                        console.log('Sales Trend Data:', data);
                        console.log('Categories:', data.categories);
                        console.log('Sales:', data.sales);
                        const options = {
                            series: [{
                                name: 'Sales',
                                data: data.sales || []
                            }],
                            chart: {
                                type: 'area',
                                height: 350,
                                zoom: { enabled: true },
                                toolbar: { show: true }
                            },
                            dataLabels: { enabled: false },
                            stroke: { curve: 'smooth', width: 3 },
                            colors: ['#0ea5e9'],
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.7,
                                    opacityTo: 0.3,
                                }
                            },
                            xaxis: {
                                categories: data.categories || [],
                                labels: {
                                    rotate: -45,
                                    rotateAlways: false
                                }
                            },
                            tooltip: {
                                theme: 'dark',
                                y: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString(undefined, {
                                            minimumFractionDigits: BiDashboard.currencyPrecision,
                                            maximumFractionDigits: BiDashboard.currencyPrecision
                                        });
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString(undefined, {
                                            minimumFractionDigits: BiDashboard.currencyPrecision,
                                            maximumFractionDigits: BiDashboard.currencyPrecision
                                        });
                                    }
                                }
                            }
                        };
                        if (this.charts.salesTrend) this.charts.salesTrend.destroy();
                        this.charts.salesTrend = new ApexCharts(document.querySelector("#sales_trend_chart"), options);
                        this.charts.salesTrend.render();
                    }
                },
                error: () => {
                    this.hideLoading();
                    console.error('Failed to load sales trend chart');
                }
            });
        },

        loadRevenueSourcesChart() {
            // Prevent multiple simultaneous requests
            if (this.loadingRevenueSources) {
                return;
            }
            this.loadingRevenueSources = true;

            const ajaxData = {
                chart_type: 'revenue_sources',
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.chart-data") }}',
                method: 'GET',
                data: ajaxData,
                success: (response) => {
                    this.loadingRevenueSources = false;
                    if (response.success) {
                        const data = response.data;

                        // Clear the chart container first
                        const container = document.querySelector("#revenue_sources_chart");
                        if (container) {
                            container.innerHTML = '';
                        }

                        // Properly destroy existing chart
                        if (this.charts.revenueSources) {
                            try {
                                this.charts.revenueSources.destroy();
                            } catch (e) {
                                console.warn('Error destroying revenue sources chart:', e);
                            }
                            this.charts.revenueSources = null;
                        }

                        const options = {
                            series: data.series || [],
                            chart: {
                                type: 'donut',
                                height: 350,
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 800
                                }
                            },
                            labels: data.labels || [],
                            colors: ['#0ea5e9', '#f5576c'],
                            legend: { position: 'bottom' },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            show: true,
                                            total: {
                                                show: true,
                                                label: 'Total Revenue',
                                                formatter: (w) => {
                                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                                    return BiDashboard.currencySymbol + total.toLocaleString(undefined, {
                                                        minimumFractionDigits: BiDashboard.currencyPrecision,
                                                        maximumFractionDigits: BiDashboard.currencyPrecision
                                                    });
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            tooltip: {
                                theme: 'dark',
                                y: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString(undefined, {
                                            minimumFractionDigits: BiDashboard.currencyPrecision,
                                            maximumFractionDigits: BiDashboard.currencyPrecision
                                        });
                                    }
                                }
                            }
                        };

                        try {
                            this.charts.revenueSources = new ApexCharts(container, options);
                            this.charts.revenueSources.render();
                        } catch (error) {
                            console.error('Error rendering revenue sources chart:', error);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    this.loadingRevenueSources = false;
                    console.error('Failed to load revenue sources chart:', error);
                }
            });
        },

        loadProfitExpenseChart() {
            // Prevent multiple simultaneous requests
            if (this.loadingProfitExpense) {
                return;
            }
            this.loadingProfitExpense = true;

            console.log('Loading Profit vs Expenses chart...');
            const ajaxData = {
                chart_type: 'profit_expense',
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.chart-data") }}',
                method: 'GET',
                data: ajaxData,
                success: (response) => {
                    this.loadingProfitExpense = false;
                    console.log('Profit Expense AJAX Response:', response);
                    if (response.success) {
                        const data = response.data;
                        console.log('Profit vs Expenses Data:', data);
                        console.log('Categories:', data.categories);
                        console.log('Profit:', data.profit);
                        console.log('Expenses:', data.expenses);

                        // Clear the chart container first
                        const container = document.querySelector("#profit_expense_chart");
                        if (container) {
                            container.innerHTML = '';
                        }

                        // Properly destroy existing chart
                        if (this.charts.profitExpense) {
                            try {
                                this.charts.profitExpense.destroy();
                            } catch (e) {
                                console.warn('Error destroying profit expense chart:', e);
                            }
                            this.charts.profitExpense = null;
                        }

                        if (!container) {
                            console.error('Profit expense chart container not found!');
                            return;
                        }

                        const options = {
                            series: [{
                                name: 'Profit',
                                data: data.profit || []
                            }, {
                                name: 'Expenses',
                                data: data.expenses || []
                            }],
                            chart: {
                                type: 'bar',
                                height: 300,
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 800
                                }
                            },
                            colors: ['#0ea5e9', '#f5576c'],
                            plotOptions: {
                                bar: {
                                    horizontal: false,
                                    columnWidth: '55%',
                                    endingShape: 'rounded'
                                },
                            },
                            dataLabels: { enabled: false },
                            xaxis: {
                                categories: data.categories || [],
                            },
                            yaxis: {
                                labels: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString(undefined, {
                                            minimumFractionDigits: BiDashboard.currencyPrecision,
                                            maximumFractionDigits: BiDashboard.currencyPrecision
                                        });
                                    }
                                }
                            },
                            tooltip: {
                                theme: 'dark',
                                shared: true,
                                intersect: false,
                                y: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString(undefined, {
                                            minimumFractionDigits: BiDashboard.currencyPrecision,
                                            maximumFractionDigits: BiDashboard.currencyPrecision
                                        });
                                    }
                                }
                            }
                        };

                        console.log('Chart options:', options);

                        try {
                            console.log('Creating new chart...');
                            this.charts.profitExpense = new ApexCharts(container, options);
                            this.charts.profitExpense.render();
                            console.log('Chart rendered successfully!');
                        } catch (error) {
                            console.error('Error rendering chart:', error);
                        }
                    } else {
                        console.error('Response not successful:', response);
                    }
                },
                error: (xhr, status, error) => {
                    this.loadingProfitExpense = false;
                    console.error('Failed to load profit expense chart');
                    console.error('XHR:', xhr);
                    console.error('Status:', status);
                    console.error('Error:', error);
                }
            });
        },

        loadCashFlowChart() {
            // Prevent multiple simultaneous requests
            if (this.loadingCashFlow) {
                return;
            }
            this.loadingCashFlow = true;

            const ajaxData = {
                chart_type: 'cash_flow',
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.chart-data") }}',
                method: 'GET',
                data: ajaxData,
                success: (response) => {
                    this.loadingCashFlow = false;
                    if (response.success) {
                        const data = response.data;

                        // Clear the chart container first
                        const container = document.querySelector("#cash_flow_chart");
                        if (container) {
                            container.innerHTML = '';
                        }

                        // Properly destroy existing chart
                        if (this.charts.cashFlow) {
                            try {
                                this.charts.cashFlow.destroy();
                            } catch (e) {
                                console.warn('Error destroying cash flow chart:', e);
                            }
                            this.charts.cashFlow = null;
                        }

                        const options = {
                            series: data.series || [],
                            chart: {
                                type: 'line',
                                height: 300,
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 800
                                }
                            },
                            colors: ['#0ea5e9', '#f5576c'],
                            stroke: { width: [4, 4], curve: 'smooth' },
                            xaxis: {
                                categories: data.categories || []
                            },
                            yaxis: {
                                labels: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString(undefined, {
                                            minimumFractionDigits: BiDashboard.currencyPrecision,
                                            maximumFractionDigits: BiDashboard.currencyPrecision
                                        });
                                    }
                                }
                            },
                            tooltip: {
                                theme: 'dark',
                                shared: true,
                                intersect: false,
                                y: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString(undefined, {
                                            minimumFractionDigits: BiDashboard.currencyPrecision,
                                            maximumFractionDigits: BiDashboard.currencyPrecision
                                        });
                                    }
                                }
                            }
                        };

                        try {
                            this.charts.cashFlow = new ApexCharts(container, options);
                            this.charts.cashFlow.render();
                        } catch (error) {
                            console.error('Error rendering cash flow chart:', error);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    this.loadingCashFlow = false;
                    console.error('Failed to load cash flow chart:', error);
                }
            });
        },

        loadProfitLossCompleteChart() {
            console.log('Loading Comprehensive Profit & Loss chart...');
            const ajaxData = {
                chart_type: 'profit_loss_complete',
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.chart-data") }}',
                method: 'GET',
                data: ajaxData,
                success: (response) => {
                    console.log('P&L Complete Response:', response);
                    if (response.success) {
                        const data = response.data;
                        console.log('P&L Data:', data);
                        
                        // Main P&L Chart (Column Chart)
                        const mainChartOptions = {
                            series: [{
                                name: 'Amount',
                                data: data.data.map(item => ({
                                    x: item.name,
                                    y: item.value,
                                    fillColor: item.color
                                }))
                            }],
                            chart: {
                                type: 'bar',
                                height: 400,
                                toolbar: {
                                    show: true,
                                    tools: {
                                        download: true
                                    }
                                },
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 1000,
                                    animateGradually: {
                                        enabled: true,
                                        delay: 200
                                    }
                                }
                            },
                            plotOptions: {
                                bar: {
                                    horizontal: false,
                                    columnWidth: '60%',
                                    endingShape: 'rounded',
                                    borderRadius: 8,
                                    dataLabels: {
                                        position: 'top'
                                    },
                                    distributed: true
                                }
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: function (val) {
                                    return BiDashboard.currencySymbol + val.toLocaleString();
                                },
                                offsetY: -25,
                                style: {
                                    fontSize: '13px',
                                    colors: ['#304758'],
                                    fontWeight: 'bold'
                                }
                            },
                            xaxis: {
                                labels: {
                                    style: {
                                        colors: data.data.map(item => item.color),
                                        fontSize: '13px',
                                        fontWeight: 600
                                    }
                                }
                            },
                            yaxis: {
                                title: {
                                    text: 'Amount (' + BiDashboard.currencySymbol + ')',
                                    style: {
                                        fontSize: '14px',
                                        fontWeight: 600
                                    }
                                },
                                labels: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString();
                                    }
                                }
                            },
                            legend: {
                                show: false
                            },
                            tooltip: {
                                theme: 'dark',
                                y: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString(undefined, {
                                            minimumFractionDigits: BiDashboard.currencyPrecision,
                                            maximumFractionDigits: BiDashboard.currencyPrecision
                                        });
                                    }
                                }
                            },
                            grid: {
                                borderColor: '#f1f1f1',
                                strokeDashArray: 4
                            }
                        };
                        
                        // Breakdown Donut Chart
                        const breakdown = data.detailed_breakdown;
                        const breakdownOptions = {
                            series: [
                                breakdown.revenue.net_revenue,
                                breakdown.cogs.total_cogs,
                                breakdown.expenses.total_expenses,
                                Math.abs(breakdown.profit.net_profit)
                            ],
                            chart: {
                                type: 'donut',
                                height: 400,
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 1200
                                }
                            },
                            labels: ['Revenue', 'COGS', 'Expenses', 'Net Profit'],
                            colors: ['#0ea5e9', '#f5576c'],
                            dataLabels: {
                                enabled: true,
                                formatter: function (val, opts) {
                                    return val.toFixed(1) + '%';
                                },
                                style: {
                                    fontSize: '14px',
                                    fontWeight: 'bold',
                                    colors: ['#fff']
                                },
                                dropShadow: {
                                    enabled: true,
                                    top: 1,
                                    left: 1,
                                    blur: 1,
                                    opacity: 0.45
                                }
                            },
                            legend: {
                                position: 'bottom',
                                fontSize: '13px',
                                fontWeight: 500,
                                markers: {
                                    width: 12,
                                    height: 12,
                                    radius: 3
                                }
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '65%',
                                        labels: {
                                            show: true,
                                            name: {
                                                show: true,
                                                fontSize: '18px',
                                                fontWeight: 600,
                                                offsetY: -10
                                            },
                                            value: {
                                                show: true,
                                                fontSize: '22px',
                                                fontWeight: 'bold',
                                                offsetY: 10,
                                                formatter: function (val) {
                                                    return BiDashboard.currencySymbol + parseFloat(val).toLocaleString();
                                                }
                                            },
                                            total: {
                                                show: true,
                                                showAlways: true,
                                                label: 'Total Revenue',
                                                fontSize: '16px',
                                                fontWeight: 600,
                                                color: '#373d3f',
                                                formatter: function (w) {
                                                    return BiDashboard.currencySymbol + breakdown.revenue.net_revenue.toLocaleString();
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            tooltip: {
                                theme: 'dark',
                                y: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString(undefined, {
                                            minimumFractionDigits: BiDashboard.currencyPrecision,
                                            maximumFractionDigits: BiDashboard.currencyPrecision
                                        });
                                    }
                                }
                            },
                            responsive: [{
                                breakpoint: 480,
                                options: {
                                    chart: {
                                        width: 300
                                    },
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }]
                        };
                        
                        // Destroy and render main chart
                        if (this.charts.profitLossComplete) {
                            this.charts.profitLossComplete.destroy();
                        }
                        this.charts.profitLossComplete = new ApexCharts(
                            document.querySelector("#profit_loss_complete_chart"),
                            mainChartOptions
                        );
                        this.charts.profitLossComplete.render();
                        
                        // Destroy and render breakdown chart
                        if (this.charts.profitLossBreakdown) {
                            this.charts.profitLossBreakdown.destroy();
                        }
                        this.charts.profitLossBreakdown = new ApexCharts(
                            document.querySelector("#profit_loss_breakdown_chart"),
                            breakdownOptions
                        );
                        this.charts.profitLossBreakdown.render();
                        
                        console.log('P&L charts rendered successfully!');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Failed to load profit & loss chart');
                    console.error('Error:', error);
                }
            });
        },

        loadSalesPurchaseExpenseChart() {
            console.log('Loading Sales, Purchase & Expense Analytics chart...');
            const ajaxData = {
                chart_type: 'sales_purchase_expense_analytics',
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.chart-data") }}',
                method: 'GET',
                data: ajaxData,
                success: (response) => {
                    console.log('Sales Purchase Expense Response:', response);
                    if (response.success) {
                        const data = response.data;
                        console.log('Chart Data:', data);
                        
                        const options = {
                            series: [
                                {
                                    name: 'Sales',
                                    data: data.sales || []
                                },
                                {
                                    name: 'Purchases',
                                    data: data.purchases || []
                                },
                                {
                                    name: 'Expenses',
                                    data: data.expenses || []
                                }
                            ],
                            chart: {
                                type: 'bar',
                                height: 380,
                                toolbar: {
                                    show: true,
                                    tools: {
                                        download: true,
                                        selection: true,
                                        zoom: true,
                                        zoomin: true,
                                        zoomout: true,
                                        pan: true,
                                        reset: true
                                    },
                                    export: {
                                        csv: {
                                            filename: 'sales-purchase-expense-analytics',
                                        },
                                        svg: {
                                            filename: 'sales-purchase-expense-analytics',
                                        },
                                        png: {
                                            filename: 'sales-purchase-expense-analytics',
                                        }
                                    }
                                },
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 800,
                                    animateGradually: {
                                        enabled: true,
                                        delay: 150
                                    },
                                    dynamicAnimation: {
                                        enabled: true,
                                        speed: 350
                                    }
                                }
                            },
                            plotOptions: {
                                bar: {
                                    horizontal: false,
                                    columnWidth: '65%',
                                    endingShape: 'rounded',
                                    borderRadius: 8,
                                    dataLabels: {
                                        position: 'top'
                                    }
                                }
                            },
                            dataLabels: {
                                enabled: false
                            },
                            colors: ['#0ea5e9', '#f5576c'],
                            stroke: {
                                show: true,
                                width: 2,
                                colors: ['transparent']
                            },
                            xaxis: {
                                categories: data.categories || [],
                                labels: {
                                    style: {
                                        colors: '#666',
                                        fontSize: '12px',
                                        fontFamily: 'Arial, sans-serif',
                                        fontWeight: 500,
                                    },
                                    rotate: -45,
                                    rotateAlways: false
                                },
                                axisBorder: {
                                    show: true,
                                    color: '#e0e0e0'
                                },
                                axisTicks: {
                                    show: true,
                                    color: '#e0e0e0'
                                }
                            },
                            yaxis: {
                                title: {
                                    text: 'Amount (' + BiDashboard.currencySymbol + ')',
                                    style: {
                                        color: '#666',
                                        fontSize: '14px',
                                        fontFamily: 'Arial, sans-serif',
                                        fontWeight: 600,
                                    }
                                },
                                labels: {
                                    style: {
                                        colors: '#666',
                                        fontSize: '12px'
                                    },
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString();
                                    }
                                }
                            },
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shade: 'light',
                                    type: 'vertical',
                                    shadeIntensity: 0.5,
                                    gradientToColors: ['#0ea5e9', '#f5576c'],
                                    inverseColors: false,
                                    opacityFrom: 0.95,
                                    opacityTo: 0.75,
                                    stops: [0, 100]
                                }
                            },
                            tooltip: {
                                theme: 'dark',
                                shared: true,
                                intersect: false,
                                style: {
                                    fontSize: '13px',
                                    fontFamily: 'Arial, sans-serif'
                                },
                                y: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString(undefined, {
                                            minimumFractionDigits: BiDashboard.currencyPrecision,
                                            maximumFractionDigits: BiDashboard.currencyPrecision
                                        });
                                    }
                                },
                                marker: {
                                    show: true
                                }
                            },
                            legend: {
                                position: 'top',
                                horizontalAlign: 'right',
                                floating: false,
                                offsetY: 0,
                                offsetX: 0,
                                fontSize: '14px',
                                fontFamily: 'Arial, sans-serif',
                                fontWeight: 500,
                                markers: {
                                    width: 14,
                                    height: 14,
                                    strokeWidth: 0,
                                    strokeColor: '#fff',
                                    radius: 3
                                },
                                itemMargin: {
                                    horizontal: 10,
                                    vertical: 0
                                }
                            },
                            grid: {
                                show: true,
                                borderColor: '#f1f1f1',
                                strokeDashArray: 4,
                                position: 'back',
                                xaxis: {
                                    lines: {
                                        show: false
                                    }
                                },   
                                yaxis: {
                                    lines: {
                                        show: true
                                    }
                                },
                                row: {
                                    colors: ['#fafafa', 'transparent'],
                                    opacity: 0.5
                                },
                                padding: {
                                    top: 0,
                                    right: 10,
                                    bottom: 0,
                                    left: 10
                                }
                            },
                            states: {
                                hover: {
                                    filter: {
                                        type: 'darken',
                                        value: 0.85
                                    }
                                },
                                active: {
                                    filter: {
                                        type: 'darken',
                                        value: 0.75
                                    }
                                }
                            }
                        };
                        
                        if (this.charts.salesPurchaseExpense) {
                            this.charts.salesPurchaseExpense.destroy();
                        }
                        this.charts.salesPurchaseExpense = new ApexCharts(
                            document.querySelector("#sales_purchase_expense_chart"), 
                            options
                        );
                        this.charts.salesPurchaseExpense.render();
                        console.log('Chart rendered successfully!');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Failed to load sales purchase expense chart');
                    console.error('Error:', error);
                }
            });
        },

        loadTopProductsChart() {
            // Prevent multiple simultaneous requests
            if (this.loadingTopProducts) {
                return;
            }
            this.loadingTopProducts = true;

            const sortBy = $('#top_products_sort_by').val();
            const ajaxData = {
                chart_type: 'top_products',
                location_id: this.selectedLocation,
                sort_by: sortBy
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.chart-data") }}',
                method: 'GET',
                data: ajaxData,
                success: (response) => {
                    this.loadingTopProducts = false;
                    if (response.success) {
                        const data = response.data;

                        // Clear the chart container first
                        const container = document.querySelector("#top_products_chart");
                        if (container) {
                            container.innerHTML = '';
                        }

                        // Properly destroy existing chart
                        if (this.charts.topProducts) {
                            try {
                                this.charts.topProducts.destroy();
                            } catch (e) {
                                console.warn('Error destroying top products chart:', e);
                            }
                            this.charts.topProducts = null;
                        }

                        const isRevenue = sortBy === 'revenue';
                        const options = {
                            series: [{
                                data: data.data || []
                            }],
                            chart: {
                                type: 'bar',
                                height: 400,
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 800
                                }
                            },
                            plotOptions: {
                                bar: {
                                    borderRadius: 4,
                                    horizontal: true,
                                }
                            },
                            colors: ['#0ea5e9'],
                            dataLabels: {
                                enabled: true,
                                formatter: function (value) {
                                    if (isRevenue) {
                                        return BiDashboard.currencySymbol + value.toFixed(BiDashboard.currencyPrecision);
                                    } else {
                                        return value.toFixed(0);
                                    }
                                }
                            },
                            xaxis: {
                                categories: data.categories || []
                            },
                            tooltip: {
                                theme: 'dark',
                                y: {
                                    formatter: function (value) {
                                        if (isRevenue) {
                                            return BiDashboard.currencySymbol + value.toFixed(BiDashboard.currencyPrecision);
                                        } else {
                                            return value.toFixed(0) + ' units';
                                        }
                                    }
                                }
                            }
                        };

                        try {
                            this.charts.topProducts = new ApexCharts(container, options);
                            this.charts.topProducts.render();
                        } catch (error) {
                            console.error('Error rendering top products chart:', error);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    this.loadingTopProducts = false;
                    console.error('Failed to load top products chart:', error);
                }
            });
        },

        loadInventoryStatusChart() {
            // Prevent multiple simultaneous requests
            if (this.loadingInventoryStatus) {
                return;
            }
            this.loadingInventoryStatus = true;

            const ajaxData = {
                chart_type: 'inventory_status',
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.chart-data") }}',
                method: 'GET',
                data: ajaxData,
                success: (response) => {
                    this.loadingInventoryStatus = false;
                    if (response.success) {
                        const data = response.data;

                        // Clear the chart container first
                        const container = document.querySelector("#inventory_status_chart");
                        if (container) {
                            container.innerHTML = '';
                        }

                        // Properly destroy existing chart
                        if (this.charts.inventoryStatus) {
                            try {
                                this.charts.inventoryStatus.destroy();
                            } catch (e) {
                                console.warn('Error destroying inventory status chart:', e);
                            }
                            this.charts.inventoryStatus = null;
                        }

                        const options = {
                            series: data.series || [],
                            chart: {
                                type: 'donut',
                                height: 400,
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 800
                                }
                            },
                            labels: data.labels || [],
                            colors: ['#0ea5e9', '#f5576c'],
                            legend: { position: 'bottom' },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            show: true,
                                            total: {
                                                show: true,
                                                label: 'Total Items',
                                                formatter: (w) => {
                                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                                    return total.toString();
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            tooltip: {
                                theme: 'dark',
                                y: {
                                    formatter: function (value) {
                                        return value.toString();
                                    }
                                }
                            }
                        };

                        try {
                            this.charts.inventoryStatus = new ApexCharts(container, options);
                            this.charts.inventoryStatus.render();
                        } catch (error) {
                            console.error('Error rendering inventory status chart:', error);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    this.loadingInventoryStatus = false;
                    console.error('Failed to load inventory status chart:', error);
                }
            });
        },

        loadExpenseBreakdownChart() {
            // Prevent multiple simultaneous requests
            if (this.loadingExpenseBreakdown) {
                return;
            }
            this.loadingExpenseBreakdown = true;

            const ajaxData = {
                chart_type: 'expense_breakdown',
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.chart-data") }}',
                method: 'GET',
                data: ajaxData,
                success: (response) => {
                    this.loadingExpenseBreakdown = false;
                    if (response.success) {
                        const data = response.data;

                        // Clear the chart container first
                        const container = document.querySelector("#expense_breakdown_chart");
                        if (container) {
                            container.innerHTML = '';
                        }

                        // Properly destroy existing chart
                        if (this.charts.expenseBreakdown) {
                            try {
                                this.charts.expenseBreakdown.destroy();
                            } catch (e) {
                                console.warn('Error destroying expense breakdown chart:', e);
                            }
                            this.charts.expenseBreakdown = null;
                        }

                        const options = {
                            series: data.series || [],
                            chart: {
                                type: 'pie',
                                height: 350,
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 800
                                }
                            },
                            labels: data.labels || [],
                            colors: ['#0ea5e9', '#f5576c'],
                            legend: { position: 'bottom' },
                            tooltip: {
                                theme: 'dark',
                                y: {
                                    formatter: function (value) {
                                        return BiDashboard.currencySymbol + value.toLocaleString(undefined, {
                                            minimumFractionDigits: BiDashboard.currencyPrecision,
                                            maximumFractionDigits: BiDashboard.currencyPrecision
                                        });
                                    }
                                }
                            }
                        };

                        try {
                            this.charts.expenseBreakdown = new ApexCharts(container, options);
                            this.charts.expenseBreakdown.render();
                        } catch (error) {
                            console.error('Error rendering expense breakdown chart:', error);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    this.loadingExpenseBreakdown = false;
                    console.error('Failed to load expense breakdown chart:', error);
                }
            });
        },

        loadCustomerGrowthChart() {
            // Prevent multiple simultaneous requests
            if (this.loadingCustomerGrowth) {
                return;
            }
            this.loadingCustomerGrowth = true;

            const ajaxData = {
                chart_type: 'customer_growth',
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.chart-data") }}',
                method: 'GET',
                data: ajaxData,
                success: (response) => {
                    this.loadingCustomerGrowth = false;
                    if (response.success) {
                        const data = response.data;

                        // Clear the chart container first
                        const container = document.querySelector("#customer_growth_chart");
                        if (container) {
                            container.innerHTML = '';
                        }

                        // Properly destroy existing chart
                        if (this.charts.customerGrowth) {
                            try {
                                this.charts.customerGrowth.destroy();
                            } catch (e) {
                                console.warn('Error destroying customer growth chart:', e);
                            }
                            this.charts.customerGrowth = null;
                        }

                        const options = {
                            series: [{
                                name: 'New Customers',
                                data: data.data || []
                            }],
                            chart: {
                                type: 'line',
                                height: 350,
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 800
                                }
                            },
                            stroke: { width: 5, curve: 'smooth' },
                            colors: ['#0ea5e9'],
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shade: 'dark',
                                    gradientToColors: ['#f5576c'],
                                    shadeIntensity: 1,
                                    type: 'horizontal',
                                    opacityFrom: 1,
                                    opacityTo: 1,
                                }
                            },
                            xaxis: {
                                categories: data.categories || []
                            },
                            tooltip: {
                                theme: 'dark',
                                y: {
                                    formatter: function (value) {
                                        return value.toString();
                                    }
                                }
                            }
                        };

                        try {
                            this.charts.customerGrowth = new ApexCharts(container, options);
                            this.charts.customerGrowth.render();
                        } catch (error) {
                            console.error('Error rendering customer growth chart:', error);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    this.loadingCustomerGrowth = false;
                    console.error('Failed to load customer growth chart:', error);
                }
            });
        },

        refreshDashboard() {
            this.showLoading();
            // Use AJAX to refresh data instead of full page reload
            const ajaxData = {
                _token: '{{ csrf_token() }}',
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.dashboard.refresh") }}',
                method: 'POST',
                data: ajaxData,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        // Reload the page to show fresh data
                        window.location.reload();
                    } else {
                        console.error('Refresh failed:', response.message);
                        this.hideLoading();
                    }
                },
                error: (xhr, status, error) => {
                    this.hideLoading();
                    console.error('Refresh error:', error);
                    // Fallback to page reload
                    window.location.reload();
                }
            });
        },

        generateInsights() {
            this.showLoading();
            const ajaxData = {
                _token: '{{ csrf_token() }}',
                location_id: this.selectedLocation
            };

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                ajaxData.start_date = start;
                ajaxData.end_date = end;
            }

            $.ajax({
                url: '{{ route("businessintelligence.insights.generate") }}',
                method: 'POST',
                data: ajaxData,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        swal('Success', 'AI Insights generated successfully!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    }
                },
                error: () => {
                    this.hideLoading();
                    swal('Error', 'Failed to generate insights', 'error');
                }
            });
        },

        exportDashboard() {
            let url = '{{ route("businessintelligence.dashboard.export") }}?location_id=' + this.selectedLocation;

            // Add date range if available
            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                url += '&start_date=' + start + '&end_date=' + end;
            }

            window.location.href = url;
        },

        handleDateRangeChange(start, end) {
            const locationValue = $('#location_filter').val();
            let url = '{{ route("businessintelligence.dashboard") }}?location_id=' + locationValue;

            if (start && end) {
                const startDate = start.format('YYYY-MM-DD');
                const endDate = end.format('YYYY-MM-DD');
                url += '&start_date=' + startDate + '&end_date=' + endDate;
            }

            window.location.href = url;
        },

        updateTopProductsSubtitle(sortBy) {
            const subtitleElement = $('#top_products_subtitle');
            const subtitleText = sortBy === 'revenue'
                ? 'Best selling products by total price volume sold'
                : 'Best selling products by total quantity volume sold';
            subtitleElement.text(subtitleText);
        },

        updateFilterInfo() {
            const infoElement = $('#filter_info');
            let infoText = '';

            if ($('#sell_list_filter_date_range').val()) {
                const start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('MMM DD, YYYY');
                const end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('MMM DD, YYYY');
                infoText = 'Showing data from ' + start + ' to ' + end;
            } else {
                infoText = 'Showing data for last 30 days';
            }

            if (this.selectedLocation) {
                // Get location name from the select option
                const locationName = $('#location_filter option:selected').text().trim();
                infoText += ' for location: ' + locationName;
            } else {
                infoText += ' across all locations';
            }

            infoElement.text(infoText);
        }
    };

    BiDashboard.init();
});
</script>
@endsection

