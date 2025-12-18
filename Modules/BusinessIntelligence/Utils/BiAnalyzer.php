<?php

namespace Modules\BusinessIntelligence\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BiAnalyzer
{
    protected $dataProcessor;
    protected $businessId;

    public function __construct($businessId = null)
    {
        $this->businessId = $this->getBusinessId($businessId);
        $this->dataProcessor = new DataProcessor($this->businessId);
    }

    /**
     * Get business ID safely
     */
    protected function getBusinessId($businessId = null)
    {
        if ($businessId) {
            return $businessId;
        }

        // Try to get from session if available
        try {
            if (request()->hasSession() && session()->has('business_id')) {
                return session()->get('business_id');
            }
        } catch (\Exception $e) {
            // Session not available
        }

        // Fallback to auth user's business
        if (auth()->check() && auth()->user()->business_id) {
            return auth()->user()->business_id;
        }

        return null;
    }

    /**
     * Get comprehensive KPI metrics
     */
    public function getKPIMetrics($startDate, $endDate, $locationId = null)
    {
        // Disable caching temporarily to ensure fresh data
        // $cacheKey = "bi_kpi_metrics_{$this->businessId}_{$startDate}_{$endDate}_{$locationId}";

        // return Cache::remember($cacheKey, config('businessintelligence.dashboard.cache_ttl', 600), function() use ($startDate, $endDate, $locationId) {

        // Get fresh data without caching
        $profitData = $this->dataProcessor->calculateProfit($startDate, $endDate, $locationId);
        $salesData = $this->dataProcessor->getSalesData($startDate, $endDate, $locationId);
        $purchaseData = $this->dataProcessor->getPurchaseData($startDate, $endDate, $locationId);
        $inventory = $this->dataProcessor->getInventoryData($locationId);
        $customerDues = $this->dataProcessor->getCustomerDues($locationId);
        $supplierDues = $this->dataProcessor->getSupplierDues($locationId);

        // Calculate profit trend data
        $profitTrendData = $this->getProfitTrendData($startDate, $endDate, $locationId);

        return [
                'revenue' => [
                    'value' => round($profitData['sales'], 0),
                    'label' => 'Total Sales',
                    'icon' => 'fas fa-dollar-sign',
                    'color' => 'success',
                    'trend' => $this->calculateTrend($salesData, 'total_sales'),
                ],
                'profit' => [
                    'value' => round($profitData['net_profit'], 0),
                    'label' => 'Net Profit',
                    'icon' => 'fas fa-chart-line',
                    'color' => 'primary',
                    'trend' => $this->calculateTrend($profitTrendData, 'net_profit'),
                    'percentage' => $profitData['profit_margin'],
                ],
                'expenses' => [
                    'value' => round($profitData['expenses'], 0),
                    'label' => 'Total Expenses',
                    'icon' => 'fas fa-money-bill-wave',
                    'color' => 'danger',
                    'trend' => null,
                ],
                'inventory_value' => [
                    'value' => $inventory->sum('stock_value'),
                    'label' => 'Inventory Value',
                    'icon' => 'fas fa-boxes',
                    'color' => 'info',
                    'count' => $inventory->count(),
                ],
                'customer_dues' => [
                    'value' => $customerDues->sum('total_due'),
                    'label' => 'Accounts Receivable',
                    'icon' => 'fas fa-users',
                    'color' => 'warning',
                    'count' => $customerDues->count(),
                ],
                'supplier_dues' => [
                    'value' => $supplierDues->sum('total_due'),
                    'label' => 'Accounts Payable',
                    'icon' => 'fas fa-truck',
                    'color' => 'danger',
                    'count' => $supplierDues->count(),
                ],
                'transactions' => [
                    'value' => $salesData->sum('transaction_count'),
                    'label' => 'Total Transactions',
                    'icon' => 'fas fa-receipt',
                    'color' => 'secondary',
                ],
                'average_sale' => [
                    'value' => $salesData->avg('average_sale') ?? 0,
                    'label' => 'Avg Sale/Invoice',
                    'icon' => 'fas fa-calculator',
                    'color' => 'info',
                ],
            ];
    }

