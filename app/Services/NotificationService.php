<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Signal;
use App\Models\SignalDistribution;
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
                'action_url' => route('stocks.index', [], false),
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
                'action_url' => route('account.investments', [], false),
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
                'action_url' => route('money.activity', [], false),
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
                'action_url' => route('money.activity', [], false),
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
                'action_url' => route('profile.kyc', [], false),
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
                'action_url' => route('account.investments', [], false),
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
                'action_url' => route('stocks.index', [], false),
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
                'action_url' => route('account.investments.portfolio', [], false),
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
                'action_url' => route('account.investments.performance', [], false),
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

    /**
     * Create a Signal delivery notification.
     */
    public static function createSignalNotification(User $user, Signal $signal, string $reason = 'membership', array $metadata = [])
    {
        $signal->loadMissing(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'targets']);
        $symbol = strtoupper((string) ($signal->instrument_symbol ?? 'Market'));
        $direction = strtoupper((string) $signal->direction);

        return $user->notifications()->create([
            'type' => 'signal',
            'title' => "New {$symbol} {$direction} Signal",
            'message' => "A {$symbol} {$direction} Signal is available. Entry "
                . number_format((float) $signal->entry_min, 2)
                . ' - '
                . number_format((float) $signal->entry_max, 2)
                . ' | SL '
                . number_format((float) $signal->stop_loss, 2),
            'data' => array_merge([
                'signal_id' => $signal->id,
                'action_url' => \Illuminate\Support\Facades\Route::has('signals.show')
                    ? route('signals.show', $signal->id, false)
                    : null,
                'symbol' => $symbol,
                'direction' => $signal->direction,
                'timeframe' => $signal->timeframe,
                'entry_min' => (float) $signal->entry_min,
                'entry_max' => (float) $signal->entry_max,
                'stop_loss' => (float) $signal->stop_loss,
                'targets' => $signal->targets->sortBy('sequence')->map(fn ($target) => [
                    'sequence' => (int) $target->sequence,
                    'price' => (float) $target->price,
                ])->values()->all(),
                'delivery_reason' => $reason,
            ], $metadata),
        ]);
    }

    /**
     * Create an operational receipt for an administrator after a Signal distribution batch.
     */
    public static function createSignalAdminDistributionReceipt(
        User $admin,
        Signal $signal,
        SignalDistribution $distribution,
        string $audienceScope
    ) {
        $signal->loadMissing(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair']);
        $symbol = strtoupper((string) ($signal->instrument_symbol ?? 'Signal'));
        $mode = ucfirst((string) $distribution->mode);
        $scope = ucfirst($audienceScope);

        return $admin->notifications()->create([
            'type' => 'signal_admin',
            'title' => "{$symbol} Signal distribution complete",
            'message' => "{$mode} · {$scope} — {$distribution->delivered_count} delivered, {$distribution->skipped_count} skipped, {$distribution->failed_count} failed.",
            'data' => [
                'signal_id' => $signal->id,
                'distribution_id' => $distribution->id,
                'symbol' => $symbol,
                'direction' => $signal->direction,
                'mode' => $distribution->mode,
                'audience_scope' => $audienceScope,
                'delivered_count' => (int) $distribution->delivered_count,
                'skipped_count' => (int) $distribution->skipped_count,
                'failed_count' => (int) $distribution->failed_count,
            ],
        ]);
    }


    /** Operational notifications for administrators. Failures never block the originating business action. */
    public static function notifyAdmins(string $type, string $title, string $message, array $data = []): void
    {
        try {
            User::query()->where('is_admin', true)->get()->each(function (User $admin) use ($type, $title, $message, $data) {
                $admin->notifications()->create(['type'=>$type,'title'=>$title,'message'=>$message,'data'=>$data]);
            });
        } catch (\Throwable $e) {
            \Log::warning('Admin notification delivery failed.', ['type'=>$type,'error'=>$e->getMessage()]);
        }
    }

    public static function createKYCAdminSubmissionNotification(User $customer, \App\Models\KYC $kyc): void
    {
        self::notifyAdmins('kyc_admin','New KYC awaiting review',$customer->name.' submitted identity verification for review.',[
            'kyc_id'=>$kyc->id,'customer_user_id'=>$customer->id,'action_url'=>route('admin.kyc.show',$kyc,false),
        ]);
    }

    public static function createCommunicationNotification(User $recipient, \App\Models\CommunicationConversation $conversation, User $sender, string $kind = 'message'): void
    {
        try {
            $support = $conversation->type === 'support_ticket';
            $recipient->notifications()->create([
                'type'=>$support?'support':'message',
                'title'=>$support?'Support update: '.$conversation->subject:'New message from '.$sender->name,
                'message'=>$support?$sender->name.' added an update to '.$conversation->ticket_number.'.':$sender->name.' sent you a private message.',
                'data'=>[
                    'conversation_id'=>$conversation->id,'sender_user_id'=>$sender->id,'kind'=>$kind,
                    'action_url'=>$recipient->isAdmin()?route($support?'admin.support.show':'admin.messages.show',$conversation,false):route($support?'support.show':'messages.show',$conversation,false),
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Communication notification delivery failed.', ['conversation_id'=>$conversation->id,'recipient_user_id'=>$recipient->id,'error'=>$e->getMessage()]);
        }
    }

}
