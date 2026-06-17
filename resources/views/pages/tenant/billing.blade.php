@extends('layouts.master')

@section('page_title', 'School Subscription & Billing')

@section('content')
<div class="container-fluid">
    @if ($tenant->subscription_status === 'suspended')
        <div class="alert alert-danger border-danger alert-styled-left alert-dismissible">
            <span class="font-weight-semibold">Subscription Suspended:</span> Your school access is currently suspended. Please contact platform operators or upgrade below to restore access.
        </div>
    @elseif (\Carbon\Carbon::parse($tenant->expires_at)->isPast())
        <div class="alert alert-warning border-warning alert-styled-left alert-dismissible">
            <span class="font-weight-semibold">Subscription Expired:</span> Your school subscription or free trial expired on {{ \Carbon\Carbon::parse($tenant->expires_at)->format('d M, Y') }}. Please renew to prevent downtime.
        </div>
    @endif

    <div class="row">
        <!-- Current Subscription Summary -->
        <div class="col-md-4">
            <div class="card card-body border-top-info text-center">
                <span class="material-symbols-rounded symbol-3x text-info mb-2">workspace_premium</span>
                <h6 class="font-weight-semibold text-uppercase mb-1">Current Package</h6>
                <h3 class="font-weight-bold text-white mb-2">{{ $tenant->plan ? $tenant->plan->name : 'No Active Plan' }}</h3>
                <div class="mb-3">
                    <span class="badge badge-pill {{ in_array($tenant->subscription_status, ['active', 'trialing']) ? 'badge-success' : 'badge-danger' }}">{{ strtoupper($tenant->subscription_status) }}</span>
                </div>
                <p class="text-muted">Expires / Renews on: <br><strong>{{ $tenant->expires_at ? \Carbon\Carbon::parse($tenant->expires_at)->format('d M, Y (H:i A)') : 'N/A' }}</strong></p>
                
                @if ($tenant->expires_at && \Carbon\Carbon::parse($tenant->expires_at)->isFuture())
                    <div class="h5 text-success font-weight-bold mt-2">
                        {{ \Carbon\Carbon::parse($tenant->expires_at)->diffInDays() }} Days Remaining
                    </div>
                @endif
            </div>
        </div>

        <!-- Plans & Renewals -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h5 class="card-title">Choose / Upgrade Packages</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach ($plans as $plan)
                            <div class="col-md-4">
                                <div class="card bg-transparent border-secondary text-center p-3 {{ $tenant->plan_id == $plan->id ? 'border-success' : '' }}">
                                    <h6 class="font-weight-bold text-white mb-2">{{ $plan->name }}</h6>
                                    <h3 class="text-info font-weight-bold mb-3">&#8358;{{ number_format($plan->monthly_price) }}<small class="text-muted">/mo</small></h3>
                                    
                                    <ul class="list-unstyled text-left small mb-3 pl-2">
                                        <li>Students Limit: {{ $plan->max_students }}</li>
                                        <li>Staff Limit: {{ $plan->max_staff }}</li>
                                    </ul>

                                    @if ($tenant->plan_id == $plan->id)
                                        <button class="btn btn-success btn-block btn-sm" disabled>Current Active Plan</button>
                                    @else
                                        <button class="btn btn-info btn-block btn-sm" onclick="initiateUpgrade({{ $plan->id }}, '{{ $plan->name }}', {{ $plan->monthly_price }})">Upgrade Plan</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice and Billing History -->
    <div class="card mt-4">
        <div class="card-header header-elements-inline">
            <h5 class="card-title">Billing & Invoice History</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Paid At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $inv)
                        <tr>
                            <td>{{ $inv->invoice_number }}</td>
                            <td>&#8358;{{ number_format($inv->amount, 2) }}</td>
                            <td>
                                <span class="badge {{ $inv->status === 'paid' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($inv->status) }}</span>
                            </td>
                            <td>{{ $inv->created_at->format('d M, Y') }}</td>
                            <td>{{ $inv->paid_at ? $inv->paid_at->format('d M, Y') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No billing history found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal checkout options -->
<div id="paymentModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary text-center">
                <h5 class="modal-title text-white">Upgrade Confirmation</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted">You are about to purchase the <strong id="modal-plan-name" class="text-white"></strong>.</p>
                <div class="my-3 p-2 bg-secondary rounded">
                    <h3 class="font-weight-bold text-white mb-0">&#8358;<span id="modal-plan-price"></span></h3>
                </div>

                <div class="form-group text-left">
                    <label class="text-white small">Billing Cycle</label>
                    <select class="form-control" id="modal-cycle">
                        <option value="monthly">Monthly Cycle</option>
                        <option value="yearly">Yearly Cycle</option>
                    </select>
                </div>

                <button type="button" class="btn btn-success btn-block mt-3" id="btn-modal-pay" onclick="triggerCheckout()">
                    Proceed to Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Verification script tools -->
<script src="https://js.paystack.co/v1/inline.js"></script>

<script>
    var selectedPlanId, selectedPlanName, selectedPlanPrice;

    function initiateUpgrade(planId, name, price) {
        selectedPlanId = planId;
        selectedPlanName = name;
        selectedPlanPrice = price;

        $('#modal-plan-name').text(name);
        $('#modal-plan-price').text(price.toLocaleString());
        $('#paymentModal').modal('show');
    }

    function triggerCheckout() {
        var cycle = $('#modal-cycle').val();
        $('#btn-modal-pay').attr('disabled', true).text('Initializing Payment...');

        $.ajax({
            url: "{{ route('tenant.billing.pay') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                plan_id: selectedPlanId,
                billing_interval: cycle
            },
            success: function(response) {
                if (response.success) {
                    // Start Paystack Checkout
                    var handler = PaystackPop.setup({
                        key: "{{ env('PAYSTACK_PUBLIC_KEY', 'pk_test_6cb9ec87db8c028ba313faea2188ffcf769862df') }}",
                        email: response.email,
                        amount: response.amount * 100,
                        currency: "NGN",
                        ref: response.reference,
                        callback: function(res) {
                            // Verify payment via webhook callback simulation
                            verifyTenantPayment(response.reference, response.invoice_id);
                        },
                        onClose: function() {
                            $('#btn-modal-pay').removeAttr('disabled').text('Proceed to Payment');
                            alert('Checkout cancelled.');
                        }
                    });
                    handler.openIframe();
                } else {
                    alert('Failed to initialize upgrade details.');
                    window.location.reload();
                }
            },
            error: function() {
                alert('Connection error occurred.');
                window.location.reload();
            }
        });
    }

    function verifyTenantPayment(reference, invoiceId) {
        $('#btn-modal-pay').text('Applying Upgrade...');

        $.ajax({
            url: "{{ route('webhook.paystack') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                event: "charge.success",
                data: {
                    reference: reference,
                    gateway_used: 'paystack',
                    tenant_id: "{{ $tenant->id }}"
                }
            },
            success: function() {
                alert('Upgrade completed successfully! The page will refresh now.');
                window.location.reload();
            },
            error: function() {
                alert('Reconciliation pending. Please refresh the page in a moment.');
                window.location.reload();
            }
        });
    }
</script>
@endsection
