<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MembershipEntitlement;
use App\Models\MembershipPlan;
use App\Models\MembershipType;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MembershipController extends Controller
{
    public function index()
    {
        $types = MembershipType::query()
            ->withCount([
                'plans',
                'plans as active_plans_count' => fn ($query) => $query->where('is_active', true),
                'memberships',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $stats = [
            'types' => MembershipType::query()->count(),
            'active_types' => MembershipType::query()->active()->count(),
            'active_memberships' => Membership::query()->activeAt()->count(),
            'pending_memberships' => Membership::query()->where('status', 'pending')->count(),
        ];

        return view('admin.memberships.index', compact('types', 'stats'));
    }

    public function storeType(Request $request)
    {
        MembershipType::query()->create($this->typeData($request));

        return back()->with('success', 'Membership type created.');
    }

    public function updateType(Request $request, MembershipType $type)
    {
        $type->update($this->typeData($request, $type));

        return back()->with('success', 'Membership type updated.');
    }

    public function toggleType(MembershipType $type)
    {
        $type->update(['is_active' => ! $type->is_active]);

        return back()->with('success', 'Membership type availability updated.');
    }

    public function destroyType(MembershipType $type)
    {
        if ($type->plans()->exists()) {
            return back()->withErrors(['type' => 'Membership types with plans cannot be deleted. Deactivate the type instead.']);
        }

        $type->delete();

        return back()->with('success', 'Unused membership type deleted.');
    }

    public function show(MembershipType $type)
    {
        $plans = $type->plans()
            ->with(['entitlements' => fn ($query) => $query->orderBy('id')])
            ->withCount('memberships')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $stats = [
            'plans' => $type->plans()->count(),
            'active_plans' => $type->plans()->where('is_active', true)->count(),
            'active_memberships' => $type->memberships()->activeAt()->count(),
            'pending_memberships' => $type->memberships()->where('status', 'pending')->count(),
        ];

        return view('admin.memberships.show', compact('type', 'plans', 'stats'));
    }

    public function storePlan(Request $request, MembershipType $type)
    {
        $type->plans()->create($this->planData($request));

        return back()->with('success', $type->name.' plan created.');
    }

    public function updatePlan(Request $request, MembershipType $type, MembershipPlan $plan)
    {
        $this->assertPlanType($type, $plan);
        $plan->update($this->planData($request, $plan));

        return back()->with('success', 'Membership plan updated.');
    }

    public function togglePlan(MembershipType $type, MembershipPlan $plan)
    {
        $this->assertPlanType($type, $plan);
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('success', 'Membership plan availability updated.');
    }

    public function destroyPlan(MembershipType $type, MembershipPlan $plan)
    {
        $this->assertPlanType($type, $plan);

        if ($plan->memberships()->exists()) {
            return back()->withErrors(['plan' => 'Plans with membership history cannot be deleted. Deactivate the plan instead.']);
        }

        $plan->delete();

        return back()->with('success', 'Unused membership plan deleted.');
    }

    public function storeEntitlement(Request $request, MembershipType $type, MembershipPlan $plan)
    {
        $this->assertPlanType($type, $plan);
        $plan->entitlements()->create($this->entitlementData($request, $plan));

        return back()->with('success', 'Membership entitlement added.');
    }

    public function updateEntitlement(
        Request $request,
        MembershipType $type,
        MembershipPlan $plan,
        MembershipEntitlement $entitlement
    ) {
        $this->assertPlanType($type, $plan);
        abort_unless($entitlement->membership_plan_id === $plan->id, 404);
        $entitlement->update($this->entitlementData($request, $plan, $entitlement));

        return back()->with('success', 'Membership entitlement updated.');
    }

    public function destroyEntitlement(
        MembershipType $type,
        MembershipPlan $plan,
        MembershipEntitlement $entitlement
    ) {
        $this->assertPlanType($type, $plan);
        abort_unless($entitlement->membership_plan_id === $plan->id, 404);
        $entitlement->delete();

        return back()->with('success', 'Membership entitlement removed.');
    }

    public function registry(Request $request, MembershipType $type)
    {
        $query = $type->memberships()
            ->with(['user', 'plan.type', 'activatedBy'])
            ->latest('memberships.id');

        if ($request->filled('status')) {
            $query->where('memberships.status', $request->string('status')->toString());
        }

        if ($request->filled('plan')) {
            $query->where('membership_plan_id', $request->integer('plan'));
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $memberships = $query->paginate(30)->withQueryString();
        $plans = $type->plans()->orderBy('sort_order')->orderBy('name')->get();
        $users = User::query()->where('is_admin', false)->orderBy('name')->get(['id', 'name', 'email']);

        $stats = [
            'active' => $type->memberships()->activeAt()->count(),
            'pending' => $type->memberships()->where('memberships.status', 'pending')->count(),
            'cancelled' => $type->memberships()->where('memberships.status', 'cancelled')->count(),
            'expired' => $type->memberships()->where(function ($query) {
                $query->where('memberships.status', 'expired')
                    ->orWhere(function ($q) {
                        $q->where('memberships.status', 'active')
                            ->whereNotNull('ends_at')
                            ->where('ends_at', '<=', now());
                    });
            })->count(),
        ];

        return view('admin.memberships.registry', compact('type', 'memberships', 'plans', 'users', 'stats'));
    }

    public function storeMembership(Request $request, MembershipType $type, MembershipService $service)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'membership_plan_id' => ['required', 'exists:membership_plans,id'],
            'price_paid' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'activate_now' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['starts_at']) && ! empty($data['ends_at']) && strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
            throw ValidationException::withMessages(['ends_at' => 'Membership end must be after the start time.']);
        }

        $user = User::query()->where('is_admin', false)->findOrFail($data['user_id']);
        $plan = MembershipPlan::query()->with('type')->findOrFail($data['membership_plan_id']);
        $this->assertPlanType($type, $plan);

        try {
            $service->create($user, $plan, [
                'price_paid' => $data['price_paid'] ?? null,
                'currency' => $data['currency'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'activate_now' => $request->boolean('activate_now'),
                'source' => 'admin',
            ], Auth::user());
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['membership' => $e->getMessage()]);
        }

        return back()->with('success', $request->boolean('activate_now')
            ? $type->name.' membership created and activated.'
            : $type->name.' membership created as pending.');
    }

    public function activateMembership(MembershipType $type, Membership $membership, MembershipService $service)
    {
        $this->assertMembershipType($type, $membership);

        try {
            $service->activate($membership, Auth::user());
        } catch (\Throwable $e) {
            return back()->withErrors(['membership' => $e->getMessage()]);
        }

        return back()->with('success', 'Membership activated.');
    }

    public function cancelMembership(MembershipType $type, Membership $membership, MembershipService $service)
    {
        $this->assertMembershipType($type, $membership);

        try {
            $service->cancel($membership, Auth::user());
        } catch (\Throwable $e) {
            return back()->withErrors(['membership' => $e->getMessage()]);
        }

        return back()->with('success', 'Membership cancelled.');
    }

    public function expireMembership(MembershipType $type, Membership $membership, MembershipService $service)
    {
        $this->assertMembershipType($type, $membership);

        try {
            $service->expire($membership, Auth::user());
        } catch (\Throwable $e) {
            return back()->withErrors(['membership' => $e->getMessage()]);
        }

        return back()->with('success', 'Membership expired.');
    }

    private function typeData(Request $request, ?MembershipType $type = null): array
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name', '')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('membership_types', 'slug')->ignore($type?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'icon' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['icon'] = $data['icon'] ?: 'badge-check';
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function planData(Request $request, ?MembershipPlan $plan = null): array
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name', '')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('membership_plans', 'slug')->ignore($plan?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_interval' => ['required', Rule::in(['monthly', 'quarterly', 'yearly', 'lifetime', 'custom'])],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['currency'] = strtoupper($data['currency']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function entitlementData(
        Request $request,
        MembershipPlan $plan,
        ?MembershipEntitlement $entitlement = null
    ): array {
        $data = $request->validate([
            'key' => [
                'required',
                'string',
                'max:120',
                Rule::unique('membership_entitlements', 'key')
                    ->where(fn ($query) => $query->where('membership_plan_id', $plan->id))
                    ->ignore($entitlement?->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'value_json' => ['nullable', 'string'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $value = null;
        if (filled($data['value_json'] ?? null)) {
            $value = json_decode($data['value_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ValidationException::withMessages(['value_json' => 'Value must be valid JSON.']);
            }
        }

        return [
            'key' => $data['key'],
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'value' => $value,
            'enabled' => $request->boolean('enabled'),
        ];
    }

    private function assertPlanType(MembershipType $type, MembershipPlan $plan): void
    {
        abort_unless($plan->membership_type_id === $type->id, 404);
    }

    private function assertMembershipType(MembershipType $type, Membership $membership): void
    {
        $membership->loadMissing('plan');
        abort_unless($membership->plan?->membership_type_id === $type->id, 404);
    }
}
