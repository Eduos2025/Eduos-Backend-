<?php

namespace App\Http\Controllers;

use App\Models\PendingRegistration;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionLog;
use App\Services\TenantProvisionService;
use App\Helpers\Qs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected $provisionService;

    public function __construct(TenantProvisionService $provisionService)
    {
        $this->provisionService = $provisionService;
    }

    /**
     * Handle Paystack Callback Verification and Webhooks
     */
    public function paystackWebhook(Request $request)
    {
        Log::info('Paystack Webhook received', $request->all());

        // 1. Check if it's the AJAX verification call from the payment page
        if ($request->input('event') === 'charge.success' && $request->has('data.reference')) {
            $reference = $request->input('data.reference');
            $gateway = $request->input('data.gateway_used', 'paystack');

            $pending = PendingRegistration::where('reference', $reference)
                                         ->where('status', 'pending')
                                         ->first();

            if ($pending) {
                // Provision Tenant
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

                    return response()->json([
                        'success' => true,
                        'redirect_url' => route('onboard.success', ['tenant_hash' => Qs::hash($tenant->id)])
                    ]);

                } catch (\Exception $e) {
                    Log::error('Provisioning failed in callback: ' . $e->getMessage());
                    return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
                }
            } else if ($request->has('data.tenant_id')) {
                $tenantId = $request->input('data.tenant_id');
                $tenant = Tenant::find($tenantId);

                if ($tenant) {
                    $invoice = Invoice::where('tenant_id', $tenantId)
                                      ->where('status', 'unpaid')
                                      ->orderBy('created_at', 'desc')
                                      ->first();

                    if ($invoice) {
                        $invoice->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);

                        $subscription = Subscription::where('tenant_id', $tenantId)->orderBy('created_at', 'desc')->first();

                        if ($subscription) {
                            $newEndsAt = \Carbon\Carbon::parse($subscription->ends_at)->isPast() 
                                ? now()->addMonth() 
                                : \Carbon\Carbon::parse($subscription->ends_at)->addMonth();

                            $subscription->update([
                                'status' => 'active',
                                'ends_at' => $newEndsAt,
                            ]);

                            $tenant->update([
                                'subscription_status' => 'active',
                                'expires_at' => $newEndsAt,
                            ]);

                            SubscriptionPayment::create([
                                'subscription_id' => $subscription->id,
                                'invoice_id' => $invoice->id,
                                'amount' => $invoice->amount,
                                'gateway' => $gateway,
                                'reference' => $reference,
                                'status' => 'successful',
                                'meta' => $request->all(),
                            ]);

                            SubscriptionLog::log($tenantId, 'subscription_renewed', "Subscription renewed for {$tenant->name}.", ['reference' => $reference]);

                            event(new \App\Events\SubscriptionLifecycleEvent($tenant, 'subscription_renewed'));
                        }
                    }

                    return response()->json(['success' => true]);
                }
            }
        }

        // 2. Process real-time signature-verified webhooks (optional fallback for back-channel checks)
        // In a real environment, verify signature signature: $request->header('x-paystack-signature')
        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Flutterwave Webhook
     */
    public function flutterwaveWebhook(Request $request)
    {
        Log::info('Flutterwave Webhook received', $request->all());

        // Flutterwave handles hooks via txRef/tx_ref
        $txRef = $request->input('txRef') ?: $request->input('data.tx_ref');

        if ($txRef) {
            $pending = PendingRegistration::where('reference', $txRef)
                                         ->where('status', 'pending')
                                         ->first();

            if ($pending) {
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
                        false,
                        $txRef
                    );

                    $pending->update(['status' => 'completed']);

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
                        'gateway' => 'flutterwave',
                        'reference' => $txRef,
                        'status' => 'successful',
                        'meta' => $request->all(),
                    ]);

                    return response()->json([
                        'success' => true,
                        'redirect_url' => route('onboard.success', ['tenant_hash' => Qs::hash($tenant->id)])
                    ]);
                } catch (\Exception $e) {
                    Log::error('Flutterwave provisioning failed: ' . $e->getMessage());
                    return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
