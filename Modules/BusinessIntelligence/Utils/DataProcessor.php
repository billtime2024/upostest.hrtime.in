<?php

namespace Modules\BusinessIntelligence\Utils;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DataProcessor
{
    protected $businessId;

    public function __construct($businessId = null)
    {
        $this->businessId = $this->getBusinessId($businessId);
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
     * Get sales data for a date range
     */
    public function getSalesData($startDate, $endDate, $locationIds = null)
    {
        $query = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if (!empty($locationIds) && is_array($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        } elseif ($locationIds && !is_array($locationIds)) {
            // Backward compatibility for single location
            $query->where('location_id', $locationIds);
        }

        return $query->select(
            DB::raw('DATE(transaction_date) as date'),
            DB::raw('COUNT(*) as transaction_count'),
            DB::raw('SUM(final_total) as total_sales'),
            DB::raw('SUM(final_total - total_before_tax) as total_tax'),
            DB::raw('AVG(final_total) as average_sale')
        )
        ->groupBy(DB::raw('DATE(transaction_date)'))
        ->orderBy('date')
        ->get();
    }

    /**
     * Get purchase data for a date range
     */
    public function getPurchaseData($startDate, $endDate, $locationId = null)
    {
        $query = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('type', 'purchase')
            ->where('status', '!=', 'draft')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->select(
            DB::raw('DATE(transaction_date) as date'),
            DB::raw('COUNT(*) as transaction_count'),
            DB::raw('SUM(final_total) as total_purchases'),
            DB::raw('AVG(final_total) as average_purchase')
        )
        ->groupBy(DB::raw('DATE(transaction_date)'))
        ->orderBy('date')
        ->get();
    }

    /**
     * Get expense data for a date range
     */
    public function getExpenseData($startDate, $endDate, $locationId = null)
    {
        $query = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->select(
            DB::raw('DATE(transaction_date) as date'),
            DB::raw('COUNT(*) as expense_count'),
            DB::raw('SUM(final_total) as total_expenses'),
            'expense_category_id'
        )
        ->groupBy(DB::raw('DATE(transaction_date)'), 'expense_category_id')
        ->orderBy('date')
        ->get();
    }

    /**
     * Get inventory/stock data
     */
    public function getInventoryData($locationId = null)
    {
        $query = DB::table('variation_location_details as vld')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('p.business_id', $this->businessId)
            ->where('p.type', '!=', 'modifier');

        if ($locationId) {
            $query->where('vld.location_id', $locationId);
        }

        return $query->select(
            'p.id as product_id',
            'p.name as product_name',
            'p.sku',
            'v.id as variation_id',
            'pv.name as variation_name',
            'vld.qty_available',
            'vld.location_id',
            DB::raw('COALESCE(v.default_purchase_price, 0) as purchase_price'),
            DB::raw('COALESCE(v.default_sell_price, 0) as sell_price'),
            DB::raw('vld.qty_available * COALESCE(v.default_purchase_price, 0) as stock_value')
        )
        ->get();
    }

    /**
     * Get customer dues (accounts receivable)
     */
    public function getCustomerDues($locationId = null)
    {
        $query = DB::table('contacts as c')
            ->leftJoin('transactions as t', function($join) {
                $join->on('c.id', '=', 't.contact_id')
                     ->where('t.type', 'sell')
                     ->where('t.payment_status', '!=', 'paid');
            })
            ->where('c.business_id', $this->businessId)
            ->where('c.type', 'customer');

        if ($locationId) {
            $query->where('t.location_id', $locationId);
        }

        return $query->select(
                'c.id',
                'c.name',
                'c.mobile',
                DB::raw('COUNT(DISTINCT t.id) as pending_invoices'),
                DB::raw('SUM(t.final_total - COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = t.id), 0)) as total_due'),
                DB::raw('MAX(t.transaction_date) as last_transaction_date')
            )
            ->groupBy('c.id', 'c.name', 'c.mobile')
            ->havingRaw('total_due > 0')
            ->get();
    }

    /**
     * Get supplier dues (accounts payable)
     */
    public function getSupplierDues($locationId = null)
    {
        $query = DB::table('contacts as c')
            ->leftJoin('transactions as t', function($join) {
                $join->on('c.id', '=', 't.contact_id')
                     ->where('t.type', 'purchase')
                     ->where('t.payment_status', '!=', 'paid');
            })
            ->where('c.business_id', $this->businessId)
            ->where('c.type', 'supplier');

        if ($locationId) {
            $query->where('t.location_id', $locationId);
        }

        return $query->select(
                'c.id',
                'c.name',
                'c.mobile',
                DB::raw('COUNT(DISTINCT t.id) as pending_invoices'),
                DB::raw('SUM(t.final_total - COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = t.id), 0)) as total_due'),
                DB::raw('MAX(t.transaction_date) as last_transaction_date')
            )
            ->groupBy('c.id', 'c.name', 'c.mobile')
            ->havingRaw('total_due > 0')
            ->get();
    }

    /**
     * Get cash flow data
     */
    public function getCashFlowData($startDate, $endDate)
    {
        return DB::table('transaction_payments as tp')
            ->join('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->where('t.business_id', $this->businessId)
            ->whereBetween('tp.paid_on', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(tp.paid_on) as date'),
                't.type as transaction_type',
                'tp.method as payment_method',
                DB::raw('SUM(tp.amount) as total_amount'),
                DB::raw('COUNT(*) as payment_count')
            )
            ->groupBy(DB::raw('DATE(tp.paid_on)'), 't.type', 'tp.method')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get top selling products
     */
    public function getTopSellingProducts($startDate, $endDate, $limit = 10, $locationIds = null, $sortBy = 'quantity')
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('t.business_id', $this->businessId)
            ->where('t.type', 'sell')
            ->where('t.status', '!=', 'draft')
            ->whereBetween('t.transaction_date', [$startDate, $endDate]);

        if (!empty($locationIds) && is_array($locationIds)) {
            $query->whereIn('t.location_id', $locationIds);
        } elseif ($locationIds && !is_array($locationIds)) {
            // Backward compatibility for single location
            $query->where('t.location_id', $locationIds);
        }

        $orderBy = $sortBy === 'revenue' ? 'total_revenue' : 'total_quantity';

        return $query->select(
                'p.id',
                'p.name',
                'p.sku',
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_revenue'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count')
            )
            ->groupBy('p.id', 'p.name', 'p.sku')
            ->orderByDesc($orderBy)
            ->limit($limit)
            ->get();
    }

    /**
     * Get top selling categories
     */
    public function getTopSellingCategories($startDate, $endDate, $limit = 10, $locationIds = null, $sortBy = 'revenue')
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('t.business_id', $this->businessId)
            ->where('t.type', 'sell')
            ->where('t.status', '!=', 'draft')
            ->whereBetween('t.transaction_date', [$startDate, $endDate]);

        if (!empty($locationIds) && is_array($locationIds)) {
            $query->whereIn('t.location_id', $locationIds);
        } elseif ($locationIds && !is_array($locationIds)) {
            // Backward compatibility for single location
            $query->where('t.location_id', $locationIds);
        }

        $orderBy = $sortBy === 'quantity' ? 'total_quantity' : 'total_revenue';

        return $query->select(
                'c.id',
                'c.name as category_name',
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_revenue'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count'),
                DB::raw('COUNT(DISTINCT p.id) as product_count')
            )
            ->groupBy('c.id', 'c.name')
            ->orderByDesc($orderBy)
            ->limit($limit)
            ->get();
    }