    /**
     * Calculate trend percentage
     */
    protected function calculateTrend($data, $column)
    {
        $values = $data->pluck($column)->toArray();

        if (count($values) < 2) {
            return ['value' => 0, 'direction' => 'right'];
        }

        $halfPoint = floor(count($values) / 2);
        $firstHalf = array_slice($values, 0, $halfPoint);
        $secondHalf = array_slice($values, $halfPoint);

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        if ($firstAvg == 0 && $secondAvg == 0) {
            return ['value' => 0, 'direction' => 'right'];
        }

        if ($firstAvg == 0) {
            return null;
        }

        $trend = (($secondAvg - $firstAvg) / $firstAvg) * 100;

        return [
            'value' => round($trend, 2),
            'direction' => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'right'),
        ];
    }

    /**
     * Get profit trend data over time
     */
    protected function getProfitTrendData($startDate, $endDate, $locationId = null)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return collect();
        }

        // Get daily profit data for the period
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $profitData = [];

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::parse($startDate)->addDays($i)->format('Y-m-d');
            $dayStart = $date . ' 00:00:00';
            $dayEnd = $date . ' 23:59:59';

            $dayProfit = $this->dataProcessor->calculateProfit($dayStart, $dayEnd, $locationId);
            $profitData[] = [
                'date' => $date,
                'net_profit' => $dayProfit['net_profit'] ?? 0
            ];
        }

        return collect($profitData);
    }

    /**
     * Get sales trend chart data
     */
    public function getSalesTrendChartData($startDate, $endDate, $locationIds = null, $groupBy = 'day')
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return ['categories' => [], 'sales' => []];
        }

        // Format dates for DATETIME field comparison
        $startDateFormatted = Carbon::parse($startDate)->format('Y-m-d 00:00:00');
        $endDateFormatted = Carbon::parse($endDate)->format('Y-m-d 23:59:59');

        // Get actual sales data
        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->where('transaction_date', '>=', $startDateFormatted)
            ->where('transaction_date', '<=', $endDateFormatted);

        if ($locationIds) {
            if (is_array($locationIds)) {
                $query->whereIn('location_id', $locationIds);
            } else {
                $query->where('location_id', $locationIds);
            }
        }

        $salesData = $query->selectRaw('DATE(transaction_date) as date, SUM(final_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');


        // Generate all dates in range
        $categories = [];
        $sales = [];
        $currentDate = Carbon::parse($startDate)->startOfDay();
        $endDateCarbon = Carbon::parse($endDate)->endOfDay();

        while ($currentDate->lte($endDateCarbon)) {
            $dateKey = $currentDate->format('Y-m-d');
            $categories[] = $currentDate->format('M d');
            
            // Use actual sales data if exists, otherwise 0
            $sales[] = isset($salesData[$dateKey]) ? (float)$salesData[$dateKey]->total : 0;
            
            $currentDate->addDay();
        }

        return [
            'categories' => $categories,
            'series' => $sales, // Changed from 'sales' to 'series' for ApexCharts
            'sales' => $sales   // Keep both for backward compatibility
        ];
    }

    /**
     * Get profit comparison chart data (monthly profit vs expenses for last 6 months)
     * Uses the same calculation as the profit-loss report
     */
    public function getProfitComparisonChartData($startDate, $endDate, $locationId = null)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return ['categories' => [], 'profit' => [], 'expenses' => []];
        }

        $months = [];
        $profits = [];
        $expenses = [];

        // Use the same TransactionUtil as the profit-loss report
        $transactionUtil = new \App\Utils\TransactionUtil();
        $permittedLocations = auth()->user()->permitted_locations();

        // Get data for the last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth()->format('Y-m-d');
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth()->format('Y-m-d');

            // Use the same method as profit-loss report
            $data = $transactionUtil->getProfitLossDetails(
                $businessId,
                $locationId, // location_id
                $monthStart,
                $monthEnd,
                null, // user_id
                $permittedLocations
            );

            $months[] = Carbon::parse($monthStart)->format('M');
            $profits[] = round($data['net_profit'] ?? 0, 2);
            $expenses[] = round($data['total_expense'] ?? 0, 2);
        }

        return [
            'categories' => $months,
            'profit' => $profits,
            'expenses' => $expenses,
        ];
    }

    /**
     * Get top products chart data
     */
    public function getTopProductsChartData($startDate, $endDate, $locationId = null, $limit = 10, $sortBy = 'quantity')
    {
        $topProducts = $this->dataProcessor->getTopSellingProducts($startDate, $endDate, $limit, $locationId, $sortBy);

        // If no products, return empty data
        if ($topProducts->isEmpty()) {
            return [
                'categories' => ['No sales data'],
                'data' => [0],
                'products' => [],
            ];
        }

        // Calculate previous period dates for trend comparison
        $startDateCarbon = Carbon::parse($startDate);
        $endDateCarbon = Carbon::parse($endDate);
        $periodLength = $startDateCarbon->diffInDays($endDateCarbon) + 1;
        $previousStartDate = $startDateCarbon->copy()->subDays($periodLength);
        $previousEndDate = $previousStartDate->copy()->addDays($periodLength - 1)->endOfDay();

        // Get previous period products
        $previousTopProducts = $this->dataProcessor->getTopSellingProducts($previousStartDate, $previousEndDate, $limit, $locationId, $sortBy);

        // Create a map of previous products by product_id for quick lookup
        $previousProductsMap = [];
        foreach ($previousTopProducts as $prevProduct) {
            $productId = $prevProduct->product_id ?? $prevProduct->id;
            $previousProductsMap[$productId] = $prevProduct;
        }

        // Add trend data to current products
        foreach ($topProducts as $product) {
            $productId = $product->product_id ?? $product->id;
            $currentRevenue = $product->total_revenue ?? 0;

            // Find previous period data for this product
            $previousRevenue = 0;
            if (isset($previousProductsMap[$productId])) {
                $previousRevenue = $previousProductsMap[$productId]->total_revenue ?? 0;
            }

            // Calculate trend percentage
            if ($previousRevenue == 0) {
                $trendPercent = $currentRevenue > 0 ? 100 : 0;
            } else {
                $trendPercent = round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1);
            }

            // Add trend data to the product object
            $product->trend_percent = $trendPercent;
            $product->trend_direction = $trendPercent > 0 ? 'up' : ($trendPercent < 0 ? 'down' : 'stable');
        }

        // Return format for ApexCharts horizontal bar and product list
        $dataField = $sortBy === 'revenue' ? 'total_revenue' : 'total_quantity';

        return [
            'categories' => $topProducts->pluck('name')->toArray(),
            'data' => $topProducts->pluck($dataField)->map(function($value) {
                return round($value, 2);
            })->toArray(),
            'products' => $topProducts->toArray(),
            'sort_by' => $sortBy,
        ];
    }

    /**
     * Get top categories chart data
     */
    public function getTopCategoriesChartData($startDate, $endDate, $locationId = null, $limit = 10, $sortBy = 'revenue')
    {
        $topCategories = $this->dataProcessor->getTopSellingCategories($startDate, $endDate, $limit, $locationId, $sortBy);

        // If no categories, return empty data
        if ($topCategories->isEmpty()) {
            return [
                'categories' => ['No category data'],
                'data' => [0],
                'categories' => [],
            ];
        }

        // Calculate previous period dates for trend comparison
        $startDateCarbon = Carbon::parse($startDate);
        $endDateCarbon = Carbon::parse($endDate);
        $periodLength = $startDateCarbon->diffInDays($endDateCarbon) + 1;
        $previousStartDate = $startDateCarbon->copy()->subDays($periodLength);
        $previousEndDate = $previousStartDate->copy()->addDays($periodLength - 1)->endOfDay();

        // Get previous period categories
        $previousTopCategories = $this->dataProcessor->getTopSellingCategories($previousStartDate, $previousEndDate, $limit, $locationId, $sortBy);

        // Create a map of previous categories by category_id for quick lookup
        $previousCategoriesMap = [];
        foreach ($previousTopCategories as $prevCategory) {
            $previousCategoriesMap[$prevCategory->id] = $prevCategory;
        }

        // Add trend data to current categories
        foreach ($topCategories as $category) {
            $categoryId = $category->id;
            $currentRevenue = $category->total_revenue ?? 0;

            // Find previous period data for this category
            $previousRevenue = 0;
            if (isset($previousCategoriesMap[$categoryId])) {
                $previousRevenue = $previousCategoriesMap[$categoryId]->total_revenue ?? 0;
            }

            // Calculate trend percentage
            if ($previousRevenue == 0) {
                $trendPercent = $currentRevenue > 0 ? 100 : 0;
            } else {
                $trendPercent = round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1);
            }

            // Add trend data to the category object
            $category->trend_percent = $trendPercent;
            $category->trend_direction = $trendPercent > 0 ? 'up' : ($trendPercent < 0 ? 'down' : 'stable');
        }

        // Return format for ApexCharts horizontal bar and category list
        $dataField = $sortBy === 'quantity' ? 'total_quantity' : 'total_revenue';

        return [
            'categories' => $topCategories->pluck('category_name')->map(function($name) {
                return $name ?: 'Uncategorized';
            })->toArray(),
            'data' => $topCategories->pluck($dataField)->map(function($value) {
                return round($value, 2);
            })->toArray(),
            'categories' => $topCategories->toArray(),
            'sort_by' => $sortBy,
        ];
    }

    /**
     * Get top brands chart data
     */
    public function getTopBrandsChartData($startDate, $endDate, $locationId = null, $limit = 10, $sortBy = 'revenue')
    {
        $topBrands = $this->dataProcessor->getTopSellingBrands($startDate, $endDate, $limit, $locationId, $sortBy);

        // If no brands, return empty data
        if ($topBrands->isEmpty()) {
            return [
                'categories' => ['No brand data'],
                'data' => [0],
                'brands' => [],
            ];
        }

        // Calculate previous period dates for trend comparison
        $startDateCarbon = Carbon::parse($startDate);
        $endDateCarbon = Carbon::parse($endDate);
        $periodLength = $startDateCarbon->diffInDays($endDateCarbon) + 1;
        $previousStartDate = $startDateCarbon->copy()->subDays($periodLength);
        $previousEndDate = $previousStartDate->copy()->addDays($periodLength - 1)->endOfDay();

        // Get previous period brands
        $previousTopBrands = $this->dataProcessor->getTopSellingBrands($previousStartDate, $previousEndDate, $limit, $locationId, $sortBy);

        // Create a map of previous brands by brand_id for quick lookup
        $previousBrandsMap = [];
        foreach ($previousTopBrands as $prevBrand) {
            $brandId = $prevBrand->brand_id ?? $prevBrand->id;
            $previousBrandsMap[$brandId] = $prevBrand;
        }

        // Add trend data to current brands
        foreach ($topBrands as $brand) {
            $brandId = $brand->brand_id ?? $brand->id;
            $currentRevenue = $brand->total_revenue ?? 0;

            // Find previous period data for this brand
            $previousRevenue = 0;
            if (isset($previousBrandsMap[$brandId])) {
                $previousRevenue = $previousBrandsMap[$brandId]->total_revenue ?? 0;
            }

            // Calculate trend percentage
            if ($previousRevenue == 0) {
                $trendPercent = $currentRevenue > 0 ? 100 : 0;
            } else {
                $trendPercent = round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1);
            }

            // Add trend data to the brand object
            $brand->trend_percent = $trendPercent;
            $brand->trend_direction = $trendPercent > 0 ? 'up' : ($trendPercent < 0 ? 'down' : 'stable');
        }

        // Return format for ApexCharts horizontal bar and brand list
        $dataField = $sortBy === 'quantity' ? 'total_quantity' : 'total_revenue';

        return [
            'categories' => $topBrands->pluck('brand_name')->map(function($name) {
                return $name ?: 'Unbranded';
            })->toArray(),
            'data' => $topBrands->pluck($dataField)->map(function($value) {
                return round($value, 2);
            })->toArray(),
            'brands' => $topBrands->toArray(),
            'sort_by' => $sortBy,
        ];
    }

    /**
     * Get expense breakdown chart data
     */
    public function getExpenseBreakdownChartData($startDate, $endDate, $locationId = null)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return ['labels' => [], 'series' => []];
        }

        $query = DB::table('transactions as t')
            ->leftJoin('expense_categories as ec', 't.expense_category_id', '=', 'ec.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'expense')
            ->whereBetween('t.transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $query->where('t.location_id', $locationId);
        }

        $expenses = $query->selectRaw('COALESCE(ec.name, "Other") as category, SUM(t.final_total) as total')
            ->groupBy('category')
            ->get();

        // If no data, return default categories
        if ($expenses->isEmpty()) {
            return [
                'labels' => ['Salaries', 'Rent', 'Utilities', 'Marketing', 'Others'],
                'series' => [0, 0, 0, 0, 0],
            ];
        }

        return [
            'labels' => $expenses->pluck('category')->toArray(),
            'series' => $expenses->pluck('total')->map(function($value) {
                return round($value, 2);
            })->toArray(),
        ];
    }

    /**
     * Get inventory status chart data
     */
    public function getInventoryStatusChartData($locationId = null)
    {
        $inventory = $this->dataProcessor->getInventoryData($locationId);

        $inStock = $inventory->where('qty_available', '>', config('businessintelligence.alerts.low_stock_threshold', 10))->count();
        $lowStock = $inventory->where('qty_available', '>', 0)->where('qty_available', '<=', config('businessintelligence.alerts.low_stock_threshold', 10))->count();
        $outOfStock = $inventory->where('qty_available', '<=', 0)->count();

        return [
            'labels' => ['In Stock', 'Low Stock', 'Out of Stock'],
            'series' => [$inStock, $lowStock, $outOfStock],
        ];
    }

    /**
     * Get cash flow chart data
     */
    public function getCashFlowChartData($startDate, $endDate, $locationId = null)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return ['categories' => [], 'series' => []];
        }

        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $dailyCashFlow = $query->selectRaw('DATE(transaction_date) as date,
                          SUM(CASE WHEN type = "sell" AND status != "draft" THEN final_total ELSE 0 END) as inflow,
                          SUM(CASE WHEN type IN ("purchase", "expense") THEN final_total ELSE 0 END) as outflow')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'categories' => $dailyCashFlow->pluck('date')->map(function($date) {
                return date('M d', strtotime($date));
            })->toArray(),
            'series' => [
                [
                    'name' => 'Cash Inflow',
                    'data' => $dailyCashFlow->pluck('inflow')->toArray(),
                ],
                [
                    'name' => 'Cash Outflow',
                    'data' => $dailyCashFlow->pluck('outflow')->toArray(),
                ],
            ],
        ];
    }

    /**
     * Get performance summary
     */
    public function getPerformanceSummary($startDate, $endDate, $locationId = null)
    {
        return [
            'sales_performance' => $this->analyzeSalesPerformance($startDate, $endDate, $locationId),
            'inventory_health' => $this->analyzeInventoryHealth($locationId),
            'financial_health' => $this->analyzeFinancialHealth($startDate, $endDate, $locationId),
            'customer_metrics' => $this->analyzeCustomerMetrics(),
        ];
    }

    /**
     * Analyze sales performance
     */
    protected function analyzeSalesPerformance($startDate, $endDate, $locationId = null)
    {
        $salesData = $this->dataProcessor->getSalesData($startDate, $endDate, $locationId);
        $totalSales = $salesData->sum('total_sales');
        $totalTransactions = $salesData->sum('transaction_count');

        return [
            'total_sales' => $totalSales,
            'total_transactions' => $totalTransactions,
            'average_transaction' => $totalTransactions > 0 ? $totalSales / $totalTransactions : 0,
            'trend' => $this->calculateTrend($salesData, 'total_sales'),
        ];
    }

    /**
     * Analyze inventory health
     */
    protected function analyzeInventoryHealth($locationId = null)
    {
        $inventory = $this->dataProcessor->getInventoryData($locationId);
        $lowStockThreshold = config('businessintelligence.alerts.low_stock_threshold', 10);

        $totalValue = $inventory->sum('stock_value');
        $lowStockCount = $inventory->filter(fn($item) => $item->qty_available <= $lowStockThreshold)->count();
        $outOfStockCount = $inventory->where('qty_available', 0)->count();

        return [
            'total_value' => $totalValue,
            'total_products' => $inventory->count(),
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'health_score' => $this->calculateInventoryHealthScore($inventory->count(), $lowStockCount, $outOfStockCount),
        ];
    }

    /**
     * Calculate inventory health score
     */
    protected function calculateInventoryHealthScore($total, $lowStock, $outOfStock)
    {
        if ($total == 0) return 100;

        $lowStockPenalty = ($lowStock / $total) * 30;
        $outOfStockPenalty = ($outOfStock / $total) * 50;

        return max(0, 100 - $lowStockPenalty - $outOfStockPenalty);
    }

    /**
     * Analyze financial health
     */
    protected function analyzeFinancialHealth($startDate, $endDate, $locationId = null)
    {
        $profitData = $this->dataProcessor->calculateProfit($startDate, $endDate, $locationId);

        return [
            'profit_margin' => $profitData['profit_margin'],
            'net_profit' => $profitData['net_profit'],
            'health_score' => $this->calculateFinancialHealthScore($profitData),
        ];
    }

    /**
     * Calculate financial health score
     */
    protected function calculateFinancialHealthScore($profitData)
    {
        $score = 50; // Base score

        // Profit margin contribution (0-30 points)
        if ($profitData['profit_margin'] >= 30) {
            $score += 30;
        } elseif ($profitData['profit_margin'] >= 20) {
            $score += 20;
        } elseif ($profitData['profit_margin'] >= 10) {
            $score += 10;
        }

        // Profitability contribution (0-20 points)
        if ($profitData['net_profit'] > 0) {
            $score += 20;
        } elseif ($profitData['net_profit'] >= -1000) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * Analyze customer metrics
     */
    protected function analyzeCustomerMetrics()
    {
        $customerDues = $this->dataProcessor->getCustomerDues();

        return [
            'total_customers' => $customerDues->count(),
            'total_receivables' => $customerDues->sum('total_due'),
            'average_due' => $customerDues->count() > 0 ? $customerDues->avg('total_due') : 0,
        ];
    }

    /**
     * Get total customers count
     */
    public function getTotalCustomers()
    {
        return DB::table('contacts')
            ->where('business_id', $this->businessId)
            ->where('type', 'customer')
            ->count();
    }

    /**
     * Get total orders count
     */
    public function getTotalOrders($startDate, $endDate, $locationId = null)
    {
        $query = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->count();
    }

    /**
     * Get total products count
     */
    public function getTotalProducts()
    {
        return DB::table('products')
            ->where('business_id', $this->businessId)
            ->where('type', '!=', 'modifier')
            ->count();
    }

    /**
     * Get customer dues value for a period
     */
    public function getCustomerDuesValue($startDate, $endDate, $locationId = null)
    {
        $query = DB::table('contacts')
            ->where('business_id', $this->businessId)
            ->where('type', 'customer')
            ->where('balance', '>', 0);

        if ($locationId) {
            $query->whereExists(function($subQuery) use ($locationId) {
                $subQuery->select(DB::raw(1))
                    ->from('transactions')
                    ->whereRaw('transactions.contact_id = contacts.id')
                    ->where('transactions.location_id', $locationId);
            });
        }

        return $query->sum('balance');
    }

    /**
     * Get supplier dues value for a period
     */
    public function getSupplierDuesValue($startDate, $endDate, $locationId = null)
    {
        $query = DB::table('contacts')
            ->where('business_id', $this->businessId)
            ->where('type', 'supplier')
            ->where('balance', '<', 0);

        if ($locationId) {
            $query->whereExists(function($subQuery) use ($locationId) {
                $subQuery->select(DB::raw(1))
                    ->from('transactions')
                    ->whereRaw('transactions.contact_id = contacts.id')
                    ->where('transactions.location_id', $locationId);
            });
        }

        return abs($query->sum('balance'));
    }

    /**
     * Get average sale value for a period
     */
    public function getAverageSaleValue($startDate, $endDate, $locationId = null)
    {
        $query = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $totalSales = $query->sum('final_total');
        $totalTransactions = $query->count();

        return $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
    }

    /**
     * Get profit margin percentage
     */
    public function getProfitMargin($startDate, $endDate, $locationId = null)
    {
        $profitData = $this->dataProcessor->calculateProfit($startDate, $endDate, $locationId);

        if ($profitData['sales'] > 0) {
            return round(($profitData['net_profit'] / $profitData['sales']) * 100, 2);
        }

        return 0;
    }

    /**
     * Get revenue sources chart data
     */
    public function getRevenueSourcesChartData($startDate, $endDate, $locationId = null)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return ['labels' => [], 'series' => []];
        }

        // Try to get revenue by source field first
        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $revenueBySource = $query->selectRaw('COALESCE(source, "Direct Sales") as source_name, SUM(final_total) as total')
            ->groupBy('source_name')
            ->get();

        // If no source data, group by location
        if ($revenueBySource->isEmpty() || $revenueBySource->count() == 1) {
            $locationQuery = DB::table('transactions as t')
                ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->where('t.business_id', $businessId)
                ->where('t.type', 'sell')
                ->where('t.status', '!=', 'draft')
                ->whereBetween('t.transaction_date', [$startDate, $endDate]);

            if ($locationId) {
                $locationQuery->where('t.location_id', $locationId);
            }

            $revenueByLocation = $locationQuery->selectRaw('COALESCE(bl.name, "Main Store") as location_name, SUM(t.final_total) as total')
                ->groupBy('location_name')
                ->get();

            // If still only one location, group by payment method
            if ($revenueByLocation->isEmpty() || $revenueByLocation->count() == 1) {
                $paymentQuery = DB::table('transaction_payments as tp')
                    ->join('transactions as t', 'tp.transaction_id', '=', 't.id')
                    ->where('t.business_id', $businessId)
                    ->where('t.type', 'sell')
                    ->where('t.status', '!=', 'draft')
                    ->whereBetween('t.transaction_date', [$startDate, $endDate]);

                if ($locationId) {
                    $paymentQuery->where('t.location_id', $locationId);
                }

                $revenueByPayment = $paymentQuery->selectRaw('tp.method as payment_method, SUM(tp.amount) as total')
                    ->groupBy('payment_method')
                    ->get();

                if (!$revenueByPayment->isEmpty()) {
                    return [
                        'labels' => $revenueByPayment->pluck('payment_method')->map(function($method) {
                            return ucfirst(str_replace('_', ' ', $method));
                        })->toArray(),
                        'series' => $revenueByPayment->pluck('total')->map(function($value) {
                            return round($value, 2);
                        })->toArray(),
                    ];
                }
            }

            return [
                'labels' => $revenueByLocation->pluck('location_name')->toArray(),
                'series' => $revenueByLocation->pluck('total')->map(function($value) {
                    return round($value, 2);
                })->toArray(),
            ];
        }

        return [
            'labels' => $revenueBySource->pluck('source_name')->toArray(),
            'series' => $revenueBySource->pluck('total')->map(function($value) {
                return round($value, 2);
            })->toArray(),
        ];
    }

    /**
     * Get profit vs expense chart data
     */
    public function getProfitExpenseChartData($startDate, $endDate, $locationId = null)
    {
        return $this->getProfitComparisonChartData($startDate, $endDate, $locationId);
    }

    /**
     * Get customer growth chart data
     */
    public function getCustomerGrowthChartData($startDate, $endDate, $locationId = null)
    {
        $months = [];
        $customers = [];

        for ($i = 8; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

            $query = DB::table('contacts')
                ->where('business_id', $this->businessId)
                ->where('type', 'customer')
                ->whereBetween('created_at', [$monthStart, $monthEnd]);

            // Note: Customer creation is not location-specific, but we can filter by first transaction location
            if ($locationId) {
                $query->whereExists(function($subQuery) use ($locationId) {
                    $subQuery->select(DB::raw(1))
                        ->from('transactions')
                        ->whereRaw('transactions.contact_id = contacts.id')
                        ->where('transactions.location_id', $locationId)
                        ->where('transactions.type', 'sell');
                });
            }

            $newCustomers = $query->count();

            $months[] = $monthStart->format('M');
            $customers[] = $newCustomers;
        }

        return [
            'categories' => $months,
            'data' => $customers,
        ];
    }

    /**
     * Get Comprehensive Profit & Loss Chart Data
     * Returns complete P&L breakdown with all components
     */
    public function getProfitLossChartData($startDate = null, $endDate = null, $locationId = null)
    {
        $business_id = $this->getBusinessId();

        // If no dates provided, default to last 30 days
        if (!$startDate || !$endDate) {
            $endDate = Carbon::now()->endOfDay()->format('Y-m-d');
            $startDate = Carbon::now()->subDays(29)->startOfDay()->format('Y-m-d');
        } else {
            // Ensure dates are in correct format
            $startDate = Carbon::parse($startDate)->startOfDay()->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->endOfDay()->format('Y-m-d');
        }

        // Get profit loss details using the same method as the existing report
        $transactionUtil = new \App\Utils\TransactionUtil();
        $permitted_locations = auth()->user()->permitted_locations();
        $location_id = $locationId;
        $user_id = null;
        
        $data = $transactionUtil->getProfitLossDetails(
            $business_id,
            $location_id,
            $startDate,
            $endDate,
            $user_id,
            $permitted_locations
        );
        
        // Format data for waterfall/breakdown chart
        return [
            'categories' => [
                'Revenue',
                'COGS',
                'Gross Profit',
                'Expenses',
                'Net Profit'
            ],
            'data' => [
                [
                    'name' => 'Total Sales',
                    'value' => round($data['total_sell'], 2),
                    'color' => '#00E396'
                ],
                [
                    'name' => 'Cost of Goods Sold',
                    'value' => round($data['total_purchase'] - $data['total_purchase_discount'] + $data['opening_stock'] - $data['closing_stock'], 2),
                    'color' => '#FF4560'
                ],
                [
                    'name' => 'Gross Profit',
                    'value' => round(($data['total_sell'] - ($data['total_purchase'] - $data['total_purchase_discount'] + $data['opening_stock'] - $data['closing_stock'])), 2),
                    'color' => '#008FFB'
                ],
                [
                    'name' => 'Total Expenses',
                    'value' => round($data['total_expense'] + $data['total_sell_discount'] + $data['total_reward_amount'], 2),
                    'color' => '#FEB019'
                ],
                [
                    'name' => 'Net Profit',
                    'value' => round($data['net_profit'], 2),
                    'color' => ($data['net_profit'] >= 0) ? '#26de81' : '#fc5c65'
                ]
            ],
            'detailed_breakdown' => [
                'revenue' => [
                    'total_sales' => round($data['total_sell'], 2),
                    'sales_returns' => round($data['total_sell_return'], 2),
                    'discounts' => round($data['total_sell_discount'], 2),
                    'net_revenue' => round($data['total_sell'] - $data['total_sell_return'] - $data['total_sell_discount'], 2)
                ],
                'cogs' => [
                    'opening_stock' => round($data['opening_stock'], 2),
                    'purchases' => round($data['total_purchase'], 2),
                    'purchase_returns' => round($data['total_purchase_return'], 2),
                    'purchase_discounts' => round($data['total_purchase_discount'], 2),
                    'closing_stock' => round($data['closing_stock'], 2),
                    'total_cogs' => round($data['total_purchase'] - $data['total_purchase_discount'] + $data['opening_stock'] - $data['closing_stock'], 2)
                ],
                'expenses' => [
                    'total_expenses' => round($data['total_expense'], 2),
                    'adjustments' => round($data['total_adjustment'], 2),
                    'rewards' => round($data['total_reward_amount'], 2),
                    'shipping' => round($data['total_purchase_shipping_charge'] + $data['total_transfer_shipping_charges'], 2)
                ],
                'profit' => [
                    'gross_profit' => round(($data['total_sell'] - ($data['total_purchase'] - $data['total_purchase_discount'] + $data['opening_stock'] - $data['closing_stock'])), 2),
                    'net_profit' => round($data['net_profit'], 2),
                    'profit_margin' => $data['total_sell'] > 0 ? round(($data['net_profit'] / $data['total_sell']) * 100, 2) : 0
                ]
            ]
        ];
    }

    /**
     * Get Sales, Purchase & Expense Analytics Chart Data
     * Returns monthly comparison data for the last 6 months
     */
    public function getSalesPurchaseExpenseAnalyticsData($dateRange = 30, $locationId = null)
    {
        $business_id = $this->getBusinessId();
        $endDate = Carbon::now()->endOfDay();
        $startDate = Carbon::now()->subMonths(6)->startOfMonth();

        // Get monthly data
        $months = [];
        $salesData = [];
        $purchaseData = [];
        $expenseData = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $months[] = $monthStart->format('M Y');

            // Sales data
            $salesQuery = DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('type', 'sell')
                ->whereIn('status', ['final', 'delivered'])
                ->whereBetween('transaction_date', [
                    $monthStart->format('Y-m-d 00:00:00'),
                    $monthEnd->format('Y-m-d 23:59:59')
                ]);

            if ($locationId) {
                $salesQuery->where('location_id', $locationId);
            }

            $sales = $salesQuery->sum('final_total');
            $salesData[] = round($sales, 2);

            // Purchase data
            $purchaseQuery = DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('type', 'purchase')
                ->whereIn('status', ['received', 'ordered'])
                ->whereBetween('transaction_date', [
                    $monthStart->format('Y-m-d 00:00:00'),
                    $monthEnd->format('Y-m-d 23:59:59')
                ]);

            if ($locationId) {
                $purchaseQuery->where('location_id', $locationId);
            }

            $purchases = $purchaseQuery->sum('final_total');
            $purchaseData[] = round($purchases, 2);

            // Expense data
            $expenseQuery = DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [
                    $monthStart->format('Y-m-d 00:00:00'),
                    $monthEnd->format('Y-m-d 23:59:59')
                ]);

            if ($locationId) {
                $expenseQuery->where('location_id', $locationId);
            }

            $expenses = $expenseQuery->sum('final_total');
            $expenseData[] = round($expenses, 2);
        }

        return [
            'categories' => $months,
            'sales' => $salesData,
            'purchases' => $purchaseData,
            'expenses' => $expenseData
        ];
    }

    /**
     * Get Payment Methods Distribution
     */
    public function getPaymentMethodsData($startDate, $endDate)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return ['labels' => [], 'series' => []];
        }

        $payments = DB::table('transaction_payments as tp')
            ->join('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', '!=', 'draft')
            ->whereBetween('tp.paid_on', [
                Carbon::parse($startDate)->format('Y-m-d 00:00:00'),
                Carbon::parse($endDate)->format('Y-m-d 23:59:59')
            ])
            ->select(
                'tp.method',
                DB::raw('SUM(tp.amount) as total_amount'),
                DB::raw('COUNT(tp.id) as transaction_count')
            )
            ->groupBy('tp.method')
            ->get();

        if ($payments->isEmpty()) {
            return [
                'labels' => ['No payment data'],
                'series' => [0]
            ];
        }

        $labels = [];
        $series = [];

        foreach ($payments as $payment) {
            $labels[] = ucfirst(str_replace('_', ' ', $payment->method ?: 'Other'));
            $series[] = (float) $payment->total_amount;
        }

        return [
            'labels' => $labels,
            'series' => $series
        ];
    }

    /**
     * Get Customer Types (New vs Returning)
     */
    public function getCustomerTypesData($startDate, $endDate)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return ['returning' => 0, 'new' => 0];
        }

        // Get all customers who made purchases in this period
        $customersInPeriod = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->whereBetween('transaction_date', [
                Carbon::parse($startDate)->format('Y-m-d 00:00:00'),
                Carbon::parse($endDate)->format('Y-m-d 23:59:59')
            ])
            ->distinct()
            ->pluck('contact_id');

        if ($customersInPeriod->isEmpty()) {
            return ['returning' => 0, 'new' => 0];
        }

        $newCustomers = 0;
        $returningCustomers = 0;

        foreach ($customersInPeriod as $contactId) {
            // Check if customer had purchases before this period
            $previousPurchases = DB::table('transactions')
                ->where('business_id', $businessId)
                ->where('type', 'sell')
                ->where('status', '!=', 'draft')
                ->where('contact_id', $contactId)
                ->where('transaction_date', '<', Carbon::parse($startDate)->format('Y-m-d 00:00:00'))
                ->count();

            if ($previousPurchases > 0) {
                $returningCustomers++;
            } else {
                $newCustomers++;
            }
        }

        $total = $newCustomers + $returningCustomers;

        return [
            'returning' => $total > 0 ? round(($returningCustomers / $total) * 100, 1) : 0,
            'new' => $total > 0 ? round(($newCustomers / $total) * 100, 1) : 0,
            'returning_count' => $returningCustomers,
            'new_count' => $newCustomers
        ];
    }

    /**
     * Get Conversion Rate (Transactions with payments / Total transactions)
     */
    public function getConversionRateData($startDate, $endDate)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return ['rate' => 0, 'paid_count' => 0, 'total_count' => 0];
        }

        // Total sell transactions
        $totalTransactions = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->whereBetween('transaction_date', [
                Carbon::parse($startDate)->format('Y-m-d 00:00:00'),
                Carbon::parse($endDate)->format('Y-m-d 23:59:59')
            ])
            ->count();

        if ($totalTransactions == 0) {
            return ['rate' => 0, 'paid_count' => 0, 'total_count' => 0];
        }

        // Transactions that have received full or partial payment
        $paidTransactions = DB::table('transactions as t')
            ->join('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', '!=', 'draft')
            ->whereBetween('t.transaction_date', [
                Carbon::parse($startDate)->format('Y-m-d 00:00:00'),
                Carbon::parse($endDate)->format('Y-m-d 23:59:59')
            ])
            ->distinct('t.id')
            ->count('t.id');

        $conversionRate = round(($paidTransactions / $totalTransactions) * 100, 1);

        return [
            'rate' => $conversionRate,
            'paid_count' => $paidTransactions,
            'total_count' => $totalTransactions
        ];
    }

    /**
     * Get Hourly Sales Data
     */
    public function getHourlySalesData($startDate, $endDate, $locationId = null)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return ['hours' => [], 'data' => []];
        }

        // Get hourly sales data for the date range
        $hourlyData = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->whereBetween('transaction_date', [
                Carbon::parse($startDate)->format('Y-m-d 00:00:00'),
                Carbon::parse($endDate)->format('Y-m-d 23:59:59')
            ])
            ->selectRaw('HOUR(transaction_date) as hour, SUM(final_total) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        if ($locationId) {
            $hourlyData = DB::table('transactions')
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->where('type', 'sell')
                ->where('status', '!=', 'draft')
                ->whereBetween('transaction_date', [
                    Carbon::parse($startDate)->format('Y-m-d 00:00:00'),
                    Carbon::parse($endDate)->format('Y-m-d 23:59:59')
                ])
                ->selectRaw('HOUR(transaction_date) as hour, SUM(final_total) as total')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->keyBy('hour');
        }

        $hours = [];
        $data = [];

        // Generate data for all 24 hours
        for ($i = 0; $i < 24; $i++) {
            $hourLabel = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
            $hours[] = $hourLabel;
            $data[] = isset($hourlyData[$i]) ? round($hourlyData[$i]->total, 2) : 0;
        }

        return [
            'hours' => $hours,
            'data' => $data
        ];
    }

    /**
     * Calculate Customer Lifetime Value (CLV)
     * CLV = Average Order Value × Purchase Frequency × Customer Lifespan
     */
    public function calculateCustomerLifetimeValue($locationId = null, $historicalMonths = 12)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return [
                'average_clv' => 0,
                'total_customers_analyzed' => 0,
                'clv_segments' => [],
                'methodology' => 'historical'
            ];
        }

        // Get customer purchase history for the last N months
        $startDate = Carbon::now()->subMonths($historicalMonths)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $customerPurchases = DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('business as b', 't.business_id', '=', 'b.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', '!=', 'draft')
            ->where('c.type', 'customer')
            ->whereBetween('t.transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $customerPurchases->where('t.location_id', $locationId);
        }

        $customerData = $customerPurchases->select(
                'c.id as customer_id',
                'c.id as contact_id',
                DB::raw('COALESCE(c.name, c.supplier_business_name, "Unknown Customer") as customer_name'),
                DB::raw('COALESCE(c.supplier_business_name, "N/A") as business_name'),
                DB::raw('COALESCE(c.mobile, "N/A") as mobile_number'),
                DB::raw('COUNT(t.id) as purchase_count'),
                DB::raw('SUM(t.final_total) as total_spent'),
                DB::raw('AVG(t.final_total) as avg_order_value'),
                DB::raw('DATEDIFF(MAX(t.transaction_date), MIN(t.transaction_date)) as customer_lifespan_days'),
                DB::raw('MAX(t.transaction_date) as last_purchase_date'),
                DB::raw('MIN(t.transaction_date) as first_purchase_date')
            )
            ->groupBy('c.id', 'c.name', 'c.mobile', 'c.supplier_business_name')
            ->having('purchase_count', '>', 0)
            ->get();

        if ($customerData->isEmpty()) {
            return [
                'average_clv' => 0,
                'total_customers_analyzed' => 0,
                'clv_segments' => [],
                'methodology' => 'historical'
            ];
        }

        // Calculate CLV for each customer
        $clvData = [];
        $totalClv = 0;

        foreach ($customerData as $customer) {
            // Purchase frequency (purchases per month)
            $lifespanMonths = max(1, $customer->customer_lifespan_days / 30);
            $purchaseFrequency = $customer->purchase_count / $lifespanMonths;

            // Customer lifespan in months (assume continued relationship)
            $monthsSinceFirstPurchase = Carbon::parse($customer->first_purchase_date)->diffInMonths(Carbon::now());
            $estimatedLifespan = max($lifespanMonths, $monthsSinceFirstPurchase * 0.8); // Conservative estimate

            // CLV calculation
            $clv = $customer->avg_order_value * $purchaseFrequency * $estimatedLifespan;

            $clvData[] = [
                'customer_id' => $customer->customer_id,
                'contact_id' => $customer->contact_id,
                'customer_name' => $customer->customer_name,
                'business_name' => $customer->business_name,
                'mobile_number' => $customer->mobile_number,
                'clv' => round($clv, 2),
                'total_spent' => round($customer->total_spent, 2),
                'purchase_count' => $customer->purchase_count,
                'avg_order_value' => round($customer->avg_order_value, 2),
                'purchase_frequency' => round($purchaseFrequency, 2),
                'estimated_lifespan_months' => round($estimatedLifespan, 1),
                'segment' => $this->categorizeClvSegment($clv)
            ];

            $totalClv += $clv;
        }

        // Sort by CLV descending
        usort($clvData, fn($a, $b) => $b['clv'] <=> $a['clv']);

        // Calculate segments
        $clvSegments = $this->calculateClvSegments($clvData);

        return [
            'average_clv' => round($totalClv / count($clvData), 2),
            'total_customers_analyzed' => count($clvData),
            'clv_segments' => $clvSegments,
            'top_customers' => array_slice($clvData, 0, 50), // Return top 50 for filtering
            'all_customers' => $clvData, // Return all customers for export
            'methodology' => 'historical',
            'historical_months' => $historicalMonths
        ];
    }

    /**
     * Categorize CLV into segments
     */
    protected function categorizeClvSegment($clv)
    {
        if ($clv >= 50000) return 'high_value';
        if ($clv >= 25000) return 'medium_value';
        if ($clv >= 10000) return 'low_value';
        return 'basic';
    }

    /**
     * Calculate CLV segments distribution
     */
    protected function calculateClvSegments($clvData)
    {
        $segments = [
            'high_value' => ['count' => 0, 'total_clv' => 0, 'avg_clv' => 0],
            'medium_value' => ['count' => 0, 'total_clv' => 0, 'avg_clv' => 0],
            'low_value' => ['count' => 0, 'total_clv' => 0, 'avg_clv' => 0],
            'basic' => ['count' => 0, 'total_clv' => 0, 'avg_clv' => 0]
        ];

        foreach ($clvData as $customer) {
            $segment = $customer['segment'];
            $segments[$segment]['count']++;
            $segments[$segment]['total_clv'] += $customer['clv'];
        }

        // Calculate averages
        foreach ($segments as $segment => &$data) {
            $data['avg_clv'] = $data['count'] > 0 ? round($data['total_clv'] / $data['count'], 2) : 0;
            $data['total_clv'] = round($data['total_clv'], 2);
        }

        return $segments;
    }

    /**
     * Perform Market Basket Analysis
     * Find product associations and recommendations
     * @param string $filter 'price' or 'qty' - analysis based on price or quantity
     */
    public function performMarketBasketAnalysis($startDate, $endDate, $locationId = null, $minSupport = 0.01, $minConfidence = 0.1, $filter = 'price')
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return [
                'associations' => [],
                'recommendations' => [],
                'analysis_period' => [$startDate, $endDate]
            ];
        }

        // Get transaction data with products
        $transactions = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', '!=', 'draft')
            ->whereBetween('t.transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $transactions->where('t.location_id', $locationId);
        }

        $transactionData = $transactions->select(
                't.id as transaction_id',
                'p.id as product_id',
                'p.name as product_name',
                'tsl.quantity',
                DB::raw('(tsl.quantity * tsl.unit_price) as line_total')
            )
            ->orderBy('t.id')
            ->get()
            ->groupBy('transaction_id');

        $totalTransactions = $transactionData->count();

        if ($transactionData->isEmpty()) {
            return [
                'associations' => [],
                'recommendations' => [],
                'analysis_period' => [$startDate, $endDate]
            ];
        }

        // Calculate product frequencies based on filter
        $productFrequency = [];
        $totalWeight = 0;

        foreach ($transactionData as $transaction) {
            foreach ($transaction as $item) {
                $productId = $item->product_id;
                $weight = $filter === 'qty' ? $item->quantity : $item->line_total;

                if (!isset($productFrequency[$productId])) {
                    $productFrequency[$productId] = [
                        'name' => $item->product_name,
                        'weight' => 0,
                        'support' => 0
                    ];
                }
                $productFrequency[$productId]['weight'] += $weight;
                $totalWeight += $weight;
            }
        }

        // Calculate support for individual products
        foreach ($productFrequency as &$product) {
            $product['support'] = $totalWeight > 0 ? $product['weight'] / $totalWeight : 0;
        }

        // Filter products by minimum support
        $frequentProducts = array_filter($productFrequency, fn($p) => $p['support'] >= $minSupport);

        // Find product pairs and calculate confidence
        $associations = [];
        $productIds = array_keys($frequentProducts);

        for ($i = 0; $i < count($productIds); $i++) {
            for ($j = $i + 1; $j < count($productIds); $j++) {
                $productA = $productIds[$i];
                $productB = $productIds[$j];

                // Count transactions containing both products
                $bothCount = 0;
                foreach ($transactionData as $transaction) {
                    $hasA = $transaction->contains('product_id', $productA);
                    $hasB = $transaction->contains('product_id', $productB);
                    if ($hasA && $hasB) {
                        $bothCount++;
                    }
                }

                if ($bothCount > 0) {
                    $supportAB = $bothCount / $totalTransactions;
                    $confidenceAB = $supportAB / $frequentProducts[$productA]['support'];
                    $confidenceBA = $supportAB / $frequentProducts[$productB]['support'];
                    $lift = $supportAB / ($frequentProducts[$productA]['support'] * $frequentProducts[$productB]['support']);

                    if ($confidenceAB >= $minConfidence || $confidenceBA >= $minConfidence) {
                        $associations[] = [
                            'product_a' => $frequentProducts[$productA]['name'],
                            'product_b' => $frequentProducts[$productB]['name'],
                            'support' => round($supportAB, 4),
                            'confidence_a_to_b' => round($confidenceAB, 4),
                            'confidence_b_to_a' => round($confidenceBA, 4),
                            'lift' => round($lift, 4),
                            'transactions' => $bothCount
                        ];
                    }
                }
            }
        }

        // Sort by lift (most interesting associations first)
        usort($associations, fn($a, $b) => $b['lift'] <=> $a['lift']);

        // Generate recommendations
        $recommendations = $this->generateBasketRecommendations($associations, $frequentProducts);

        return [
            'associations' => array_slice($associations, 0, 20), // Top 20 associations
            'recommendations' => $recommendations,
            'analysis_period' => [$startDate, $endDate],
            'total_transactions' => $totalTransactions,
            'frequent_products_count' => count($frequentProducts),
            'min_support' => $minSupport,
            'min_confidence' => $minConfidence,
            'filter' => $filter,
            'total_weight' => $totalWeight
        ];
    }

    /**
     * Generate basket recommendations
     */
    protected function generateBasketRecommendations($associations, $frequentProducts)
    {
        $recommendations = [];

        foreach ($associations as $assoc) {
            if ($assoc['lift'] > 1.5 && $assoc['confidence_a_to_b'] > 0.15) {
                $recommendations[] = [
                    'if_customer_buys' => $assoc['product_a'],
                    'recommend' => $assoc['product_b'],
                    'confidence' => $assoc['confidence_a_to_b'],
                    'lift' => $assoc['lift'],
                    'strength' => $assoc['lift'] * $assoc['confidence_a_to_b']
                ];
            }

            if ($assoc['lift'] > 1.5 && $assoc['confidence_b_to_a'] > 0.15) {
                $recommendations[] = [
                    'if_customer_buys' => $assoc['product_b'],
                    'recommend' => $assoc['product_a'],
                    'confidence' => $assoc['confidence_b_to_a'],
                    'lift' => $assoc['lift'],
                    'strength' => $assoc['lift'] * $assoc['confidence_b_to_a']
                ];
            }
        }

        // Sort by strength and return top recommendations
        usort($recommendations, fn($a, $b) => $b['strength'] <=> $a['strength']);

        return array_slice($recommendations, 0, 10);
    }

    /**
     * Perform Seasonality and Trend Analysis
     */
    public function performSeasonalityTrendAnalysis($locationId = null, $analysisYears = 2)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return [
                'seasonal_patterns' => [],
                'trend_analysis' => [],
                'forecast' => [],
                'analysis_period' => []
            ];
        }

        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->subYears($analysisYears)->startOfMonth();

        // Get monthly sales data
        $monthlySales = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $monthlySales->where('location_id', $locationId);
        }

        $salesData = $monthlySales->select(
                DB::raw('YEAR(transaction_date) as year'),
                DB::raw('MONTH(transaction_date) as month'),
                DB::raw('SUM(final_total) as total_sales'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        if ($salesData->isEmpty()) {
            return [
                'seasonal_patterns' => [],
                'trend_analysis' => [],
                'forecast' => [],
                'analysis_period' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
            ];
        }

        // Analyze seasonal patterns
        $seasonalPatterns = $this->analyzeSeasonalPatterns($salesData);

        // Analyze overall trend
        $trendAnalysis = $this->analyzeTrend($salesData);

        // Generate forecast
        $forecast = $this->generateSalesForecast($salesData, 6); // 6 months forecast

        return [
            'seasonal_patterns' => $seasonalPatterns,
            'trend_analysis' => $trendAnalysis,
            'forecast' => $forecast,
            'analysis_period' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')],
            'data_points' => $salesData->count()
        ];
    }

    /**
     * Analyze seasonal patterns in sales data
     */
    protected function analyzeSeasonalPatterns($salesData)
    {
        $monthlyAverages = [];
        $monthlyData = [];

        // Group by month across years
        foreach ($salesData as $data) {
            $month = $data->month;
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [];
            }
            $monthlyData[$month][] = $data->total_sales;
        }

        // Calculate monthly averages and seasonal indices
        $overallAverage = $salesData->avg('total_sales');
        $seasonalIndices = [];

        foreach ($monthlyData as $month => $values) {
            $monthlyAvg = array_sum($values) / count($values);
            $seasonalIndex = $overallAverage > 0 ? ($monthlyAvg / $overallAverage) * 100 : 100;

            $seasonalIndices[] = [
                'month' => (int)$month,
                'month_name' => Carbon::create()->month($month)->format('F'),
                'average_sales' => round($monthlyAvg, 2),
                'seasonal_index' => round($seasonalIndex, 2),
                'seasonality_type' => $this->classifySeasonality($seasonalIndex),
                'data_points' => count($values)
            ];
        }

        // Sort by seasonal index to find peak and low seasons
        usort($seasonalIndices, fn($a, $b) => $b['seasonal_index'] <=> $a['seasonal_index']);

        return [
            'monthly_patterns' => $seasonalIndices,
            'peak_season' => $seasonalIndices[0] ?? null,
            'low_season' => end($seasonalIndices) ?? null,
            'seasonal_variation' => $this->calculateSeasonalVariation($seasonalIndices)
        ];
    }

    /**
     * Classify seasonality type
     */
    protected function classifySeasonality($index)
    {
        if ($index >= 120) return 'Very High';
        if ($index >= 110) return 'High';
        if ($index >= 90) return 'Normal';
        if ($index >= 80) return 'Low';
        return 'Very Low';
    }

    /**
     * Calculate seasonal variation coefficient
     */
    protected function calculateSeasonalVariation($seasonalIndices)
    {
        if (empty($seasonalIndices)) return 0;

        $indices = array_column($seasonalIndices, 'seasonal_index');
        $mean = array_sum($indices) / count($indices);
        $variance = 0;

        foreach ($indices as $index) {
            $variance += pow($index - $mean, 2);
        }

        $variance /= count($indices);
        return round(sqrt($variance), 2);
    }

    /**
     * Analyze overall trend in sales data
     */
    protected function analyzeTrend($salesData)
    {
        $dataPoints = $salesData->map(function($item, $index) {
            return [
                'period' => $index + 1,
                'sales' => $item->total_sales,
                'month' => $item->month,
                'year' => $item->year
            ];
        })->values();

        // Simple linear regression for trend
        $n = count($dataPoints);
        if ($n < 2) {
            return ['slope' => 0, 'direction' => 'stable', 'r_squared' => 0];
        }

        $sumX = $dataPoints->sum('period');
        $sumY = $dataPoints->sum('sales');
        $sumXY = $dataPoints->sum(function($point) {
            return $point['period'] * $point['sales'];
        });
        $sumX2 = $dataPoints->sum(function($point) {
            return pow($point['period'], 2);
        });

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - pow($sumX, 2));
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Calculate R-squared
        $yMean = $sumY / $n;
        $ssRes = 0;
        $ssTot = 0;

        foreach ($dataPoints as $point) {
            $predicted = $slope * $point['period'] + $intercept;
            $ssRes += pow($point['sales'] - $predicted, 2);
            $ssTot += pow($point['sales'] - $yMean, 2);
        }

        $rSquared = $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0;

        // Determine trend direction
        $direction = 'stable';
        if ($slope > 100) $direction = 'strongly_up';
        elseif ($slope > 50) $direction = 'up';
        elseif ($slope < -100) $direction = 'strongly_down';
        elseif ($slope < -50) $direction = 'down';

        return [
            'slope' => round($slope, 2),
            'direction' => $direction,
            'r_squared' => round($rSquared, 4),
            'intercept' => round($intercept, 2),
            'trend_strength' => $this->classifyTrendStrength($rSquared),
            'monthly_growth_rate' => round(($slope / $yMean) * 100, 2)
        ];
    }

    /**
     * Classify trend strength based on R-squared
     */
    protected function classifyTrendStrength($rSquared)
    {
        if ($rSquared >= 0.8) return 'Very Strong';
        if ($rSquared >= 0.6) return 'Strong';
        if ($rSquared >= 0.3) return 'Moderate';
        if ($rSquared >= 0.1) return 'Weak';
        return 'Very Weak';
    }

    /**
     * Generate sales forecast using trend and seasonality
     */
    protected function generateSalesForecast($salesData, $monthsAhead = 6)
    {
        $lastDataPoint = $salesData->last();
        if (!$lastDataPoint) return [];

        $forecast = [];
        $currentPeriod = $salesData->count() + 1;

        // Get trend analysis
        $trend = $this->analyzeTrend($salesData);
        $seasonalPatterns = $this->analyzeSeasonalPatterns($salesData);

        // Create seasonal index map
        $seasonalMap = [];
        foreach ($seasonalPatterns['monthly_patterns'] as $pattern) {
            $seasonalMap[$pattern['month']] = $pattern['seasonal_index'] / 100;
        }

        for ($i = 1; $i <= $monthsAhead; $i++) {
            $forecastPeriod = $currentPeriod + $i - 1;
            $forecastMonth = ($lastDataPoint->month + $i - 1) % 12 + 1;
            $forecastYear = $lastDataPoint->year + floor(($lastDataPoint->month + $i - 1) / 12);

            // Base forecast from trend line
            $trendForecast = $trend['slope'] * $forecastPeriod + $trend['intercept'];

            // Apply seasonality
            $seasonalMultiplier = $seasonalMap[$forecastMonth] ?? 1;
            $finalForecast = $trendForecast * $seasonalMultiplier;

            $forecast[] = [
                'month' => $forecastMonth,
                'year' => $forecastYear,
                'month_name' => Carbon::create()->month($forecastMonth)->format('M'),
                'forecasted_sales' => round(max(0, $finalForecast), 2),
                'trend_component' => round($trendForecast, 2),
                'seasonal_multiplier' => round($seasonalMultiplier, 4),
                'confidence_level' => $this->calculateForecastConfidence($trend['r_squared'], $i)
            ];
        }

        return $forecast;
    }

    /**
     * Calculate forecast confidence level
     */
    protected function calculateForecastConfidence($rSquared, $monthsAhead)
    {
        // Confidence decreases with time and increases with trend strength
        $baseConfidence = $rSquared * 100;
        $timePenalty = $monthsAhead * 5; // 5% penalty per month
        return max(0, min(100, $baseConfidence - $timePenalty));
    }

    /**
     * Analyze Customer Churn Rate
     */
    public function analyzeCustomerChurnRate($locationId = null, $churnPeriodDays = 90, $analysisMonths = 12)
    {
        $businessId = $this->getBusinessId();
        if (!$businessId) {
            return [
                'churn_rate' => 0,
                'churned_customers' => 0,
                'active_customers' => 0,
                'churn_segments' => [],
                'retention_rate' => 100
            ];
        }

        $endDate = Carbon::now();
        $startDate = Carbon::now()->subMonths($analysisMonths);

        // Get all customers who had transactions in the analysis period
        $customersQuery = DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', '!=', 'draft')
            ->where('c.type', 'customer')
            ->whereBetween('t.transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $customersQuery->where('t.location_id', $locationId);
        }

        $customerTransactions = $customersQuery->select(
                'c.id as customer_id',
                'c.id as contact_id',
                'c.name as customer_name',
                DB::raw('COALESCE(c.supplier_business_name, "N/A") as business_name'),
                DB::raw('COALESCE(c.mobile, "N/A") as mobile_number'),
                DB::raw('COUNT(t.id) as transaction_count'),
                DB::raw('SUM(t.final_total) as total_spent'),
                DB::raw('MAX(t.transaction_date) as last_transaction_date'),
                DB::raw('MIN(t.transaction_date) as first_transaction_date'),
                DB::raw('AVG(t.final_total) as avg_order_value')
            )
            ->groupBy('c.id', 'c.name', 'c.supplier_business_name', 'c.mobile')
            ->get();

        if ($customerTransactions->isEmpty()) {
            return [
                'churn_rate' => 0,
                'churned_customers' => 0,
                'active_customers' => 0,
                'churn_segments' => [],
                'retention_rate' => 100
            ];
        }

        // Classify customers as churned or active
        $churnedCustomers = [];
        $activeCustomers = [];
        $atRiskCustomers = [];

        foreach ($customerTransactions as $customer) {
            $daysSinceLastPurchase = Carbon::parse($customer->last_transaction_date)->diffInDays($endDate);
            $customerLifespan = Carbon::parse($customer->first_transaction_date)->diffInDays($endDate);

            if ($daysSinceLastPurchase > $churnPeriodDays) {
                // Churned customer
                $churnedCustomers[] = [
                    'customer_id' => $customer->customer_id,
                    'customer_name' => $customer->customer_name,
                    'business_name' => $customer->business_name,
                    'mobile_number' => $customer->mobile_number,
                    'days_since_last_purchase' => $daysSinceLastPurchase,
                    'total_spent' => round($customer->total_spent, 2),
                    'transaction_count' => $customer->transaction_count,
                    'avg_order_value' => round($customer->avg_order_value, 2),
                    'churn_reason' => $this->classifyChurnReason($customer, $daysSinceLastPurchase)
                ];
            } elseif ($daysSinceLastPurchase > ($churnPeriodDays * 0.7)) {
                // At risk customer
                $atRiskCustomers[] = [
                    'customer_id' => $customer->customer_id,
                    'customer_name' => $customer->customer_name,
                    'days_since_last_purchase' => $daysSinceLastPurchase,
                    'risk_level' => 'high'
                ];
            } else {
                // Active customer
                $activeCustomers[] = [
                    'customer_id' => $customer->customer_id,
                    'customer_name' => $customer->customer_name,
                    'days_since_last_purchase' => $daysSinceLastPurchase,
                    'total_spent' => round($customer->total_spent, 2)
                ];
            }
        }

        $totalCustomers = count($customerTransactions);
        $churnedCount = count($churnedCustomers);
        $activeCount = count($activeCustomers);
        $atRiskCount = count($atRiskCustomers);

        $churnRate = $totalCustomers > 0 ? ($churnedCount / $totalCustomers) * 100 : 0;
        $retentionRate = 100 - $churnRate;

        // Analyze churn by segments
        $churnSegments = $this->analyzeChurnSegments($churnedCustomers, $activeCustomers);

        return [
            'churn_rate' => round($churnRate, 2),
            'retention_rate' => round($retentionRate, 2),
            'churned_customers' => $churnedCount,
            'active_customers' => $activeCount,
            'at_risk_customers' => $atRiskCount,
            'total_customers_analyzed' => $totalCustomers,
            'churn_segments' => $churnSegments,
            'top_churned_customers' => array_slice($churnedCustomers, 0, 10),
            'all_churned_customers' => $churnedCustomers,
            'churn_period_days' => $churnPeriodDays,
            'analysis_period_months' => $analysisMonths
        ];
    }

    /**
     * Classify churn reason based on customer data
     */
    protected function classifyChurnReason($customer, $daysSinceLastPurchase)
    {
        if ($customer->transaction_count == 1) {
            return 'One-time customer';
        }

        if ($daysSinceLastPurchase > 365) {
            return 'Long-term inactive';
        }

        if ($customer->avg_order_value < 100) {
            return 'Low value customer';
        }

        return 'Recent inactivity';
    }

    /**
     * Analyze churn patterns by customer segments
     */
    protected function analyzeChurnSegments($churnedCustomers, $activeCustomers)
    {
        // Segment by total spent
        $segments = [
            'high_value' => ['churned' => 0, 'active' => 0, 'threshold' => 50000],
            'medium_value' => ['churned' => 0, 'active' => 0, 'threshold' => 10000],
            'low_value' => ['churned' => 0, 'active' => 0, 'threshold' => 0]
        ];

        foreach ($churnedCustomers as $customer) {
            $spent = $customer['total_spent'];
            if ($spent >= $segments['high_value']['threshold']) {
                $segments['high_value']['churned']++;
            } elseif ($spent >= $segments['medium_value']['threshold']) {
                $segments['medium_value']['churned']++;
            } else {
                $segments['low_value']['churned']++;
            }
        }

        foreach ($activeCustomers as $customer) {
            $spent = $customer['total_spent'];
            if ($spent >= $segments['high_value']['threshold']) {
                $segments['high_value']['active']++;
            } elseif ($spent >= $segments['medium_value']['threshold']) {
                $segments['medium_value']['active']++;
            } else {
                $segments['low_value']['active']++;
            }
        }

        // Calculate churn rates by segment
        foreach ($segments as &$segment) {
            $total = $segment['churned'] + $segment['active'];
            $segment['churn_rate'] = $total > 0 ? round(($segment['churned'] / $total) * 100, 2) : 0;
            $segment['total_customers'] = $total;
        }

        return $segments;
    }
}

