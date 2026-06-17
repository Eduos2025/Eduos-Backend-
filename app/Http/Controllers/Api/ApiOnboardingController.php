<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use App\Models\PendingRegistration;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\SubscriptionPayment;
use App\Services\TenantProvisionService;
use App\Helpers\Qs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class ApiOnboardingController extends ApiBaseController
{
    protected $provisionService;

    public function __construct(TenantProvisionService $provisionService)
    {
        $this->provisionService = $provisionService;
    }

    /**
     * Get list of active subscription plans.
     */
    public function plans(): JsonResponse
    {
        try {
            $plans = Plan::where('active', true)->orderBy('sort_order')->get();
            return $this->sendResponse($plans, 'Plans retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to fetch plans.', ['error' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Live Subdomain availability check.
     */
    public function checkSubdomain(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subdomain' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $subdomain = strtolower($request->query('subdomain'));
        
        if (!preg_match('/^[a-z0-9\-]+$/', $subdomain)) {
            return $this->sendResponse(['available' => false], 'Invalid characters. Use only letters, numbers, and hyphens.');
        }

        $reserved = ['www', 'admin', 'portal', 'api', 'saas', 'billing', 'mail', 'itguy', 'central', 'localhost', '127.0.0.1'];
        if (in_array($subdomain, $reserved)) {
            return $this->sendResponse(['available' => false], 'This subdomain is reserved.');
        }

        $host = request()->getHost();
        $domainToCheck = $subdomain . '.' . $host;

        $exists = DB::table('domains')->where('domain', $domainToCheck)->exists();

        return $this->sendResponse(
            ['available' => !$exists],
            !$exists ? 'Subdomain is available.' : 'Subdomain is already taken.'
        );
    }

    /**
     * Submit onboarding registration.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $subdomain = strtolower($request->subdomain);
        $host = request()->getHost();
        $domainName = $subdomain . '.' . $host;

        // Double check domain availability
        if (DB::table('domains')->where('domain', $domainName)->exists()) {
            return $this->sendError('Subdomain is already taken.', ['subdomain' => ['The chosen subdomain is already taken.']], 422);
        }

        $plan = Plan::where('slug', $request->plan_slug)->first();

        if ($request->payment_method === 'trial') {
            try {
                $tenant = $this->provisionService->provision(
                    $plan,
                    $subdomain,
                    $request->school_name,
                    $request->school_email,
                    $request->owner_name,
                    $request->owner_email,
                    $request->owner_password,
                    $request->billing_interval,
                    true // isTrial = true
                );

                $domain = $tenant->domains()->first();
                $finalDomain = $domain ? $domain->domain : $domainName;

                return $this->sendResponse([
                    'provisioned' => true,
                    'tenant_id' => $tenant->id,
                    'subdomain' => $subdomain,
                    'domain' => $finalDomain,
                    'redirect_url' => 'http://' . $finalDomain . '/login'
                ], 'School trial workspace provisioned successfully.', 201);

            } catch (\Exception $e) {
                return $this->sendError('Workspace provisioning failed.', ['exception' => [$e->getMessage()]], 500);
            }
        } else {
            // Paid onboarding: Create pending registration
            $reference = 'EDUOS-' . strtoupper(uniqid());
            $amount = $request->billing_interval === 'yearly' ? $plan->yearly_price : $plan->monthly_price;

            try {
                $pending = PendingRegistration::create([
                    'reference' => $reference,
                    'plan_id' => $plan->id,
                    'billing_interval' => $request->billing_interval,
                    'subdomain' => $subdomain,
                    'school_name' => $request->school_name,
                    'school_email' => $request->school_email,
                    'owner_name' => $request->owner_name,
                    'owner_email' => $request->owner_email,
                    'owner_password' => $request->owner_password,
                    'amount' => $amount,
                    'payment_method' => $request->payment_method,
                    'status' => 'pending',
                ]);

                $tenant_hash = Qs::hash($pending->id);

                // Set gateway keys
                $publicKey = $request->payment_method === 'national' 
                    ? env('PAYSTACK_PUBLIC_KEY', 'pk_test_6cb9ec87db8c028ba313faea2188ffcf769862df')
                    : env('FLUTTERWAVE_PUBLIC_KEY', 'FLWPUBK_TEST-94ea1c8c56fa90e219ffc6e93e2b2fdf-X');

                return $this->sendResponse([
                    'provisioned' => false,
                    'pending_id' => $pending->id,
                    'tenant_hash' => $tenant_hash,
                    'reference' => $reference,
                    'amount' => $amount,
                    'payment_method' => $request->payment_method,
                    'payment_url' => route('onboard.payment', ['tenant_hash' => $tenant_hash]),
                    'checkout_details' => [
                        'email' => $request->owner_email,
                        'name' => $request->owner_name,
                        'amount' => $amount,
                        'currency' => 'NGN',
                        'reference' => $reference,
                        'public_key' => $publicKey,
                        'gateway' => $request->payment_method === 'national' ? 'paystack' : 'flutterwave'
                    ]
                ], 'Registration pending. Initialize checkout in the mobile app.');

            } catch (\Exception $e) {
                return $this->sendError('Pending registration failed to create.', ['exception' => [$e->getMessage()]], 500);
            }
        }
    }

    /**
     * Verify payment status and provision space.
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'gateway' => 'required|in:paystack,flutterwave',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $reference = $request->reference;
        $gateway = $request->gateway;

        $pending = PendingRegistration::where('reference', $reference)
                                     ->where('status', 'pending')
                                     ->first();

        if (!$pending) {
            return $this->sendError('Pending registration not found or already processed.', [], 404);
        }

        try {
            $tenant = $this->provisionService->provision(
                $pending->plan,
                $pending->subdomain,
                $pending->school_name,
                $pending->school_email,
                $pending->owner_name,
                $pending->owner_email,
                $pending->owner_password,
                $pending->billing_interval,
                false, // isTrial = false (paid)
                $reference
            );

            $pending->update(['status' => 'completed']);

            // Create Payment Record
            $subscription = Subscription::where('tenant_id', $tenant->id)->first();
            $invoice = Invoice::where('tenant_id', $tenant->id)->first();

            if ($invoice) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            SubscriptionPayment::create([
                'subscription_id' => $subscription ? $subscription->id : null,
                'invoice_id' => $invoice ? $invoice->id : null,
                'amount' => $pending->amount,
                'gateway' => $gateway,
                'reference' => $reference,
                'status' => 'successful',
                'meta' => $request->all(),
            ]);

            $domain = $tenant->domains()->first();
            $domainName = $domain ? $domain->domain : ($pending->subdomain . '.' . request()->getHost());

            return $this->sendResponse([
                'provisioned' => true,
                'tenant_id' => $tenant->id,
                'subdomain' => $pending->subdomain,
                'domain' => $domainName,
                'redirect_url' => 'http://' . $domainName . '/login'
            ], 'Workspace provisioned successfully post payment.');

        } catch (\Exception $e) {
            return $this->sendError('Provisioning failed during verification.', ['exception' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Start free trial from payment fallback.
     */
    public function processTrial(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_hash' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        try {
            $pendingId = Qs::decodeHash($request->tenant_hash);
            $pending = PendingRegistration::with('plan')->findOrFail($pendingId);

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

            $pending->delete();

            $domain = $tenant->domains()->first();
            $domainName = $domain ? $domain->domain : ($pending->subdomain . '.' . request()->getHost());

            return $this->sendResponse([
                'provisioned' => true,
                'tenant_id' => $tenant->id,
                'subdomain' => $pending->subdomain,
                'domain' => $domainName,
                'redirect_url' => 'http://' . $domainName . '/login'
            ], 'Workspace converted and provisioned as free trial.');

        } catch (\Exception $e) {
            return $this->sendError('Conversion to trial failed.', ['exception' => [$e->getMessage()]], 500);
        }
    }
}