    /**
     * Get top selling brands
     */
    public function getTopSellingBrands($startDate, $endDate, $limit = 10, $locationIds = null, $sortBy = 'revenue')
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->where('t.business_id', $this->businessId)
            ->where('t.type', 'sell')
            ->where('t.status', '!=', 'draft')
            ->whereBetween('t.transaction_date', [$startDate, $endDate]);

        if (!empty($locationIds) && is_array($locationIds)) {
            $query->whereIn('t.location_id', $locationIds);
        } elseif ($locationIds && !is_array($locationIds)) {
            // Backward compatibility for single location
            $query->where('t.location_id', $locationIds);
        }

        $orderBy = $sortBy === 'quantity' ? 'total_quantity' : 'total_revenue';

        return $query->select(
                'b.id',
                'b.name as brand_name',
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_revenue'),
                DB::raw('COUNT(DISTINCT t.id) as transaction_count'),
                DB::raw('COUNT(DISTINCT p.id) as product_count')
            )
            ->groupBy('b.id', 'b.name')
            ->orderByDesc($orderBy)
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate profit for a period
     */
    public function calculateProfit($startDate, $endDate, $locationId = null)
    {
        // Sales revenue
        $salesQuery = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $salesQuery->where('location_id', $locationId);
        }

        $sales = $salesQuery->sum('final_total');

        // Cost of goods sold (using default_purchase_price from variations table)
        // This matches the system's approach: left join variations to get purchase price
        $cogsQuery = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('t.business_id', $this->businessId)
            ->where('t.type', 'sell')
            ->where('t.status', '!=', 'draft')
            ->whereBetween('t.transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $cogsQuery->where('t.location_id', $locationId);
        }

        $cogs = $cogsQuery->sum(DB::raw('tsl.quantity * COALESCE(v.default_purchase_price, tsl.unit_price * 0.7, 0)'));

        // Expenses
        $expensesQuery = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($locationId) {
            $expensesQuery->where('location_id', $locationId);
        }

        $expenses = $expensesQuery->sum('final_total');

        $grossProfit = $sales - $cogs;
        $netProfit = $grossProfit - $expenses;
        $profitMargin = $sales > 0 ? ($netProfit / $sales) * 100 : 0;

        return [
            'sales' => $sales,
            'cogs' => $cogs,
            'expenses' => $expenses,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'profit_margin' => round($profitMargin, 2)
        ];
    }

    /**
     * Get business summary
     */
    public function getBusinessSummary($startDate, $endDate)
    {
        return [
            'sales' => $this->getSalesData($startDate, $endDate),
            'purchases' => $this->getPurchaseData($startDate, $endDate),
            'expenses' => $this->getExpenseData($startDate, $endDate),
            'profit' => $this->calculateProfit($startDate, $endDate),
            'inventory' => $this->getInventoryData(),
            'customer_dues' => $this->getCustomerDues(),
            'supplier_dues' => $this->getSupplierDues(),
            'cash_flow' => $this->getCashFlowData($startDate, $endDate),
        ];
    }
}

