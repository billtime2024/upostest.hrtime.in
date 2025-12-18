<?php

namespace Modules\Accounting\Console;

use Illuminate\Console\Command;
use Modules\Accounting\Entities\AccountingAccount;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Utils\AccountingUtil;
use App\Business;
use App\BusinessLocation;
use App\Transaction;
use App\TransactionPayment;
use DB;

class AutoConfigureAccounting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:auto-configure {--business_id= : Business ID to configure} {--force : Force reconfiguration even if already configured}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-configure accounting module for existing businesses and transactions';

    protected $accountingUtil;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(AccountingUtil $accountingUtil)
    {
        parent::__construct();
        $this->accountingUtil = $accountingUtil;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $businessId = $this->option('business_id');
        $force = $this->option('force');

        if ($businessId) {
            $businesses = Business::where('id', $businessId)->get();
        } else {
            $businesses = Business::all();
        }

        $this->info('Starting accounting auto-configuration...');

        foreach ($businesses as $business) {
            $this->info("Configuring business: {$business->name} (ID: {$business->id})");

            // Check if already configured
            $existingAccounts = AccountingAccount::where('business_id', $business->id)->count();
            if ($existingAccounts > 0 && !$force) {
                $this->warn("Business {$business->id} already has {$existingAccounts} accounts. Use --force to reconfigure.");
                continue;
            }

            DB::beginTransaction();
            try {
                // Step 1: Create default accounts
                $this->createDefaultAccounts($business->id);

                // Step 2: Set default mappings for all locations
                $this->setDefaultMappings($business->id);

                // Step 3: Bulk map existing transactions
                $this->bulkMapTransactions($business->id);

                DB::commit();
                $this->info("Successfully configured business {$business->id}");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to configure business {$business->id}: " . $e->getMessage());
                continue;
            }
        }

        $this->info('Accounting auto-configuration completed!');
        return 0;
    }

    /**
     * Create default accounts for a business
     */
    private function createDefaultAccounts($businessId)
    {
        $this->info("Creating default accounts for business {$businessId}...");

        $defaultAccounts = $this->getDefaultAccountsData($businessId);

        foreach ($defaultAccounts as $account) {
            AccountingAccount::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'name' => $account['name']
                ],
                $account
            );
        }

        $this->info("Created " . count($defaultAccounts) . " default accounts");
    }

    /**
     * Set default account mappings for all business locations
     */
    private function setDefaultMappings($businessId)
    {
        $this->info("Setting default mappings for business {$businessId}...");

        $locations = BusinessLocation::where('business_id', $businessId)->get();
        $defaultMappings = $this->getDefaultMappings($businessId);

        foreach ($locations as $location) {
            $existingMap = json_decode($location->accounting_default_map, true) ?? [];

            // Merge with existing mappings, preferring new defaults
            $mergedMap = array_merge($existingMap, $defaultMappings);

            $location->accounting_default_map = json_encode($mergedMap);
            $location->save();
        }

        $this->info("Set default mappings for " . $locations->count() . " locations");
    }

    /**
     * Bulk map existing unmapped transactions
     */
    private function bulkMapTransactions($businessId)
    {
        $this->info("Bulk mapping transactions for business {$businessId}...");

        // Get default mappings
        $defaultMappings = $this->getDefaultMappings($businessId);

        // Map sales transactions
        $this->mapSalesTransactions($businessId, $defaultMappings);

        // Map purchase transactions
        $this->mapPurchaseTransactions($businessId, $defaultMappings);

        // Map payment transactions
        $this->mapPaymentTransactions($businessId, $defaultMappings);

        // Map expense transactions
        $this->mapExpenseTransactions($businessId, $defaultMappings);
    }

    private function mapSalesTransactions($businessId, $defaultMappings)
    {
        $sales = Transaction::where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDoesntHave('accountingTransactions')
            ->get();

        $mapped = 0;
        foreach ($sales as $sale) {
            if (isset($defaultMappings['sale'])) {
                $this->accountingUtil->saveMap(
                    'sell',
                    $sale->id,
                    1, // system user
                    $businessId,
                    $defaultMappings['sale']['deposit_to'] ?? null,
                    $defaultMappings['sale']['payment_account'] ?? null
                );
                $mapped++;
            }
        }
        $this->info("Mapped {$mapped} sales transactions");
    }

    private function mapPurchaseTransactions($businessId, $defaultMappings)
    {
        $purchases = Transaction::where('business_id', $businessId)
            ->where('type', 'purchase')
            ->where('status', 'received')
            ->whereDoesntHave('accountingTransactions')
            ->get();

        $mapped = 0;
        foreach ($purchases as $purchase) {
            if (isset($defaultMappings['purchases'])) {
                $this->accountingUtil->saveMap(
                    'purchase',
                    $purchase->id,
                    1, // system user
                    $businessId,
                    $defaultMappings['purchases']['deposit_to'] ?? null,
                    $defaultMappings['purchases']['payment_account'] ?? null
                );
                $mapped++;
            }
        }
        $this->info("Mapped {$mapped} purchase transactions");
    }

    private function mapPaymentTransactions($businessId, $defaultMappings)
    {
        // Map sell payments
        $sellPayments = TransactionPayment::where('business_id', $businessId)
            ->whereHas('transaction', function($q) {
                $q->where('type', 'sell');
            })
            ->whereDoesntHave('accountingTransactions')
            ->get();

        $mapped = 0;
        foreach ($sellPayments as $payment) {
            if (isset($defaultMappings['sell_payment'])) {
                $this->accountingUtil->saveMap(
                    'sell_payment',
                    $payment->id,
                    1, // system user
                    $businessId,
                    $defaultMappings['sell_payment']['deposit_to'] ?? null,
                    $defaultMappings['sell_payment']['payment_account'] ?? null
                );
                $mapped++;
            }
        }
        $this->info("Mapped {$mapped} sell payment transactions");

        // Map purchase payments
        $purchasePayments = TransactionPayment::where('business_id', $businessId)
            ->whereHas('transaction', function($q) {
                $q->where('type', 'purchase');
            })
            ->whereDoesntHave('accountingTransactions')
            ->get();

        $mapped = 0;
        foreach ($purchasePayments as $payment) {
            if (isset($defaultMappings['purchase_payment'])) {
                $this->accountingUtil->saveMap(
                    'purchase_payment',
                    $payment->id,
                    1, // system user
                    $businessId,
                    $defaultMappings['purchase_payment']['deposit_to'] ?? null,
                    $defaultMappings['purchase_payment']['payment_account'] ?? null
                );
                $mapped++;
            }
        }
        $this->info("Mapped {$mapped} purchase payment transactions");
    }

    private function mapExpenseTransactions($businessId, $defaultMappings)
    {
        $expenses = Transaction::where('business_id', $businessId)
            ->where('type', 'expense')
            ->whereDoesntHave('accountingTransactions')
            ->get();

        $mapped = 0;
        foreach ($expenses as $expense) {
            if (isset($defaultMappings['expense'])) {
                $this->accountingUtil->saveMap(
                    'expense',
                    $expense->id,
                    1, // system user
                    $businessId,
                    $defaultMappings['expense']['deposit_to'] ?? null,
                    $defaultMappings['expense']['payment_account'] ?? null
                );
                $mapped++;
            }
        }
        $this->info("Mapped {$mapped} expense transactions");
    }

    /**
     * Get default accounts data
     */
    private function getDefaultAccountsData($businessId)
    {
        return [
            // Assets
            ['name' => 'Cash', 'business_id' => $businessId, 'account_primary_type' => 'asset', 'account_sub_type_id' => 3, 'detail_type_id' => 31, 'status' => 'active', 'created_by' => 1],
            ['name' => 'Bank', 'business_id' => $businessId, 'account_primary_type' => 'asset', 'account_sub_type_id' => 3, 'detail_type_id' => 30, 'status' => 'active', 'created_by' => 1],
            ['name' => 'Accounts Receivable', 'business_id' => $businessId, 'account_primary_type' => 'asset', 'account_sub_type_id' => 1, 'detail_type_id' => 16, 'status' => 'active', 'created_by' => 1],
            ['name' => 'Inventory', 'business_id' => $businessId, 'account_primary_type' => 'asset', 'account_sub_type_id' => 2, 'detail_type_id' => 21, 'status' => 'active', 'created_by' => 1],

            // Liabilities
            ['name' => 'Accounts Payable', 'business_id' => $businessId, 'account_primary_type' => 'liability', 'account_sub_type_id' => 6, 'detail_type_id' => 58, 'status' => 'active', 'created_by' => 1],

            // Income
            ['name' => 'Sales Revenue', 'business_id' => $businessId, 'account_primary_type' => 'income', 'account_sub_type_id' => 11, 'detail_type_id' => 103, 'status' => 'active', 'created_by' => 1],

            // Expenses
            ['name' => 'Cost of Goods Sold', 'business_id' => $businessId, 'account_primary_type' => 'expenses', 'account_sub_type_id' => 13, 'detail_type_id' => 118, 'status' => 'active', 'created_by' => 1],
            ['name' => 'General Expenses', 'business_id' => $businessId, 'account_primary_type' => 'expenses', 'account_sub_type_id' => 14, 'detail_type_id' => 138, 'status' => 'active', 'created_by' => 1],

            // Equity
            ['name' => 'Retained Earnings', 'business_id' => $businessId, 'account_primary_type' => 'equity', 'account_sub_type_id' => 10, 'detail_type_id' => 94, 'status' => 'active', 'created_by' => 1],
        ];
    }

    /**
     * Get default account mappings
     */
    private function getDefaultMappings($businessId)
    {
        // Get account IDs
        $accounts = AccountingAccount::where('business_id', $businessId)
            ->where('status', 'active')
            ->pluck('id', 'name')
            ->toArray();

        return [
            'sale' => [
                'payment_account' => $accounts['Sales Revenue'] ?? null,
                'deposit_to' => $accounts['Accounts Receivable'] ?? null,
            ],
            'sell_payment' => [
                'payment_account' => $accounts['Cash'] ?? null,
                'deposit_to' => $accounts['Accounts Receivable'] ?? null,
            ],
            'purchases' => [
                'payment_account' => $accounts['Cost of Goods Sold'] ?? null,
                'deposit_to' => $accounts['Accounts Payable'] ?? null,
            ],
            'purchase_payment' => [
                'payment_account' => $accounts['Cash'] ?? null,
                'deposit_to' => $accounts['Accounts Payable'] ?? null,
            ],
            'expense' => [
                'payment_account' => $accounts['General Expenses'] ?? null,
                'deposit_to' => $accounts['Cash'] ?? null,
            ],
        ];
    }
}