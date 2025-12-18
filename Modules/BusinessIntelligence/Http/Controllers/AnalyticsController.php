<?php

namespace Modules\BusinessIntelligence\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BusinessIntelligence\Utils\BiAnalyzer;
use Modules\BusinessIntelligence\Utils\DataProcessor;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected $biAnalyzer;
    protected $dataProcessor;
    protected $businessId;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->businessId = $request->session()->get('user.business_id');
            $this->biAnalyzer = new BiAnalyzer($this->businessId);
            $this->dataProcessor = new DataProcessor($this->businessId);
            return $next($request);
        });
    }

    /**
     * Calculate start and end dates based on date range selection
     */
    private function calculateDateRange($dateRange, Request $request = null)
    {
        $now = Carbon::now();

        switch ($dateRange) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;

            case 'yesterday':
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
                break;

            case 'last_7_days':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;

            case 'last_30_days':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;

            case 'this_month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfDay();
                break;

            case 'last_month':
                $startDate = $now->copy()->subMonth()->startOfMonth();
                $endDate = $now->copy()->subMonth()->endOfMonth();
                break;

            case 'this_month_last_year':
                $startDate = $now->copy()->subYear()->startOfMonth();
                $endDate = $now->copy()->subYear()->endOfMonth();
                break;

            case 'this_year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfDay();
                break;

            case 'last_year':
                $startDate = $now->copy()->subYear()->startOfYear();
                $endDate = $now->copy()->subYear()->endOfYear();
                break;

            case 'current_financial_year':
                // Financial year: April 1 to March 31
                $currentYear = $now->year;
                $currentMonth = $now->month;

                if ($currentMonth >= 4) {
                    // Current financial year started this year
                    $startDate = Carbon::create($currentYear, 4, 1)->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                } else {
                    // Current financial year started last year
                    $startDate = Carbon::create($currentYear - 1, 4, 1)->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                }
                break;

            case 'last_financial_year':
                // Financial year: April 1 to March 31
                $currentYear = $now->year;
                $currentMonth = $now->month;

                if ($currentMonth >= 4) {
                    // Last financial year was previous year
                    $startDate = Carbon::create($currentYear - 1, 4, 1)->startOfDay();
                    $endDate = Carbon::create($currentYear, 3, 31)->endOfDay();
                } else {
                    // Last financial year was two years ago
                    $startDate = Carbon::create($currentYear - 2, 4, 1)->startOfDay();
                    $endDate = Carbon::create($currentYear - 1, 3, 31)->endOfDay();
                }
                break;

            case 'custom':
                $startDate = $request && $request->get('start_date')
                    ? Carbon::parse($request->get('start_date'))->startOfDay()
                    : $now->copy()->subDays(29)->startOfDay();
                $endDate = $request && $request->get('end_date')
                    ? Carbon::parse($request->get('end_date'))->endOfDay()
                    : $now->copy()->endOfDay();
                break;

            default:
                // Fallback to last 30 days
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
        }

        return [$startDate, $endDate];
    }

    /**
     * Calculate previous period start date for trend comparison
     */
    private function calculatePreviousPeriodStart($dateRange, $currentStartDate)
    {
        switch ($dateRange) {
            case 'today':
            case 'yesterday':
                return $currentStartDate->copy()->subDay();
            case 'last_7_days':
                return $currentStartDate->copy()->subDays(7);
            case 'last_30_days':
                return $currentStartDate->copy()->subDays(30);
            case 'this_month':
                return $currentStartDate->copy()->subMonth();
            case 'last_month':
                return $currentStartDate->copy()->subMonths(2);
            case 'this_month_last_year':
                return $currentStartDate->copy()->subYear()->subMonth();
            case 'this_year':
                return $currentStartDate->copy()->subYear();
            case 'last_year':
                return $currentStartDate->copy()->subYears(2);
            case 'current_financial_year':
            case 'last_financial_year':
                return $currentStartDate->copy()->subYear();
            case 'custom':
                // For custom range, calculate based on duration
                $duration = $currentStartDate->diffInDays($currentStartDate->copy()->modify('+1 year'));
                return $currentStartDate->copy()->subDays($duration);
            default:
                return $currentStartDate->copy()->subDays(30);
        }
    }

    /**
     * Get sales analytics (Visual Dashboard)
     */
    public function getSalesAnalytics(Request $request)
    {
        // Handle date range selection - default to last 30 days
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');

        if ($startDateParam && $endDateParam) {
            $startDate = Carbon::parse($startDateParam)->startOfDay();
            $endDate = Carbon::parse($endDateParam)->endOfDay();
            $dateRange = 'custom'; // For backward compatibility in view
        } else {
            $dateRange = $request->get('date_range', 'last_30_days');
            // Calculate date range based on selection
            list($startDate, $endDate) = $this->calculateDateRange($dateRange, $request);
        }

        // Calculate number of days for the selected range
        $daysInRange = $startDate->diffInDays($endDate) + 1;

        // Calculate previous period dates for comparison
        $previousStartDate = $this->calculatePreviousPeriodStart($dateRange, $startDate);
        $previousEndDate = $startDate->copy()->subSecond();

        // Parse location_ids from comma-separated string or array
        $locationIdsParam = $request->get('location_ids', '');
        $locationIds = [];
        if (!empty($locationIdsParam)) {
            if (is_array($locationIdsParam)) {
                $locationIds = $locationIdsParam;
            } else {
                $locationIds = explode(',', $locationIdsParam);
            }
            $locationIds = array_filter(array_map('intval', $locationIds));
        }

        // Get business locations for filter
        $businessLocations = \App\BusinessLocation::forDropdown($this->businessId, true);

        $salesData = $this->dataProcessor->getSalesData($startDate, $endDate, $locationIds);
        $topProducts = $this->dataProcessor->getTopSellingProducts($startDate, $endDate, 10, $locationIds, 'revenue');
        $topCategories = $this->dataProcessor->getTopSellingCategories($startDate, $endDate, 10, $locationIds, 'revenue');
        $topBrands = $this->dataProcessor->getTopSellingBrands($startDate, $endDate, 10, $locationIds, 'revenue');

        // Get previous period data for trend calculation
        $previousTopCategories = $this->dataProcessor->getTopSellingCategories($previousStartDate, $previousEndDate, 10, $locationIds, 'revenue');
        $previousTopProducts = $this->dataProcessor->getTopSellingProducts($previousStartDate, $previousEndDate, 10, $locationIds, 'revenue');
        $previousTopBrands = $this->dataProcessor->getTopSellingBrands($previousStartDate, $previousEndDate, 10, $locationIds, 'revenue');

        // Add trend data to top categories, products, and brands
        $topCategories = $this->addTrendDataToCategories($topCategories, $previousTopCategories);
        $topProducts = $this->addTrendDataToProducts($topProducts, $previousTopProducts);
        $topBrands = $this->addTrendDataToBrands($topBrands, $previousTopBrands);

        // Calculate current period metrics
        $totalSales = $salesData->sum('total_sales');
        $totalTransactions = $salesData->sum('transaction_count');
        $averageSale = $salesData->avg('average_sale');

        // Calculate previous period metrics for comparison
        $previousSalesData = $this->dataProcessor->getSalesData($previousStartDate, $previousEndDate, $locationIds);
        $previousTotalSales = $previousSalesData->sum('total_sales');
        $previousTotalTransactions = $previousSalesData->sum('transaction_count');
        $previousAverageSale = $previousSalesData->avg('average_sale');

        // Calculate percentage changes
        $salesChangePercent = $this->calculatePercentageChange($previousTotalSales, $totalSales);
        $transactionsChangePercent = $this->calculatePercentageChange($previousTotalTransactions, $totalTransactions);
        $averageSaleChangePercent = $this->calculatePercentageChange($previousAverageSale, $averageSale);

        // Return view with data
        return view('businessintelligence::analytics.sales', compact(
            'salesData',
            'topProducts',
            'topCategories',
            'topBrands',
            'totalSales',
            'totalTransactions',
            'averageSale',
            'dateRange',
            'daysInRange',
            'businessLocations',
            'locationIds',
            'salesChangePercent',
            'transactionsChangePercent',
            'averageSaleChangePercent'
        ));
    }

    /**
     * Get inventory analytics
     */
    public function getInventoryAnalytics(Request $request)
    {
        $locationId = $request->get('location_id');

        $inventory = $this->dataProcessor->getInventoryData($locationId);
        $inventoryChart = $this->biAnalyzer->getInventoryStatusChartData();
        $lowStockThreshold = config('businessintelligence.alerts.low_stock_threshold', 10);

        $lowStockItems = $inventory->filter(fn($item) => $item->qty_available <= $lowStockThreshold && $item->qty_available > 0);
        $outOfStockItems = $inventory->filter(fn($item) => $item->qty_available == 0);

        return response()->json([
            'success' => true,
            'data' => [
                'total_products' => $inventory->count(),
                'total_value' => $inventory->sum('stock_value'),
                'low_stock_items' => $lowStockItems->values(),
                'out_of_stock_items' => $outOfStockItems->values(),
                'chart_data' => $inventoryChart,
            ]
        ]);
    }

    /**
     * Get financial analytics
     */
    public function getFinancialAnalytics(Request $request)
    {
        $dateRange = $request->get('date_range', 30);
        $endDate = Carbon::now()->endOfDay();
        $startDate = Carbon::now()->subDays($dateRange - 1)->startOfDay();

        $profitData = $this->dataProcessor->calculateProfit($startDate, $endDate);
        $profitChart = $this->biAnalyzer->getProfitComparisonChartData($startDate, $endDate);
        $expenseChart = $this->biAnalyzer->getExpenseBreakdownChartData($startDate, $endDate);
        $cashFlowChart = $this->biAnalyzer->getCashFlowChartData($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => [
                'profit_data' => $profitData,
                'profit_chart' => $profitChart,
                'expense_chart' => $expenseChart,
                'cash_flow_chart' => $cashFlowChart,
            ]
        ]);
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(Request $request)
    {
        $customerDues = $this->dataProcessor->getCustomerDues();
        $overdueThreshold = config('businessintelligence.alerts.overdue_days_threshold', 30);

        $overdueCustomers = $customerDues->filter(function($customer) use ($overdueThreshold) {
            return Carbon::parse($customer->last_transaction_date)->diffInDays(Carbon::now()) > $overdueThreshold;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_customers' => $customerDues->count(),
                'total_receivables' => $customerDues->sum('total_due'),
                'overdue_customers' => $overdueCustomers->values(),
                'average_due' => $customerDues->avg('total_due'),
            ]
        ]);
    }

    /**
     * Get supplier analytics
     */
    public function getSupplierAnalytics(Request $request)
    {
        $supplierDues = $this->dataProcessor->getSupplierDues();

        return response()->json([
            'success' => true,
            'data' => [
                'total_suppliers' => $supplierDues->count(),
                'total_payables' => $supplierDues->sum('total_due'),
                'suppliers_with_dues' => $supplierDues->values(),
                'average_due' => $supplierDues->avg('total_due'),
            ]
        ]);
    }

    /**
     * Get comprehensive analytics
     */
    public function getComprehensiveAnalytics(Request $request)
    {
        $dateRange = $request->get('date_range', 30);
        $endDate = Carbon::now()->endOfDay();
        $startDate = Carbon::now()->subDays($dateRange - 1)->startOfDay();

        $summary = $this->biAnalyzer->getPerformanceSummary($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Export analytics data
     */
    public function exportAnalytics(Request $request)
    {
        $type = $request->get('type', 'comprehensive');
        $dateRange = $request->get('date_range', 30);
        $endDate = Carbon::now()->endOfDay();
        $startDate = Carbon::now()->subDays($dateRange - 1)->startOfDay();

        // Generate export data based on type
        $data = $this->biAnalyzer->getPerformanceSummary($startDate, $endDate);

        // In a real implementation, you would create an Excel/CSV file here
        // For now, return JSON
        return response()->json([
            'success' => true,
            'message' => 'Export feature coming soon',
            'data' => $data
        ]);
    }

    /**
     * Calculate percentage change between two values
     */
    private function calculatePercentageChange($previousValue, $currentValue)
    {
        if ($previousValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }

        return round((($currentValue - $previousValue) / $previousValue) * 100, 1);
    }

    /**
     * Add trend data to categories by comparing current and previous period data
     */
    private function addTrendDataToCategories($currentCategories, $previousCategories)
    {
        // Create a map of previous categories by category_id for quick lookup
        $previousCategoriesMap = [];
        foreach ($previousCategories as $prevCategory) {
            $previousCategoriesMap[$prevCategory->id] = $prevCategory;
        }

        // Add trend data to each current category
        foreach ($currentCategories as $category) {
            $categoryId = $category->id;
            $currentRevenue = $category->total_revenue ?? 0;

            // Find previous period data for this category
            $previousRevenue = 0;
            if (isset($previousCategoriesMap[$categoryId])) {
                $previousRevenue = $previousCategoriesMap[$categoryId]->total_revenue ?? 0;
            }

            // Calculate trend percentage
            $trendPercent = $this->calculatePercentageChange($previousRevenue, $currentRevenue);

            // Add trend data to the category object
            $category->trend_percent = $trendPercent;
            $category->trend_direction = $trendPercent > 0 ? 'up' : ($trendPercent < 0 ? 'down' : 'stable');
        }

        return $currentCategories;
    }

    /**
     * Add trend data to products by comparing current and previous period data
     */
    private function addTrendDataToProducts($currentProducts, $previousProducts)
    {
        // Create a map of previous products by product_id for quick lookup
        $previousProductsMap = [];
        foreach ($previousProducts as $prevProduct) {
            $productId = $prevProduct->product_id ?? $prevProduct->id;
            $previousProductsMap[$productId] = $prevProduct;
        }

        // Add trend data to each current product
        foreach ($currentProducts as $product) {
            $productId = $product->product_id ?? $product->id;
            $currentRevenue = $product->total_revenue ?? 0;

            // Find previous period data for this product
            $previousRevenue = 0;
            if (isset($previousProductsMap[$productId])) {
                $previousRevenue = $previousProductsMap[$productId]->total_revenue ?? 0;
            }

            // Calculate trend percentage
            $trendPercent = $this->calculatePercentageChange($previousRevenue, $currentRevenue);

            // Add trend data to the product object
            $product->trend_percent = $trendPercent;
            $product->trend_direction = $trendPercent > 0 ? 'up' : ($trendPercent < 0 ? 'down' : 'stable');
        }

        return $currentProducts;
    }

    /**
     * Add trend data to brands by comparing current and previous period data
     */
    private function addTrendDataToBrands($currentBrands, $previousBrands)
    {
        // Create a map of previous brands by brand_id for quick lookup
        $previousBrandsMap = [];
        foreach ($previousBrands as $prevBrand) {
            $brandId = $prevBrand->brand_id ?? $prevBrand->id;
            $previousBrandsMap[$brandId] = $prevBrand;
        }

        // Add trend data to each current brand
        foreach ($currentBrands as $brand) {
            $brandId = $brand->brand_id ?? $brand->id;
            $currentRevenue = $brand->total_revenue ?? 0;

            // Find previous period data for this brand
            $previousRevenue = 0;
            if (isset($previousBrandsMap[$brandId])) {
                $previousRevenue = $previousBrandsMap[$brandId]->total_revenue ?? 0;
            }

            // Calculate trend percentage
            $trendPercent = $this->calculatePercentageChange($previousRevenue, $currentRevenue);

            // Add trend data to the brand object
            $brand->trend_percent = $trendPercent;
            $brand->trend_direction = $trendPercent > 0 ? 'up' : ($trendPercent < 0 ? 'down' : 'stable');
        }

        return $currentBrands;
    }

    /**
     * Get sales analytics data via AJAX
     */
    public function getSalesAnalyticsData(Request $request)
    {
        // Handle date range selection - default to last 30 days
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');

        if ($startDateParam && $endDateParam) {
            $startDate = Carbon::parse($startDateParam)->startOfDay();
            $endDate = Carbon::parse($endDateParam)->endOfDay();
            $dateRange = 'custom'; // For backward compatibility in view
        } else {
            $dateRange = $request->get('date_range', 'last_30_days');
            // Calculate date range based on selection
            list($startDate, $endDate) = $this->calculateDateRange($dateRange, $request);
        }

        // Calculate number of days for the selected range
        $daysInRange = $startDate->diffInDays($endDate) + 1;

        // Calculate previous period dates for comparison
        $previousStartDate = $this->calculatePreviousPeriodStart($dateRange, $startDate);
        $previousEndDate = $startDate->copy()->subSecond();

        // Parse location_ids from comma-separated string or array
        $locationIdsParam = $request->get('location_ids', '');
        $locationIds = [];
        if (!empty($locationIdsParam)) {
            if (is_array($locationIdsParam)) {
                $locationIds = $locationIdsParam;
            } else {
                $locationIds = explode(',', $locationIdsParam);
            }
            $locationIds = array_filter(array_map('intval', $locationIds));
        }

        $salesData = $this->dataProcessor->getSalesData($startDate, $endDate, $locationIds);

        // Calculate current period metrics
        $totalSales = $salesData->sum('total_sales');
        $totalTransactions = $salesData->sum('transaction_count');
        $averageSale = $salesData->avg('average_sale');

        // Calculate previous period metrics for comparison
        $previousSalesData = $this->dataProcessor->getSalesData($previousStartDate, $previousEndDate, $locationIds);
        $previousTotalSales = $previousSalesData->sum('total_sales');
        $previousTotalTransactions = $previousSalesData->sum('transaction_count');
        $previousAverageSale = $previousSalesData->avg('average_sale');

        // Calculate percentage changes
        $salesChangePercent = $this->calculatePercentageChange($previousTotalSales, $totalSales);
        $transactionsChangePercent = $this->calculatePercentageChange($previousTotalTransactions, $totalTransactions);
        $averageSaleChangePercent = $this->calculatePercentageChange($previousAverageSale, $averageSale);

        // Get currency settings
        $currencySymbol = session('currency.symbol', '৳');
        $symbolPlacement = session('business.currency_symbol_placement', 'before');
        $currencyPrecision = session('business.currency_precision', 2);
        $decimalSeparator = session('currency.decimal_separator', '.');
        $thousandSeparator = session('currency.thousand_separator', ',');

        // Format values
        $formattedTotalSales = number_format($totalSales, $currencyPrecision, $decimalSeparator, $thousandSeparator);
        $formattedAverageSale = number_format($averageSale, $currencyPrecision, $decimalSeparator, $thousandSeparator);

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => $totalSales,
                'total_sales_formatted' => $symbolPlacement == 'before' ? $currencySymbol . $formattedTotalSales : $formattedTotalSales . ' ' . $currencySymbol,
                'total_transactions' => $totalTransactions,
                'average_sale' => $averageSale,
                'average_sale_formatted' => $symbolPlacement == 'before' ? $currencySymbol . $formattedAverageSale : $formattedAverageSale . ' ' . $currencySymbol,
                'sales_change_percent' => $salesChangePercent,
                'transactions_change_percent' => $transactionsChangePercent,
                'average_sale_change_percent' => $averageSaleChangePercent,
                'days_in_range' => $daysInRange,
                'date_range' => $dateRange,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]
        ]);
    }
}

