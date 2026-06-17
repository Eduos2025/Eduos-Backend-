<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant) {
            // Check if user is trying to access billing or profile update or logout or 2fa verification
            $allowedRoutes = [
                'tenant.billing',
                'my_account',
                'my_account.update',
                'my_account.change_pass',
                'logout',
                '2fa.show',
                '2fa.authenticate',
                'verification.notice',
                'verification.verify',
                'verification.send',
            ];

            $currentRouteName = $request->route() ? $request->route()->getName() : null;

            if (in_array($currentRouteName, $allowedRoutes)) {
                return $next($request);
            }

            // Expiry/Suspension Checks
            $status = $tenant->subscription_status;
            $expiresAt = $tenant->expires_at ? \Carbon\Carbon::parse($tenant->expires_at) : null;

            if ($status === 'suspended') {
                return redirect()->route('tenant.billing')->with('flash_error', 'Your school subscription has been suspended by the administrator.');
            }

            if ($status === 'expired' || ($expiresAt && $expiresAt->isPast())) {
                return redirect()->route('tenant.billing')->with('flash_error', 'Your school subscription / trial has expired. Please upgrade or make a payment to continue.');
            }
        }

        return $next($request);
    }
}
