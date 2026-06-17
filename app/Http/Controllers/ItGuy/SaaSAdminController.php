<?php

namespace App\Http\Controllers\ItGuy;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Helpers\Qs;
use Illuminate\Http\Request;

class SaaSAdminController extends Controller
{
    public function subscriptionsIndex()
    {
        $tenants = Tenant::with(['plan', 'domain'])->get();
        $plans = Plan::all();
        $logs = SubscriptionLog::orderBy('created_at', 'desc')->take(50)->get();

        return view('pages.it_guy.subscriptions', compact('tenants', 'plans', 'logs'));
    }

    public function suspendTenant($tenant_id)
    {
        $tenantId = Qs::decodeHash($tenant_id);
        $tenant = Tenant::findOrFail($tenantId);

        $tenant->update(['subscription_status' => 'suspended']);
        SubscriptionLog::log($tenant->id, 'tenant_suspended', "Tenant {$tenant->name} has been suspended by IT Guy admin.");

        return back()->with('pop_success', 'School tenant subscription has been suspended successfully.');
    }

    public function activateTenant($tenant_id)
    {
        $tenantId = Qs::decodeHash($tenant_id);
        $tenant = Tenant::findOrFail($tenantId);

        // Retrieve active subscription or set active
        $tenant->update([
            'subscription_status' => 'active',
            'expires_at' => $tenant->expires_at ? $tenant->expires_at : now()->addMonth(),
        ]);
        SubscriptionLog::log($tenant->id, 'tenant_activated', "Tenant {$tenant->name} has been activated by IT Guy admin.");

        return back()->with('pop_success', 'School tenant subscription has been activated successfully.');
    }

    public function upgradePlan(Request $request, $tenant_id)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenantId = Qs::decodeHash($tenant_id);
        $tenant = Tenant::findOrFail($tenantId);
        $plan = Plan::findOrFail($request->plan_id);

        $tenant->update(['plan_id' => $plan->id]);

        // Update active subscription record too
        $sub = Subscription::where('tenant_id', $tenant->id)->orderBy('created_at', 'desc')->first();
        if ($sub) {
            $sub->update(['plan_id' => $plan->id]);
        }

        SubscriptionLog::log($tenant->id, 'plan_upgraded', "Tenant {$tenant->name} upgraded to plan {$plan->name}.");

        return back()->with('pop_success', "Tenant plan updated successfully to {$plan->name}.");
    }

    public function extendSubscription(Request $request, $tenant_id)
    {
        $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $tenantId = Qs::decodeHash($tenant_id);
        $tenant = Tenant::findOrFail($tenantId);

        $currentExpiry = $tenant->expires_at ? \Carbon\Carbon::parse($tenant->expires_at) : now();
        $newExpiry = $currentExpiry->addDays($request->days);

        $tenant->update(['expires_at' => $newExpiry]);

        $sub = Subscription::where('tenant_id', $tenant->id)->orderBy('created_at', 'desc')->first();
        if ($sub) {
            $sub->update(['ends_at' => $newExpiry]);
        }

        SubscriptionLog::log($tenant->id, 'trial_extended', "Subscription/trial for {$tenant->name} extended by {$request->days} days.");

        return back()->with('pop_success', "Subscription extended successfully to {$newExpiry->format('d M, Y')}.");
    }
}
