<?php

namespace Modules\Daybook\Exports;

use App\Utils\Util;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DaybookExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $business_id;
    protected $filters;
    protected $commonUtil;

    public function __construct($business_id, $filters = [])
    {
        $this->business_id = $business_id;
        $this->filters = $filters;
        $this->commonUtil = new Util();
    }

    public function collection()
    {
        $start_date = !empty($this->filters['start_date']) 
            ? $this->commonUtil->uf_date($this->filters['start_date']) 
            : \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        
        $end_date = !empty($this->filters['end_date']) 
            ? $this->commonUtil->uf_date($this->filters['end_date']) 
            : \Carbon\Carbon::now()->format('Y-m-d');
        
        $location_id = $this->filters['location_id'] ?? null;
        $user_id = $this->filters['user_id'] ?? null;
        $account_id = $this->filters['account_id'] ?? null;
        $module_filter = $this->filters['module_filter'] ?? null;
        $scope = $this->filters['scope'] ?? 'cash_bank';

        // Get permitted locations
        $permitted_locations = auth()->user()->permitted_locations();
        $query_locations = ($permitted_locations != 'all') ? $permitted_locations : null;

        // Get account IDs based on scope
        $account_ids = $this->getAccountIdsByScope($scope);

        // Build query using the same logic as controller
        $daybook_entries = $this->buildDaybookQuery(
            $start_date,
            $end_date,
            $location_id,
            $user_id,
            $account_id,
            $module_filter,
            $query_locations,
            $account_ids
        );

        // Get all entries
        $entries = $daybook_entries->get();

        // Calculate opening balance
        $opening_balance = $this->calculateOpeningBalance(
            $this->business_id,
            $start_date,
            $location_id,
            $query_locations,
            $account_ids
        );

        // Calculate running balance starting with opening balance
        $running_balance = $opening_balance;
        $formatted_entries = collect();

        // Opening Balance is NOT added as a table row - it only appears in footer
        // The running balance calculation starts with opening balance

        // Add regular entries
        foreach ($entries as $entry) {
            if ($entry->type == 'debit') {
                $running_balance += $entry->amount;
            } else {
                $running_balance -= $entry->amount;
            }

            $formatted_entries->push([
                'datetime' => $entry->datetime,
                'voucher_no' => $entry->voucher_no,
                'module' => ucfirst(str_replace('_', ' ', $entry->module)),
                'party' => $entry->party ?? '-',
                'location' => $entry->location ?? '-',
                'account' => $entry->account ?? '-',
                'debit' => $entry->type == 'debit' ? $entry->amount : 0,
                'credit' => $entry->type == 'credit' ? $entry->amount : 0,
                'running_balance' => $running_balance,
                'narration' => $entry->narration ?? '-',
                'user' => $entry->user ?? '-',
            ]);
        }

        return $formatted_entries;
    }

    public function headings(): array
    {
        return [
            __('daybook::lang.datetime'),
            __('daybook::lang.voucher_no'),
            __('daybook::lang.module'),
            __('daybook::lang.party'),
            __('daybook::lang.location'),
            __('daybook::lang.account'),
            __('daybook::lang.debit'),
            __('daybook::lang.credit'),
            __('daybook::lang.running_balance'),
            __('daybook::lang.narration'),
            __('daybook::lang.user'),
        ];
    }

    public function map($row): array
    {
        return [
            $this->commonUtil->format_date($row['datetime'], true),
            $row['voucher_no'],
            $row['module'],
            $row['party'],
            $row['location'],
            $row['account'],
            $this->commonUtil->num_f($row['debit'], false),
            $this->commonUtil->num_f($row['credit'], false),
            $this->commonUtil->num_f($row['running_balance'], false),
            $row['narration'],
            $row['user'],
        ];
    }

    public function title(): string
    {
        return 'Daybook';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E6E6E6']
                ]
            ],
        ];
    }

    protected function getAccountIdsByScope($scope)
    {
        if ($scope == 'all') {
            return null;
        }

        $account_ids = [];

        if (in_array($scope, ['cash', 'cash_bank'])) {
            $cash = \App\Account::where('business_id', $this->business_id)
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
            $bank = \App\Account::where('business_id', $this->business_id)
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

        return !empty($account_ids) ? array_unique($account_ids) : null;
    }

    protected function buildDaybookQuery($start_date, $end_date, $location_id, $user_id, $account_id, $module_filter, $query_locations, $account_ids)
    {
        // Reuse the same query building logic from controller
        $controller = new \Modules\Daybook\Http\Controllers\DaybookController(new Util());
        
        return $controller->buildDaybookQuery(
            $this->business_id,
            $start_date,
            $end_date,
            $location_id,
            $user_id,
            $account_id,
            $module_filter,
            $query_locations,
            $account_ids
        );
    }
    
    /**
     * Access protected method calculateOpeningBalance via reflection
     */
    protected function calculateOpeningBalance($business_id, $start_date, $location_id, $query_locations, $account_ids)
    {
        $controller = new \Modules\Daybook\Http\Controllers\DaybookController(new Util());
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('calculateOpeningBalance');
        $method->setAccessible(true);
        return $method->invoke($controller, $business_id, $start_date, $location_id, $query_locations, $account_ids);
    }
}

