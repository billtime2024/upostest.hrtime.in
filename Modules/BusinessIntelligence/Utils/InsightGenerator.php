<?php

namespace Modules\BusinessIntelligence\Utils;

use Modules\BusinessIntelligence\Entities\BiInsight;
use Carbon\Carbon;

class InsightGenerator
{
    protected $dataProcessor;
    protected $aiEngine;
    protected $businessId;

    public function __construct($businessId = null)
    {
        $this->businessId = $this->getBusinessId($businessId);
        $this->dataProcessor = new DataProcessor($this->businessId);
        $this->aiEngine = new AiEngine();
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
     * Generate all insights for current business
     */
    public function generateAllInsights($dateRange = 30)
    {
        try {
            $endDate = Carbon::now()->endOfDay();
            $startDate = Carbon::now()->subDays($dateRange - 1)->startOfDay();

            // Gather all data
            $businessData = $this->gatherBusinessData($startDate, $endDate);

            // Generate insights using AI
            $insights = [
                'sales' => $this->aiEngine->generateInsights(
                    $businessData['sales_analysis'],
                    'sales'
                ),
                'inventory' => $this->aiEngine->generateInsights(
                    $businessData['inventory_analysis'],
                    'inventory'
                ),
                'financial' => $this->aiEngine->generateInsights(
                    $businessData['financial_analysis'],
                    'financial'
                ),
                'customer' => $this->aiEngine->generateInsights(
                    $businessData['customer_analysis'],
                    'customer'
                ),
            ];

            // Store insights in database
            return $this->storeInsights($insights);
        } catch (\Exception $e) {
            \Log::error('Failed to generate insights: ' . $e->getMessage());
            // Return empty array instead of throwing exception
            return [];
        }
    }

    /**
     * Gather all business data for analysis
     */
    protected function gatherBusinessData($startDate, $endDate)
    {
        return [
            'sales_analysis' => $this->prepareSalesAnalysis($startDate, $endDate),
            'inventory_analysis' => $this->prepareInventoryAnalysis(),
            'financial_analysis' => $this->prepareFinancialAnalysis($startDate, $endDate),
            'customer_analysis' => $this->prepareCustomerAnalysis(),
        ];
    }

    /**
     * Prepare sales analysis data
     */
    protected function prepareSalesAnalysis($startDate, $endDate)
    {
        $salesData = $this->dataProcessor->getSalesData($startDate, $endDate);
        $topProducts = $this->dataProcessor->getTopSellingProducts($startDate, $endDate);

        // Calculate trends
        $dailySales = $salesData->pluck('total_sales')->toArray();
        $averageSale = count($dailySales) > 0 ? array_sum($dailySales) / count($dailySales) : 0;

        // Compare with previous period
        $previousStartDate = $startDate->copy()->subDays($endDate->diffInDays($startDate));
        $previousSalesData = $this->dataProcessor->getSalesData($previousStartDate, $startDate);
        $previousDailySales = $previousSalesData->pluck('total_sales')->toArray();
        $previousAverage = count($previousDailySales) > 0 ? array_sum($previousDailySales) / count($previousDailySales) : 0;

        $growthRate = $previousAverage > 0 ? (($averageSale - $previousAverage) / $previousAverage) * 100 : 0;

        return [
            'sales_trend' => $salesData->toArray(),
            'top_products' => $topProducts->toArray(),
            'average_daily_sales' => $averageSale,
            'growth_rate' => $growthRate,
            'total_transactions' => $salesData->sum('transaction_count'),
        ];
    }

    /**
     * Prepare inventory analysis data
     */
    protected function prepareInventoryAnalysis()
    {
        $inventory = $this->dataProcessor->getInventoryData();
        $lowStockThreshold = config('businessintelligence.alerts.low_stock_threshold', 10);

        $lowStockItems = $inventory->filter(function($item) use ($lowStockThreshold) {
            return $item->qty_available <= $lowStockThreshold;
        });

        // Identify overstock (items with more than 90 days of supply)
        // This is simplified - in production you'd calculate based on sales velocity
        $avgDailySales = 5; // Placeholder
        $overstockItems = $inventory->filter(function($item) use ($avgDailySales) {
            return $item->qty_available > ($avgDailySales * 90);
        });

        return [
            'total_products' => $inventory->count(),
            'inventory_value' => $inventory->sum('stock_value'),
            'low_stock_items' => $lowStockItems->values()->toArray(),
            'overstock_items' => $overstockItems->values()->toArray(),
            'out_of_stock_items' => $inventory->where('qty_available', 0)->values()->toArray(),
        ];
    }

    /**
     * Prepare financial analysis data
     */
    protected function prepareFinancialAnalysis($startDate, $endDate)
    {
        $profitData = $this->dataProcessor->calculateProfit($startDate, $endDate);
        $cashFlow = $this->dataProcessor->getCashFlowData($startDate, $endDate);

        // Calculate expense growth
        $previousStartDate = $startDate->copy()->subDays($endDate->diffInDays($startDate));
        $previousExpenses = $this->dataProcessor->getExpenseData($previousStartDate, $startDate)->sum('total_expenses');
        $currentExpenses = $profitData['expenses'];
        
        $expenseGrowth = $previousExpenses > 0 ? (($currentExpenses - $previousExpenses) / $previousExpenses) * 100 : 0;

        return [
            'profit_margin' => $profitData['profit_margin'],
            'net_profit' => $profitData['net_profit'],
            'gross_profit' => $profitData['gross_profit'],
            'total_expenses' => $profitData['expenses'],
            'expense_growth' => $expenseGrowth,
            'cash_balance' => $cashFlow->sum('total_amount'), // Simplified
            'monthly_expenses' => $currentExpenses,
        ];
    }

    /**
     * Prepare customer analysis data
     */
    protected function prepareCustomerAnalysis()
    {
        $customerDues = $this->dataProcessor->getCustomerDues();
        $overdueThreshold = config('businessintelligence.alerts.overdue_days_threshold', 30);

        $overdueCustomers = $customerDues->filter(function($customer) use ($overdueThreshold) {
            return Carbon::parse($customer->last_transaction_date)->diffInDays(Carbon::now()) > $overdueThreshold;
        });

        return [
            'total_customers' => $customerDues->count(),
            'repeat_customers' => 0, // Would need additional query
            'overdue_customers' => $overdueCustomers->values()->toArray(),
            'total_receivables' => $customerDues->sum('total_due'),
        ];
    }

    /**
     * Store insights in database
     */
    protected function storeInsights(array $insightsByType)
    {
        $storedInsights = [];

        foreach ($insightsByType as $type => $insights) {
            foreach ($insights as $insightData) {
                $insight = BiInsight::create([
                    'business_id' => $this->businessId,
                    'insight_type' => $insightData['type'],
                    'category' => $insightData['category'],
                    'title' => $insightData['title'],
                    'description' => $insightData['description'],
                    'data' => $insightData,
                    'confidence_score' => $insightData['confidence_score'],
                    'priority' => $insightData['priority'],
                    'status' => 'active',
                    'action_items' => $insightData['action_items'] ?? [],
                    'icon' => $insightData['icon'] ?? 'fas fa-lightbulb',
                    'color' => $insightData['color'] ?? 'blue',
                    'insight_date' => Carbon::now(),
                ]);

                $storedInsights[] = $insight;
            }
        }

        return $storedInsights;
    }

    /**
     * Get active insights
     */
    public function getActiveInsights($limit = null)
    {
        $query = BiInsight::where('business_id', $this->businessId)
            ->where('status', 'active')
            ->orderByDesc('priority')
            ->orderByDesc('confidence_score')
            ->orderByDesc('insight_date');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get insights by type
     */
    public function getInsightsByType($type, $limit = null)
    {
        $query = BiInsight::where('business_id', $this->businessId)
            ->where('insight_type', $type)
            ->where('status', 'active')
            ->orderByDesc('priority')
            ->orderByDesc('confidence_score');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get critical insights
     */
    public function getCriticalInsights()
    {
        return BiInsight::where('business_id', $this->businessId)
            ->where('priority', 'critical')
            ->where('status', 'active')
            ->orderByDesc('insight_date')
            ->get();
    }
}

