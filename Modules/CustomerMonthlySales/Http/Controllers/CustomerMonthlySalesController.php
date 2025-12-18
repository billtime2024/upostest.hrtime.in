<?php

namespace Modules\CustomerMonthlySales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Contact;
use App\Transaction;
use App\BusinessLocation;
use App\User;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CustomerMonthlySalesController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $transactionUtil;
    protected $moduleUtil;
    protected $businessUtil;
    protected $util;

    /**
     * Constructor
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, BusinessUtil $businessUtil, \App\Utils\Util $util)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
        $this->util = $util;
    }

    /**
     * Display customer monthly sales report
     */
    public function index()
    {
        if (!auth()->user()->can('sell.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get dropdowns for filters
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $customers = Contact::customersDropdown($business_id, false);
        $users = User::forDropdown($business_id, false, true, true);

        // Get payment types for filters
        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
        $payment_types = collect($payment_types)->prepend(__('lang_v1.all'), '');

        return view('customermonthlysales::report.customer_monthly_sales')
            ->with(compact(
                'business_locations',
                'customers',
                'users',
                'payment_types'
            ));
    }

    /**
     * Get customer monthly sales data for DataTables
     */
    public function getCustomerMonthlyData(Request $request)
    {
        if (!auth()->user()->can('sell.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $year = $request->get('year', date('Y'));
        $start_date = $year . '-01-01';
        $end_date = $year . '-12-31';

        try {
            // Build base query for customer sales data
            $query = Transaction::join('contacts as c', 'transactions.contact_id', '=', 'c.id')
                ->leftjoin('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->leftjoin('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftjoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
                ->leftjoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
                ->leftjoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->where('transactions.business_id', $business_id)
                ->where('c.type', 'customer')
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                ->whereNull('tsl.parent_sell_line_id');

            // Apply payment method filter before other filters
            $payment_method = $request->get('payment_method');
            if (!empty($payment_method)) {
                $query->whereExists(function ($q) use ($payment_method, $business_id) {
                    $q->select(DB::raw(1))
                        ->from('transaction_payments as tp')
                        ->whereColumn('tp.transaction_id', 'transactions.id')
                        ->where('tp.method', $payment_method)
                        ->where('tp.business_id', $business_id);
                });
            }

            // Apply filters (excluding payment_method which is handled above)
            $this->applyFilters($query, $request);

            // Group by customer and get monthly data
            $monthlyData = $query->select([
                'c.id as customer_id',
                'c.contact_id',
                'c.name as customer_name',
                'c.supplier_business_name',
                DB::raw('MONTH(transactions.transaction_date) as month'),
                DB::raw('SUM((tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * tsl.unit_price_inc_tax) as monthly_sales'),
                DB::raw('SUM(CASE WHEN p.enable_stock = 0 THEN 0 ELSE COALESCE(tspl.quantity - tspl.qty_returned, tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * COALESCE(pl.purchase_price_inc_tax, v.default_purchase_price) END) as monthly_purchase_cost')
            ])
                ->groupBy('c.id', 'c.contact_id', 'c.name', 'c.supplier_business_name', DB::raw('MONTH(transactions.transaction_date)'))
                ->get();

            // Transform data into the required format
            $customerData = [];
            foreach ($monthlyData as $data) {
                $customerId = $data->customer_id;

                if (!isset($customerData[$customerId])) {
                    $customerName = $data->customer_name;
                    if (!empty($data->supplier_business_name)) {
                        $customerName = $data->supplier_business_name . ' - ' . $customerName;
                    }

                    $customerData[$customerId] = [
                        'customer_id' => $customerId,
                        'contact_id' => $data->contact_id,
                        'customer_name' => $customerName,
                        'jan' => 0,
                        'feb' => 0,
                        'mar' => 0,
                        'apr' => 0,
                        'may' => 0,
                        'jun' => 0,
                        'jul' => 0,
                        'aug' => 0,
                        'sep' => 0,
                        'oct' => 0,
                        'nov' => 0,
                        'dec' => 0,
                        'jan_cost' => 0,
                        'feb_cost' => 0,
                        'mar_cost' => 0,
                        'apr_cost' => 0,
                        'may_cost' => 0,
                        'jun_cost' => 0,
                        'jul_cost' => 0,
                        'aug_cost' => 0,
                        'sep_cost' => 0,
                        'oct_cost' => 0,
                        'nov_cost' => 0,
                        'dec_cost' => 0,
                        'total_transactions' => 0,
                    ];
                }

                $month = (int)$data->month;
                $monthNames = ['', 'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
                $monthName = $monthNames[$month];

                $customerData[$customerId][$monthName] += $data->monthly_sales;
                $customerData[$customerId][$monthName . '_cost'] += $data->monthly_purchase_cost;
            }

            // Get transaction counts per customer (with same filters as sales data)
            $transactionCountsQuery = Transaction::join('contacts as c', 'transactions.contact_id', '=', 'c.id')
                ->where('transactions.business_id', $business_id)
                ->where('c.type', 'customer')
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
            $this->applyFilters($transactionCountsQuery, $request);
            $transactionCounts = $transactionCountsQuery->select([
                'c.id as customer_id',
                DB::raw('COUNT(DISTINCT transactions.id) as transaction_count')
            ])
            ->groupBy('c.id')
            ->pluck('transaction_count', 'customer_id');

            // Add transaction counts to customer data
            foreach ($customerData as $customerId => &$data) {
                $data['total_transactions'] = $transactionCounts[$customerId] ?? 0;
            }

            // Calculate totals for footer
            $footerTotals = [
                'jan' => 0,
                'feb' => 0,
                'mar' => 0,
                'apr' => 0,
                'may' => 0,
                'jun' => 0,
                'jul' => 0,
                'aug' => 0,
                'sep' => 0,
                'oct' => 0,
                'nov' => 0,
                'dec' => 0,
                'total_sales' => 0,
                'total_cost' => 0,
                'total_profit' => 0,
                'total_transactions' => 0,
            ];

            // Convert to array for DataTables and add calculated columns
            $dataTableData = [];
            foreach ($customerData as $data) {
                $totalSales = $data['jan'] + $data['feb'] + $data['mar'] + $data['apr'] + 
                             $data['may'] + $data['jun'] + $data['jul'] + $data['aug'] + 
                             $data['sep'] + $data['oct'] + $data['nov'] + $data['dec'];
                $totalCost = $data['jan_cost'] + $data['feb_cost'] + $data['mar_cost'] + $data['apr_cost'] + 
                            $data['may_cost'] + $data['jun_cost'] + $data['jul_cost'] + $data['aug_cost'] + 
                            $data['sep_cost'] + $data['oct_cost'] + $data['nov_cost'] + $data['dec_cost'];
                $totalProfit = $totalSales - $totalCost;
                $profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;

                // Accumulate totals for footer
                $footerTotals['jan'] += $data['jan'];
                $footerTotals['feb'] += $data['feb'];
                $footerTotals['mar'] += $data['mar'];
                $footerTotals['apr'] += $data['apr'];
                $footerTotals['may'] += $data['may'];
                $footerTotals['jun'] += $data['jun'];
                $footerTotals['jul'] += $data['jul'];
                $footerTotals['aug'] += $data['aug'];
                $footerTotals['sep'] += $data['sep'];
                $footerTotals['oct'] += $data['oct'];
                $footerTotals['nov'] += $data['nov'];
                $footerTotals['dec'] += $data['dec'];
                $footerTotals['total_sales'] += $totalSales;
                $footerTotals['total_cost'] += $totalCost;
                $footerTotals['total_profit'] += $totalProfit;
                $footerTotals['total_transactions'] += $data['total_transactions'];

                $dataTableData[] = [
                    'customer_id' => $data['customer_id'],
                    'contact_id' => $data['contact_id'],
                    'customer_name' => $data['customer_name'],
                    'jan' => $data['jan'],
                    'feb' => $data['feb'],
                    'mar' => $data['mar'],
                    'apr' => $data['apr'],
                    'may' => $data['may'],
                    'jun' => $data['jun'],
                    'jul' => $data['jul'],
                    'aug' => $data['aug'],
                    'sep' => $data['sep'],
                    'oct' => $data['oct'],
                    'nov' => $data['nov'],
                    'dec' => $data['dec'],
                    'total_sales' => $totalSales,
                    'total_cost' => $totalCost,
                    'total_profit' => $totalProfit,
                    'profit_margin' => $profitMargin,
                    'total_transactions' => $data['total_transactions'],
                ];
            }

            // Calculate overall profit margin for footer
            $footerTotals['profit_margin'] = $footerTotals['total_sales'] > 0 
                ? ($footerTotals['total_profit'] / $footerTotals['total_sales']) * 100 
                : 0;

            return DataTables::of($dataTableData)
                ->addColumn('total_sales_formatted', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                           $this->util->num_f_indian($row['total_sales'], 2) . '</span>';
                })
                ->addColumn('total_cost_formatted', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                           $this->util->num_f_indian($row['total_cost'], 2) . '</span>';
                })
                ->addColumn('total_profit_formatted', function ($row) {
                    $class = $row['total_profit'] >= 0 ? 'text-success' : 'text-danger';
                    return '<span class="display_currency ' . $class . '" data-currency_symbol="true">' .
                           $this->util->num_f_indian($row['total_profit'], 2) . '</span>';
                })
                ->addColumn('profit_margin_formatted', function ($row) {
                    $class = $row['profit_margin'] >= 0 ? 'text-success' : 'text-danger';
                    return '<span class="' . $class . '">' . $this->util->num_f_indian($row['profit_margin'], 2) . '%</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button type="button" class="btn btn-info btn-xs view-customer-details" 
                            data-customer-id="' . $row['customer_id'] . '">
                            <i class="fa fa-eye"></i> View
                            </button>';
                })
                ->editColumn('jan', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['jan'], 2) . '</span>';
                })
                ->editColumn('feb', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['feb'], 2) . '</span>';
                })
                ->editColumn('mar', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['mar'], 2) . '</span>';
                })
                ->editColumn('apr', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['apr'], 2) . '</span>';
                })
                ->editColumn('may', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['may'], 2) . '</span>';
                })
                ->editColumn('jun', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['jun'], 2) . '</span>';
                })
                ->editColumn('jul', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['jul'], 2) . '</span>';
                })
                ->editColumn('aug', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['aug'], 2) . '</span>';
                })
                ->editColumn('sep', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['sep'], 2) . '</span>';
                })
                ->editColumn('oct', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['oct'], 2) . '</span>';
                })
                ->editColumn('nov', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['nov'], 2) . '</span>';
                })
                ->editColumn('dec', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->util->num_f_indian($row['dec'], 2) . '</span>';
                })
                ->rawColumns(['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 
                             'total_sales_formatted', 'total_cost_formatted', 'total_profit_formatted', 
                             'profit_margin_formatted', 'action'])
                ->with('footer_totals', $footerTotals)
                ->make(true);

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['error' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, $request)
    {
        // Location filter
        $location_id = $request->get('location_id');
        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        // Customer filter
        $customer_id = $request->get('customer_id');
        if (!empty($customer_id)) {
            $query->where('transactions.contact_id', $customer_id);
        }

        // Payment status filter
        $payment_status = $request->get('payment_status');
        if (!empty($payment_status)) {
            if ($payment_status == 'paid') {
                $query->where('transactions.payment_status', 'paid');
            } elseif ($payment_status == 'partial') {
                $query->where('transactions.payment_status', 'partial');
            } elseif ($payment_status == 'due') {
                $query->where('transactions.payment_status', 'due');
            }
        }

        // Payment method filter is applied in getCustomerMonthlyData before this method
        // to avoid conflicts with the complex join structure

        // Staff/User filter
        $user_id = $request->get('user_id');
        if (!empty($user_id)) {
            $query->where('transactions.created_by', $user_id);
        }

        // Permitted locations
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
    }

    /**
     * Apply filters to summary query
     */
    private function applyFiltersToSummary($query, $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        // Location filter
        $location_id = $request->get('location_id');
        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        // Customer filter
        $customer_id = $request->get('customer_id');
        if (!empty($customer_id)) {
            $query->where('transactions.contact_id', $customer_id);
        }

        // Payment status filter
        $payment_status = $request->get('payment_status');
        if (!empty($payment_status)) {
            if ($payment_status == 'paid') {
                $query->where('transactions.payment_status', 'paid');
            } elseif ($payment_status == 'partial') {
                $query->where('transactions.payment_status', 'partial');
            } elseif ($payment_status == 'due') {
                $query->where('transactions.payment_status', 'due');
            }
        }

        // Payment method filter is applied in getSummary before this method
        // to avoid conflicts with the complex join structure

        // Staff/User filter
        $user_id = $request->get('user_id');
        if (!empty($user_id)) {
            $query->where('transactions.created_by', $user_id);
        }

        // Permitted locations
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
    }

    /**
     * Get summary statistics
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('sell.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $year = $request->get('year', date('Y'));
        $start_date = $year . '-01-01';
        $end_date = $year . '-12-31';

        try {
            $query = Transaction::join('contacts as c', 'transactions.contact_id', '=', 'c.id')
                ->leftjoin('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->leftjoin('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftjoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
                ->leftjoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
                ->leftjoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->where('transactions.business_id', $business_id)
                ->where('c.type', 'customer')
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                ->whereNull('tsl.parent_sell_line_id');

            // Apply payment method filter before other filters for summary
            $payment_method = $request->get('payment_method');
            if (!empty($payment_method)) {
                $query->whereExists(function ($q) use ($payment_method, $business_id) {
                    $q->select(DB::raw(1))
                        ->from('transaction_payments as tp')
                        ->whereColumn('tp.transaction_id', 'transactions.id')
                        ->where('tp.method', $payment_method)
                        ->where('tp.business_id', $business_id);
                });
            }

            // Apply filters for summary
            $this->applyFiltersToSummary($query, $request);

            $summary = $query->select([
                DB::raw('COUNT(DISTINCT c.id) as total_customers'),
                DB::raw('COUNT(DISTINCT transactions.id) as total_transactions'),
                DB::raw('SUM((tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * tsl.unit_price_inc_tax) as total_sales'),
                DB::raw('SUM(CASE WHEN p.enable_stock = 0 THEN 0 ELSE COALESCE(tspl.quantity - tspl.qty_returned, tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * COALESCE(pl.purchase_price_inc_tax, v.default_purchase_price) END) as total_cost')
            ])->first();

            $total_sales = $summary->total_sales ?? 0;
            $total_cost = $summary->total_cost ?? 0;
            $total_profit = $total_sales - $total_cost;
            $profit_margin = $total_sales > 0 ? ($total_profit / $total_sales) * 100 : 0;
            $avg_sales = $summary->total_customers > 0 ? ($total_sales / $summary->total_customers) : 0;

            return response()->json([
                'total_customers' => $summary->total_customers ?? 0,
                'total_transactions' => $summary->total_transactions ?? 0,
                'total_sales' => $total_sales,
                'total_profit' => $total_profit,
                'profit_margin' => $profit_margin,
                'avg_sales' => $avg_sales,
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['error' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Get customer details
     */
    public function getCustomerDetails($customerId, Request $request)
    {
        if (!auth()->user()->can('sell.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $year = $request->get('year', date('Y'));
        $start_date = $year . '-01-01';
        $end_date = $year . '-12-31';

        try {
            $customer = Contact::where('business_id', $business_id)
                ->where('type', 'customer')
                ->findOrFail($customerId);

            // Get transactions with sell lines for this customer
            $transactions = Transaction::join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->leftjoin('products as p', 'tsl.product_id', '=', 'p.id')
                ->leftjoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.contact_id', $customerId)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                ->whereNull('tsl.parent_sell_line_id')
                ->select([
                    'transactions.id',
                    'transactions.invoice_no',
                    'transactions.transaction_date',
                    'transactions.payment_status',
                    'tsl.product_id',
                    'tsl.variation_id',
                    DB::raw('COALESCE(p.name, "") as product_name'),
                    DB::raw('COALESCE(v.name, "") as variation_name'),
                    'tsl.quantity',
                    'tsl.unit_price',
                    'tsl.unit_price_inc_tax',
                    DB::raw('((tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * tsl.unit_price_inc_tax) as line_total'),
                    DB::raw('MONTH(transactions.transaction_date) as month')
                ])
                ->orderBy('transactions.transaction_date', 'desc')
                ->orderBy('transactions.id', 'desc')
                ->get();

            // Calculate summary statistics
            $summary = Transaction::join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->leftjoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
                ->leftjoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
                ->leftjoin('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.contact_id', $customerId)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                ->whereNull('tsl.parent_sell_line_id')
                ->select([
                    DB::raw('COUNT(DISTINCT transactions.id) as total_transactions'),
                    DB::raw('SUM((tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * tsl.unit_price_inc_tax) as total_sales'),
                    DB::raw('SUM(tsl.quantity - COALESCE(tsl.quantity_returned, 0)) as total_qty')
                ])
                ->first();

            $total_transactions = $summary->total_transactions ?? 0;
            $total_sales = $summary->total_sales ?? 0;
            $total_qty = $summary->total_qty ?? 0;
            $avg_per_transaction = $total_transactions > 0 ? ($total_sales / $total_transactions) : 0;

            // Format transaction lines
            $transaction_lines = [];
            foreach ($transactions as $trans) {
                $product_name = trim($trans->product_name ?? '');
                if (!empty($trans->variation_name)) {
                    $variation_name = trim($trans->variation_name);
                    if (!empty($product_name)) {
                        $product_name .= ' - ' . $variation_name;
                    } else {
                        $product_name = $variation_name;
                    }
                }
                if (empty($product_name)) {
                    $product_name = 'N/A';
                }
                
                $month_names = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
                
                $transaction_lines[] = [
                    'date' => $trans->transaction_date,
                    'invoice_no' => $trans->invoice_no ?? 'N/A',
                    'product_name' => $product_name,
                    'month' => $month_names[$trans->month] ?? '',
                    'quantity' => $trans->quantity ?? 0,
                    'unit_price' => $trans->unit_price_inc_tax ?? 0,
                    'line_total' => $trans->line_total ?? 0,
                    'payment_status' => $trans->payment_status ?? 'N/A',
                ];
            }

            return response()->json([
                'customer' => [
                    'name' => $customer->name,
                    'supplier_business_name' => $customer->supplier_business_name,
                    'contact_id' => $customer->contact_id,
                    'mobile' => $customer->mobile,
                    'email' => $customer->email,
                    'address_line_1' => $customer->address_line_1,
                    'address_line_2' => $customer->address_line_2,
                    'city' => $customer->city,
                    'state' => $customer->state,
                    'country' => $customer->country,
                ],
                'summary' => [
                    'total_transactions' => $total_transactions,
                    'total_sales' => $total_sales,
                    'avg_per_transaction' => $avg_per_transaction,
                    'total_qty' => $total_qty,
                ],
                'transactions' => $transaction_lines,
                'year' => $year,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error("Customer not found: " . $e->getMessage());
            return response()->json(['error' => 'Customer not found'], 404);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            \Log::emergency("Stack trace: " . $e->getTraceAsString());
            return response()->json([
                'error' => __('messages.something_went_wrong'),
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

