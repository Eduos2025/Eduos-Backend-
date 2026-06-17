<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\SubscriptionLog;
use App\Models\PendingRegistration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\User;

class TenantProvisionService
{
    /**
     * Provision a tenant from a pending registration or directly for trial.
     */
    public function provision(
        Plan $plan,
        string $subdomain,
        string $schoolName,
        string $schoolEmail,
        string $ownerName,
        string $ownerEmail,
        string $ownerPassword,
        string $billingInterval,
        bool $isTrial = true,
        ?string $paymentRef = null
    ): Tenant {
        // 1. Calculate dates
        $trialDays = $plan->trial_days ?: 14;
        $startsAt = now();
        $endsAt = $isTrial ? now()->addDays($trialDays) : ($billingInterval === 'yearly' ? now()->addYear() : now()->addMonth());
        $status = $isTrial ? 'trialing' : 'active';

        // 2. Create tenant in central DB
        $tenant = Tenant::create([
            'name' => $schoolName,
            'plan_id' => $plan->id,
            'subscription_status' => $status,
            'expires_at' => $endsAt,
        ]);

        // 3. Attach domain
        // Ensure clean subdomain mapping
        $host = request()->getHost();
        // If host contains port (like localhost:8000), keep it
        $domainName = $subdomain . '.' . $host;
        $tenant->createDomain([
            'domain' => $domainName,
        ]);

        // 4. Create Subscription Record in Central DB
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'trial_starts_at' => $isTrial ? $startsAt : null,
            'trial_ends_at' => $isTrial ? $endsAt : null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        // 5. Create Invoice Record
        $invoiceNumber = 'INV-' . strtoupper(uniqid());
        $amount = $isTrial ? 0.00 : ($billingInterval === 'yearly' ? $plan->yearly_price : $plan->monthly_price);
        
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'status' => $isTrial ? 'paid' : 'unpaid',
            'due_date' => now(),
            'paid_at' => $isTrial ? now() : null,
        ]);

        // 6. Record Log
        SubscriptionLog::log(
            $tenant->id, 
            $isTrial ? 'trial_started' : 'subscription_activated', 
            "School {$schoolName} provisioned successfully with plan {$plan->name}.", 
            ['payment_ref' => $paymentRef]
        );

        event(new \App\Events\SubscriptionLifecycleEvent($tenant, $isTrial ? 'trial_started' : 'school_created'));

        // 7. Initialize tenant database context & create custom owner
        tenancy()->initialize($tenant);

        try {
            // Check if user table exists and create owner
            User::create([
                'name' => $ownerName,
                'email' => $ownerEmail,
                'username' => explode('@', $ownerEmail)[0],
                'password' => Hash::make($ownerPassword),
                'user_type' => 'super_admin',
                'blocked' => 0,
                'religion' => 'Islam',
                'gender' => 'Male',
                'dob' => '01/11/1111',
                'photo' => 'global_assets/images/user.png',
                'phone' => '+234 800 000 0000',
                'address' => 'School Address',
                'state_id' => 1, // Default state placeholder
                'lga_id' => 1,
                'nal_id' => 1,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to create tenant owner account: " . $e->getMessage());
        }

        tenancy()->end();

        return $tenant;
    }
}
