<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\PendingRegistration;
use App\Services\TenantProvisionService;
use App\Helpers\Qs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    protected $provisionService;

    public function __construct(TenantProvisionService $provisionService)
    {
        $this->provisionService = $provisionService;
    }

    public function checkSubdomain(Request $request)
    {
        $subdomain = strtolower($request->query('subdomain'));
        
        if (!preg_match('/^[a-z0-9\-]+$/', $subdomain)) {
            return response()->json(['available' => false, 'message' => 'Invalid characters. Use only letters, numbers, and hyphens.']);
        }

        // Exclude system names
        $reserved = ['www', 'admin', 'portal', 'api', 'saas', 'billing', 'mail', 'itguy', 'central', 'localhost', '127.0.0.1'];
        if (in_array($subdomain, $reserved)) {
            return response()->json(['available' => false, 'message' => 'This subdomain is reserved.']);
        }

        $host = request()->getHost();
        // Suffix mapping
        $domainToCheck = $subdomain . '.' . $host;

        $exists = DB::table('domains')->where('domain', $domainToCheck)->exists();

        return response()->json(['available' => !$exists]);
    }

    public function wizard(Request $request)
    {
        $plans = Plan::where('active', true)->orderBy('sort_order')->get();
        $selectedPlan = $request->query('plan');

        return view('saas.onboard_wizard', compact('plans', 'selectedPlan'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'plan_slug' => 'required|exists:plans,slug',
            'subdomain' => 'required|string|max:100',
            'school_name' => 'required|string|max:255',
            'school_email' => 'required|email|max:255',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|max:255',
            'owner_password' => 'required|string|min:8',
            'billing_interval' => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:trial,national,international',
        ]);

        $subdomain = strtolower($request->subdomain);
        $host = request()->getHost();
        $domainName = $subdomain . '.' . $host;

        // Double check domain availability
        if (DB::table('domains')->where('domain', $domainName)->exists()) {
            return back()->withErrors(['subdomain' => 'The chosen subdomain is already taken.'])->withInput();
        }

        $plan = Plan::where('slug', $request->plan_slug)->firstOrFail();

        if ($request->payment_method === 'trial') {
            // Provision 14-day trial immediately
            $tenant = $this->provisionService->provision(
                $plan,
                $subdomain,
                $request->school_name,
                $request->school_email,
                $request->owner_name,
                $request->owner_email,
                $request->owner_password,
                $request->billing_interval,
                true // isTrial
            );

            // Redirect to success page with hashed tenant ID
            $hashedId = Qs::hash($tenant->id);
            return redirect()->route('onboard.success', ['tenant_hash' => $hashedId])
                             ->with('pop_success', 'School tenant provisioned successfully!');
        } else {
            // Paid onboarding: Create pending registration
            $reference = 'EDUOS-' . strtoupper(uniqid());
            $amount = $request->billing_interval === 'yearly' ? $plan->yearly_price : $plan->monthly_price;

            $pending = PendingRegistration::create([
                'reference' => $reference,
                'plan_id' => $plan->id,
                'billing_interval' => $request->billing_interval,
                'subdomain' => $subdomain,
                'school_name' => $request->school_name,
                'school_email' => $request->school_email,
                'owner_name' => $request->owner_name,
                'owner_email' => $request->owner_email,
                'owner_password' => $request->owner_password, // we will hash this inside provisioner, save plain/hashed here
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
            ]);

            return redirect()->route('onboard.payment', ['tenant_hash' => Qs::hash($pending->id)]);
        }
    }

    public function paymentPage($tenant_hash)
    {
        $pendingId = Qs::decodeHash($tenant_hash);
        $pending = PendingRegistration::with('plan')->findOrFail($pendingId);

        return view('saas.payment', compact('pending', 'tenant_hash'));
    }

    public function processTrial($tenant_hash)
    {
        $pendingId = Qs::decodeHash($tenant_hash);
        $pending = PendingRegistration::with('plan')->findOrFail($pendingId);

        // Provision trial using the details from pending registration
        $tenant = $this->provisionService->provision(
            $pending->plan,
            $pending->subdomain,
            $pending->school_name,
            $pending->school_email,
            $pending->owner_name,
            $pending->owner_email,
            $pending->owner_password,
            $pending->billing_interval,
            true // isTrial = true
        );

        // Delete pending registration after successful conversion to trial
        $pending->delete();

        // Redirect to success page with hashed tenant ID
        $hashedId = Qs::hash($tenant->id);
        return redirect()->route('onboard.success', ['tenant_hash' => $hashedId])
                         ->with('pop_success', 'School trial plan started successfully!');
    }

    public function successPage($tenant_hash)
    {
        $tenantId = Qs::decodeHash($tenant_hash);
        $tenant = Tenant::with('domain')->findOrFail($tenantId);

        return view('saas.success', compact('tenant'));
    }
}
