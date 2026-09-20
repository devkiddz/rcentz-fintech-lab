<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Models\MembershipTransaction;
use App\Models\MembershipType;
use App\Services\MembershipAccessService;
use App\Services\MembershipPurchaseService;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function index(Request $request, MembershipAccessService $membershipAccess)
    {
        $user = $request->user();

        $activeMemberships = $membershipAccess->activeMemberships($user);
        $membershipStatuses = $membershipAccess->statusMemberships($user);
        $currentMembership = $activeMemberships->first() ?? $membershipStatuses->first();

        if ($currentMembership) {
            $currentMembership->loadMissing(['plan.type', 'plan.entitlements']);
        }

        $privileges = collect();

        if ($currentMembership?->is_active && $currentMembership->plan) {
            $privileges = $currentMembership->plan->entitlements
                ->where('enabled', true)
                ->values();
        }

        $availableTypes = MembershipType::query()
            ->active()
            ->with([
                'plans' => fn ($query) => $query
                    ->active()
                    ->with(['entitlements' => fn ($entitlements) => $entitlements
                        ->where('enabled', true)
                        ->orderBy('id')])
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->reject(fn (MembershipType $type) => (bool) data_get($type->metadata, 'qa_fixture', false))
            ->map(function (MembershipType $type) {
                $type->setRelation(
                    'plans',
                    $type->plans
                        ->reject(fn (MembershipPlan $plan) => (bool) data_get($plan->metadata, 'qa_fixture', false))
                        ->values()
                );

                return $type;
            })
            ->filter(fn (MembershipType $type) => $type->plans->isNotEmpty())
            ->values();

        $recentPurchases = MembershipTransaction::query()
            ->with(['plan.type', 'membership'])
            ->where('user_id', $user->id)
            ->latest('processed_at')
            ->latest('id')
            ->limit(8)
            ->get();

        $walletAvailable = (float) ($user->wallet?->available_balance ?? 0);

        return view('memberships.index', compact(
            'currentMembership',
            'privileges',
            'availableTypes',
            'activeMemberships',
            'recentPurchases',
            'walletAvailable'
        ));
    }

    public function purchase(
        Request $request,
        MembershipType $type,
        MembershipPlan $plan,
        MembershipPurchaseService $purchases
    ) {
        abort_unless(
            (int) $plan->membership_type_id === (int) $type->id
            && $type->is_active
            && $plan->is_active
            && ! data_get($type->metadata, 'qa_fixture', false)
            && ! data_get($plan->metadata, 'qa_fixture', false),
            404
        );

        $data = $request->validate([
            'idempotency_key' => 'required|string|max:120',
        ]);

        try {
            $transaction = $purchases->purchase(
                $request->user(),
                $plan,
                $data['idempotency_key'],
                $request->user()
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['membership' => $e->getMessage()]);
        }

        return back()->with(
            'success',
            $transaction->membership?->is_active
                ? $type->name.' membership activated successfully.'
                : 'Membership payment completed.'
        );
    }
}
