@extends('layouts.saas_master')

@section('page_title', 'School Registration')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Progress Tracker -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-around text-center" id="steps-indicator">
                        <div class="step-indicator active" data-step="1">
                            <span class="badge badge-pill badge-info mr-1">1</span> Choose Plan
                        </div>
                        <div class="step-indicator" data-step="2">
                            <span class="badge badge-pill badge-secondary mr-1">2</span> Subdomain
                        </div>
                        <div class="step-indicator" data-step="3">
                            <span class="badge badge-pill badge-secondary mr-1">3</span> School Info
                        </div>
                        <div class="step-indicator" data-step="4">
                            <span class="badge badge-pill badge-secondary mr-1">4</span> Owner Details
                        </div>
                        <div class="step-indicator" data-step="5">
                            <span class="badge badge-pill badge-secondary mr-1">5</span> Checkout
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wizard Form -->
            <form id="onboardForm" action="{{ route('onboard.register') }}" method="POST" novalidate>
                @csrf
                
                <!-- STEP 1: SELECT PLAN -->
                <div class="setup-step" id="step-1">
                    <div class="card bg-dark border-secondary">
                        <div class="card-header border-secondary bg-transparent text-center">
                            <h3 class="card-title text-white font-weight-bold">Step 1: Choose Your Subscription Plan</h3>
                            <p class="text-muted mb-0">Select the package that fits your institution's size.</p>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($plans as $plan)
                                    <div class="col-md-4">
                                        <div class="card text-center p-3 mb-3 border-secondary bg-transparent plan-selector-card {{ $selectedPlan === $plan->slug || (!$selectedPlan && $plan->slug === 'standard') ? 'border-info' : '' }}" style="cursor: pointer;" onclick="selectPlan('{{ $plan->slug }}', this)">
                                            <h4 class="text-white font-weight-bold mb-2">{{ $plan->name }}</h4>
                                            <h2 class="text-info font-weight-bold mb-2">&#8358;{{ number_format($plan->monthly_price) }}<small class="text-muted">/mo</small></h2>
                                            <p class="text-muted small">Max Students: {{ $plan->max_students }}</p>
                                            <p class="text-muted small">Max Staff: {{ $plan->max_staff }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <input type="hidden" name="plan_slug" id="plan_slug" value="{{ $selectedPlan ?: 'standard' }}">
                            
                            <div class="text-right mt-4">
                                <button type="button" class="btn btn-info px-4" onclick="nextStep(2)">Next Step <span class="material-symbols-rounded align-middle ml-1">arrow_forward</span></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: CHECK SUBDOMAIN -->
                <div class="setup-step d-none" id="step-2">
                    <div class="card bg-dark border-secondary">
                        <div class="card-header border-secondary bg-transparent text-center">
                            <h3 class="card-title text-white font-weight-bold">Step 2: Choose Portal Subdomain</h3>
                            <p class="text-muted mb-0">Define the unique web address where your users will access the portal.</p>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="text-white">Subdomain Prefix</label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-right" id="subdomain_input" name="subdomain" required placeholder="e.g. greenwood" style="text-transform: lowercase;">
                                    <div class="input-group-append">
                                        <span class="input-group-text bg-secondary text-white">.{{ request()->getHost() }}</span>
                                    </div>
                                </div>
                                <div class="mt-2" id="subdomain_feedback"></div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(1)"><span class="material-symbols-rounded align-middle mr-1">arrow_back</span> Back</button>
                                <button type="button" class="btn btn-info px-4" id="btn-subdomain-next" onclick="validateSubdomainStep()" disabled>Next Step <span class="material-symbols-rounded align-middle ml-1">arrow_forward</span></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: SCHOOL INFO -->
                <div class="setup-step d-none" id="step-3">
                    <div class="card bg-dark border-secondary">
                        <div class="card-header border-secondary bg-transparent text-center">
                            <h3 class="card-title text-white font-weight-bold">Step 3: School Information</h3>
                            <p class="text-muted mb-0">Enter the primary organization contact credentials.</p>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="text-white">Official School Name</label>
                                <input type="text" class="form-control" name="school_name" required placeholder="Greenwood Academy">
                            </div>
                            <div class="form-group">
                                <label class="text-white">School Email Address</label>
                                <input type="email" class="form-control" name="school_email" required placeholder="info@greenwood.com">
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(2)"><span class="material-symbols-rounded align-middle mr-1">arrow_back</span> Back</button>
                                <button type="button" class="btn btn-info px-4" onclick="nextStep(4)">Next Step <span class="material-symbols-rounded align-middle ml-1">arrow_forward</span></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: OWNER DETAILS -->
                <div class="setup-step d-none" id="step-4">
                    <div class="card bg-dark border-secondary">
                        <div class="card-header border-secondary bg-transparent text-center">
                            <h3 class="card-title text-white font-weight-bold">Step 4: School Administrator Account</h3>
                            <p class="text-muted mb-0">Create the primary owner login credentials to access the school portal.</p>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="text-white">Full Name</label>
                                <input type="text" class="form-control" name="owner_name" required placeholder="Principal Adams">
                            </div>
                            <div class="form-group">
                                <label class="text-white">Personal / Official Email Address</label>
                                <input type="email" class="form-control" name="owner_email" required placeholder="adams@greenwood.com">
                            </div>
                            <div class="form-group">
                                <label class="text-white">Password</label>
                                <input type="password" class="form-control" name="owner_password" required placeholder="Min. 8 characters">
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(3)"><span class="material-symbols-rounded align-middle mr-1">arrow_back</span> Back</button>
                                <button type="button" class="btn btn-info px-4" onclick="nextStep(5)">Next Step <span class="material-symbols-rounded align-middle ml-1">arrow_forward</span></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: CHECKOUT -->
                <div class="setup-step d-none" id="step-5">
                    <div class="card bg-dark border-secondary">
                        <div class="card-header border-secondary bg-transparent text-center">
                            <h3 class="card-title text-white font-weight-bold">Step 5: Select Billing Option & Checkout</h3>
                            <p class="text-muted mb-0">Review details and choose billing frequency.</p>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6 form-group">
                                    <label class="text-white">Billing Cycle</label>
                                    <select class="form-control" name="billing_interval">
                                        <option value="monthly">Monthly Cycle</option>
                                        <option value="yearly">Yearly Cycle (Save up to 15%)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="text-white">Payment Option</label>
                                    <select class="form-control" name="payment_method" id="payment_method_select">
                                        <option value="trial">14-Day Free Trial (No Card Required)</option>
                                        <option value="national">National (Nigerian Cards, Bank, USSD via Paystack)</option>
                                        <option value="international">International (Global USD Card via Flutterwave)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="alert alert-info border-info alert-styled-left">
                                <span class="font-weight-semibold">Selected Trial Strategy:</span> If you select the 14-day free trial, your school subdomain portal will be provisioned immediately. You can upgrade your plan or complete payments anytime from your tenant settings panel.
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(4)"><span class="material-symbols-rounded align-middle mr-1">arrow_back</span> Back</button>
                                <button type="submit" class="btn btn-success btn-lg px-4"><span class="material-symbols-rounded align-middle mr-1">check_circle</span> Complete Onboarding</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.step-indicator').on('click', function() {
            var step = parseInt($(this).data('step'));
            
            // Subdomain check fallback alert
            if (step > 2 && $('#btn-subdomain-next').attr('disabled')) {
                alert('Please enter and verify an available subdomain prefix in Step 2 first.');
                return;
            }
            
            nextStep(step);
        });

        // Form submission handler
        $('#onboardForm').on('submit', function(e) {
            // Validate all steps from 1 to 4 before allowing form submission
            for (var i = 1; i <= 4; i++) {
                if (!validateStepFields(i)) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // Disable submit button to prevent double-submits and show spinner
            var submitBtn = $(this).find('button[type="submit"]');
            submitBtn.attr('disabled', true).html('<span class="material-symbols-rounded spinner align-middle mr-1">sync</span> Processing Onboarding...');
            return true;
        });
    });

    function selectPlan(slug, element) {
        $('.plan-selector-card').removeClass('border-info');
        $(element).addClass('border-info');
        $('#plan_slug').val(slug);
    }

    function validateStepFields(stepNum) {
        if (stepNum === 1) {
            return true; // Plan selection is always pre-selected
        }
        
        if (stepNum === 2) {
            if ($('#btn-subdomain-next').attr('disabled')) {
                alert('Please enter and verify an available subdomain prefix in Step 2 first.');
                return false;
            }
            var input = $('#subdomain_input')[0];
            if (!input.checkValidity()) {
                input.reportValidity();
                input.focus();
                return false;
            }
            return true;
        }
        
        var isValid = true;
        var firstInvalidInput = null;
        
        $('#step-' + stepNum + ' [required]').each(function() {
            if (!this.checkValidity()) {
                isValid = false;
                $(this).addClass('is-invalid');
                if (!firstInvalidInput) {
                    firstInvalidInput = this;
                }
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            // Switch view back to the step with validation errors
            $('.setup-step').addClass('d-none');
            $('#step-' + stepNum).removeClass('d-none');
            updateProgressIndicators(stepNum);
            
            if (firstInvalidInput) {
                firstInvalidInput.reportValidity();
                firstInvalidInput.focus();
            }
            return false;
        }
        
        return true;
    }

    function nextStep(stepNum) {
        // Validate preceding steps before going to stepNum
        var currentStepNum = parseInt($('.setup-step:not(.d-none)').attr('id').replace('step-', ''));
        
        // If moving forward, validate all steps from current up to target - 1
        if (stepNum > currentStepNum) {
            for (var i = currentStepNum; i < stepNum; i++) {
                if (!validateStepFields(i)) {
                    return; // stop navigation
                }
            }
        }
        
        $('.setup-step').addClass('d-none');
        $('#step-' + stepNum).removeClass('d-none');
        updateProgressIndicators(stepNum);
    }

    function prevStep(stepNum) {
        $('.setup-step').addClass('d-none');
        $('#step-' + stepNum).removeClass('d-none');
        updateProgressIndicators(stepNum);
    }

    function updateProgressIndicators(stepNum) {
        $('.step-indicator').removeClass('active').addClass('text-muted');
        $('.step-indicator').each(function() {
            var step = $(this).data('step');
            if (step === stepNum) {
                $(this).addClass('active').removeClass('text-muted');
                $(this).find('.badge').removeClass('badge-secondary').addClass('badge-info');
            } else if (step < stepNum) {
                $(this).find('.badge').removeClass('badge-secondary').addClass('badge-success');
            } else {
                $(this).find('.badge').removeClass('badge-info badge-success').addClass('badge-secondary');
            }
        });
    }

    // Live Subdomain Availability Lookup
    var lookupTimer;
    $('#subdomain_input').on('keyup input', function() {
        clearTimeout(lookupTimer);
        var subdomain = $(this).val().trim().toLowerCase();
        
        if (subdomain.length === 0) {
            $('#subdomain_feedback').html('');
            $('#btn-subdomain-next').attr('disabled', true);
            return;
        }

        $('#subdomain_feedback').html('<span class="text-info"><i class="material-symbols-rounded spinner symbol-xs align-middle mr-1">sync</i> Verifying subdomain availability...</span>');
        
        lookupTimer = setTimeout(function() {
            $.ajax({
                url: "{{ route('api.check_subdomain') }}",
                data: { subdomain: subdomain },
                success: function(response) {
                    if (response.available) {
                        $('#subdomain_feedback').html('<span class="text-success"><i class="material-symbols-rounded symbol-xs align-middle mr-1">check_circle</i> Subdomain is available!</span>');
                        $('#btn-subdomain-next').removeAttr('disabled');
                    } else {
                        $('#subdomain_feedback').html('<span class="text-danger"><i class="material-symbols-rounded symbol-xs align-middle mr-1">cancel</i> ' + (response.message || 'Subdomain is already taken.') + '</span>');
                        $('#btn-subdomain-next').attr('disabled', true);
                    }
                },
                error: function() {
                    $('#subdomain_feedback').html('<span class="text-warning">Failed to verify. Please try again.</span>');
                }
            });
        }, 600);
    });

    function validateSubdomainStep() {
        nextStep(3);
    }
</script>

<style>
    .step-indicator {
        font-size: 1.1rem;
        font-weight: 600;
        transition: all 0.3s;
        cursor: pointer;
    }
    .step-indicator.active {
        color: #00bcd4;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .spinner {
        display: inline-block;
        animation: spin 1.5s linear infinite;
    }
</style>
@endsection
