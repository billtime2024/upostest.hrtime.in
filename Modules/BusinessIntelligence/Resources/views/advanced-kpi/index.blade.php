@extends('businessintelligence::layouts.app')

@section('page_title', __('Advanced KPI Analytics'))
@section('page_subtitle', __('Customer Lifetime Value • Market Basket Analysis • Seasonality & Trends • Churn Analysis'))

@php
/**
 * Format number in Indian currency format
 * @param float $number
 * @return string
 */
function formatIndianCurrency($number) {
    if ($number >= 10000000) { // 1 crore
        return number_format($number / 10000000, 2) . ' Cr';
    } elseif ($number >= 100000) { // 1 lakh
        return number_format($number / 100000, 2) . ' L';
    } elseif ($number >= 1000) { // 1 thousand
        return number_format($number / 1000, 2) . ' Th';
    } else {
        return number_format($number, 0);
    }
}
@endphp

@section('bi_content')

<style>
/* Advanced KPI Dashboard Styles */
.advanced-kpi-dashboard {
    background: #f5f7fa;
    padding: 20px;
}

/* Advanced KPI Cards */
.advanced-kpi-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    transition: all 0.3s ease;
}

.advanced-kpi-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.advanced-kpi-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f1f5f9;
}

.advanced-kpi-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-right: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.advanced-kpi-title {
    font-size: 20px;
    font-weight: 600;
    color: #2d3748;
    margin: 0;
}

.advanced-kpi-subtitle {
    font-size: 14px;
    color: #718096;
    margin: 5px 0 0 0;
}

/* Metric Cards */
.metric-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.metric-card {
    background: #f8fafc;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    border: 1px solid #e2e8f0;
}

.metric-value {
    font-size: 28px;
    font-weight: 700;
    color: #0ea5e9;
    margin: 10px 0;
}

.metric-label {
    font-size: 13px;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Chart Containers */
.chart-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.chart-title {
    font-size: 16px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 15px;
}

/* Customer Segments */
.segment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.segment-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
}

.segment-name {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 5px;
}

.segment-value {
    font-size: 18px;
    font-weight: 700;
}

/* Association Rules */
.association-list {
    max-height: 300px;
    overflow-y: auto;
}

.association-item {
    background: #f8fafc;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    border-left: 4px solid #0ea5e9;
}

.association-rule {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 5px;
}

.association-metrics {
    font-size: 12px;
    color: #718096;
}

/* Seasonality Indicators */
.seasonality-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
    margin-top: 15px;
}

.seasonality-item {
    background: #f8fafc;
    border-radius: 8px;
    padding: 12px;
    text-align: center;
}

.seasonality-month {
    font-weight: 600;
    color: #2d3748;
    font-size: 14px;
}

.seasonality-index {
    font-size: 16px;
    font-weight: 700;
    margin: 5px 0;
}

