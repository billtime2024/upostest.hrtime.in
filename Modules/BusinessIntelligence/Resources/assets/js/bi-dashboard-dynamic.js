/**
 * Dynamic Business Intelligence Dashboard
 * All charts load data from backend via AJAX
 */

const BiDashboard = {
    dateRange: 30,
    charts: {},
    baseUrl: '/business-intelligence/dashboard',

    init() {
        this.setupEventListeners();
        this.loadAllCharts();
    },

    setupEventListeners() {
        $('#date_range_filter').on('change', (e) => {
            this.dateRange = $(e.target).val();
            this.refreshDashboard();
        });

        $('#refresh_dashboard').on('click', () => {
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
        this.loadTopProductsChart();
        this.loadInventoryStatusChart();
        this.loadExpenseBreakdownChart();
        this.loadCustomerGrowthChart();
    },

    loadChart(chartType, containerId, renderCallback) {
        $.ajax({
            url: this.baseUrl + '/chart-data',
            method: 'GET',
            data: {
                chart_type: chartType,
                date_range: this.dateRange
            },
            success: (response) => {
                if (response.success) {
                    renderCallback(response.data, containerId);
                }
            },
            error: (error) => {
                console.error(`Failed to load ${chartType}:`, error);
            }
        });
    },

    loadSalesTrendChart() {
        this.loadChart('sales_trend', '#sales_trend_chart', (data, containerId) => {
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
                colors: ['#667eea'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3,
                    }
                },
                xaxis: {
                    categories: data.categories || []
                },
                tooltip: { theme: 'dark' },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            };
            if (this.charts.salesTrend) this.charts.salesTrend.destroy();
            this.charts.salesTrend = new ApexCharts(document.querySelector(containerId), options);
            this.charts.salesTrend.render();
        });
    },

    loadRevenueSourcesChart() {
        this.loadChart('revenue_sources', '#revenue_sources_chart', (data, containerId) => {
            const options = {
                series: data.series || [0, 0, 0, 0, 0],
                chart: {
                    type: 'donut',
                    height: 350
                },
                labels: data.labels || ['No Data'],
                colors: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#43e97b'],
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
                                        return '$' + total.toFixed(2);
                                    }
                                }
                            }
                        }
                    }
                }
            };
            if (this.charts.revenueSources) this.charts.revenueSources.destroy();
            this.charts.revenueSources = new ApexCharts(document.querySelector(containerId), options);
            this.charts.revenueSources.render();
        });
    },

    loadProfitExpenseChart() {
        this.loadChart('profit_expense', '#profit_expense_chart', (data, containerId) => {
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
                    height: 300
                },
                colors: ['#43e97b', '#f5576c'],
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
                            return '$' + value.toFixed(0);
                        }
                    }
                }
            };
            if (this.charts.profitExpense) this.charts.profitExpense.destroy();
            this.charts.profitExpense = new ApexCharts(document.querySelector(containerId), options);
            this.charts.profitExpense.render();
        });
    },

    loadCashFlowChart() {
        this.loadChart('cash_flow', '#cash_flow_chart', (data, containerId) => {
            const options = {
                series: [{
                    name: 'Cash In',
                    data: data.cash_in || []
                }, {
                    name: 'Cash Out',
                    data: data.cash_out || []
                }],
                chart: {
                    type: 'line',
                    height: 300
                },
                colors: ['#43e97b', '#f5576c'],
                stroke: { width: [4, 4], curve: 'smooth' },
                xaxis: {
                    categories: data.categories || []
                },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return '$' + value.toFixed(0);
                        }
                    }
                }
            };
            if (this.charts.cashFlow) this.charts.cashFlow.destroy();
            this.charts.cashFlow = new ApexCharts(document.querySelector(containerId), options);
            this.charts.cashFlow.render();
        });
    },

    loadTopProductsChart() {
        this.loadChart('top_products', '#top_products_chart', (data, containerId) => {
            const options = {
                series: [{
                    data: data.data || []
                }],
                chart: {
                    type: 'bar',
                    height: 400
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: true,
                    }
                },
                colors: ['#667eea'],
                dataLabels: {
                    enabled: true,
                    formatter: function (value) {
                        return '$' + value.toFixed(2);
                    }
                },
                xaxis: {
                    categories: data.categories || []
                }
            };
            if (this.charts.topProducts) this.charts.topProducts.destroy();
            this.charts.topProducts = new ApexCharts(document.querySelector(containerId), options);
            this.charts.topProducts.render();
        });
    },

    loadInventoryStatusChart() {
        this.loadChart('inventory_status', '#inventory_status_chart', (data, containerId) => {
            const options = {
                series: data.series || [0, 0, 0],
                chart: {
                    type: 'donut',
                    height: 400
                },
                labels: data.labels || ['No Data'],
                colors: ['#43e97b', '#ffd700', '#f5576c'],
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
                }
            };
            if (this.charts.inventoryStatus) this.charts.inventoryStatus.destroy();
            this.charts.inventoryStatus = new ApexCharts(document.querySelector(containerId), options);
            this.charts.inventoryStatus.render();
        });
    },

    loadExpenseBreakdownChart() {
        this.loadChart('expense_breakdown', '#expense_breakdown_chart', (data, containerId) => {
            const options = {
                series: data.series || [0],
                chart: {
                    type: 'pie',
                    height: 350
                },
                labels: data.labels || ['No Data'],
                colors: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#43e97b'],
                legend: { position: 'bottom' }
            };
            if (this.charts.expenseBreakdown) this.charts.expenseBreakdown.destroy();
            this.charts.expenseBreakdown = new ApexCharts(document.querySelector(containerId), options);
            this.charts.expenseBreakdown.render();
        });
    },

    loadCustomerGrowthChart() {
        this.loadChart('customer_growth', '#customer_growth_chart', (data, containerId) => {
            const options = {
                series: [{
                    name: 'New Customers',
                    data: data.data || []
                }],
                chart: {
                    type: 'line',
                    height: 350
                },
                stroke: { width: 5, curve: 'smooth' },
                colors: ['#667eea'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'dark',
                        gradientToColors: ['#764ba2'],
                        shadeIntensity: 1,
                        type: 'horizontal',
                        opacityFrom: 1,
                        opacityTo: 1,
                    }
                },
                xaxis: {
                    categories: data.categories || []
                }
            };
            if (this.charts.customerGrowth) this.charts.customerGrowth.destroy();
            this.charts.customerGrowth = new ApexCharts(document.querySelector(containerId), options);
            this.charts.customerGrowth.render();
        });
    },

    refreshDashboard() {
        this.showLoading();
        window.location.href = this.baseUrl + '?date_range=' + this.dateRange;
    },

    generateInsights() {
        this.showLoading();
        $.ajax({
            url: '/business-intelligence/insights/generate',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                date_range: this.dateRange
            },
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
        window.location.href = this.baseUrl + '/export?date_range=' + this.dateRange;
    }
};

// Initialize on document ready
$(document).ready(function() {
    BiDashboard.init();
});

