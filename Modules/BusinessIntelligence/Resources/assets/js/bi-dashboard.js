/**
 * Business Intelligence Dashboard JavaScript
 * Handles charts, AJAX calls, and user interactions
 */

class BiDashboard {
    constructor() {
        this.dateRange = 30;
        this.charts = {};
        this.refreshInterval = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadAllCharts();
        this.setupAutoRefresh();
    }

    setupEventListeners() {
        // Date range filter
        $('#date_range_filter').on('change', (e) => {
            this.dateRange = $(e.target).val();
            this.refreshDashboard();
        });

        // Refresh button
        $('#refresh_dashboard').on('click', () => {
            this.refreshDashboard();
        });

        // Generate insights
        $('#generate_insights, #generate_first_insights').on('click', () => {
            this.generateInsights();
        });

        // Acknowledge insight
        $(document).on('click', '.acknowledge-insight', (e) => {
            const insightId = $(e.target).closest('button').data('id');
            this.acknowledgeInsight(insightId);
        });

        // Export report
        $('#export_report').on('click', () => {
            this.exportReport();
        });
    }

    loadAllCharts() {
        this.loadChart('sales_trend', 'sales_trend_chart', 'line');
        this.loadChart('inventory_status', 'inventory_chart', 'doughnut');
        this.loadChart('profit_comparison', 'profit_chart', 'bar');
        this.loadChart('expense_breakdown', 'expense_chart', 'pie');
        this.loadChart('top_products', 'top_products_chart', 'horizontalBar');
        this.loadChart('cash_flow', 'cash_flow_chart', 'line');
    }

    loadChart(chartType, canvasId, type) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        $.ajax({
            url: '/business-intelligence/dashboard/chart-data',
            method: 'GET',
            data: {
                chart_type: chartType,
                date_range: this.dateRange
            },
            success: (response) => {
                if (response.success) {
                    // Destroy existing chart if it exists
                    if (this.charts[canvasId]) {
                        this.charts[canvasId].destroy();
                    }

                    // Create new chart
                    const ctx = canvas.getContext('2d');
                    this.charts[canvasId] = new Chart(ctx, {
                        type: type,
                        data: response.data,
                        options: this.getChartOptions(type, chartType)
                    });
                }
            },
            error: (xhr) => {
                console.error('Error loading chart:', xhr);
                this.showError('Failed to load ' + chartType + ' chart');
            }
        });
    }

    getChartOptions(type, chartType) {
        const baseOptions = {
            responsive: true,
            maintainAspectRatio: type !== 'doughnut' && type !== 'pie',
            plugins: {
                legend: {
                    display: true,
                    position: type === 'line' ? 'top' : 'bottom'
                },
                tooltip: {
                    enabled: true,
                    mode: 'index',
                    intersect: false
                }
            }
        };

        // Line chart specific options
        if (type === 'line') {
            baseOptions.scales = {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            };
            baseOptions.elements = {
                line: {
                    tension: 0.4
                }
            };
        }

        // Bar chart specific options
        if (type === 'bar' || type === 'horizontalBar') {
            baseOptions.scales = {
                y: {
                    beginAtZero: true
                }
            };
        }

        return baseOptions;
    }

    refreshDashboard() {
        this.showLoading('Refreshing dashboard...');

        $.ajax({
            url: '/business-intelligence/dashboard/refresh',
            method: 'POST',
            data: {
                date_range: this.dateRange
            },
            success: (response) => {
                if (response.success) {
                    this.hideLoading();
                    this.showSuccess('Dashboard refreshed successfully!');
                    
                    // Reload charts
                    this.loadAllCharts();
                    
                    // Optionally reload page for KPI updates
                    // location.reload();
                }
            },
            error: (xhr) => {
                this.hideLoading();
                this.showError('Failed to refresh dashboard');
            }
        });
    }

    generateInsights() {
        this.showLoading('Generating AI insights...', 'This may take a few moments while we analyze your data.');

        $.ajax({
            url: '/business-intelligence/insights/generate',
            method: 'POST',
            data: {
                date_range: this.dateRange
            },
            success: (response) => {
                this.hideLoading();
                if (response.success) {
                    this.showSuccess(`Successfully generated ${response.count} insights!`);
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            },
            error: (xhr) => {
                this.hideLoading();
                this.showError('Failed to generate insights');
            }
        });
    }

    acknowledgeInsight(insightId) {
        $.ajax({
            url: `/business-intelligence/insights/${insightId}/acknowledge`,
            method: 'POST',
            success: (response) => {
                if (response.success) {
                    this.showSuccess('Insight acknowledged');
                    $(`[data-id="${insightId}"]`).closest('.bi-insight-card').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            },
            error: (xhr) => {
                this.showError('Failed to acknowledge insight');
            }
        });
    }

    exportReport() {
        this.showLoading('Preparing export...');

        window.location.href = `/business-intelligence/analytics/export?date_range=${this.dateRange}&format=excel`;

        setTimeout(() => {
            this.hideLoading();
        }, 2000);
    }

    setupAutoRefresh() {
        const refreshInterval = 300000; // 5 minutes (configurable)
        
        this.refreshInterval = setInterval(() => {
            console.log('Auto-refreshing dashboard...');
            this.loadAllCharts(); // Only refresh charts, not full page
        }, refreshInterval);
    }

    showLoading(title = 'Loading...', text = 'Please wait...') {
        if (typeof swal !== 'undefined') {
            swal({
                title: title,
                text: text,
                icon: 'info',
                buttons: false,
                closeOnClickOutside: false,
                closeOnEsc: false
            });
        }
    }

    hideLoading() {
        if (typeof swal !== 'undefined') {
            swal.close();
        }
    }

    showSuccess(message) {
        if (typeof toastr !== 'undefined') {
            toastr.success(message);
        } else {
            alert(message);
        }
    }

    showError(message) {
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else {
            alert('Error: ' + message);
        }
    }

    destroy() {
        // Clean up charts
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });

        // Clear auto-refresh interval
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }
}

// Initialize dashboard when document is ready
$(document).ready(function() {
    if ($('#kpi_cards').length > 0) {
        window.biDashboard = new BiDashboard();
    }
});

// Clean up on page unload
$(window).on('beforeunload', function() {
    if (window.biDashboard) {
        window.biDashboard.destroy();
    }
});

