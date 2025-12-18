<?php

namespace Modules\BusinessIntelligence\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\BusinessIntelligence\Utils\BiAnalyzer;
use Modules\BusinessIntelligence\Utils\InsightGenerator;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $biAnalyzer;
    protected $insightGenerator;
    protected $businessId;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->businessId = $request->session()->get('business_id');
            $this->biAnalyzer = new BiAnalyzer($this->businessId);
            $this->insightGenerator = new InsightGenerator($this->businessId);
            return $next($request);
        });
    }

    /**
     * Convert date range string to number of days
     */
    private function getDaysFromDateRange($dateRange)
    {
        switch ($dateRange) {
            case 'today':
            case 'yesterday':
                return 1;
            case 'last_7_days':
                return 7;
            case 'last_30_days':
                return 30;
            case 'this_month':
            case 'last_month':
            case 'this_month_last_year':
                return 30; // Approximate
            case 'this_year':
            case 'last_year':
            case 'current_financial_year':
            case 'last_financial_year':
                return 365; // Approximate
            case 'custom':
                return 30; // Default for custom
            default:
                return 30; // Default fallback
        }
    }

    /**
     * Calculate previous period dates for trend comparison
     */
    private function calculatePreviousPeriod($dateRange, $currentStartDate, $currentEndDate)
    {
        $now = Carbon::now();

        switch ($dateRange) {
            case 'today':
                // Previous period: yesterday
                $previousStart = $now->copy()->subDay()->startOfDay();
                $previousEnd = $now->copy()->subDay()->endOfDay();
                break;

            case 'yesterday':
                // Previous period: day before yesterday
                $previousStart = $now->copy()->subDays(2)->startOfDay();
                $previousEnd = $now->copy()->subDays(2)->endOfDay();
                break;

            case 'last_7_days':
                // Previous period: 7 days before the current 7-day period
                $previousStart = $currentStartDate->copy()->subDays(7);
                $previousEnd = $currentEndDate->copy()->subDays(7);
                break;

            case 'last_30_days':
                // Previous period: 30 days before the current 30-day period
                $previousStart = $currentStartDate->copy()->subDays(30);
                $previousEnd = $currentEndDate->copy()->subDays(30);
                break;

            case 'this_month':
                // Previous period: last month
                $previousStart = $now->copy()->subMonth()->startOfMonth();
                $previousEnd = $now->copy()->subMonth()->endOfMonth();
                break;

            case 'last_month':
                // Previous period: month before last month
                $previousStart = $now->copy()->subMonths(2)->startOfMonth();
                $previousEnd = $now->copy()->subMonths(2)->endOfMonth();
                break;

            case 'this_month_last_year':
                // Previous period: this month two years ago
                $previousStart = $now->copy()->subYear()->startOfMonth();
                $previousEnd = $now->copy()->subYear()->endOfMonth();
                break;

            case 'this_year':
                // Previous period: last year
                $previousStart = $now->copy()->subYear()->startOfYear();
                $previousEnd = $now->copy()->subYear()->endOfYear();
                break;

            case 'last_year':
                // Previous period: year before last year
                $previousStart = $now->copy()->subYears(2)->startOfYear();
                $previousEnd = $now->copy()->subYears(2)->endOfYear();
                break;

            case 'current_financial_year':
                // Previous period: last financial year
                $currentYear = $now->year;
                $currentMonth = $now->month;

                if ($currentMonth >= 4) {
                    // Last financial year was previous year
                    $previousStart = Carbon::create($currentYear - 1, 4, 1)->startOfDay();
                    $previousEnd = Carbon::create($currentYear, 3, 31)->endOfDay();
                } else {
                    // Last financial year was two years ago
                    $previousStart = Carbon::create($currentYear - 2, 4, 1)->startOfDay();
                    $previousEnd = Carbon::create($currentYear - 1, 3, 31)->endOfDay();
                }
                break;

            case 'last_financial_year':
                // Previous period: financial year before last
                $currentYear = $now->year;
                $currentMonth = $now->month;

                if ($currentMonth >= 4) {
                    // Financial year before last was two years ago
                    $previousStart = Carbon::create($currentYear - 2, 4, 1)->startOfDay();
                    $previousEnd = Carbon::create($currentYear - 1, 3, 31)->endOfDay();
                } else {
                    // Financial year before last was three years ago
                    $previousStart = Carbon::create($currentYear - 3, 4, 1)->startOfDay();
                    $previousEnd = Carbon::create($currentYear - 2, 3, 31)->endOfDay();
                }
                break;

            case 'custom':
                // For custom ranges, calculate previous period of same length
                $periodLength = $currentStartDate->diffInDays($currentEndDate) + 1;
                $previousStart = $currentStartDate->copy()->subDays($periodLength);
                $previousEnd = $currentEndDate->copy()->subDays($periodLength);
                break;

            default:
                // Default: assume 30-day period
                $previousStart = $currentStartDate->copy()->subDays(30);
                $previousEnd = $currentEndDate->copy()->subDays(30);
                break;
        }

        return [$previousStart, $previousEnd];
    }

    /**
     * Calculate multiple previous periods for trend comparison (3 periods)
     */
    private function calculateMultiplePreviousPeriods($dateRange, $currentStartDate, $currentEndDate, $numPeriods = 3)
    {
        $periods = [];

        // Calculate each previous period iteratively
        $tempStart = $currentStartDate;
        $tempEnd = $currentEndDate;

        for ($i = 0; $i < $numPeriods; $i++) {
            list($prevStart, $prevEnd) = $this->calculatePreviousPeriod($dateRange, $tempStart, $tempEnd);
            $periods[] = [$prevStart, $prevEnd];
            $tempStart = $prevStart;
            $tempEnd = $prevEnd;
        }

        return $periods;
    }

    /**
     * Calculate trend value between previous and current periods
     */
    private function calculateTrendValue($previousValue, $currentValue)
    {
        if ($previousValue == 0 && $currentValue == 0) {
            return [
                'value' => 0,
                'direction' => 'right',
            ];
        }

        if ($previousValue == 0) {
            return null;
        }

        $trend = (($currentValue - $previousValue) / $previousValue) * 100;

        return [
            'value' => round($trend, 2),
            'direction' => $trend >= 0 ? 'up' : 'down',
        ];
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
     * Display the main BI dashboard
     */
    public function index(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');

        // Handle date range selection - default to last 30 days
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');

        if ($startDateParam && $endDateParam) {
            $startDate = Carbon::parse($startDateParam)->startOfDay();
            $endDate = Carbon::parse($endDateParam)->endOfDay();
            $dateRange = 'custom'; // For backward compatibility in view
        } else {
            // Default to last 30 days
            $dateRange = 'last_30_days';
            list($startDate, $endDate) = $this->calculateDateRange($dateRange, $request);
        }

        // Handle location filter
        $selectedLocation = $request->get('location_id', '');

        // Get KPI metrics with location filtering
        $locationId = $selectedLocation ?: null;
        $baseKpis = $this->biAnalyzer->getKPIMetrics($startDate, $endDate, $locationId);

        // Calculate multiple previous periods for trend comparison (3 periods)
        $previousPeriods = $this->calculateMultiplePreviousPeriods($dateRange, $startDate, $endDate, 3);

        // Calculate trends for orders KPI
        $currentOrders = $this->biAnalyzer->getTotalOrders($startDate, $endDate, $locationId);
        $ordersTrends = [];
        $previousOrdersValues = [$currentOrders]; // Start with current

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevOrders = $this->biAnalyzer->getTotalOrders($prevStart, $prevEnd, $locationId);
            $previousOrdersValues[] = $prevOrders;
        }

        // Calculate trends between consecutive periods (current vs prev1, prev1 vs prev2, prev2 vs prev3)
        for ($i = 0; $i < count($previousOrdersValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousOrdersValues[$i + 1], $previousOrdersValues[$i]);
            $ordersTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        // Calculate trends for customer dues KPI
        $currentCustomerDues = $baseKpis['customer_dues']['value'];
        $customerDuesTrends = [];
        $previousCustomerDuesValues = [$currentCustomerDues];

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevCustomerDues = $this->biAnalyzer->getCustomerDuesValue($prevStart, $prevEnd, $locationId);
            $previousCustomerDuesValues[] = $prevCustomerDues;
        }

        for ($i = 0; $i < count($previousCustomerDuesValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousCustomerDuesValues[$i + 1], $previousCustomerDuesValues[$i]);
            $customerDuesTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        // Calculate trends for supplier dues KPI
        $currentSupplierDues = $baseKpis['supplier_dues']['value'];
        $supplierDuesTrends = [];
        $previousSupplierDuesValues = [$currentSupplierDues];

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevSupplierDues = $this->biAnalyzer->getSupplierDuesValue($prevStart, $prevEnd, $locationId);
            $previousSupplierDuesValues[] = $prevSupplierDues;
        }

        for ($i = 0; $i < count($previousSupplierDuesValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousSupplierDuesValues[$i + 1], $previousSupplierDuesValues[$i]);
            $supplierDuesTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        // Calculate trends for average sale KPI
        $currentAverageSale = $baseKpis['average_sale']['value'];
        $averageSaleTrends = [];
        $previousAverageSaleValues = [$currentAverageSale];

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevAverageSale = $this->biAnalyzer->getAverageSaleValue($prevStart, $prevEnd, $locationId);
            $previousAverageSaleValues[] = $prevAverageSale;
        }

        for ($i = 0; $i < count($previousAverageSaleValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousAverageSaleValues[$i + 1], $previousAverageSaleValues[$i]);
            $averageSaleTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        // Calculate trends for revenue KPI
        $currentRevenue = $baseKpis['revenue']['value'];
        $revenueTrends = [];
        $previousRevenueValues = [$currentRevenue];

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevKpis = $this->biAnalyzer->getKPIMetrics($prevStart, $prevEnd, $locationId);
            $previousRevenueValues[] = $prevKpis['revenue']['value'];
        }

        for ($i = 0; $i < count($previousRevenueValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousRevenueValues[$i + 1], $previousRevenueValues[$i]);
            $revenueTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        // Calculate trends for profit KPI
        $currentProfit = $baseKpis['profit']['value'];
        $profitTrends = [];
        $previousProfitValues = [$currentProfit];

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevKpis = $this->biAnalyzer->getKPIMetrics($prevStart, $prevEnd, $locationId);
            $previousProfitValues[] = $prevKpis['profit']['value'];
        }

        for ($i = 0; $i < count($previousProfitValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousProfitValues[$i + 1], $previousProfitValues[$i]);
            $profitTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        $customersTrend = null; // Customers is total count, trend might not be meaningful

        // Ensure all KPIs have trend indicators, default to 0 if not calculated
        $defaultTrends = [
            ['value' => 0, 'direction' => 'right'],
            ['value' => 0, 'direction' => 'right'],
            ['value' => 0, 'direction' => 'right']
        ];

        // Reorder KPIs according to specification and remove unwanted ones
        $kpis = [
            'revenue' => array_merge($baseKpis['revenue'], ['trends' => $revenueTrends]),
            'profit' => array_merge($baseKpis['profit'], ['trends' => $profitTrends]),
            'customer_dues' => array_merge($baseKpis['customer_dues'], ['trends' => $customerDuesTrends ?? $defaultTrends]),
            'supplier_dues' => array_merge($baseKpis['supplier_dues'], ['trends' => $supplierDuesTrends ?? $defaultTrends]),
            'customers' => [
                'label' => 'Total Customers',
                'value' => $this->biAnalyzer->getTotalCustomers(),
                'icon' => 'fa fa-users',
                'color' => 'primary',
                'trends' => $customersTrend ? [$customersTrend, $customersTrend, $customersTrend] : $defaultTrends
            ],
            'orders' => [
                'label' => 'Total Invoices',
                'value' => $this->biAnalyzer->getTotalOrders($startDate, $endDate, $locationId),
                'icon' => 'fa fa-shopping-cart',
                'color' => 'success',
                'trends' => $ordersTrends ?? $defaultTrends
            ],
            'products' => [
                'label' => 'Total Products',
                'value' => $this->biAnalyzer->getTotalProducts(),
                'icon' => 'fa fa-cube',
                'color' => 'info',
                'trends' => $defaultTrends // Products is static, show 0 trend
            ],
            'average_sale' => array_merge($baseKpis['average_sale'], ['trends' => $averageSaleTrends ?? $defaultTrends])
        ];

        // Get recent insights
        $insights = $this->insightGenerator->getActiveInsights(5);

        // Auto-generate insights if none exist (first-time load)
        if ($insights->count() == 0) {
            try {
                Log::info('Auto-generating insights for business: ' . $businessId);
                // Convert date range string to days for insight generation
                $days = $this->getDaysFromDateRange($dateRange);
                $generatedInsights = $this->insightGenerator->generateAllInsights($days);
                $insights = $this->insightGenerator->getActiveInsights(5);
                Log::info('Auto-generated ' . $insights->count() . ' insights from ' . count($generatedInsights) . ' generated');
            } catch (\Exception $e) {
                Log::error('Failed to auto-generate insights: ' . $e->getMessage());
                // Continue anyway, will show empty state with generate button
                $insights = collect(); // Ensure it's a collection
            }
        }

        // Get critical insights
        $criticalInsights = $this->insightGenerator->getCriticalInsights();

        // Get business locations for filter dropdown
        $businessLocations = \App\BusinessLocation::where('business_id', $businessId)->get();

        // Get currency settings from session (Ultimate POS standard)
        $currencySymbol = session('currency.symbol', '৳');
        $currencyPrecision = session('business.currency_precision', 2);
        $currencyDecimalSeparator = session('currency.decimal_separator', '.');
        $currencyThousandSeparator = session('currency.thousand_separator', ',');

        return view('businessintelligence::dashboard.index', compact(
            'kpis',
            'insights',
            'criticalInsights',
            'dateRange',
            'selectedLocation',
            'businessLocations',
            'currencySymbol',
            'currencyPrecision',
            'currencyDecimalSeparator',
            'currencyThousandSeparator'
        ));
    }

    /**
     * Get KPI data via AJAX
     */
    public function getKPIs(Request $request)
    {
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $locationId = $request->get('location_id', null);

        if ($startDateParam && $endDateParam) {
            $startDate = Carbon::parse($startDateParam)->startOfDay();
            $endDate = Carbon::parse($endDateParam)->endOfDay();
            $dateRange = 'custom';
        } else {
            // Default to last 30 days
            $dateRange = 'last_30_days';
            list($startDate, $endDate) = $this->calculateDateRange($dateRange, $request);
        }

        $baseKpis = $this->biAnalyzer->getKPIMetrics($startDate, $endDate, $locationId);

        // Calculate multiple previous periods for trend comparison (3 periods)
        $previousPeriods = $this->calculateMultiplePreviousPeriods($dateRange, $startDate, $endDate, 3);

        // Calculate trends for revenue KPI
        $currentRevenue = $baseKpis['revenue']['value'];
        $revenueTrends = [];
        $previousRevenueValues = [$currentRevenue];

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevKpis = $this->biAnalyzer->getKPIMetrics($prevStart, $prevEnd, $locationId);
            $previousRevenueValues[] = $prevKpis['revenue']['value'];
        }

        for ($i = 0; $i < count($previousRevenueValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousRevenueValues[$i + 1], $previousRevenueValues[$i]);
            $revenueTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        // Calculate trends for profit KPI
        $currentProfit = $baseKpis['profit']['value'];
        $profitTrends = [];
        $previousProfitValues = [$currentProfit];

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevKpis = $this->biAnalyzer->getKPIMetrics($prevStart, $prevEnd, $locationId);
            $previousProfitValues[] = $prevKpis['profit']['value'];
        }

        for ($i = 0; $i < count($previousProfitValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousProfitValues[$i + 1], $previousProfitValues[$i]);
            $profitTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        // Calculate trends for other KPIs as in index method
        $currentOrders = $this->biAnalyzer->getTotalOrders($startDate, $endDate, $locationId);
        $ordersTrends = [];
        $previousOrdersValues = [$currentOrders];

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevOrders = $this->biAnalyzer->getTotalOrders($prevStart, $prevEnd, $locationId);
            $previousOrdersValues[] = $prevOrders;
        }

        for ($i = 0; $i < count($previousOrdersValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousOrdersValues[$i + 1], $previousOrdersValues[$i]);
            $ordersTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        // Calculate trends for customer dues
        $currentCustomerDues = $baseKpis['customer_dues']['value'];
        $customerDuesTrends = [];
        $previousCustomerDuesValues = [$currentCustomerDues];

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevCustomerDues = $this->biAnalyzer->getCustomerDuesValue($prevStart, $prevEnd, $locationId);
            $previousCustomerDuesValues[] = $prevCustomerDues;
        }

        for ($i = 0; $i < count($previousCustomerDuesValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousCustomerDuesValues[$i + 1], $previousCustomerDuesValues[$i]);
            $customerDuesTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        // Calculate trends for supplier dues
        $currentSupplierDues = $baseKpis['supplier_dues']['value'];
        $supplierDuesTrends = [];
        $previousSupplierDuesValues = [$currentSupplierDues];

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevSupplierDues = $this->biAnalyzer->getSupplierDuesValue($prevStart, $prevEnd, $locationId);
            $previousSupplierDuesValues[] = $prevSupplierDues;
        }

        for ($i = 0; $i < count($previousSupplierDuesValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousSupplierDuesValues[$i + 1], $previousSupplierDuesValues[$i]);
            $supplierDuesTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        // Calculate trends for average sale
        $currentAverageSale = $baseKpis['average_sale']['value'];
        $averageSaleTrends = [];
        $previousAverageSaleValues = [$currentAverageSale];

        foreach ($previousPeriods as $period) {
            list($prevStart, $prevEnd) = $period;
            $prevAverageSale = $this->biAnalyzer->getAverageSaleValue($prevStart, $prevEnd, $locationId);
            $previousAverageSaleValues[] = $prevAverageSale;
        }

        for ($i = 0; $i < count($previousAverageSaleValues) - 1; $i++) {
            $trend = $this->calculateTrendValue($previousAverageSaleValues[$i + 1], $previousAverageSaleValues[$i]);
            $averageSaleTrends[] = $trend ?? ['value' => 0, 'direction' => 'right'];
        }

        // Default trends
        $defaultTrends = [
            ['value' => 0, 'direction' => 'right'],
            ['value' => 0, 'direction' => 'right'],
            ['value' => 0, 'direction' => 'right']
        ];

        // Build KPIs with trends
        $kpis = [
            'revenue' => array_merge($baseKpis['revenue'], ['trends' => $revenueTrends]),
            'profit' => array_merge($baseKpis['profit'], ['trends' => $profitTrends]),
            'customer_dues' => array_merge($baseKpis['customer_dues'], ['trends' => $customerDuesTrends]),
            'supplier_dues' => array_merge($baseKpis['supplier_dues'], ['trends' => $supplierDuesTrends]),
            'customers' => [
                'label' => 'Total Customers',
                'value' => $this->biAnalyzer->getTotalCustomers(),
                'icon' => 'fa fa-users',
                'color' => 'primary',
                'trends' => $defaultTrends
            ],
            'orders' => [
                'label' => 'Total Invoices',
                'value' => $this->biAnalyzer->getTotalOrders($startDate, $endDate, $locationId),
                'icon' => 'fa fa-shopping-cart',
                'color' => 'success',
                'trends' => $ordersTrends
            ],
            'products' => [
                'label' => 'Total Products',
                'value' => $this->biAnalyzer->getTotalProducts(),
                'icon' => 'fa fa-cube',
                'color' => 'info',
                'trends' => $defaultTrends
            ],
            'average_sale' => array_merge($baseKpis['average_sale'], ['trends' => $averageSaleTrends])
        ];

        return response()->json([
            'success' => true,
            'data' => $kpis
        ]);
    }

    /**
     * Get chart data
     */
    public function getChartData(Request $request)
    {
        $chartType = $request->get('chart_type');
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $locationId = $request->get('location_id', null);
        $locationIds = $request->get('location_ids', null); // For multiple locations
        $sortBy = $request->get('sort_by', 'revenue');

        // Parse location_ids if provided
        if ($locationIds) {
            if (is_array($locationIds)) {
                $locationIds = $locationIds;
            } else {
                $locationIds = explode(',', $locationIds);
            }
            $locationIds = array_filter(array_map('intval', $locationIds));
        } elseif ($locationId) {
            $locationIds = [$locationId];
        } else {
            $locationIds = null;
        }

        if ($startDateParam && $endDateParam) {
            $startDate = Carbon::parse($startDateParam)->startOfDay();
            $endDate = Carbon::parse($endDateParam)->endOfDay();
        } else {
            // Default to last 30 days
            list($startDate, $endDate) = $this->calculateDateRange('last_30_days', $request);
        }

        $chartData = [];

        switch ($chartType) {
            case 'sales_trend':
                $chartData = $this->biAnalyzer->getSalesTrendChartData($startDate, $endDate, $locationIds);
                break;
            case 'revenue_sources':
                $chartData = $this->biAnalyzer->getRevenueSourcesChartData($startDate, $endDate, $locationId);
                break;
            case 'profit_expense':
                $chartData = $this->biAnalyzer->getProfitExpenseChartData($startDate, $endDate, $locationId);
                break;
            case 'cash_flow':
                $chartData = $this->biAnalyzer->getCashFlowChartData($startDate, $endDate, $locationId);
                break;
            case 'top_products':
                $chartData = $this->biAnalyzer->getTopProductsChartData($startDate, $endDate, $locationIds, 10, $sortBy);
                break;
            case 'top_categories':
                $chartData = $this->biAnalyzer->getTopCategoriesChartData($startDate, $endDate, $locationIds, 10, $sortBy);
                break;
            case 'top_brands':
                $chartData = $this->biAnalyzer->getTopBrandsChartData($startDate, $endDate, $locationIds, 10, $sortBy);
                break;
            case 'inventory_status':
                $chartData = $this->biAnalyzer->getInventoryStatusChartData($locationId);
                break;
            case 'expense_breakdown':
                $chartData = $this->biAnalyzer->getExpenseBreakdownChartData($startDate, $endDate, $locationId);
                break;
            case 'customer_growth':
                $chartData = $this->biAnalyzer->getCustomerGrowthChartData($startDate, $endDate, $locationId);
                break;
            case 'sales_purchase_expense_analytics':
                // This method always returns last 6 months data, dateRange parameter is ignored
                $chartData = $this->biAnalyzer->getSalesPurchaseExpenseAnalyticsData(30, $locationId);
                break;
            case 'profit_loss_complete':
                // Pass actual start and end dates to BiAnalyzer method
                $chartData = $this->biAnalyzer->getProfitLossChartData($startDate, $endDate, $locationId);
                break;
            case 'payment_methods':
                $chartData = $this->biAnalyzer->getPaymentMethodsData($startDate, $endDate, $locationId);
                break;
            case 'customer_types':
                $chartData = $this->biAnalyzer->getCustomerTypesData($startDate, $endDate, $locationId);
                break;
            case 'conversion_rate':
                $chartData = $this->biAnalyzer->getConversionRateData($startDate, $endDate, $locationId);
                break;
            case 'hourly_sales':
                $chartData = $this->biAnalyzer->getHourlySalesData($startDate, $endDate, $locationId);
                break;
            default:
                return response()->json(['success' => false, 'message' => 'Invalid chart type'], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $chartData
        ]);
    }

    /**
     * Get performance summary
     */
    public function getPerformanceSummary(Request $request)
    {
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $locationId = $request->get('location_id', null);

        if ($startDateParam && $endDateParam) {
            $startDate = Carbon::parse($startDateParam)->startOfDay();
            $endDate = Carbon::parse($endDateParam)->endOfDay();
        } else {
            // Default to last 30 days
            list($startDate, $endDate) = $this->calculateDateRange('last_30_days', $request);
        }

        $summary = $this->biAnalyzer->getPerformanceSummary($startDate, $endDate, $locationId);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Refresh dashboard data
     */
    public function refreshData(Request $request)
    {
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $locationId = $request->get('location_id', null);

        if ($startDateParam && $endDateParam) {
            $startDate = Carbon::parse($startDateParam)->startOfDay();
            $endDate = Carbon::parse($endDateParam)->endOfDay();
        } else {
            // Default to last 30 days
            list($startDate, $endDate) = $this->calculateDateRange('last_30_days', $request);
        }

        // Clear cache - use Cache facade properly
        try {
            Cache::forget("bi_kpi_metrics_{$request->session()->get('user.business_id')}_{$startDate}_{$endDate}_{$locationId}");
        } catch (\Exception $e) {
            // Cache might not be available, continue anyway
            Log::warning('Cache forget failed: ' . $e->getMessage());
        }

        // Get fresh data
        $kpis = $this->biAnalyzer->getKPIMetrics($startDate, $endDate, $locationId);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data refreshed successfully',
            'data' => $kpis
        ]);
    }

    /**
     * Export dashboard data
     */
    public function exportDashboard(Request $request)
    {
        $startDateParam = $request->get('start_date');
        $endDateParam = $request->get('end_date');
        $locationId = $request->get('location_id', null);

        if ($startDateParam && $endDateParam) {
            $startDate = Carbon::parse($startDateParam)->startOfDay();
            $endDate = Carbon::parse($endDateParam)->endOfDay();
        } else {
            // Default to last 30 days
            list($startDate, $endDate) = $this->calculateDateRange('last_30_days', $request);
        }

        // Get all dashboard data
        $kpis = $this->biAnalyzer->getKPIMetrics($startDate, $endDate, $locationId);
        
        // For now, return JSON. Can be enhanced to PDF/Excel later
        return response()->json([
            'success' => true,
            'data' => [
                'date_range' => $dateRange,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'kpis' => $kpis,
            ],
            'message' => 'Dashboard data exported successfully'
        ]);
    }

    /**
     * Display Advanced KPI Analytics page
     */
    public function advancedKpi(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');

        // Handle location filter
        $selectedLocation = $request->get('location_id', '');


        // Get advanced analytics data
        $locationId = $selectedLocation ?: null;

        // Customer Lifetime Value Analysis
        $clvData = $this->biAnalyzer->calculateCustomerLifetimeValue($locationId);

        // Market Basket Analysis (last 6 months)
        $basketAnalysisStart = Carbon::now()->subMonths(6)->startOfMonth();
        $basketAnalysisEnd = Carbon::now()->endOfMonth();
        $basketData = $this->biAnalyzer->performMarketBasketAnalysis(
            $basketAnalysisStart->format('Y-m-d'),
            $basketAnalysisEnd->format('Y-m-d'),
            $locationId,
            0.01,
            0.1
        );

        // Seasonality & Trend Analysis
        $seasonalityData = $this->biAnalyzer->performSeasonalityTrendAnalysis($locationId);

        // Customer Churn Analysis
        $churnData = $this->biAnalyzer->analyzeCustomerChurnRate($locationId);

        // Get business locations for filter dropdown
        $businessLocations = \App\BusinessLocation::where('business_id', $businessId)->get();

        // Get currency settings
        $currencySymbol = session('currency.symbol', '৳');
        $currencyPrecision = session('business.currency_precision', 2);

        return view('businessintelligence::advanced-kpi.index', compact(
            'clvData',
            'basketData',
            'seasonalityData',
            'churnData',
            'selectedLocation',
            'businessLocations',
            'currencySymbol',
            'currencyPrecision'
        ));
    }

    /**
     * Get advanced KPI data via AJAX
     */
    public function getAdvancedKpiData(Request $request)
    {
        $analysisType = $request->get('analysis_type');
        $locationId = $request->get('location_id', null);

        $data = [];

        switch ($analysisType) {
            case 'clv':
                $data = $this->biAnalyzer->calculateCustomerLifetimeValue($locationId);
                break;
            case 'market_basket':
                $basketAnalysisStart = Carbon::now()->subMonths(6)->startOfMonth();
                $basketAnalysisEnd = Carbon::now()->endOfMonth();
                $data = $this->biAnalyzer->performMarketBasketAnalysis(
                    $basketAnalysisStart->format('Y-m-d'),
                    $basketAnalysisEnd->format('Y-m-d'),
                    $locationId,
                    0.01,
                    0.1
                );
                break;
            case 'seasonality':
                $data = $this->biAnalyzer->performSeasonalityTrendAnalysis($locationId);
                break;
            case 'churn':
                $data = $this->biAnalyzer->analyzeCustomerChurnRate($locationId);
                break;
            default:
                return response()->json(['success' => false, 'message' => 'Invalid analysis type'], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}


