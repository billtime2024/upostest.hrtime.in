<?php

namespace App\Utils;

use InvalidArgumentException;

class LoanCalculationUtil
{
    /**
     * Calculate EMI for a loan.
     *
     * @param float $principal The loan principal amount
     * @param float $annualRate The annual interest rate (e.g., 0.12 for 12%)
     * @param int $tenureMonths The loan tenure in months
     * @return float The calculated EMI
     * @throws InvalidArgumentException
     */
    public static function calculateEMI(float $principal, float $annualRate, int $tenureMonths): float
    {
        if ($principal <= 0) {
            throw new InvalidArgumentException('Principal must be greater than 0');
        }
        if ($annualRate < 0 || $annualRate > 1) {
            throw new InvalidArgumentException('Annual rate must be between 0 and 1');
        }
        if ($tenureMonths <= 0) {
            throw new InvalidArgumentException('Tenure must be greater than 0');
        }

        $ratePerPeriod = $annualRate / 12;
        $periods = $tenureMonths;

        return self::calculateEMIInternal($principal, $ratePerPeriod, $periods);
    }

    /**
     * Calculate interest rate from EMI, principal, and tenure.
     *
     * @param float $emi The EMI amount
     * @param float $principal The loan principal amount
     * @param int $tenureMonths The loan tenure in months
     * @param float $tolerance The tolerance for approximation
     * @param int $maxIterations Maximum iterations for bisection
     * @return float The calculated annual interest rate
     * @throws InvalidArgumentException
     */
    public static function calculateInterestRate(float $emi, float $principal, int $tenureMonths, float $tolerance = 0.0001, int $maxIterations = 100): float
    {
        if ($emi <= 0) {
            throw new InvalidArgumentException('EMI must be greater than 0');
        }
        if ($principal <= 0) {
            throw new InvalidArgumentException('Principal must be greater than 0');
        }
        if ($tenureMonths <= 0) {
            throw new InvalidArgumentException('Tenure must be greater than 0');
        }
        if ($emi < $principal / $tenureMonths) {
            throw new InvalidArgumentException('EMI is too low for the given principal and tenure');
        }

        $low = 0.0;
        $high = 0.5; // 50% annual rate max

        for ($i = 0; $i < $maxIterations; $i++) {
            $mid = ($low + $high) / 2;
            $calculatedEMI = self::calculateEMIInternal($principal, $mid / 12, $tenureMonths);

            if (abs($calculatedEMI - $emi) < $tolerance) {
                return round($mid, 4);
            }

            if ($calculatedEMI < $emi) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        return round(($low + $high) / 2, 4);
    }

    /**
     * Generate repayment schedule for different frequencies.
     *
     * @param float $principal The loan principal amount
     * @param float $annualRate The annual interest rate
     * @param int $tenureMonths The loan tenure in months
     * @param string $frequency The repayment frequency ('daily', 'weekly', 'monthly')
     * @return array The repayment schedule
     * @throws InvalidArgumentException
     */
    public static function generateRepaymentSchedule(float $principal, float $annualRate, int $tenureMonths, string $frequency = 'monthly'): array
    {
        $frequency = strtolower($frequency);
        if (!in_array($frequency, ['daily', 'weekly', 'monthly'])) {
            throw new InvalidArgumentException('Frequency must be daily, weekly, or monthly');
        }
        if ($principal <= 0) {
            throw new InvalidArgumentException('Principal must be greater than 0');
        }
        if ($annualRate < 0 || $annualRate > 1) {
            throw new InvalidArgumentException('Annual rate must be between 0 and 1');
        }
        if ($tenureMonths <= 0) {
            throw new InvalidArgumentException('Tenure must be greater than 0');
        }

        // Calculate periods and rate per period based on frequency
        switch ($frequency) {
            case 'monthly':
                $periodsPerYear = 12;
                $periods = $tenureMonths;
                break;
            case 'weekly':
                $periodsPerYear = 52;
                $periods = round($tenureMonths * (52 / 12));
                break;
            case 'daily':
                $periodsPerYear = 365;
                $periods = round($tenureMonths * (365 / 12));
                break;
        }

        $ratePerPeriod = $annualRate / $periodsPerYear;
        $emi = self::calculateEMIInternal($principal, $ratePerPeriod, $periods);

        $balance = $principal;
        $schedule = [];

        for ($i = 1; $i <= $periods; $i++) {
            $interest = $balance * $ratePerPeriod;
            $principalPaid = $emi - $interest;
            if ($principalPaid > $balance) {
                $principalPaid = $balance;
                $emi = $interest + $principalPaid; // Adjust last EMI
            }
            $balance -= $principalPaid;

            $schedule[] = [
                'installment' => $i,
                'emi' => round($emi, 2),
                'principal' => round($principalPaid, 2),
                'interest' => round($interest, 2),
                'balance' => round($balance, 2)
            ];

            if ($balance <= 0) {
                break;
            }
        }

        return $schedule;
    }

    /**
     * Get principal and interest breakdown for each installment (monthly).
     *
     * @param float $principal The loan principal amount
     * @param float $annualRate The annual interest rate
     * @param int $tenureMonths The loan tenure in months
     * @return array The breakdown array
     * @throws InvalidArgumentException
     */
    public static function getPrincipalInterestBreakdown(float $principal, float $annualRate, int $tenureMonths): array
    {
        if ($principal <= 0) {
            throw new InvalidArgumentException('Principal must be greater than 0');
        }
        if ($annualRate < 0 || $annualRate > 1) {
            throw new InvalidArgumentException('Annual rate must be between 0 and 1');
        }
        if ($tenureMonths <= 0) {
            throw new InvalidArgumentException('Tenure must be greater than 0');
        }

        $ratePerPeriod = $annualRate / 12;
        $periods = $tenureMonths;
        $emi = self::calculateEMIInternal($principal, $ratePerPeriod, $periods);

        $balance = $principal;
        $breakdown = [];

        for ($i = 1; $i <= $periods; $i++) {
            $interest = $balance * $ratePerPeriod;
            $principalPaid = $emi - $interest;
            if ($principalPaid > $balance) {
                $principalPaid = $balance;
            }
            $balance -= $principalPaid;

            $breakdown[] = [
                'installment' => $i,
                'principal' => round($principalPaid, 2),
                'interest' => round($interest, 2),
                'balance' => round($balance, 2)
            ];

            if ($balance <= 0) {
                break;
            }
        }

        return $breakdown;
    }

    /**
     * Internal method to calculate EMI.
     *
     * @param float $principal
     * @param float $ratePerPeriod
     * @param int $periods
     * @return float
     */
    private static function calculateEMIInternal(float $principal, float $ratePerPeriod, int $periods): float
    {
        if ($ratePerPeriod == 0) {
            return round($principal / $periods, 2);
        }

        $emi = $principal * $ratePerPeriod * pow(1 + $ratePerPeriod, $periods) / (pow(1 + $ratePerPeriod, $periods) - 1);
        return round($emi, 2);
    }
}