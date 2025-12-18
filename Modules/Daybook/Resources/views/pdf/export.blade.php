<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daybook Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .header { text-align: center; margin-bottom: 20px; }
        .summary { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Daybook Report</h2>
        @if($business)
            <h3>{{ $business->name }}</h3>
        @endif
        @if($business_location)
            <p>Location: {{ $business_location->name }}</p>
        @endif
        <p>
            Date Range: {{ !empty($filters['start_date']) ? $commonUtil->format_date($filters['start_date']) : 'N/A' }} 
            to {{ !empty($filters['end_date']) ? $commonUtil->format_date($filters['end_date']) : 'N/A' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Voucher No.</th>
                <th>Module</th>
                <th>Party</th>
                <th>Location</th>
                <th>Account</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
                <th>Narration</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_debit = 0;
                $total_credit = 0;
            @endphp
            @foreach($entries as $entry)
                @php
                    // Skip opening balance entries (they appear only in footer)
                    if (isset($entry->voucher_no) && $entry->voucher_no == 'Opening Balance') {
                        continue;
                    }
                    $debit = isset($entry->type) && $entry->type == 'debit' ? (isset($entry->amount) ? $entry->amount : 0) : 0;
                    $credit = isset($entry->type) && $entry->type == 'credit' ? (isset($entry->amount) ? $entry->amount : 0) : 0;
                    $total_debit += $debit;
                    $total_credit += $credit;
                @endphp
                @if(!isset($entry->voucher_no) || $entry->voucher_no != 'Opening Balance')
                <tr>
                    <td>{{ isset($entry->datetime) ? $commonUtil->format_date($entry->datetime, true) : '-' }}</td>
                    <td>{{ $entry->voucher_no ?? '-' }}</td>
                    <td>{{ isset($entry->module) ? ucfirst(str_replace('_', ' ', $entry->module)) : '-' }}</td>
                    <td>{{ $entry->party ?? '-' }}</td>
                    <td>{{ $entry->location ?? '-' }}</td>
                    <td>{{ $entry->account ?? '-' }}</td>
                    <td class="text-right">{{ $debit > 0 ? $commonUtil->num_f($debit, false) : '-' }}</td>
                    <td class="text-right">{{ $credit > 0 ? $commonUtil->num_f($credit, false) : '-' }}</td>
                    <td class="text-right">{{ isset($entry->running_balance) ? $commonUtil->num_f($entry->running_balance, false) : '-' }}</td>
                    <td>{{ $entry->narration ?? '-' }}</td>
                    <td>{{ $entry->user ?? '-' }}</td>
                </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="6" class="text-right"><strong>Total:</strong></td>
                <td class="text-right"><strong>{{ $commonUtil->num_f($total_debit, false) }}</strong></td>
                <td class="text-right"><strong>{{ $commonUtil->num_f($total_credit, false) }}</strong></td>
                <td colspan="3"></td>
            </tr>
            @php
                $opening_balance = $opening_balance ?? 0;
            @endphp
            @if(abs($opening_balance) > 0.01)
            <tr style="font-weight: bold; background-color: #fff3cd;">
                <td colspan="6" class="text-right"><strong>Opening Balance:</strong></td>
                <td class="text-right"><strong>{{ $opening_balance >= 0 ? $commonUtil->num_f(abs($opening_balance), false) : '-' }}</strong></td>
                <td class="text-right"><strong>{{ $opening_balance < 0 ? $commonUtil->num_f(abs($opening_balance), false) : '-' }}</strong></td>
                <td colspan="3"></td>
            </tr>
            @endif
            @php
                $closing_balance = $running_balance ?? $opening_balance;
            @endphp
            <tr style="font-weight: bold; background-color: #ffe6e6;">
                <td colspan="6" class="text-right"><strong>Closing Balance:</strong></td>
                <td class="text-right"><strong>{{ $closing_balance >= 0 ? $commonUtil->num_f(abs($closing_balance), false) : '-' }}</strong></td>
                <td class="text-right"><strong>{{ $closing_balance < 0 ? $commonUtil->num_f(abs($closing_balance), false) : '-' }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <p><strong>Generated:</strong> {{ date('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>

