<?php

namespace App\Services;

use App\Models\PrivateInvestmentInstrument;

class PrivateInvestmentProjectionService
{
    public function forInstrument(PrivateInvestmentInstrument $instrument, ?float $amount = null): array
    {
        $amount = $amount ?? (float) $instrument->minimum_investment;
        $amount = max(0, $amount);

        $subscriptionFee = $amount * ((float) $instrument->subscription_fee_percent / 100);
        $deployed = max(0, $amount - $subscriptionFee);

        $durationDays = max(1, (int) $instrument->duration_days);
        $intervalDays = max(1, (int) $instrument->return_interval_days);
        $cycles = $durationDays / $intervalDays;

        $cycleMin = $deployed * ((float) $instrument->projected_return_min_percent / 100);
        $cycleMax = $deployed * ((float) $instrument->projected_return_max_percent / 100);

        $grossTermMin = $cycleMin * $cycles;
        $grossTermMax = $cycleMax * $cycles;

        // For this lab product, management fee is presented as a full-term estimate.
        $managementFee = $deployed * ((float) $instrument->management_fee_percent / 100);

        $netTermMin = $grossTermMin - $managementFee - $subscriptionFee;
        $netTermMax = $grossTermMax - $managementFee - $subscriptionFee;

        $price = max(0.000001, (float) $instrument->current_price);
        $estimatedUnits = $deployed / $price;

        return [
            'amount' => $amount,
            'subscription_fee' => $subscriptionFee,
            'deployed_capital' => $deployed,
            'estimated_units' => $estimatedUnits,
            'duration_days' => $durationDays,
            'duration_label' => $this->humanizeDays($durationDays),
            'return_interval_days' => $intervalDays,
            'return_interval_label' => $this->humanizeDays($intervalDays),
            'cycles' => $cycles,
            'cycle_min_profit' => $cycleMin,
            'cycle_max_profit' => $cycleMax,
            'daily_min_profit' => $grossTermMin / $durationDays,
            'daily_max_profit' => $grossTermMax / $durationDays,
            'gross_term_min_profit' => $grossTermMin,
            'gross_term_max_profit' => $grossTermMax,
            'management_fee_estimate' => $managementFee,
            'net_term_min_profit' => $netTermMin,
            'net_term_max_profit' => $netTermMax,
            'minimum_maturity_value' => $amount + $netTermMin,
            'maximum_maturity_value' => $amount + $netTermMax,
            'cycle_return_label' => number_format((float)$instrument->projected_return_min_percent,2).'% – '.number_format((float)$instrument->projected_return_max_percent,2).'%',
        ];
    }

    private function humanizeDays(int $days): string
    {
        if ($days >= 365 && $days % 365 === 0) {
            $years = intdiv($days,365);
            return $years.' '.($years === 1 ? 'year' : 'years');
        }

        if ($days >= 30 && $days % 30 === 0) {
            $months = intdiv($days,30);
            return $months.' '.($months === 1 ? 'month' : 'months');
        }

        if ($days >= 7 && $days % 7 === 0) {
            $weeks = intdiv($days,7);
            return $weeks.' '.($weeks === 1 ? 'week' : 'weeks');
        }

        return $days.' '.($days === 1 ? 'day' : 'days');
    }
}
