<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\PendingRegistration;
use App\Helpers\Qs;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index()
    {
        $tenant = tenant();
        $plans = Plan::where('active', true)->orderBy('sort_order')->get();
        
        // Load invoices, payments and logs from central DB using Tenant ID
        $invoices = Invoice::where('tenant_id', $tenant->id)->orderBy('created_at', 'desc')->get();
        $subscription = Subscription::where('tenant_id', $tenant->id)->orderBy('created_at', 'desc')->first();

        return view('pages.tenant.billing', compact('tenant', 'plans', 'invoices', 'subscription'));
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_interval' => 'required|in:monthly,yearly',
        ]);

        $tenant = tenant();
        $plan = Plan::findOrFail($request->plan_id);
        $amount = $request->billing_interval === 'yearly' ? $plan->yearly_price : $plan->monthly_price;
        $reference = 'REN-' . strtoupper(uniqid());

        // Create a temporary record or pass it directly to billing inline javascript
        // We can create an unpaid invoice first
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();
        
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription ? $subscription->id : null,
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'amount' => $amount,
            'status' => 'unpaid',
            'due_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'reference' => $reference,
            'amount' => $amount,
            'invoice_id' => $invoice->id,
            'email' => auth()->user()->email,
            'name' => auth()->user()->name,
        ]);
    }
}