.seasonality-type {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Loading and Error States */
.loading-state {
    text-align: center;
    padding: 40px;
    color: #718096;
}

.error-state {
    background: #fed7d7;
    color: #c53030;
    border-radius: 8px;
    padding: 15px;
    margin: 10px 0;
}

/* CLV Controls */
.clv-controls {
    margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .metric-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .segment-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .seasonality-grid {
        grid-template-columns: repeat(3, 1fr);
    }

    .clv-controls .col-md-6 {
        margin-bottom: 10px;
    }

    .clv-controls .text-md-end {
        text-align: start !important;
    }
}
</style>

<div class="advanced-kpi-dashboard">
    <!-- Filter Section -->
    <div class="bi-filter-section">
        <div class="row">
            <div class="col-12 col-md-6 mb-3 mb-md-0">
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
                <button class="btn btn-primary" id="refresh_advanced_kpi">
                    <i class="fa fa-refresh"></i> Refresh Analytics
                </button>
            </div>
        </div>
    </div>

    <!-- Customer Lifetime Value Analysis -->
    <div class="advanced-kpi-card">
        <div class="advanced-kpi-header">
            <div class="advanced-kpi-icon">
                <i class="fas fa-users-cog"></i>
            </div>
            <div>
                <h3 class="advanced-kpi-title">Customer Lifetime Value (CLV)</h3>
                <p class="advanced-kpi-subtitle">Predict future customer value and identify high-value segments</p>
            </div>
        </div>

        <!-- CLV Controls -->
        <div class="row mb-3">
            <div class="col-12 text-end">
                <button class="btn btn-success btn-sm" id="export_clv_excel">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
            </div>
        </div>

        @if(isset($clvData) && $clvData['total_customers_analyzed'] > 0)
            <div class="metric-grid">
                <div class="metric-card" data-bs-toggle="tooltip" title="Average Customer Lifetime Value across all analyzed customers">
                    <div class="metric-value">{{ number_format($clvData['average_clv'], 0) }}</div>
                    <div class="metric-label">Average CLV</div>
                </div>
                <div class="metric-card" data-bs-toggle="tooltip" title="Total number of customers with purchase history analyzed">
                    <div class="metric-value">{{ $clvData['total_customers_analyzed'] }}</div>
                    <div class="metric-label">Customers Analyzed</div>
                </div>
                <div class="metric-card" data-bs-toggle="tooltip" title="Customers with CLV above {{ number_format(50000, 0) }}">
                    <div class="metric-value">{{ $clvData['clv_segments']['high_value']['count'] }}</div>
                    <div class="metric-label">High-Value Customers</div>
                </div>
                <div class="metric-card" data-bs-toggle="tooltip" title="Average CLV for high-value customer segment">
                    <div class="metric-value">{{ number_format($clvData['clv_segments']['high_value']['avg_clv'], 0) }}</div>
                    <div class="metric-label">Avg High-Value CLV</div>
                </div>
            </div>

            <div class="segment-grid">
                @foreach($clvData['clv_segments'] as $segment => $data)
                    <div class="segment-card" style="background: linear-gradient(135deg,
                        @if($segment == 'high_value') #667eea 0%, #764ba2 100%
                        @elseif($segment == 'medium_value') #f093fb 0%, #f5576c 100%
                        @elseif($segment == 'low_value') #4facfe 0%, #00f2fe 100%
                        @else #43e97b 0%, #38f9d7 100%
                        @endif
                    );">
                        <div class="segment-name">{{ ucfirst(str_replace('_', ' ', $segment)) }}</div>
                        <div class="segment-value">{{ $data['count'] }}</div>
                        <div style="font-size: 12px; opacity: 0.9;">{{ number_format($data['avg_clv'], 0) }} avg</div>
                    </div>
                @endforeach
            </div>

            @if(count($clvData['top_customers']) > 0)
                <div class="chart-container">
                    <div class="chart-title" id="clv_chart_title">Top 10 Customers by CLV</div>
                    <div id="clv_top_customers_chart" style="height: 300px;"></div>
                </div>
            @endif
        @else
            <div class="loading-state">
                <i class="fas fa-chart-line fa-3x" style="margin-bottom: 20px; opacity: 0.5;"></i>
                <p>No customer data available for CLV analysis</p>
            </div>
        @endif
    </div>

    <!-- Market Basket Analysis -->
    <div class="advanced-kpi-card">
        <div class="advanced-kpi-header">
            <div class="advanced-kpi-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-shopping-basket"></i>
            </div>
            <div>
                <h3 class="advanced-kpi-title">Market Basket Analysis</h3>
                <p class="advanced-kpi-subtitle">Discover product associations and cross-selling opportunities</p>
            </div>
        </div>


        @if(isset($basketData) && count($basketData['associations']) > 0)
            <div class="metric-grid">
                <div class="metric-card">
                    <div class="metric-value">{{ count($basketData['associations']) }}</div>
                    <div class="metric-label">Product Associations</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ $basketData['total_transactions'] }}</div>
                    <div class="metric-label">Transactions Analyzed</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ $basketData['frequent_products_count'] }}</div>
                    <div class="metric-label">Frequent Products</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ count($basketData['recommendations']) }}</div>
                    <div class="metric-label">Recommendations</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="chart-container">
                        <div class="chart-title">Top Product Associations</div>
                        <div id="basket_associations_chart" style="height: 300px;"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <div class="chart-title">Cross-Selling Recommendations</div>
                        <div class="association-list">
                            @foreach(array_slice($basketData['recommendations'], 0, 8) as $rec)
                                <div class="association-item">
                                    <div class="association-rule">
                                        "{{ $rec['if_customer_buys'] }}" → "{{ $rec['recommend'] }}"
                                    </div>
                                    <div class="association-metrics">
                                        Confidence: {{ number_format($rec['confidence'] * 100, 1) }}% |
                                        Lift: {{ number_format($rec['lift'], 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="loading-state">
                <i class="fas fa-shopping-basket fa-3x" style="margin-bottom: 20px; opacity: 0.5;"></i>
                <p>Insufficient transaction data for basket analysis</p>
            </div>
        @endif
    </div>

    <!-- Seasonality & Trend Analysis -->
    <div class="advanced-kpi-card">
        <div class="advanced-kpi-header">
            <div class="advanced-kpi-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-chart-area"></i>
            </div>
            <div>
                <h3 class="advanced-kpi-title">Seasonality & Trend Analysis</h3>
                <p class="advanced-kpi-subtitle">Identify seasonal patterns and long-term trends in your sales</p>
            </div>
        </div>

        @if(isset($seasonalityData) && count($seasonalityData['seasonal_patterns']['monthly_patterns']) > 0)
            <div class="metric-grid">
                <div class="metric-card">
                    <div class="metric-value">{{ $seasonalityData['seasonal_patterns']['peak_season']['month_name'] ?? 'N/A' }}</div>
                    <div class="metric-label">Peak Season</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ $seasonalityData['seasonal_patterns']['low_season']['month_name'] ?? 'N/A' }}</div>
                    <div class="metric-label">Low Season</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ number_format($seasonalityData['seasonal_patterns']['seasonal_variation'], 1) }}%</div>
                    <div class="metric-label">Seasonal Variation</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ number_format($seasonalityData['trend_analysis']['monthly_growth_rate'], 1) }}%</div>
                    <div class="metric-label">Monthly Growth</div>
                </div>
            </div>

            <div class="seasonality-grid">
                @foreach($seasonalityData['seasonal_patterns']['monthly_patterns'] as $pattern)
                    <div class="seasonality-item" style="border-left: 4px solid
                        @if($pattern['seasonality_type'] == 'Very High') #e53e3e
                        @elseif($pattern['seasonality_type'] == 'High') #dd6b20
                        @elseif($pattern['seasonality_type'] == 'Normal') #38a169
                        @elseif($pattern['seasonality_type'] == 'Low') #3182ce
                        @else #718096
                        @endif
                    ;">
                        <div class="seasonality-month">{{ $pattern['month_name'] }}</div>
                        <div class="seasonality-index" style="color:
                            @if($pattern['seasonality_type'] == 'Very High') #e53e3e
                            @elseif($pattern['seasonality_type'] == 'High') #dd6b20
                            @elseif($pattern['seasonality_type'] == 'Normal') #38a169
                            @elseif($pattern['seasonality_type'] == 'Low') #3182ce
                            @else #718096
                            @endif
                        ;">{{ number_format($pattern['seasonal_index'], 1) }}</div>
                        <div class="seasonality-type">{{ $pattern['seasonality_type'] }}</div>
                    </div>
                @endforeach
            </div>

            @if(count($seasonalityData['forecast']) > 0)
                <div class="chart-title" style="text-align: center; margin-bottom: 20px;">Sales Forecast (Next 6 Months)</div>
                <div class="metric-grid">
                    @foreach($seasonalityData['forecast'] as $forecast)
                        <div class="metric-card" data-bs-toggle="tooltip" title="Forecasted sales for {{ $forecast['month_name'] }} {{ $forecast['year'] }}">
                            <div class="metric-value">
                                {{ formatIndianCurrency($forecast['forecasted_sales']) }}<br>
                                <small style="font-size: 11px; opacity: 0.7;">({{ number_format($forecast['forecasted_sales'], 0) }})</small>
                            </div>
                            <div class="metric-label">{{ $forecast['month_name'] }} {{ $forecast['year'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <div class="loading-state">
                <i class="fas fa-chart-area fa-3x" style="margin-bottom: 20px; opacity: 0.5;"></i>
                <p>Insufficient historical data for seasonality analysis</p>
            </div>
        @endif
    </div>

    <!-- Customer Churn Analysis -->
    <div class="advanced-kpi-card">
        <div class="advanced-kpi-header">
            <div class="advanced-kpi-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <i class="fas fa-user-times"></i>
            </div>
            <div>
                <h3 class="advanced-kpi-title">Customer Churn Analysis</h3>
                <p class="advanced-kpi-subtitle">Identify at-risk customers and improve retention strategies</p>
            </div>
        </div>

        <!-- Churn Controls -->
        <div class="row mb-3">
            <div class="col-12 text-end">
                <button class="btn btn-success btn-sm" id="export_churn_excel">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
            </div>
        </div>

        @if(isset($churnData) && $churnData['total_customers_analyzed'] > 0)
            <div class="metric-grid">
                <div class="metric-card">
                    <div class="metric-value">{{ number_format($churnData['churn_rate'], 1) }}%</div>
                    <div class="metric-label">Churn Rate</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ number_format($churnData['retention_rate'], 1) }}%</div>
                    <div class="metric-label">Retention Rate</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ $churnData['churned_customers'] }}</div>
                    <div class="metric-label">Churned Customers</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">{{ $churnData['at_risk_customers'] }}</div>
                    <div class="metric-label">At-Risk Customers</div>
                </div>
            </div>

            <div class="segment-grid">
                @foreach($churnData['churn_segments'] as $segment => $data)
                    <div class="segment-card" style="background: linear-gradient(135deg,
                        @if($segment == 'high_value') #e53e3e 0%, #c53030 100%
                        @elseif($segment == 'medium_value') #dd6b20 0%, #c05621 100%
                        @else #38a169 0%, #2f855a 100%
                        @endif
                    );">
                        <div class="segment-name">{{ ucfirst(str_replace('_', ' ', $segment)) }}</div>
                        <div class="segment-value">{{ number_format($data['churn_rate'], 1) }}%</div>
                        <div style="font-size: 12px; opacity: 0.9;">{{ $data['total_customers'] }} total</div>
                    </div>
                @endforeach
            </div>

            @if(count($churnData['top_churned_customers']) > 0)
                <div class="chart-container">
                    <div class="chart-title">Top Churned Customers by Value</div>
                    <div id="churn_top_customers_chart" style="height: 300px;"></div>
                </div>
            @endif
        @else
            <div class="loading-state">
                <i class="fas fa-user-times fa-3x" style="margin-bottom: 20px; opacity: 0.5;"></i>
                <p>No customer data available for churn analysis</p>
            </div>
        @endif
    </div>
