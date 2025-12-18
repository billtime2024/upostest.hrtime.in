<?php

namespace Modules\Daybook\Http\Controllers;

use App\Transaction;
use App\TransactionPayment;
use App\AccountTransaction;
use App\BusinessLocation;
use App\Account;
use App\Contact;
use App\User;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class DaybookController extends Controller
{
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display daybook index page with filters
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('daybook.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $accounts = Account::forDropdown($business_id, true, false, false);
        $users = User::forDropdown($business_id, false, true, false);

        // Set default account to the first available account
        $default_account = !empty($accounts) ? array_key_first($accounts) : null;

        return view('daybook::index', compact(
            'business_locations',
            'accounts',
            'users',
            'default_account'
        ));
    }

    /**
     * Get daybook data via AJAX/DataTables
     */
    public function getData(Request $request)
    {
        try {
            if (!auth()->user()->can('daybook.view')) {
                abort(403, 'Unauthorized action.');
            }

            $business_id = $request->session()->get('user.business_id');
            
            // Convert dates - if empty, show all-time data
            $start_date = null;
            $end_date = null;
            
            // Only apply date filter if dates are provided
            if (!empty($request->start_date)) {
                try {
                    $start_date = $this->commonUtil->uf_date($request->start_date);
                    if (empty($start_date)) {
                        $start_date = null; // Show all-time if conversion fails
                    }
                } catch (\Exception $e) {
                    \Log::warning('Daybook start_date conversion error: ' . $e->getMessage() . ' - Input: ' . $request->start_date);
                    $start_date = null; // Show all-time if conversion fails
                }
            }
            
            if (!empty($request->end_date)) {
                try {
                    $end_date = $this->commonUtil->uf_date($request->end_date);
                    if (empty($end_date)) {
                        $end_date = null; // Show all-time if conversion fails
                    }
                } catch (\Exception $e) {
                    \Log::warning('Daybook end_date conversion error: ' . $e->getMessage() . ' - Input: ' . $request->end_date);
                    $end_date = null; // Show all-time if conversion fails
                }
            }
            
            // If only one date is provided, use same date for both
            if ($start_date && !$end_date) {
                $end_date = $start_date;
            } elseif ($end_date && !$start_date) {
                $start_date = $end_date;
            }
            
            $location_id = $request->location_id;
            $user_id = $request->user_id;
            $account_id = $request->account_id;
            $module_filter = $request->module_filter; // sales, purchase, expense, etc.
            $scope = $request->scope ?? 'all'; // cash, bank, cash_bank, all

            // Get permitted locations
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query_locations = $permitted_locations;
            } else {
                $query_locations = null;
            }

            // Get account IDs based on scope - only filter if scope is not 'all'
            $account_ids = null;
            if ($scope != 'all') {
                $account_ids = $this->getAccountIdsByScope($business_id, $scope);
            }
            
            // Log for debugging
            \Log::info('Daybook Query Params:', [
                'business_id' => $business_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'location_id' => $location_id,
                'user_id' => $user_id,
                'account_id' => $account_id,
                'module_filter' => $module_filter,
                'scope' => $scope,
                'account_ids' => $account_ids
            ]);

            // Union query for all transaction types
            $daybook_entries = $this->buildDaybookQuery(
                $business_id,
                $start_date,
                $end_date,
                $location_id,
                $user_id,
                $account_id,
                $module_filter,
                $query_locations,
                $account_ids
            );

            // Handle empty query result
            if ($daybook_entries instanceof \Illuminate\Support\Collection) {
                if ($daybook_entries->isEmpty()) {
                    $entries = collect([]);
                } else {
                    $entries = $daybook_entries;
                }
            } elseif (is_object($daybook_entries) && method_exists($daybook_entries, 'get')) {
                $entries = $daybook_entries->get();
            } else {
                $entries = collect([]);
            }

            // Calculate opening balance from previous day's closing balance
            $opening_balance = $this->calculateOpeningBalance(
                $business_id,
                $start_date,
                $location_id,
                $query_locations,
                $account_ids,
                $account_id  // Pass the specific account_id if selected
            );
            
            // Apply running balance calculation and normalize data
            $running_balance = $opening_balance; // Start with opening balance
            $entries_array = [];
            
            // Opening Balance is NOT added as a table row - it only appears in the footer
            // The running balance calculation starts with opening balance
            
            foreach ($entries as $entry) {
                // Convert to array for consistent handling
                $entry_data = is_object($entry) ? (array) $entry : $entry;
                if (!is_array($entry_data)) {
                    continue;
                }
                
                $type = $entry_data['type'] ?? '';
                $amount = floatval($entry_data['amount'] ?? 0);
                
                if ($type == 'debit') {
                    $running_balance += $amount;
                } else {
                    $running_balance -= $amount;
                }
                
                $entry_data['running_balance'] = $running_balance;
                $entries_array[] = $entry_data;
            }

            // Calculate summary values for footer
            $current_period_debit = 0;
            $current_period_credit = 0;
            
            // Calculate current period totals (filtered date range transactions only)
            foreach ($entries_array as $entry) {
                $type = $entry['type'] ?? '';
                $amount = floatval($entry['amount'] ?? 0);
                
                if ($type == 'debit') {
                    $current_period_debit += $amount;
                } else {
                    $current_period_credit += $amount;
                }
            }
            
            // Current Total = Current Period + Opening Balance (only if opening balance exists)
            // Opening balance is only included if it's non-zero and applicable to the filtered date range
            if (abs($opening_balance) > 0.01 && $start_date) {
                // Add opening balance to current totals based on its side
                if ($opening_balance >= 0) {
                    // Opening balance is on debit side
                    $current_period_debit += abs($opening_balance);
                } else {
                    // Opening balance is on credit side
                    $current_period_credit += abs($opening_balance);
                }
            }
            // If no opening balance or opening balance is 0, Current Total = Current Period only
            
            // Get closing balance (last running balance)
            $closing_balance = $running_balance;

            return DataTables::of($entries_array)
                ->with([
                    'opening_balance' => $opening_balance,
                    'current_period_debit' => $current_period_debit,
                    'current_period_credit' => $current_period_credit,
                    'closing_balance' => $closing_balance
                ])
                ->editColumn('datetime', function ($row) {
                    $datetime = $row['datetime'] ?? null;
                    return $datetime ? $this->commonUtil->format_date($datetime, true) : '-';
                })
                ->editColumn('voucher_no', function ($row) {
                    $voucher_no = $row['voucher_no'] ?? '';
                    $transaction_id = $row['transaction_id'] ?? null;
                    $transaction_payment_id = $row['transaction_payment_id'] ?? null;
                    $module = $row['module'] ?? '';
                    
                    if ($transaction_id || $transaction_payment_id) {
                        $data_attr = '';
                        if ($transaction_id) {
                            $data_attr = 'data-transaction-id="' . $transaction_id . '" data-module="' . htmlspecialchars($module, ENT_QUOTES) . '"';
                        } else {
                            $data_attr = 'data-payment-id="' . $transaction_payment_id . '" data-module="' . htmlspecialchars($module, ENT_QUOTES) . '"';
                        }
                        return '<a href="#" class="voucher-details-link" ' . $data_attr . ' style="cursor: pointer; color: #0066cc;">' . $voucher_no . '</a>';
                    }
                    return $voucher_no;
                })
                ->editColumn('module', function ($row) {
                    $module = $row['module'] ?? '';
                    return ucfirst(str_replace('_', ' ', $module));
                })
                ->editColumn('party', function ($row) {
                    return $row['party'] ?? '-';
                })
                ->editColumn('location', function ($row) {
                    return $row['location'] ?? '-';
                })
                ->editColumn('account', function ($row) {
                    return $row['account'] ?? '-';
                })
                ->editColumn('debit', function ($row) {
                    $type = $row['type'] ?? '';
                    $amount = floatval($row['amount'] ?? 0);
                    return $type == 'debit' ? $this->commonUtil->num_f($amount) : '-';
                })
                ->editColumn('credit', function ($row) {
                    $type = $row['type'] ?? '';
                    $amount = floatval($row['amount'] ?? 0);
                    return $type == 'credit' ? $this->commonUtil->num_f($amount) : '-';
                })
                ->editColumn('narration', function ($row) {
                    return $row['narration'] ?? '-';
                })
                ->editColumn('user', function ($row) {
                    return $row['user'] ?? '-';
                })
                ->rawColumns(['voucher_no', 'module', 'debit', 'credit'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Daybook getData Error: ' . $e->getMessage());
            \Log::error('Daybook getData Trace: ' . $e->getTraceAsString());
            \Log::error('Daybook getData File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            \Log::error('Daybook getData Request: ' . json_encode($request->all()));
            
            // Return proper DataTables JSON format even on error with detailed message for debugging
            $draw = intval($request->get('draw', 1));
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error loading daybook data: ' . $e->getMessage() . ' (Check logs for details)'
            ], 200); // Return 200 to prevent DataTables error popup
        }
    }

    /**
     * Build union query for all transaction sources (Tally ERP Logic)
     * Tally Daybook Principles:
     * 1. Strict chronological order (by datetime, not just date)
     * 2. Opening balance from previous day
     * 3. Cash account perspective: Debit = Money Received, Credit = Money Paid
     * 4. No duplicate entries
     * 5. All financial transactions captured
     */
    public function buildDaybookQuery($business_id, $start_date, $end_date, $location_id, $user_id, $account_id, $module_filter, $query_locations, $account_ids)
    {
        $queries = [];

        // Note: Opening balances are calculated from account opening_balance field
        // Opening balance transactions are excluded from daybook entries

        // 1. Sales (finalized) - Only show sales with actual cash payments (exclude credit/due sales)
        if (empty($module_filter) || $module_filter == 'sell') {
            $sales_query = DB::table('transactions as t')
                ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->join('transaction_payments as tp', function($join) {
                    // Only include sales that have actual payments (exclude credit/due sales)
                    $join->on('t.id', '=', 'tp.transaction_id')
                         ->where('tp.is_return', 0)
                         ->whereNotNull('tp.account_id'); // Must have cash/bank account payment
                })
                ->leftJoin('accounts as a', 'tp.account_id', '=', 'a.id')
                ->leftJoin('users as u', 't.created_by', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');
            
            // Apply date filter - support both range and end_date only (for opening balance)
            if ($start_date && $end_date) {
                $sales_query->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start_date, $end_date]);
            } elseif ($end_date && !$start_date) {
                // For opening balance calculation - get all entries up to end_date
                $sales_query->where(DB::raw('DATE(t.transaction_date)'), '<=', $end_date);
            }
            
            $sales_query->select(
                    DB::raw("COALESCE(tp.paid_on, t.transaction_date) as datetime"), // Use payment datetime if available, otherwise transaction date
                    DB::raw("COALESCE(t.invoice_no, t.ref_no, CONCAT('SALE-', t.id)) as voucher_no"),
                    DB::raw("'sell' as module"),
                    DB::raw("COALESCE(c.name, 'Walk-in Customer') as party"),
                    DB::raw("bl.name as location"),
                    DB::raw("a.name as account"),
                    DB::raw("tp.amount as debit"),  // Cash received = Debit (only actual payment amount, exclude credit portion)
                    DB::raw("0 as credit"),
                    DB::raw("tp.amount as amount"),
                    DB::raw("'debit' as type"),  // Sales increase cash = Debit
                    DB::raw("CONCAT('Sale - ', COALESCE(t.invoice_no, t.ref_no)) as narration"),
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user"),
                    DB::raw("t.id as transaction_id"),
                    DB::raw("tp.id as transaction_payment_id"), // Track the actual payment
                    DB::raw("NULL as account_transaction_id")
                );

            if ($location_id) {
                $sales_query->where('t.location_id', $location_id);
            }
            if ($user_id) {
                $sales_query->where('t.created_by', $user_id);
            }
            if ($query_locations) {
                $sales_query->whereIn('t.location_id', $query_locations);
            }
            // Account filter - only show payments for the specified account(s)
            // Since we're using innerJoin on transaction_payments, all sales must have payments
            if ($account_id) {
                $sales_query->where('tp.account_id', $account_id);
            } elseif (!empty($account_ids) && is_array($account_ids) && count($account_ids) > 0) {
                $sales_query->whereIn('tp.account_id', $account_ids);
            }

            $queries[] = $sales_query;
        }

        // 2. Sales Return - Tally Logic: Cash refunded = Credit
        if (empty($module_filter) || $module_filter == 'sell_return') {
            $sales_return_query = DB::table('transactions as t')
                ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
                ->leftJoin('accounts as a', 'tp.account_id', '=', 'a.id')
                ->leftJoin('users as u', 't.created_by', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell_return');
            
            // Apply date filter - support both range and end_date only (for opening balance)
            if ($start_date && $end_date) {
                $sales_return_query->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start_date, $end_date]);
            } elseif ($end_date && !$start_date) {
                // For opening balance calculation - get all entries up to end_date
                $sales_return_query->where(DB::raw('DATE(t.transaction_date)'), '<=', $end_date);
            }
            
            $sales_return_query->select(
                    DB::raw("COALESCE(
                        CASE
                            WHEN t.transaction_date IS NOT NULL
                                AND UNIX_TIMESTAMP(t.transaction_date) > 0  -- Valid timestamp (not 1970-01-01 or earlier)
                                AND YEAR(t.transaction_date) >= 2000  -- Ensure it's a reasonable date (year >= 2000)
                            THEN CASE WHEN TIME(t.transaction_date) != '00:00:00' THEN t.transaction_date ELSE CONCAT(DATE(t.transaction_date), ' ', COALESCE(TIME(t.created_at), TIME('00:00:00'))) END
                            ELSE t.created_at  -- Use created_at for invalid dates (1970, null, etc.)
                        END,
                        t.created_at,
                        NOW()
                    ) as datetime"), // Use transaction_date if valid, otherwise use created_at (actual creation time)
                    DB::raw("COALESCE(t.invoice_no, t.ref_no, CONCAT('SR-', t.id)) as voucher_no"),
                    DB::raw("'sell_return' as module"),
                    DB::raw("COALESCE(c.name, 'Walk-in Customer') as party"),
                    DB::raw("bl.name as location"),
                    DB::raw("a.name as account"),
                    DB::raw("0 as debit"),
                    DB::raw("COALESCE(tp.amount, t.final_total) as credit"),  // Cash refunded = Credit (decreases balance)
                    DB::raw("COALESCE(tp.amount, t.final_total) as amount"),
                    DB::raw("'credit' as type"),  // Sales returns decrease cash = Credit
                    DB::raw("CONCAT('Sales Return - ', COALESCE(t.invoice_no, t.ref_no)) as narration"),
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user"),
                    DB::raw("t.id as transaction_id"),
                    DB::raw("NULL as transaction_payment_id"),
                    DB::raw("NULL as account_transaction_id")
                );

            if ($location_id) {
                $sales_return_query->where('t.location_id', $location_id);
            }
            if ($user_id) {
                $sales_return_query->where('t.created_by', $user_id);
            }
            if ($query_locations) {
                $sales_return_query->whereIn('t.location_id', $query_locations);
            }
            if ($account_id) {
                $sales_return_query->where(function($q) use ($account_id) {
                    $q->where('tp.account_id', $account_id)
                      ->orWhereNull('tp.id');
                });
            } elseif (!empty($account_ids) && is_array($account_ids) && count($account_ids) > 0) {
                $sales_return_query->where(function($q) use ($account_ids) {
                    $q->whereIn('tp.account_id', $account_ids)
                      ->orWhereNull('tp.id');
                });
            }

            $queries[] = $sales_return_query;
        }

        // 3. Purchases (received) - Tally Logic: Cash paid = Credit
        // Use payment date (paid_on) if available, otherwise use transaction_date or created_at
        if (empty($module_filter) || $module_filter == 'purchase') {
            $purchase_query = DB::table('transactions as t')
                ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
                ->leftJoin('accounts as a', 'tp.account_id', '=', 'a.id')
                ->leftJoin('users as u', 't.created_by', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'purchase')
                ->where('t.status', 'received')
                ->where('tp.is_return', 0); // Exclude return payments
            
            // Apply date filter - support both range and end_date only (for opening balance)
            if ($start_date && $end_date) {
                $purchase_query->whereBetween(DB::raw('DATE(COALESCE(tp.paid_on, t.transaction_date, t.created_at))'), [$start_date, $end_date]);
            } elseif ($end_date && !$start_date) {
                // For opening balance calculation - get all entries up to end_date
                $purchase_query->where(DB::raw('DATE(COALESCE(tp.paid_on, t.transaction_date, t.created_at))'), '<=', $end_date);
            }
            
            $purchase_query->select(
                    DB::raw("COALESCE(
                        tp.paid_on,  -- Use payment date first (most accurate for when cash was paid)
                        CASE 
                            WHEN t.transaction_date IS NOT NULL 
                                AND UNIX_TIMESTAMP(t.transaction_date) > 0  -- Valid timestamp (not 1970-01-01 or earlier)
                                AND YEAR(t.transaction_date) >= 2000  -- Ensure it's a reasonable date (year >= 2000)
                            THEN t.transaction_date
                            ELSE NULL  -- Skip invalid transaction_date
                        END,
                        t.created_at,  -- Use created_at for invalid dates (1970, null, etc.)
                        NOW()
                    ) as datetime"), // Prioritize: paid_on > valid transaction_date > created_at
                    DB::raw("COALESCE(t.ref_no, CONCAT('PUR-', t.id)) as voucher_no"),
                    DB::raw("'purchase' as module"),
                    DB::raw("COALESCE(c.name, 'Supplier') as party"),
                    DB::raw("bl.name as location"),
                    DB::raw("a.name as account"),
                    DB::raw("0 as debit"),
                    DB::raw("COALESCE(tp.amount, t.final_total) as credit"),  // Cash paid = Credit (decreases balance)
                    DB::raw("COALESCE(tp.amount, t.final_total) as amount"),
                    DB::raw("'credit' as type"),  // Purchases decrease cash = Credit
                    DB::raw("CONCAT('Purchase - ', COALESCE(t.ref_no, t.id)) as narration"),
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user"),
                    DB::raw("t.id as transaction_id"),
                    DB::raw("NULL as transaction_payment_id"),
                    DB::raw("NULL as account_transaction_id")
                );

            if ($location_id) {
                $purchase_query->where('t.location_id', $location_id);
            }
            if ($user_id) {
                $purchase_query->where('t.created_by', $user_id);
            }
            if ($query_locations) {
                $purchase_query->whereIn('t.location_id', $query_locations);
            }
            if ($account_id) {
                $purchase_query->where(function($q) use ($account_id) {
                    $q->where('tp.account_id', $account_id)
                      ->orWhereNull('tp.id');
                });
            } elseif (!empty($account_ids) && is_array($account_ids) && count($account_ids) > 0) {
                $purchase_query->where(function($q) use ($account_ids) {
                    $q->whereIn('tp.account_id', $account_ids)
                      ->orWhereNull('tp.id');
                });
            }

            $queries[] = $purchase_query;
        }

        // 4. Purchase Return - Tally Logic: Cash received back = Debit
        if (empty($module_filter) || $module_filter == 'purchase_return') {
            $purchase_return_query = DB::table('transactions as t')
                ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
                ->leftJoin('accounts as a', 'tp.account_id', '=', 'a.id')
                ->leftJoin('users as u', 't.created_by', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'purchase_return');
            
            // Apply date filter - support both range and end_date only (for opening balance)
            if ($start_date && $end_date) {
                $purchase_return_query->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start_date, $end_date]);
            } elseif ($end_date && !$start_date) {
                // For opening balance calculation - get all entries up to end_date
                $purchase_return_query->where(DB::raw('DATE(t.transaction_date)'), '<=', $end_date);
            }
            
            $purchase_return_query->select(
                    DB::raw("COALESCE(
                        CASE
                            WHEN t.transaction_date IS NOT NULL
                                AND UNIX_TIMESTAMP(t.transaction_date) > 0  -- Valid timestamp (not 1970-01-01 or earlier)
                                AND YEAR(t.transaction_date) >= 2000  -- Ensure it's a reasonable date (year >= 2000)
                            THEN CASE WHEN TIME(t.transaction_date) != '00:00:00' THEN t.transaction_date ELSE CONCAT(DATE(t.transaction_date), ' ', COALESCE(TIME(t.created_at), TIME('00:00:00'))) END
                            ELSE t.created_at  -- Use created_at for invalid dates (1970, null, etc.)
                        END,
                        t.created_at,
                        NOW()
                    ) as datetime"), // Use transaction_date if valid, otherwise use created_at (actual creation time)
                    DB::raw("COALESCE(t.ref_no, CONCAT('PR-', t.id)) as voucher_no"),
                    DB::raw("'purchase_return' as module"),
                    DB::raw("COALESCE(c.name, 'Supplier') as party"),
                    DB::raw("bl.name as location"),
                    DB::raw("a.name as account"),
                    DB::raw("COALESCE(tp.amount, t.final_total) as debit"),  // Cash received back = Debit (increases balance)
                    DB::raw("0 as credit"),
                    DB::raw("COALESCE(tp.amount, t.final_total) as amount"),
                    DB::raw("'debit' as type"),  // Purchase returns increase cash = Debit
                    DB::raw("CONCAT('Purchase Return - ', COALESCE(t.ref_no, t.id)) as narration"),
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user"),
                    DB::raw("t.id as transaction_id"),
                    DB::raw("NULL as transaction_payment_id"),
                    DB::raw("NULL as account_transaction_id")
                );

            if ($location_id) {
                $purchase_return_query->where('t.location_id', $location_id);
            }
            if ($user_id) {
                $purchase_return_query->where('t.created_by', $user_id);
            }
            if ($query_locations) {
                $purchase_return_query->whereIn('t.location_id', $query_locations);
            }
            if ($account_id) {
                $purchase_return_query->where(function($q) use ($account_id) {
                    $q->where('tp.account_id', $account_id)
                      ->orWhereNull('tp.id');
                });
            } elseif (!empty($account_ids) && is_array($account_ids) && count($account_ids) > 0) {
                $purchase_return_query->where(function($q) use ($account_ids) {
                    $q->whereIn('tp.account_id', $account_ids)
                      ->orWhereNull('tp.id');
                });
            }

            $queries[] = $purchase_return_query;
        }

        // 5. Expenses - Tally Logic: Cash paid = Credit
        // Use payment date (paid_on) if available, otherwise use transaction_date or created_at
        if (empty($module_filter) || $module_filter == 'expense') {
            $expense_query = DB::table('transactions as t')
                ->leftJoin('expense_categories as ec', 't.expense_category_id', '=', 'ec.id')
                ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
                ->leftJoin('accounts as a', 'tp.account_id', '=', 'a.id')
                ->leftJoin('users as u', 't.created_by', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'expense');
            
            // Apply date filter - support both range and end_date only (for opening balance)
            if ($start_date && $end_date) {
                $expense_query->whereBetween(DB::raw('DATE(COALESCE(tp.paid_on, t.transaction_date, t.created_at))'), [$start_date, $end_date]);
            } elseif ($end_date && !$start_date) {
                // For opening balance calculation - get all entries up to end_date
                $expense_query->where(DB::raw('DATE(COALESCE(tp.paid_on, t.transaction_date, t.created_at))'), '<=', $end_date);
            }
            
            $expense_query->select(
                    DB::raw("COALESCE(
                        tp.paid_on,  -- Use payment date first (most accurate for when cash was paid)
                        CASE 
                            WHEN t.transaction_date IS NOT NULL 
                                AND UNIX_TIMESTAMP(t.transaction_date) > 0  -- Valid timestamp (not 1970-01-01 or earlier)
                                AND YEAR(t.transaction_date) >= 2000  -- Ensure it's a reasonable date (year >= 2000)
                            THEN t.transaction_date
                            ELSE NULL  -- Skip invalid transaction_date
                        END,
                        t.created_at,  -- Use created_at for invalid dates (1970, null, etc.)
                        NOW()
                    ) as datetime"), // Prioritize: paid_on > valid transaction_date > created_at
                    DB::raw("COALESCE(t.ref_no, CONCAT('EXP-', t.id)) as voucher_no"),
                    DB::raw("'expense' as module"),
                    DB::raw("COALESCE(ec.name, 'Expense') as party"),
                    DB::raw("bl.name as location"),
                    DB::raw("a.name as account"),
                    DB::raw("0 as debit"),
                    DB::raw("t.final_total as credit"),  // Cash paid = Credit (decreases balance)
                    DB::raw("t.final_total as amount"),
                    DB::raw("'credit' as type"),  // Expenses decrease cash = Credit
                    DB::raw("CONCAT('Expense - ', COALESCE(ec.name, ''), ' - ', COALESCE(t.additional_notes, '')) as narration"),
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user"),
                    DB::raw("t.id as transaction_id"),
                    DB::raw("NULL as transaction_payment_id"),
                    DB::raw("NULL as account_transaction_id")
                );

            if ($location_id) {
                $expense_query->where('t.location_id', $location_id);
            }
            if ($user_id) {
                $expense_query->where('t.created_by', $user_id);
            }
            if ($query_locations) {
                $expense_query->whereIn('t.location_id', $query_locations);
            }
            if ($account_id) {
                $expense_query->where(function($q) use ($account_id) {
                    $q->where('tp.account_id', $account_id)
                      ->orWhereNull('tp.id');
                });
            } elseif (!empty($account_ids) && is_array($account_ids) && count($account_ids) > 0) {
                $expense_query->where(function($q) use ($account_ids) {
                    $q->whereIn('tp.account_id', $account_ids)
                      ->orWhereNull('tp.id');
                });
            }

            $queries[] = $expense_query;
        }

        // 6. Account Transactions (Payments, Transfers, etc.)
        // Exclude sales, purchases, expenses, returns from account_transactions as they are handled separately
        // Also exclude opening_balance transactions as opening balance is handled separately
        if (empty($module_filter) || in_array($module_filter, ['payment', 'transfer'])) {
            $account_trans_query = DB::table('account_transactions as at')
                ->leftJoin('accounts as a', 'at.account_id', '=', 'a.id')
                ->leftJoin('transactions as t', 'at.transaction_id', '=', 't.id')
                ->leftJoin('transaction_payments as tp', 'at.transaction_payment_id', '=', 'tp.id')
                ->leftJoin('contacts as c', function($join) {
                    // Get contact from transaction if available, otherwise try payment_for field in transaction_payments
                    $join->on('c.id', '=', 't.contact_id')
                         ->orOn('c.id', '=', DB::raw('tp.payment_for'));
                })
                ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->leftJoin('users as u', 'at.created_by', '=', 'u.id')
                ->where('a.business_id', $business_id)
                ->whereNull('at.deleted_at')
                ->where('at.sub_type', '!=', 'opening_balance') // Exclude opening balance transactions
                // Exclude account_transactions linked to sales, purchases, expenses, and returns (these are shown in their respective sections)
                ->where(function($q) {
                    $q->whereNull('t.type')
                      ->orWhereNotIn('t.type', ['sell', 'sell_return', 'purchase', 'purchase_return', 'expense']);
                });
            
            // Apply account filters first (needed for opening balance logic)
            $has_account_filter = false;
            if ($account_id) {
                $account_trans_query->where('at.account_id', $account_id);
                $has_account_filter = true;
            }
            if (!empty($account_ids) && is_array($account_ids) && count($account_ids) > 0) {
                $account_trans_query->whereIn('at.account_id', $account_ids);
                $has_account_filter = true;
            }
            
            // Apply date filter
            if ($start_date && $end_date) {
                $account_trans_query->whereBetween(DB::raw('DATE(at.operation_date)'), [$start_date, $end_date]);
            } elseif ($end_date && !$start_date) {
                $account_trans_query->where(DB::raw('DATE(at.operation_date)'), '<=', $end_date);
            }
            
            $account_trans_query->select(
                    DB::raw("at.operation_date as datetime"),
                    DB::raw("COALESCE(at.reff_no, CONCAT('ACC-', at.id)) as voucher_no"),
                    // Show "Receive" for customer payments (money received), "Payment" for supplier payments (money paid)
                    DB::raw("CASE
                        WHEN COALESCE(at.sub_type, 'payment') = 'payment' AND COALESCE(c.type, '') IN ('customer', 'both') THEN 'receive'
                        WHEN COALESCE(at.sub_type, 'payment') = 'payment' AND c.type = 'supplier' THEN 'payment'
                        ELSE COALESCE(at.sub_type, 'payment')
                    END as module"),
                    DB::raw("COALESCE(c.name, '-') as party"),
                    DB::raw("COALESCE(bl.name, '-') as location"),
                    DB::raw("a.name as account"),
                    // Tally Logic Fix: For customer payments (receiving money), always show as Debit
                    // Customer payment (receiving money) = Cash increases = Debit
                    // Supplier payment (paying money) = Cash decreases = Credit
                    DB::raw("CASE
                        WHEN COALESCE(at.sub_type, 'payment') = 'payment' AND COALESCE(c.type, '') IN ('customer', 'both') THEN at.amount
                        WHEN COALESCE(at.sub_type, 'payment') = 'payment' AND c.type = 'supplier' THEN 0
                        WHEN COALESCE(at.type, '') = 'debit' THEN at.amount
                        ELSE 0
                    END as debit"),
                    DB::raw("CASE
                        WHEN COALESCE(at.sub_type, 'payment') = 'payment' AND COALESCE(c.type, '') IN ('customer', 'both') THEN 0
                        WHEN COALESCE(at.sub_type, 'payment') = 'payment' AND c.type = 'supplier' THEN at.amount
                        WHEN COALESCE(at.type, '') = 'credit' THEN at.amount
                        ELSE 0
                    END as credit"),
                    DB::raw("at.amount as amount"),
                    // Determine type: Customer payment = debit, Supplier payment = credit
                    DB::raw("CASE
                        WHEN COALESCE(at.sub_type, 'payment') = 'payment' AND COALESCE(c.type, '') IN ('customer', 'both') THEN 'debit'
                        WHEN COALESCE(at.sub_type, 'payment') = 'payment' AND c.type = 'supplier' THEN 'credit'
                        ELSE COALESCE(at.type, 'credit')
                    END as type"),
                    DB::raw("COALESCE(at.note, CONCAT(COALESCE(at.sub_type, 'payment'), ' - ', a.name)) as narration"),
                    DB::raw("COALESCE(CONCAT(u.first_name, ' ', u.last_name), '-') as user"),
                    DB::raw("at.transaction_id"),
                    DB::raw("at.transaction_payment_id"),
                    DB::raw("at.id as account_transaction_id")
                );
            if ($user_id) {
                $account_trans_query->where('at.created_by', $user_id);
            }

            $queries[] = $account_trans_query;
        }

        // 7. Stock Adjustments (only valued)
        if (empty($module_filter) || $module_filter == 'stock_adjustment') {
            $stock_adj_query = DB::table('transactions as t')
                ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
                ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
                ->leftJoin('accounts as a', 'tp.account_id', '=', 'a.id')
                ->leftJoin('users as u', 't.created_by', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'stock_adjustment')
                ->where('t.final_total', '!=', 0);
            
            // Apply date filter - support both range and end_date only (for opening balance)
            if ($start_date && $end_date) {
                $stock_adj_query->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start_date, $end_date]);
            } elseif ($end_date && !$start_date) {
                // For opening balance calculation - get all entries up to end_date
                $stock_adj_query->where(DB::raw('DATE(t.transaction_date)'), '<=', $end_date);
            }
            
            $stock_adj_query->select(
                    DB::raw("COALESCE(
                        CASE
                            WHEN t.transaction_date IS NOT NULL
                                AND UNIX_TIMESTAMP(t.transaction_date) > 0  -- Valid timestamp (not 1970-01-01 or earlier)
                                AND YEAR(t.transaction_date) >= 2000  -- Ensure it's a reasonable date (year >= 2000)
                            THEN CASE WHEN TIME(t.transaction_date) != '00:00:00' THEN t.transaction_date ELSE CONCAT(DATE(t.transaction_date), ' ', COALESCE(TIME(t.created_at), TIME('00:00:00'))) END
                            ELSE t.created_at  -- Use created_at for invalid dates (1970, null, etc.)
                        END,
                        t.created_at,
                        NOW()
                    ) as datetime"), // Use transaction_date if valid, otherwise use created_at (actual creation time) for Tally-style chronological order
                    DB::raw("COALESCE(t.ref_no, CONCAT('STK-', t.id)) as voucher_no"),
                    DB::raw("'stock_adjustment' as module"),
                    DB::raw("'Stock Adjustment' as party"),
                    DB::raw("bl.name as location"),
                    DB::raw("a.name as account"),
                    DB::raw("IF(t.final_total < 0, ABS(t.final_total), 0) as debit"),  // Negative adjustment = cash received back = Debit
                    DB::raw("IF(t.final_total > 0, t.final_total, 0) as credit"),  // Positive adjustment = cash paid out = Credit
                    DB::raw("ABS(t.final_total) as amount"),
                    DB::raw("IF(t.final_total < 0, 'debit', 'credit') as type"),  // Negative = Debit, Positive = Credit
                    DB::raw("CONCAT('Stock Adjustment - ', COALESCE(t.additional_notes, '')) as narration"),
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user"),
                    DB::raw("t.id as transaction_id"),
                    DB::raw("NULL as transaction_payment_id"),
                    DB::raw("NULL as account_transaction_id")
                );

            if ($location_id) {
                $stock_adj_query->where('t.location_id', $location_id);
            }
            if ($user_id) {
                $stock_adj_query->where('t.created_by', $user_id);
            }
            if ($query_locations) {
                $stock_adj_query->whereIn('t.location_id', $query_locations);
            }

            $queries[] = $stock_adj_query;
        }

        // Union all queries and order by datetime
        if (count($queries) == 0) {
            return collect([]);
        }

        try {
            $union_query = null;
            foreach ($queries as $index => $query) {
                if ($union_query === null) {
                    $union_query = $query;
                } else {
                    $union_query = $union_query->unionAll($query);
                }
            }

            // Execute the union query - Tally-style: strict chronological order by datetime (including time)
            // First, get the SQL and bindings
            $sql = $union_query->toSql();
            $bindings = $union_query->getBindings();
            
            // Order by datetime - handle both string and datetime formats
            $result = DB::table(DB::raw("({$sql}) as daybook"))
                ->mergeBindings($union_query)
                ->orderBy('datetime', 'asc'); // Simple order by should work for datetime strings

            return $result;
        } catch (\Exception $e) {
            \Log::error('Daybook buildDaybookQuery Error: ' . $e->getMessage());
            \Log::error('Daybook buildDaybookQuery Trace: ' . $e->getTraceAsString());
            return collect([]);
        }
    }

    /**
     * Get account IDs based on scope
     */
    public function getAccountIdsByScope($business_id, $scope)
    {
        if ($scope == 'all') {
            return null; // Don't filter by accounts
        }

        $account_ids = [];

        if (in_array($scope, ['cash', 'cash_bank'])) {
            // Filter accounts by name containing 'cash' (case insensitive)
            $cash = Account::where('business_id', $business_id)
                ->where(function($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%cash%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%petty%']);
                })
                ->where('is_closed', 0)
                ->pluck('id')
                ->toArray();
            $account_ids = array_merge($account_ids, $cash);
        }

        if (in_array($scope, ['bank', 'cash_bank'])) {
            // Filter accounts by name containing 'bank' (case insensitive)
            $bank = Account::where('business_id', $business_id)
                ->where(function($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%bank%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%cheque%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%transfer%']);
                })
                ->where('is_closed', 0)
                ->pluck('id')
                ->toArray();
            $account_ids = array_merge($account_ids, $bank);
        }

        $result = !empty($account_ids) ? array_unique($account_ids) : null;
        
        // Return null if empty array to avoid whereIn issues
        if (is_array($result) && count($result) == 0) {
            return null;
        }
        
        return $result;
    }

    /**
     * Calculate opening balance from all account_transactions before start_date
     * Opening balance = Balance from all transactions before the start date for filtered accounts
     */
    protected function calculateOpeningBalance($business_id, $start_date, $location_id, $query_locations, $account_ids, $account_id = null)
    {
        // If no start date, no opening balance (showing all-time data)
        if (!$start_date) {
            return 0;
        }

        try {
            // Calculate opening balance as balance from all transactions before start_date for filtered accounts
            $opening_balance = 0;
            $query = DB::table('account_transactions as at')
                ->join('accounts as a', 'at.account_id', '=', 'a.id')
                ->where('a.business_id', $business_id)
                ->where('a.is_closed', 0)
                ->where('at.operation_date', '<', $start_date)
                ->whereNull('at.deleted_at');

            if ($account_id) {
                $query->where('at.account_id', $account_id);
            } elseif (!empty($account_ids) && is_array($account_ids) && count($account_ids) > 0) {
                $query->whereIn('at.account_id', $account_ids);
            }

            // Sum the amounts: debit increases balance, credit decreases balance
            $debits = (clone $query)->where('at.type', 'debit')->sum('at.amount');
            $credits = (clone $query)->where('at.type', 'credit')->sum('at.amount');

            $opening_balance = $debits - $credits;

            \Log::info('Opening Balance Calculated:', [
                'opening_balance' => $opening_balance,
                'debits' => $debits,
                'credits' => $credits,
                'start_date' => $start_date,
                'account_id' => $account_id
            ]);

            return $opening_balance;
        } catch (\Exception $e) {
            \Log::error('Daybook Opening Balance Calculation Error: ' . $e->getMessage());
            \Log::error('Daybook Opening Balance Trace: ' . $e->getTraceAsString());
            return 0; // Return 0 on error to not break the flow
        }
    }
    
    /**
     * Get source document URL for drill-down
     */
    protected function getSourceDocumentUrl($row)
    {
        $transaction_id = is_array($row) ? ($row['transaction_id'] ?? null) : (is_object($row) ? ($row->transaction_id ?? null) : null);
        
        if (!empty($transaction_id)) {
            try {
                $transaction = Transaction::find($transaction_id);
                if ($transaction) {
                    switch ($transaction->type) {
                        case 'sell':
                            return action([\App\Http\Controllers\SellController::class, 'show'], [$transaction->id]);
                        case 'purchase':
                            return action([\App\Http\Controllers\PurchaseController::class, 'show'], [$transaction->id]);
                        case 'expense':
                            return action([\App\Http\Controllers\ExpenseController::class, 'show'], [$transaction->id]);
                        case 'stock_adjustment':
                            return action([\App\Http\Controllers\StockAdjustmentController::class, 'show'], [$transaction->id]);
                        default:
                            return null;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Daybook getSourceDocumentUrl Error: ' . $e->getMessage());
            }
        }

        $payment_id = is_array($row) ? ($row['transaction_payment_id'] ?? null) : (is_object($row) ? ($row->transaction_payment_id ?? null) : null);
        
        if (!empty($payment_id)) {
            try {
                $payment = TransactionPayment::find($payment_id);
                if ($payment && $payment->transaction_id) {
                    $transaction = Transaction::find($payment->transaction_id);
                    if ($transaction) {
                        switch ($transaction->type) {
                            case 'sell':
                                return action([\App\Http\Controllers\SellController::class, 'show'], [$transaction->id]);
                            case 'purchase':
                                return action([\App\Http\Controllers\PurchaseController::class, 'show'], [$transaction->id]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Daybook getSourceDocumentUrl Payment Error: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Get voucher details for modal display
     */
    public function getVoucherDetails(Request $request)
    {
        try {
            $transaction_id = $request->get('transaction_id');
            $payment_id = $request->get('payment_id');
            $business_id = $request->session()->get('user.business_id');

            \Log::info('getVoucherDetails called', [
                'transaction_id' => $transaction_id,
                'payment_id' => $payment_id,
                'business_id' => $business_id
            ]);

            $data = [];

            if ($transaction_id) {
                \Log::info('Loading transaction', ['transaction_id' => $transaction_id]);

                $transaction = Transaction::with([
                    'contact:id,name,business_id,type',
                    'location:id,name',
                    'payment_lines',
                    'sell_lines.product',
                    'sell_lines.variations',
                    'purchase_lines.product',
                    'purchase_lines.variations'
                ])->where('business_id', $business_id)
                  ->find($transaction_id);

                if (!$transaction) {
                    \Log::warning('Transaction not found', ['transaction_id' => $transaction_id, 'business_id' => $business_id]);
                    return response()->json([
                        'success' => false,
                        'msg' => __('lang_v1.data_not_found')
                    ], 404);
                }

                \Log::info('Transaction found', [
                    'transaction_id' => $transaction_id,
                    'type' => $transaction->type,
                    'status' => $transaction->status
                ]);

                try {
                    $data = [
                        'type' => $transaction->type,
                        'invoice_no' => $transaction->invoice_no ?? $transaction->ref_no,
                        'date' => $this->commonUtil->format_date($transaction->transaction_date, true),
                        'contact' => $transaction->contact ? $transaction->contact->name : 'Walk-In Customer',
                        'location' => $transaction->location ? $transaction->location->name : '-',
                        'subtotal' => $this->commonUtil->num_f($transaction->total_before_tax ?? 0),
                        'tax' => $this->commonUtil->num_f($transaction->tax_amount ?? 0),
                        'discount' => $this->commonUtil->num_f($transaction->discount_amount ?? 0),
                        'total' => $this->commonUtil->num_f($transaction->final_total ?? 0),
                        'status' => $transaction->status,
                        'payment_status' => $transaction->payment_status ?? '-',
                        'notes' => $transaction->additional_notes ?? '-',
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error formatting transaction data', [
                        'transaction_id' => $transaction_id,
                        'error' => $e->getMessage()
                    ]);
                    // Provide fallback data
                    $data = [
                        'type' => $transaction->type ?? 'unknown',
                        'invoice_no' => $transaction->invoice_no ?? $transaction->ref_no ?? '-',
                        'date' => '-',
                        'contact' => 'Error loading contact',
                        'location' => 'Error loading location',
                        'subtotal' => '0.00',
                        'tax' => '0.00',
                        'discount' => '0.00',
                        'total' => '0.00',
                        'status' => $transaction->status ?? 'unknown',
                        'payment_status' => '-',
                        'notes' => 'Error loading transaction data',
                    ];
                }

                // Add lines based on transaction type
            try {
                if ($transaction->type == 'sell' || $transaction->type == 'sell_return') {
                    \Log::info('Processing sell/sell_return lines', ['transaction_id' => $transaction_id, 'line_count' => $transaction->sell_lines ? $transaction->sell_lines->count() : 0]);
                    $data['lines'] = $transaction->sell_lines->map(function($line) {
                        try {
                            $product_name = $line->product ? $line->product->name : '-';
                            if ($line->variations) {
                                $product_name .= ' (' . $line->variations->name . ')';
                            }
                            return [
                                'product' => $product_name,
                                'quantity' => $line->quantity ?? 0,
                                'unit_price' => $this->commonUtil->num_f($line->unit_price ?? 0),
                                'subtotal' => $this->commonUtil->num_f(($line->quantity ?? 0) * ($line->unit_price ?? 0)),
                            ];
                        } catch (\Exception $e) {
                            \Log::warning('Error processing sell line', ['line_id' => $line->id ?? 'unknown', 'error' => $e->getMessage()]);
                            return [
                                'product' => 'Error loading product',
                                'quantity' => 0,
                                'unit_price' => '0.00',
                                'subtotal' => '0.00',
                            ];
                        }
                    });
                } elseif ($transaction->type == 'purchase' || $transaction->type == 'purchase_return') {
                    \Log::info('Processing purchase/purchase_return lines', ['transaction_id' => $transaction_id, 'line_count' => $transaction->purchase_lines ? $transaction->purchase_lines->count() : 0]);
                    $data['lines'] = $transaction->purchase_lines->map(function($line) use ($transaction) {
                        try {
                            $product_name = $line->product ? $line->product->name : '-';
                            if ($line->variations) {
                                $product_name .= ' (' . $line->variations->name . ')';
                            }
                            // Use quantity_returned for purchase_return transactions, quantity for regular purchases
                            $quantity = $transaction->type == 'purchase_return' ? ($line->quantity_returned ?? 0) : ($line->quantity ?? 0);
                            return [
                                'product' => $product_name,
                                'quantity' => $quantity,
                                'unit_price' => $this->commonUtil->num_f($line->unit_price ?? 0),
                                'subtotal' => $this->commonUtil->num_f($quantity * ($line->unit_price ?? 0)),
                            ];
                        } catch (\Exception $e) {
                            \Log::warning('Error processing purchase line', ['line_id' => $line->id ?? 'unknown', 'error' => $e->getMessage()]);
                            return [
                                'product' => 'Error loading product',
                                'quantity' => 0,
                                'unit_price' => '0.00',
                                'subtotal' => '0.00',
                            ];
                        }
                    });
                } else {
                    \Log::info('No lines to process for transaction type', ['transaction_id' => $transaction_id, 'type' => $transaction->type]);
                }
            } catch (\Exception $e) {
                \Log::error('Error processing transaction lines', [
                    'transaction_id' => $transaction_id,
                    'type' => $transaction->type,
                    'error' => $e->getMessage()
                ]);
                // Continue without lines
            }

                // Add payments
                try {
                    if ($transaction->payment_lines && $transaction->payment_lines->count() > 0) {
                        \Log::info('Processing payment lines', ['transaction_id' => $transaction_id, 'payment_count' => $transaction->payment_lines->count()]);
                        $data['payments'] = $transaction->payment_lines->map(function($payment) {
                            try {
                                $account_name = '-';
                                if ($payment->account_id) {
                                    $account = Account::find($payment->account_id);
                                    $account_name = $account ? $account->name : '-';
                                }
                                return [
                                    'method' => $payment->method ?? '-',
                                    'amount' => $this->commonUtil->num_f($payment->amount ?? 0),
                                    'date' => $this->commonUtil->format_date($payment->paid_on ?? $payment->created_at, true),
                                    'account' => $account_name,
                                ];
                            } catch (\Exception $e) {
                                \Log::warning('Error processing payment', ['payment_id' => $payment->id ?? 'unknown', 'error' => $e->getMessage()]);
                                return [
                                    'method' => 'Error',
                                    'amount' => '0.00',
                                    'date' => '-',
                                    'account' => '-',
                                ];
                            }
                        });
                    } else {
                        \Log::info('No payment lines found', ['transaction_id' => $transaction_id]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Error processing payment lines', [
                        'transaction_id' => $transaction_id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue without payments
                }
            } elseif ($payment_id) {
                \Log::info('Loading payment', ['payment_id' => $payment_id]);

                try {
                    $payment = TransactionPayment::with(['transaction.contact', 'account'])
                        ->whereHas('transaction', function($q) use ($business_id) {
                            $q->where('business_id', $business_id);
                        })
                        ->find($payment_id);

                    if ($payment && $payment->transaction) {
                        \Log::info('Payment found', ['payment_id' => $payment_id, 'transaction_id' => $payment->transaction->id]);
                        $data = [
                            'type' => 'payment',
                            'voucher_no' => $payment->payment_ref_no ?? 'ACC-' . $payment->id,
                            'date' => $this->commonUtil->format_date($payment->paid_on ?? $payment->created_at, true),
                            'contact' => $payment->transaction->contact ? $payment->transaction->contact->name : '-',
                            'amount' => $this->commonUtil->num_f($payment->amount ?? 0),
                            'method' => $payment->method ?? '-',
                            'account' => $payment->account ? $payment->account->name : '-',
                            'reference' => $payment->payment_ref_no ?? '-',
                            'notes' => $payment->note ?? '-',
                            'transaction_ref' => $payment->transaction->invoice_no ?? $payment->transaction->ref_no ?? '-',
                        ];
                    } else {
                        \Log::warning('Payment not found or invalid', ['payment_id' => $payment_id, 'business_id' => $business_id]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Error loading payment', ['payment_id' => $payment_id, 'error' => $e->getMessage()]);
                }
            }

            if (empty($data)) {
                \Log::warning('No data found for request', [
                    'transaction_id' => $transaction_id,
                    'payment_id' => $payment_id
                ]);
                return response()->json([
                    'success' => false,
                    'msg' => __('lang_v1.data_not_found')
                ], 404);
            }

            \Log::info('Returning voucher details data', ['data_keys' => array_keys($data)]);
            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Daybook getVoucherDetails Error: ' . $e->getMessage());
            \Log::error('Daybook getVoucherDetails Trace: ' . $e->getTraceAsString());
            \Log::error('Daybook getVoucherDetails Request: ' . json_encode($request->all()));
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export daybook to PDF/Excel
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('daybook.export')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $format = $request->get('format', 'excel'); // excel, pdf, csv

        $filters = [
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'location_id' => $request->get('location_id'),
            'user_id' => $request->get('user_id'),
            'account_id' => $request->get('account_id'),
            'module_filter' => $request->get('module_filter'),
            'scope' => $request->get('scope', 'cash_bank'),
        ];

        switch ($format) {
            case 'pdf':
                return $this->exportToPdf($business_id, $filters);
            case 'csv':
                return $this->exportToCsv($business_id, $filters);
            default:
                return $this->exportToExcel($business_id, $filters);
        }
    }

    /**
     * Export to Excel
     */
    protected function exportToExcel($business_id, $filters)
    {
        $filename = 'daybook_report_' . date('Y-m-d_H-i-s') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \Modules\Daybook\Exports\DaybookExport($business_id, $filters),
            $filename
        );
    }

    /**
     * Export to PDF
     */
    protected function exportToPdf($business_id, $filters)
    {
        // Get data for PDF - dates are already in YYYY-MM-DD format from filters
        $start_date = !empty($filters['start_date'])
            ? Carbon::createFromFormat('Y-m-d', $filters['start_date'])->format('Y-m-d')
            : Carbon::now()->startOfMonth()->format('Y-m-d');

        $end_date = !empty($filters['end_date'])
            ? Carbon::createFromFormat('Y-m-d', $filters['end_date'])->format('Y-m-d')
            : Carbon::now()->format('Y-m-d');

        $location_id = $filters['location_id'] ?? null;
        $user_id = $filters['user_id'] ?? null;
        $account_id = $filters['account_id'] ?? null;
        $module_filter = $filters['module_filter'] ?? null;
        $scope = $filters['scope'] ?? 'cash_bank';

        $permitted_locations = auth()->user()->permitted_locations();
        $query_locations = ($permitted_locations != 'all') ? $permitted_locations : null;
        $account_ids = $this->getAccountIdsByScope($business_id, $scope);

        $daybook_entries = $this->buildDaybookQuery(
            $business_id,
            $start_date,
            $end_date,
            $location_id,
            $user_id,
            $account_id,
            $module_filter,
            $query_locations,
            $account_ids
        );

        $entries = $daybook_entries->get();

        // Calculate opening balance
        $opening_balance = $this->calculateOpeningBalance(
            $business_id,
            $start_date,
            $location_id,
            $query_locations,
            $account_ids
        );
        
        // Calculate running balance starting with opening balance
        $running_balance = $opening_balance;
        
        // Opening Balance is NOT added as a table row - it only appears in footer
        // The running balance calculation starts with opening balance
        
        // Calculate running balance for regular entries
        $entries_collection = collect();
        foreach ($entries as $entry) {
            if ($entry->type == 'debit') {
                $running_balance += $entry->amount;
            } else {
                $running_balance -= $entry->amount;
            }
            $entry->running_balance = $running_balance;
            $entries_collection->push($entry);
        }
        
        // Use entries without opening balance row
        $entries = $entries_collection;
        
        // Calculate closing balance for PDF footer
        $closing_balance = $running_balance;

        $business = \App\Business::find($business_id);
        $business_location = $location_id ? \App\BusinessLocation::find($location_id) : null;

        $html = view('daybook::pdf.export', compact('entries', 'filters', 'business', 'business_location', 'opening_balance', 'closing_balance', 'running_balance'))->with('commonUtil', $this->commonUtil)->render();

        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => public_path('uploads/temp'),
            'mode' => 'utf-8',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'format' => 'A4-L', // Landscape for better table display
        ]);

        $mpdf->SetTitle('Daybook Report');
        $mpdf->WriteHTML($html);
        $mpdf->Output('daybook_report_' . date('Y-m-d') . '.pdf', 'I');
    }

    /**
     * Export to CSV
     */
    protected function exportToCsv($business_id, $filters)
    {
        $filename = 'daybook_report_' . date('Y-m-d_H-i-s') . '.csv';

        // Use Excel export class but save as CSV
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \Modules\Daybook\Exports\DaybookExport($business_id, $filters),
            $filename,
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Display Monthly Cashbook Report
     */
    public function monthlyCashbook(Request $request)
    {
        if (!auth()->user()->can('daybook.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $accounts = Account::forDropdown($business_id, true, false, false);

        // Set default account to the first available account
        $default_account = !empty($accounts) ? array_key_first($accounts) : null;

        // Get current business
        $business = \App\Business::find($business_id);

        // Generate year options for dropdown (current year ± 5 years)
        $current_year = date('Y');
        $years = [];
        for ($i = $current_year - 5; $i <= $current_year + 1; $i++) {
            $years[$i] = $i;
        }

        return view('daybook::monthly_cashbook', compact(
            'business_locations',
            'accounts',
            'business',
            'years',
            'default_account'
        ));
    }

    /**
     * Get Monthly Cashbook Data
     */
    public function getMonthlyCashbookData(Request $request)
    {
        try {
            if (!auth()->user()->can('daybook.view')) {
                abort(403, 'Unauthorized action.');
            }

            $business_id = $request->session()->get('user.business_id');
            
            // Get date range - dates are sent in YYYY-MM-DD format from frontend
            $start_date = null;
            $end_date = null;

            if (!empty($request->start_date)) {
                try {
                    // Dates are already in YYYY-MM-DD format, parse directly with Carbon
                    $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    \Log::warning('Monthly Cashbook start_date conversion error: ' . $e->getMessage() . ' - Input: ' . $request->start_date);
                    $start_date = null;
                }
            }

            if (!empty($request->end_date)) {
                try {
                    // Dates are already in YYYY-MM-DD format, parse directly with Carbon
                    $end_date = Carbon::createFromFormat('Y-m-d', $request->end_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    \Log::warning('Monthly Cashbook end_date conversion error: ' . $e->getMessage() . ' - Input: ' . $request->end_date);
                    $end_date = null;
                }
            }

            // Default to current financial year if no dates provided
            if (!$start_date || !$end_date) {
                $current_year = Carbon::now()->year();
                $current_month = Carbon::now()->month;

                if ($current_month >= 4) {
                    $start_year = $current_year;
                    $end_year = $current_year + 1;
                } else {
                    $start_year = $current_year - 1;
                    $end_year = $current_year;
                }

                if (!$start_date) {
                    $start_date = $start_year . '-04-01';
                }
                if (!$end_date) {
                    $end_date = $end_year . '-03-31';
                }
            }

            $location_id = $request->location_id;
            $account_id = $request->account_id;
            $scope = $request->scope ?? 'all';

            // Get permitted locations
            $permitted_locations = auth()->user()->permitted_locations();
            $query_locations = ($permitted_locations != 'all') ? $permitted_locations : null;
            
            // Get account IDs based on scope
            $account_ids = null;
            if ($scope != 'all') {
                $account_ids = $this->getAccountIdsByScope($business_id, $scope);
            }
            
            // If specific account selected, use only that
            if ($account_id) {
                $account_ids = [$account_id];
            }

            // Build query for all transactions in the date range
            $daybook_entries = $this->buildDaybookQuery(
                $business_id,
                $start_date,
                $end_date,
                $location_id,
                null, // No user filter
                $account_id,
                null, // No module filter
                $query_locations,
                $account_ids
            );

            // Get all entries
            $entries = $daybook_entries->get();

            // Group by month and calculate totals
            $monthly_data = $this->calculateMonthlyData($entries, $business_id, $start_date, $location_id, $query_locations, $account_ids, $end_date, $account_id);

            return response()->json([
                'success' => true,
                'data' => $monthly_data
            ]);

        } catch (\Exception $e) {
            \Log::error('Monthly Cashbook Error: ' . $e->getMessage());
            \Log::error('Monthly Cashbook Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'msg' => 'Error loading monthly cashbook data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate monthly data with opening and closing balances
     */
    protected function calculateMonthlyData($entries, $business_id, $start_date, $location_id, $query_locations, $account_ids, $end_date = null, $account_id = null)
    {
        // Parse date range boundaries
        $start_date_obj = Carbon::parse($start_date);
        $end_date_obj = Carbon::parse($end_date);
        
        // Group entries by month, but only include entries within the exact date range
        $monthly_groups = [];
        foreach ($entries as $entry) {
            $entry_data = is_object($entry) ? (array) $entry : $entry;
            if (!is_array($entry_data)) {
                continue;
            }

            $datetime = $entry_data['datetime'] ?? null;
            if (!$datetime) {
                continue;
            }

            try {
                // Parse datetime - handle various formats
                $date = Carbon::parse($datetime);
                
                // CRITICAL: Only include entries within the selected date range
                // Don't use full month - use exact date range
                // Compare only date portion (ignore time)
                $entry_date_only = $date->copy()->startOfDay();
                $start_date_only = $start_date_obj->copy()->startOfDay();
                $end_date_only = $end_date_obj->copy()->endOfDay(); // Include full end date
                
                if ($entry_date_only->lt($start_date_only) || $entry_date_only->gt($end_date_only)) {
                    // Skip entries outside the date range
                    continue;
                }
                
                // Group by month for display, but only count transactions in date range
                $month_key = $date->format('Y-m'); // e.g., '2024-01' or '2025-11'
                $month_name = $date->format('F'); // e.g., 'January' or 'November'
                
                // Check if this month spans partial range (not full month)
                $month_start = Carbon::parse($date->format('Y-m-01'))->startOfDay();
                $month_last_day = $month_start->copy()->endOfMonth();
                
                // Determine if partial month
                // Compare only date portions (ignore time)
                $range_start_in_month = max($start_date_obj->copy()->startOfDay(), $month_start);
                $range_end_in_month = min($end_date_obj->copy()->startOfDay(), $month_last_day->copy()->startOfDay());
                
                // Check if the date range exactly matches a full month (start of month to end of month)
                $is_full_month = ($start_date_obj->format('Y-m-d') == $month_start->format('Y-m-d')) && 
                                 ($end_date_obj->format('Y-m-d') == $month_last_day->format('Y-m-d'));
                
                $is_partial_month = !$is_full_month && (
                    ($range_start_in_month->format('Y-m-d') != $month_start->format('Y-m-d')) || 
                    ($range_end_in_month->format('Y-m-d') != $month_last_day->format('Y-m-d'))
                );
                
                if (!isset($monthly_groups[$month_key])) {
                    $display_name = $month_name;
                    if ($is_partial_month) {
                        // Show date range for partial months
                        $day_start = $range_start_in_month->day;
                        $day_end = $range_end_in_month->day;
                        $display_name = $month_name . ' (' . $day_start . '-' . $day_end . ')';
                    }
                    
                    $monthly_groups[$month_key] = [
                        'month_name' => $display_name,
                        'month_key' => $month_key,
                        'debit' => 0,
                        'credit' => 0,
                        'entries' => [],
                        'is_partial' => $is_partial_month,
                        'date_range_start' => $range_start_in_month->format('Y-m-d'),
                        'date_range_end' => $range_end_in_month->format('Y-m-d')
                    ];
                }

                // Check if this is an opening balance transaction
                $module = $entry_data['module'] ?? '';
                $type = $entry_data['type'] ?? '';
                $amount = floatval($entry_data['amount'] ?? 0);

                // Opening balance transactions are ALWAYS treated as DEBIT
                if ($module == 'opening_balance') {
                    $monthly_groups[$month_key]['debit'] += $amount;
                } elseif ($type == 'debit') {
                    $monthly_groups[$month_key]['debit'] += $amount;
                } else {
                    $monthly_groups[$month_key]['credit'] += $amount;
                }

                $monthly_groups[$month_key]['entries'][] = $entry_data;
            } catch (\Exception $e) {
                \Log::warning('Error parsing date in monthly cashbook: ' . $datetime . ' - ' . $e->getMessage());
                continue;
            }
        }

        // Initialize months that fall within date range (even if no transactions)
        // But only for months that are actually in the range
        if ($start_date && $end_date) {
            $current = $start_date_obj->copy()->startOfMonth();
            $end_month = $end_date_obj->copy()->startOfMonth();
            
            while ($current <= $end_month) {
                $month_key = $current->format('Y-m');
                $month_name = $current->format('F');
                
                // Check if this month is partial
                $month_start = $current->copy()->startOfMonth()->startOfDay();
                $month_last_day = $current->copy()->endOfMonth();
                
                // Determine if partial month
                // Compare only date portions (ignore time)
                $range_start_in_month = max($start_date_obj->copy()->startOfDay(), $month_start);
                $range_end_in_month = min($end_date_obj->copy()->startOfDay(), $month_last_day->copy()->startOfDay());
                
                // Check if the date range exactly matches a full month (start of month to end of month)
                $is_full_month = ($start_date_obj->format('Y-m-d') == $month_start->format('Y-m-d')) && 
                                 ($end_date_obj->format('Y-m-d') == $month_last_day->format('Y-m-d'));
                
                $is_partial_month = !$is_full_month && (
                    ($range_start_in_month->format('Y-m-d') != $month_start->format('Y-m-d')) || 
                    ($range_end_in_month->format('Y-m-d') != $month_last_day->format('Y-m-d'))
                );
                
                if (!isset($monthly_groups[$month_key])) {
                    $display_name = $month_name;
                    if ($is_partial_month) {
                        $day_start = $range_start_in_month->day;
                        $day_end = $range_end_in_month->day;
                        $display_name = $month_name . ' (' . $day_start . '-' . $day_end . ')';
                    }
                    
                    $monthly_groups[$month_key] = [
                        'month_name' => $display_name,
                        'month_key' => $month_key,
                        'debit' => 0,
                        'credit' => 0,
                        'entries' => [],
                        'is_partial' => $is_partial_month,
                        'date_range_start' => $range_start_in_month->format('Y-m-d'),
                        'date_range_end' => $range_end_in_month->format('Y-m-d')
                    ];
                } else {
                    // Update existing entry with partial month info if needed
                    if ($is_partial_month && !strpos($monthly_groups[$month_key]['month_name'], '(')) {
                        $day_start = $range_start_in_month->day;
                        $day_end = $range_end_in_month->day;
                        $monthly_groups[$month_key]['month_name'] = $month_name . ' (' . $day_start . '-' . $day_end . ')';
                        $monthly_groups[$month_key]['is_partial'] = true;
                    }
                }
                
                $current->addMonth();
            }
        }

        // Sort by month
        ksort($monthly_groups);

        // Debug: Log monthly groups
        \Log::info('Monthly Cashbook - Monthly Groups:', [
            'groups' => array_keys($monthly_groups),
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_months' => count($monthly_groups)
        ]);

        // Calculate opening balance (from start of period)
        $start_date_obj = Carbon::parse($start_date)->startOfDay();
        $opening_balance = $this->calculateOpeningBalance(
            $business_id,
            $start_date,
            $location_id,
            $query_locations,
            $account_ids,
            $account_id  // Pass account_id for opening balance calculation
        );

        // Build result array
        $result = [];
        $running_balance = $opening_balance;
        $total_debit = 0;
        $total_credit = 0;

        // Add opening balance row - ALWAYS shown as DEBIT
        $result[] = [
            'particulars' => __('daybook::lang.opening_balance'),
            'month_key' => '',
            'debit' => abs($opening_balance),  // Always debit for opening balance
            'credit' => 0,  // Never credit for opening balance
            'closing_balance' => $opening_balance,
            'is_opening' => true,
            'is_total' => false
        ];

        // Process each month in chronological order
        foreach ($monthly_groups as $month_key => $month_data) {
            $month_debit = $month_data['debit'];
            $month_credit = $month_data['credit'];

            // Update running balance: Debit increases balance, Credit decreases balance
            $running_balance = $running_balance + $month_debit - $month_credit;
            
            $total_debit += $month_debit;
            $total_credit += $month_credit;

            $result[] = [
                'particulars' => $month_data['month_name'],
                'month_key' => $month_key,
                'debit' => $month_debit,
                'credit' => $month_credit,
                'closing_balance' => $running_balance,
                'is_opening' => false,
                'is_total' => false,
                'chart_data' => [
                    'month' => substr($month_key, 0, 7), // '2024-01'
                    'net' => $month_debit - $month_credit // Net change for chart
                ],
                'debug_info' => [
                    'debit' => $month_debit,
                    'credit' => $month_credit,
                    'net' => $month_debit - $month_credit
                ]
            ];
        }

        // Add grand total row
        $result[] = [
            'particulars' => __('daybook::lang.grand_total'),
            'month_key' => '',
            'debit' => $total_debit,
            'credit' => $total_credit,
            'closing_balance' => $running_balance,
            'is_opening' => false,
            'is_total' => true
        ];

        return [
            'monthly_data' => $result,
            'opening_balance' => $opening_balance,
            'final_closing_balance' => $running_balance,
            'total_debit' => $total_debit,
            'total_credit' => $total_credit
        ];
    }

    /**
     * Display Daily Cashbook Report
     */
    public function dailyCashbook(Request $request)
    {
        if (!auth()->user()->can('daybook.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $accounts = Account::forDropdown($business_id, true, false, false);
        $business = \App\Business::find($business_id);

        // Set default account to the first available account
        $default_account = !empty($accounts) ? array_key_first($accounts) : null;

        // Generate year options for dropdown (current year ± 5 years)
        $current_year = date('Y');
        $years = [];
        for ($i = $current_year - 5; $i <= $current_year + 1; $i++) {
            $years[$i] = $i;
        }

        return view('daybook::daily_cashbook', compact(
            'business_locations',
            'accounts',
            'business',
            'years',
            'default_account'
        ));
    }

    /**
     * Get Daily Cashbook Data
     */
    public function getDailyCashbookData(Request $request)
    {
        try {
            if (!auth()->user()->can('daybook.view')) {
                abort(403, 'Unauthorized action.');
            }

            $business_id = $request->session()->get('user.business_id');
            
            // Get date range - dates are sent in YYYY-MM-DD format from frontend
            $start_date = null;
            $end_date = null;

            if (!empty($request->start_date)) {
                try {
                    // Dates are already in YYYY-MM-DD format, parse directly with Carbon
                    $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    \Log::warning('Daily Cashbook start_date conversion error: ' . $e->getMessage() . ' - Input: ' . $request->start_date);
                    $start_date = null;
                }
            }

            if (!empty($request->end_date)) {
                try {
                    // Dates are already in YYYY-MM-DD format, parse directly with Carbon
                    $end_date = Carbon::createFromFormat('Y-m-d', $request->end_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    \Log::warning('Daily Cashbook end_date conversion error: ' . $e->getMessage() . ' - Input: ' . $request->end_date);
                    $end_date = null;
                }
            }

            // Default to current month if no dates provided
            if (!$start_date) {
                $start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
            }
            if (!$end_date) {
                $end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
            }

            $location_id = $request->location_id;
            $account_id = $request->account_id;
            $scope = $request->scope ?? 'all';

            // Get permitted locations
            $permitted_locations = auth()->user()->permitted_locations();
            $query_locations = ($permitted_locations != 'all') ? $permitted_locations : null;
            
            // Get account IDs based on scope
            $account_ids = null;
            if ($scope != 'all') {
                $account_ids = $this->getAccountIdsByScope($business_id, $scope);
            }
            
            // If specific account selected, use only that
            if ($account_id) {
                $account_ids = [$account_id];
            }

            // Build query for all transactions in the date range
            $daybook_entries = $this->buildDaybookQuery(
                $business_id,
                $start_date,
                $end_date,
                $location_id,
                null, // No user filter
                $account_id,
                null, // No module filter
                $query_locations,
                $account_ids
            );

            // Get all entries
            $entries = $daybook_entries->get();

            // Group by day and calculate totals
            $daily_data = $this->calculateDailyData($entries, $business_id, $start_date, $end_date, $location_id, $query_locations, $account_ids, $account_id);

            return response()->json([
                'success' => true,
                'data' => $daily_data
            ]);

        } catch (\Exception $e) {
            \Log::error('Daily Cashbook Error: ' . $e->getMessage());
            \Log::error('Daily Cashbook Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'msg' => 'Error loading daily cashbook data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate daily data with opening and closing balances
     */
    protected function calculateDailyData($entries, $business_id, $start_date, $end_date, $location_id, $query_locations, $account_ids, $account_id = null)
    {
        // Parse date range boundaries
        $start_date_obj = Carbon::parse($start_date);
        $end_date_obj = Carbon::parse($end_date);
        
        // Group entries by day
        $daily_groups = [];
        foreach ($entries as $entry) {
            $entry_data = is_object($entry) ? (array) $entry : $entry;
            if (!is_array($entry_data)) {
                continue;
            }

            $datetime = $entry_data['datetime'] ?? null;
            if (!$datetime) {
                continue;
            }

            try {
                // Parse datetime
                $date = Carbon::parse($datetime);
                
                // Only include entries within the selected date range
                $entry_date_only = $date->copy()->startOfDay();
                $start_date_only = $start_date_obj->copy()->startOfDay();
                $end_date_only = $end_date_obj->copy()->endOfDay();
                
                if ($entry_date_only->lt($start_date_only) || $entry_date_only->gt($end_date_only)) {
                    continue;
                }
                
                // Group by day (YYYY-MM-DD format)
                $day_key = $date->format('Y-m-d');
                $day_display = $date->format('d-M-Y'); // e.g., '01-Nov-2025'
                $day_name = $date->format('l'); // Day name (Monday, Tuesday, etc.)
                
                if (!isset($daily_groups[$day_key])) {
                    $daily_groups[$day_key] = [
                        'date' => $day_display,
                        'date_key' => $day_key,
                        'day_name' => $day_name,
                        'debit' => 0,
                        'credit' => 0,
                        'entries' => []
                    ];
                }

                // Check if this is an opening balance transaction
                $module = $entry_data['module'] ?? '';
                $type = $entry_data['type'] ?? '';
                $amount = floatval($entry_data['amount'] ?? 0);

                // Opening balance transactions are ALWAYS treated as DEBIT
                if ($module == 'opening_balance') {
                    $daily_groups[$day_key]['debit'] += $amount;
                } elseif ($type == 'debit') {
                    $daily_groups[$day_key]['debit'] += $amount;
                } else {
                    $daily_groups[$day_key]['credit'] += $amount;
                }

                $daily_groups[$day_key]['entries'][] = $entry_data;
            } catch (\Exception $e) {
                \Log::warning('Error parsing date in daily cashbook: ' . $datetime . ' - ' . $e->getMessage());
                continue;
            }
        }

        // Initialize all days in date range (even if no transactions)
        if ($start_date && $end_date) {
            $current = $start_date_obj->copy();
            
            while ($current <= $end_date_obj) {
                $day_key = $current->format('Y-m-d');
                $day_display = $current->format('d-M-Y');
                $day_name = $current->format('l');
                
                if (!isset($daily_groups[$day_key])) {
                    $daily_groups[$day_key] = [
                        'date' => $day_display,
                        'date_key' => $day_key,
                        'day_name' => $day_name,
                        'debit' => 0,
                        'credit' => 0,
                        'entries' => []
                    ];
                }
                
                $current->addDay();
            }
        }

        // Sort by date
        ksort($daily_groups);

        // Calculate opening balance (from start of period)
        $opening_balance = $this->calculateOpeningBalance(
            $business_id,
            $start_date,
            $location_id,
            $query_locations,
            $account_ids,
            $account_id  // Pass account_id for opening balance calculation
        );

        // Build result array
        $result = [];
        $running_balance = $opening_balance;
        $total_debit = 0;
        $total_credit = 0;

        // Add opening balance row - ALWAYS shown as DEBIT
        $result[] = [
            'date' => __('daybook::lang.opening_balance'),
            'date_key' => '',
            'day_name' => '',
            'debit' => abs($opening_balance),  // Always debit for opening balance
            'credit' => 0,  // Never credit for opening balance
            'balance' => $opening_balance,
            'is_opening' => true,
            'is_total' => false
        ];

        // Process each day in chronological order
        foreach ($daily_groups as $day_key => $day_data) {
            $day_debit = $day_data['debit'];
            $day_credit = $day_data['credit'];

            // Update running balance: Debit increases balance, Credit decreases balance
            $running_balance = $running_balance + $day_debit - $day_credit;
            
            $total_debit += $day_debit;
            $total_credit += $day_credit;

            $result[] = [
                'date' => $day_data['date'],
                'date_key' => $day_key,
                'day_name' => $day_data['day_name'],
                'debit' => $day_debit,
                'credit' => $day_credit,
                'balance' => $running_balance,
                'is_opening' => false,
                'is_total' => false
            ];
        }

        // Add grand total row
        $result[] = [
            'date' => __('daybook::lang.grand_total'),
            'date_key' => '',
            'day_name' => '',
            'debit' => $total_debit,
            'credit' => $total_credit,
            'balance' => $running_balance,
            'is_opening' => false,
            'is_total' => true
        ];

        return [
            'daily_data' => $result,
            'opening_balance' => $opening_balance,
            'final_closing_balance' => $running_balance,
            'total_debit' => $total_debit,
            'total_credit' => $total_credit,
            'start_date' => $start_date_obj->format('d-M-Y'),
            'end_date' => $end_date_obj->format('d-M-Y')
        ];
    }

    /**
     * Display Daily Payment Report
     */
    public function dailyPayment(Request $request)
    {
        if (!auth()->user()->can('daybook.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $accounts = Account::forDropdown($business_id, true, false, false);
        $users = User::forDropdown($business_id, false, true, false);

        // Set default account to the first available account
        $default_account = !empty($accounts) ? array_key_first($accounts) : null;

        return view('daybook::daily_payment', compact(
            'business_locations',
            'accounts',
            'users',
            'default_account'
        ));
    }

    /**
     * Display Monthly Dashboard
     */
    public function monthlyDashboard(Request $request)
    {
        if (!auth()->user()->can('daybook.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $accounts = Account::forDropdown($business_id, true, false, false);
        $business = \App\Business::find($business_id);

        return view('daybook::monthly_dashboard', compact(
            'business_locations',
            'accounts',
            'business'
        ));
    }

    /**
     * Get Monthly Dashboard Data
     */
    public function getMonthlyDashboardData(Request $request)
    {
        try {
            if (!auth()->user()->can('daybook.view')) {
                abort(403, 'Unauthorized action.');
            }

            $business_id = $request->session()->get('user.business_id');
            
            // Get date range - dates are sent in YYYY-MM-DD format from frontend
            $start_date = null;
            $end_date = null;

            if (!empty($request->start_date)) {
                try {
                    // Dates are already in YYYY-MM-DD format, parse directly with Carbon
                    $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    \Log::warning('Monthly Dashboard start_date conversion error: ' . $e->getMessage() . ' - Input: ' . $request->start_date);
                    $start_date = null;
                }
            }

            if (!empty($request->end_date)) {
                try {
                    // Dates are already in YYYY-MM-DD format, parse directly with Carbon
                    $end_date = Carbon::createFromFormat('Y-m-d', $request->end_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    \Log::warning('Monthly Dashboard end_date conversion error: ' . $e->getMessage() . ' - Input: ' . $request->end_date);
                    $end_date = null;
                }
            }

            // Default to current year if no dates provided
            if (!$start_date) {
                $start_date = Carbon::now()->startOfYear()->format('Y-m-d');
            }
            if (!$end_date) {
                $end_date = Carbon::now()->format('Y-m-d');
            }

            $location_id = $request->location_id;
            $account_id = $request->account_id;
            $scope = $request->scope ?? 'all';

            // Get permitted locations
            $permitted_locations = auth()->user()->permitted_locations();
            $query_locations = ($permitted_locations != 'all') ? $permitted_locations : null;
            
            // Get account IDs based on scope
            $account_ids = null;
            if ($scope != 'all') {
                $account_ids = $this->getAccountIdsByScope($business_id, $scope);
            }
            
            // If specific account selected, use only that
            if ($account_id) {
                $account_ids = [$account_id];
            }

            // Build query for all transactions in the date range
            $daybook_entries = $this->buildDaybookQuery(
                $business_id,
                $start_date,
                $end_date,
                $location_id,
                null, // No user filter
                $account_id,
                null, // No module filter
                $query_locations,
                $account_ids
            );

            // Get all entries
            $entries = $daybook_entries->get();

            // Calculate monthly summary
            $monthly_summary = $this->calculateMonthlySummary($entries, $business_id, $start_date, $end_date, $location_id, $query_locations, $account_ids, $account_id);

            return response()->json([
                'success' => true,
                'data' => $monthly_summary
            ]);

        } catch (\Exception $e) {
            \Log::error('Monthly Dashboard Error: ' . $e->getMessage());
            \Log::error('Monthly Dashboard Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'msg' => 'Error loading monthly dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate Monthly Summary for Dashboard
     */
    protected function calculateMonthlySummary($entries, $business_id, $start_date, $end_date, $location_id, $query_locations, $account_ids, $account_id = null)
    {
        // Group by month
        $monthly_data = [];
        
        foreach ($entries as $entry) {
            $entry_data = is_object($entry) ? (array) $entry : $entry;
            if (!is_array($entry_data)) {
                continue;
            }

            $datetime = $entry_data['datetime'] ?? null;
            if (!$datetime) {
                continue;
            }

            try {
                $date = Carbon::parse($datetime);
                $month_key = $date->format('Y-m');
                $month_name = $date->format('F Y');
                
                if (!isset($monthly_data[$month_key])) {
                    $monthly_data[$month_key] = [
                        'month_name' => $month_name,
                        'month_key' => $month_key,
                        'cash_in' => 0,   // Debit
                        'cash_out' => 0,  // Credit
                        'net_balance' => 0
                    ];
                }

                // Check if this is an opening balance transaction
                $module = $entry_data['module'] ?? '';
                $type = $entry_data['type'] ?? '';
                $amount = floatval($entry_data['amount'] ?? 0);

                // Opening balance transactions are ALWAYS treated as DEBIT (cash_in)
                if ($module == 'opening_balance') {
                    $monthly_data[$month_key]['cash_in'] += $amount;
                } elseif ($type == 'debit') {
                    $monthly_data[$month_key]['cash_in'] += $amount;
                } else {
                    $monthly_data[$month_key]['cash_out'] += $amount;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Initialize all months in date range
        if ($start_date && $end_date) {
            $start = Carbon::parse($start_date)->startOfMonth();
            $end = Carbon::parse($end_date)->startOfMonth();
            
            $current = $start->copy();
            while ($current <= $end) {
                $month_key = $current->format('Y-m');
                $month_name = $current->format('F Y');
                
                if (!isset($monthly_data[$month_key])) {
                    $monthly_data[$month_key] = [
                        'month_name' => $month_name,
                        'month_key' => $month_key,
                        'cash_in' => 0,
                        'cash_out' => 0,
                        'net_balance' => 0
                    ];
                }
                
                $current->addMonth();
            }
        }

        // Sort by month and calculate net balance
        ksort($monthly_data);
        $total_cash_in = 0;
        $total_cash_out = 0;
        
        foreach ($monthly_data as $month_key => &$data) {
            $data['net_balance'] = $data['cash_in'] - $data['cash_out'];
            $total_cash_in += $data['cash_in'];
            $total_cash_out += $data['cash_out'];
        }

        // Calculate opening balance
        $opening_balance = $this->calculateOpeningBalance(
            $business_id,
            $start_date,
            $location_id,
            $query_locations,
            $account_ids,
            $account_id  // Pass account_id for opening balance calculation
        );

        return [
            'monthly_data' => array_values($monthly_data),
            'opening_balance' => $opening_balance,
            'total_cash_in' => $total_cash_in,
            'total_cash_out' => $total_cash_out,
            'net_balance' => $total_cash_in - $total_cash_out,
            'final_balance' => $opening_balance + ($total_cash_in - $total_cash_out)
        ];
    }

    /**
     * Get Daily Payment Data
     */
    public function getDailyPaymentData(Request $request)
    {
        try {
            if (!auth()->user()->can('daybook.view')) {
                abort(403, 'Unauthorized action.');
            }

            $business_id = $request->session()->get('user.business_id');

            // Get date range - dates are sent in YYYY-MM-DD format from frontend
            $start_date = null;
            $end_date = null;

            if (!empty($request->start_date)) {
                try {
                    $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    \Log::warning('Daily Payment start_date conversion error: ' . $e->getMessage() . ' - Input: ' . $request->start_date);
                    $start_date = null;
                }
            }

            if (!empty($request->end_date)) {
                try {
                    $end_date = Carbon::createFromFormat('Y-m-d', $request->end_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    \Log::warning('Daily Payment end_date conversion error: ' . $e->getMessage() . ' - Input: ' . $request->end_date);
                    $end_date = null;
                }
            }

            // Default to today if no dates provided
            if (!$start_date) {
                $start_date = Carbon::now()->format('Y-m-d');
            }
            if (!$end_date) {
                $end_date = Carbon::now()->format('Y-m-d');
            }

            $location_id = $request->location_id;
            $user_id = $request->user_id;
            $account_id = $request->account_id;

            // Get permitted locations
            $permitted_locations = auth()->user()->permitted_locations();
            $query_locations = ($permitted_locations != 'all') ? $permitted_locations : null;

            // Build query for all transactions in the date range
            $daybook_entries = $this->buildDaybookQuery(
                $business_id,
                $start_date,
                $end_date,
                $location_id,
                $user_id,
                $account_id,
                null, // No module filter
                $query_locations,
                null // No account_ids filter for scope
            );

            // Get all entries
            $entries = $daybook_entries->get();

            // Group by account and calculate totals
            $payment_data = $this->calculateDailyPaymentData($entries, $business_id, $start_date, $end_date, $location_id, $query_locations, $account_id);

            return response()->json([
                'success' => true,
                'data' => $payment_data
            ]);

        } catch (\Exception $e) {
            \Log::error('Daily Payment Error: ' . $e->getMessage());
            \Log::error('Daily Payment Trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'msg' => 'Error loading daily payment data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate Daily Payment Data - Group by payment account
     */
    protected function calculateDailyPaymentData($entries, $business_id, $start_date, $end_date, $location_id, $query_locations, $account_id = null)
    {
        // Group entries by account
        $account_groups = [];
        $total_debit = 0;
        $total_credit = 0;

        foreach ($entries as $entry) {
            $entry_data = is_object($entry) ? (array) $entry : $entry;
            if (!is_array($entry_data)) {
                continue;
            }

            $account_name = $entry_data['account'] ?? 'Unknown Account';
            $type = $entry_data['type'] ?? '';
            $amount = floatval($entry_data['amount'] ?? 0);

            if (!isset($account_groups[$account_name])) {
                $account_groups[$account_name] = [
                    'account_name' => $account_name,
                    'debit' => 0,
                    'credit' => 0,
                    'net_amount' => 0
                ];
            }

            if ($type == 'debit') {
                $account_groups[$account_name]['debit'] += $amount;
                $total_debit += $amount;
            } else {
                $account_groups[$account_name]['credit'] += $amount;
                $total_credit += $amount;
            }

            // Calculate net amount (debit - credit) for each account
            $account_groups[$account_name]['net_amount'] = $account_groups[$account_name]['debit'] - $account_groups[$account_name]['credit'];
        }

        // Sort accounts by total transaction volume (debit + credit)
        uasort($account_groups, function($a, $b) {
            $a_total = $a['debit'] + $a['credit'];
            $b_total = $b['debit'] + $b['credit'];
            return $b_total <=> $a_total; // Descending order
        });

        return [
            'payment_accounts' => array_values($account_groups),
            'total_debit' => $total_debit,
            'total_credit' => $total_credit,
            'net_total' => $total_debit - $total_credit,
            'date_range' => [
                'start_date' => Carbon::parse($start_date)->format('d-M-Y'),
                'end_date' => Carbon::parse($end_date)->format('d-M-Y')
            ]
        ];
    }
}