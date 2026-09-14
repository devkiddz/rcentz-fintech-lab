<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Create a stock update notification
     */
    public static function createStockUpdateNotification(User $user, $stockSymbol, $percentageChange)
    {
        $isPositive = $percentageChange >= 0;
        $title = $stockSymbol . ' stock ' . ($isPositive ? 'up' : 'down') . ' ' . abs($percentageChange) . '%';
        $message = 'Your ' . $stockSymbol . ' holdings ' . ($isPositive ? 'increased' : 'decreased') . ' in value';

        return $user->notifications()->create([
            'type' => 'stock_update',
            'title' => $title,
            'message' => $message,
            'data' => [
                'symbol' => $stockSymbol,
                'percentage_change' => $percentageChange,
            ],
        ]);
    }

    /**
     * Create an investment success notification
     */
    public static function createInvestmentSuccessNotification(User $user, $planName, $amount)
    {
        return $user->notifications()->create([
            'type' => 'investment_success',
            'title' => 'Investment successful',
            'message' => 'Your ' . $planName . ' purchase was completed for $' . number_format($amount, 2),
            'data' => [
                'plan_name' => $planName,
                'amount' => $amount,
            ],
        ]);
    }

    /**
     * Create a wallet update notification
     */
    public static function createWalletUpdateNotification(User $user, $type, $amount)
    {
        $title = ucfirst($type) . ' successful';
        $message = 'Your wallet has been ' . $type . 'ed $' . number_format($amount, 2);

        return $user->notifications()->create([
            'type' => 'wallet_update',
            'title' => $title,
            'message' => $message,
            'data' => [
                'type' => $type,
                'amount' => $amount,
            ],
        ]);
    }

    /**
     * Create a wallet deposit pending notification (e.g., after user submits crypto tx hash)
     */
    public static function createWalletDepositPendingNotification(User $user, $amount, string $paymentMethodName, ?string $referenceId = null)
    {
        return $user->notifications()->create([
            'type' => 'wallet_update',
            'title' => 'Deposit submitted - pending approval',
            'message' => 'We received your crypto deposit of $' . number_format($amount, 2) . ' via ' . $paymentMethodName . '. It will be credited after admin approval.',
            'data' => [
                'type' => 'deposit_pending',
                'amount' => $amount,
                'payment_method' => $paymentMethodName,
                'reference_id' => $referenceId,
            ],
        ]);
    }

    /**
     * Create a KYC status notification
     */
    public static function createKYCStatusNotification(User $user, $status, $reason = null)
    {
        $titles = [
            'approved' => 'KYC Verification Approved',
            'rejected' => 'KYC Verification Rejected',
            'pending' => 'KYC Verification Submitted',
        ];

        $messages = [
            'approved' => 'Your identity has been verified successfully.',
            'rejected' => 'Your KYC verification was rejected. ' . ($reason ? 'Reason: ' . $reason : ''),
            'pending' => 'Your KYC verification has been submitted and is under review.',
        ];

        return $user->notifications()->create([
            'type' => 'kyc_status',
            'title' => $titles[$status] ?? 'KYC Status Update',
            'message' => $messages[$status] ?? 'Your KYC status has been updated.',
            'data' => [
                'status' => $status,
                'reason' => $reason,
            ],
        ]);
    }

    /**
     * Create an automatic investment notification
     */
    public static function createAutomaticInvestmentNotification(User $user, $planName, $amount)
    {
        return $user->notifications()->create([
            'type' => 'automatic_investment',
            'title' => 'Automatic Investment Executed',
            'message' => 'Your automatic investment in ' . $planName . ' was executed for $' . number_format($amount, 2),
            'data' => [
                'plan_name' => $planName,
                'amount' => $amount,
            ],
        ]);
    }

    /**
     * Create a price alert notification
     */
    public static function createPriceAlertNotification(User $user, $symbol, $currentPrice, $alertPrice)
    {
        return $user->notifications()->create([
            'type' => 'price_alert',
            'title' => 'Price Alert: ' . $symbol,
            'message' => $symbol . ' has reached your alert price of $' . number_format($alertPrice, 2) . ' (Current: $' . number_format($currentPrice, 2) . ')',
            'data' => [
                'symbol' => $symbol,
                'current_price' => $currentPrice,
                'alert_price' => $alertPrice,
            ],
        ]);
    }

    /**
     * Create an investment update notification
     */
    public static function createInvestmentUpdateNotification(User $user, $planName, $percentageChange)
    {
        $isPositive = $percentageChange >= 0;
        $title = $planName . ' investment ' . ($isPositive ? 'up' : 'down') . ' ' . number_format(abs($percentageChange), 2) . '%';
        $message = 'Your ' . $planName . ' investment value has ' . ($isPositive ? 'increased' : 'decreased') . ' by ' . number_format(abs($percentageChange), 2) . '%';

        return $user->notifications()->create([
            'type' => 'investment_update',
            'title' => $title,
            'message' => $message,
            'data' => [
                'plan_name' => $planName,
                'percentage_change' => $percentageChange,
            ],
        ]);
    }

    /**
     * Create a weekly portfolio summary notification
     */
    public static function createWeeklyPortfolioSummary(User $user, $totalInvested, $totalCurrentValue, $totalGainLoss, $totalGainLossPercentage)
    {
        $isPositive = $totalGainLoss >= 0;
        $title = 'Weekly Portfolio Summary';
        $message = 'Your investment portfolio is ' . ($isPositive ? 'up' : 'down') . ' ' . number_format(abs($totalGainLossPercentage), 2) . '% this week. Total value: $' . number_format($totalCurrentValue, 2);

        return $user->notifications()->create([
            'type' => 'portfolio_summary',
            'title' => $title,
            'message' => $message,
            'data' => [
                'total_invested' => $totalInvested,
                'total_current_value' => $totalCurrentValue,
                'total_gain_loss' => $totalGainLoss,
                'total_gain_loss_percentage' => $totalGainLossPercentage,
            ],
        ]);
    }

    /**
     * Create a system notification
     */
    public static function createSystemNotification(User $user, $title, $message, $data = [])
    {
        return $user->notifications()->create([
            'type' => 'system',
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