</div>

@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script type="text/javascript">
$(document).ready(function() {
    const AdvancedKpiDashboard = {
        selectedLocation: '{{ $selectedLocation ?? "" }}',
        currencySymbol: '{{ $currencySymbol ?? "৳" }}',
        currencyPrecision: {{ $currencyPrecision ?? 2 }},
        charts: {},

        init() {
            this.setupEventListeners();
            this.loadAllCharts();
            this.initializeTooltips();
        },

        initializeTooltips() {
            // Initialize Bootstrap tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        },

        setupEventListeners() {
            $('#location_filter').on('change', (e) => {
                const selectedValue = $(e.target).val();
                const url = '{{ route("businessintelligence.advanced-kpi") }}?location_id=' + selectedValue;
                window.location.href = url;
            });

            $('#refresh_advanced_kpi').on('click', () => {
                this.showLoading();
                window.location.reload();
            });

            // CLV export
            $('#export_clv_excel').on('click', () => {
                this.exportClvToExcel();
            });

            // Churn export
            $('#export_churn_excel').on('click', () => {
                this.exportChurnToExcel();
            });
        },

        showLoading() {
            // Add loading overlay
            if (!$('.loading-overlay').length) {
                $('body').append('<div class="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 9999;"><div class="spinner-border text-primary" role="status"></div></div>');
            }
        },

        loadAllCharts() {
            this.loadClvTopCustomersChart();
            this.loadBasketAssociationsChart();
            this.loadChurnTopCustomersChart();
        },

        loadClvTopCustomersChart() {
            @if(isset($clvData) && count($clvData['top_customers']) > 0)
                const allCustomers = @json($clvData['top_customers']);
                const customers = allCustomers.slice(0, 10);
                const options = {
                    series: [{
                        name: 'CLV',
                        data: customers.map(c => c.clv)
                    }],
                    chart: {
                        type: 'bar',
                        height: 300,
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            dataLabels: { position: 'top' }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (value) {
                            return AdvancedKpiDashboard.currencySymbol + value.toLocaleString();
                        }
                    },
                    xaxis: {
                        categories: customers.map(c => {
                            const displayName = c.customer_name && c.customer_name.trim() !== '' ? c.customer_name : (c.business_name || 'Unknown Customer');
                            return displayName.length > 15 ? displayName.substring(0, 15) + '...' : displayName;
                        })
                    },
                    tooltip: {
                        y: {
                            formatter: function (value) {
                                return AdvancedKpiDashboard.currencySymbol + value.toLocaleString();
                            }
                        }
                    }
                };

                if (this.charts.clvTopCustomers) this.charts.clvTopCustomers.destroy();
                this.charts.clvTopCustomers = new ApexCharts(document.querySelector("#clv_top_customers_chart"), options);
                this.charts.clvTopCustomers.render();
            @endif
        },

        loadBasketAssociationsChart() {
            @if(isset($basketData) && count($basketData['associations']) > 0)
                const associations = @json(array_slice($basketData['associations'], 0, 10));
                const options = {
                    series: [{
                        name: 'Lift',
                        data: associations.map(a => a.lift)
                    }],
                    chart: {
                        type: 'bar',
                        height: 300,
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            dataLabels: { position: 'top' }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (value) {
                            return value.toFixed(2);
                        }
                    },
                    xaxis: {
                        categories: associations.map(a => a.product_a + ' → ' + a.product_b),
                        labels: { rotate: -45 }
                    },
                    tooltip: {
                        y: {
                            formatter: function (value) {
                                return value.toFixed(2);
                            }
                        }
                    }
                };

                if (this.charts.basketAssociations) this.charts.basketAssociations.destroy();
                this.charts.basketAssociations = new ApexCharts(document.querySelector("#basket_associations_chart"), options);
                this.charts.basketAssociations.render();
            @endif
        },


        loadChurnTopCustomersChart() {
            @if(isset($churnData) && count($churnData['top_churned_customers']) > 0)
                const customers = @json(array_slice($churnData['top_churned_customers'], 0, 10));
                const options = {
                    series: [{
                        name: 'Lost Revenue',
                        data: customers.map(c => c.total_spent)
                    }],
                    chart: {
                        type: 'bar',
                        height: 300,
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            dataLabels: { position: 'top' }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (value) {
                            return AdvancedKpiDashboard.currencySymbol + value.toLocaleString();
                        }
                    },
                    xaxis: {
                        categories: customers.map(c => {
                            const displayName = c.customer_name && c.customer_name.trim() !== '' ? c.customer_name : (c.business_name || 'Unknown Customer');
                            return displayName.length > 15 ? displayName.substring(0, 15) + '...' : displayName;
                        })
                    },
                    tooltip: {
                        y: {
                            formatter: function (value) {
                                return AdvancedKpiDashboard.currencySymbol + value.toLocaleString();
                            }
                        }
                    }
                };

                if (this.charts.churnTopCustomers) this.charts.churnTopCustomers.destroy();
                this.charts.churnTopCustomers = new ApexCharts(document.querySelector("#churn_top_customers_chart"), options);
                this.charts.churnTopCustomers.render();
            @endif
        },


        exportClvToExcel() {
            @if(isset($clvData) && count($clvData['all_customers']) > 0)
                const customers = @json($clvData['all_customers']);

                // Create CSV content
                let csvContent = "Customer Name,Contact's Business Name,Contact ID,Mobile Number,CLV,Total Spent,Purchase Count,Avg Order Value,Purchase Frequency,Estimated Lifespan\n";

                customers.forEach(customer => {
                    csvContent += `"${customer.customer_name}","${customer.business_name}",${customer.contact_id},"${customer.mobile_number}",${customer.clv},${customer.total_spent},${customer.purchase_count},${customer.avg_order_value},${customer.purchase_frequency},${customer.estimated_lifespan_months}\n`;
                });

                // Create and download file
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", `clv_all_customers.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            @endif
        },

        exportChurnToExcel() {
            @if(isset($churnData) && count($churnData['all_churned_customers']) > 0)
                const customers = @json($churnData['all_churned_customers']);

                // Create CSV content
                let csvContent = "Customer Name,Customer's Business Name,Mobile Number,Total Spent,Purchase Count,Avg Order Value,Days Since Last Purchase,Churn Reason\n";

                customers.forEach(customer => {
                    csvContent += `"${customer.customer_name}","${customer.business_name}","${customer.mobile_number}",${customer.total_spent},${customer.transaction_count},${customer.avg_order_value},${customer.days_since_last_purchase},"${customer.churn_reason}"\n`;
                });

                // Create and download file
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", `churn_all_customers.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            @endif
        }
    };

    AdvancedKpiDashboard.init();
});
</script>
@endsection