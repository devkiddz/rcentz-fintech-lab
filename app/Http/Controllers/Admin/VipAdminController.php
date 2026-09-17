<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VipEntitlement;
use App\Models\VipMembership;
use App\Models\VipPlan;
use App\Services\VipMembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VipAdminController extends Controller
{
    public function index()
    {
        $plans = VipPlan::query()
            ->with(['entitlements' => fn ($query) => $query->orderBy('id')])
            ->withCount('memberships')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $stats = [
            'plans' => VipPlan::query()->count(),
            'active_plans' => VipPlan::query()->where('is_active', true)->count(),
            'active_memberships' => VipMembership::query()->activeAt()->count(),
            'pending_memberships' => VipMembership::query()->where('status', 'pending')->count(),
        ];

        return view('admin.vip.index', compact('plans', 'stats'));
    }

    public function storePlan(Request $request)
    {
        $data = $this->planData($request);
        VipPlan::query()->create($data);

        return back()->with('success', 'VIP plan created.');
    }

    public function updatePlan(Request $request, VipPlan $plan)
    {
        $plan->update($this->planData($request, $plan));

        return back()->with('success', 'VIP plan updated.');
    }

    public function togglePlan(VipPlan $plan)
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('success', 'VIP plan availability updated.');
    }

    public function destroyPlan(VipPlan $plan)
    {
        if ($plan->memberships()->exists()) {
            return back()->withErrors(['plan' => 'Plans with membership history cannot be deleted. Deactivate the plan instead.']);
        }

        $plan->delete();

        return back()->with('success', 'Unused VIP plan deleted.');
    }

    public function storeEntitlement(Request $request, VipPlan $plan)
    {
        $plan->entitlements()->create($this->entitlementData($request, $plan));

        return back()->with('success', 'VIP entitlement added.');
    }

    public function updateEntitlement(Request $request, VipPlan $plan, VipEntitlement $entitlement)
    {
        abort_unless($entitlement->vip_plan_id === $plan->id, 404);
        $entitlement->update($this->entitlementData($request, $plan, $entitlement));

        return back()->with('success', 'VIP entitlement updated.');
    }

    public function destroyEntitlement(VipPlan $plan, VipEntitlement $entitlement)
    {
        abort_unless($entitlement->vip_plan_id === $plan->id, 404);
        $entitlement->delete();

        return back()->with('success', 'VIP entitlement removed.');
    }

    public function memberships(Request $request)
    {
        $query = VipMembership::query()->with(['user', 'plan', 'activatedBy'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('plan')) {
            $query->where('vip_plan_id', $request->integer('plan'));
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
        $plans = VipPlan::query()->orderBy('sort_order')->orderBy('name')->get();
        $users = User::query()->where('is_admin', false)->orderBy('name')->get(['id', 'name', 'email']);

        $stats = [
            'active' => VipMembership::query()->activeAt()->count(),
            'pending' => VipMembership::query()->where('status', 'pending')->count(),
            'cancelled' => VipMembership::query()->where('status', 'cancelled')->count(),
            'expired' => VipMembership::query()->where(function ($query) {
                $query->where('status', 'expired')
                    ->orWhere(function ($q) {
                        $q->where('status', 'active')->whereNotNull('ends_at')->where('ends_at', '<=', now());
                    });
            })->count(),
        ];

        return view('admin.vip.memberships', compact('memberships', 'plans', 'users', 'stats'));
    }

    public function storeMembership(Request $request, VipMembershipService $service)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'vip_plan_id' => ['required', 'exists:vip_plans,id'],
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
        $plan = VipPlan::query()->findOrFail($data['vip_plan_id']);

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
            ? 'VIP membership created and activated.'
            : 'VIP membership created as pending.');
    }

    public function activateMembership(VipMembership $membership, VipMembershipService $service)
    {
        try {
            $service->activate($membership, Auth::user());
        } catch (\Throwable $e) {
            return back()->withErrors(['membership' => $e->getMessage()]);
        }

        return back()->with('success', 'VIP membership activated.');
    }

    public function cancelMembership(VipMembership $membership, VipMembershipService $service)
    {
        try {
            $service->cancel($membership, Auth::user());
        } catch (\Throwable $e) {
            return back()->withErrors(['membership' => $e->getMessage()]);
        }

        return back()->with('success', 'VIP membership cancelled.');
    }

    public function expireMembership(VipMembership $membership, VipMembershipService $service)
    {
        try {
            $service->expire($membership, Auth::user());
        } catch (\Throwable $e) {
            return back()->withErrors(['membership' => $e->getMessage()]);
        }

        return back()->with('success', 'VIP membership expired.');
    }

    private function planData(Request $request, ?VipPlan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('vip_plans', 'slug')->ignore($plan?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_interval' => ['required', Rule::in(['monthly', 'quarterly', 'yearly', 'lifetime', 'custom'])],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($data['slug'] ?: $data['name']);

        if ($slug === '') {
            throw ValidationException::withMessages(['slug' => 'A valid plan slug is required.']);
        }

        $conflict = VipPlan::query()->where('slug', $slug)
            ->when($plan, fn ($query) => $query->where('id', '!=', $plan->id))
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages(['slug' => 'This VIP plan slug is already in use.']);
        }

        return [
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'currency' => strtoupper($data['currency']),
            'billing_interval' => $data['billing_interval'],
            'duration_days' => $data['billing_interval'] === 'lifetime' ? null : ($data['duration_days'] ?? null),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    private function entitlementData(Request $request, VipPlan $plan, ?VipEntitlement $entitlement = null): array
    {
        $unique = Rule::unique('vip_entitlements', 'key')
            ->where(fn ($query) => $query->where('vip_plan_id', $plan->id))
            ->ignore($entitlement?->id);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9._-]+$/', $unique],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'value_json' => ['nullable', 'string', 'max:10000'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        return [
            'key' => strtolower(trim($data['key'])),
            'label' => trim($data['label']),
            'description' => $data['description'] ?? null,
            'value' => $this->decodeValue($data['value_json'] ?? null),
            'enabled' => $request->boolean('enabled'),
        ];
    }

    private function decodeValue(?string $value)
    {
        if ($value === null || trim($value) === '') return null;

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'value_json' => 'Entitlement value must be valid JSON, for example {"limit":10}.',
            ]);
        }

        return $decoded;
    }
}
