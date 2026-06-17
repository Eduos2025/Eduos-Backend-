@extends('layouts.saas_master')

@section('page_title', 'School Management Portal')

@section('content')
<div class="container hero-section">
    <div class="row align-items-center">
        <div class="col-lg-6">
            <h1 class="display-4 font-weight-bold text-white mb-4">Complete Cloud Platform for Modern Schools</h1>
            <p class="lead mb-4">Streamline your academic operations, student records, fees collection, continuous assessments, timetables, and communication. Secure, multi-tenant, and premium experience.</p>
            <div class="d-flex">
                <a href="{{ route('onboard.wizard') }}" class="btn btn-info btn-lg mr-3">Get Started (14-Day Free Trial)</a>
                <a href="{{ route('saas.demo') }}" class="btn btn-outline-light btn-lg">Request Demo</a>
            </div>
        </div>
        <div class="col-lg-6 text-center mt-5 mt-lg-0">
            <div class="card bg-dark border-secondary p-3 shadow-lg">
                <div class="card-body">
                    <span class="material-symbols-rounded text-info-custom symbol-4x mb-3">dashboard</span>
                    <h4 class="text-white">Central Admin Dashboard</h4>
                    <p class="text-muted">Intuitive interfaces built for School Owners, Administrators, Teachers, Parents, and Students.</p>
                    <div class="row text-center mt-4">
                        <div class="col-4">
                            <h3 class="text-info font-weight-bold mb-0">100%</h3>
                            <small class="text-muted">Isolated DBs</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-info font-weight-bold mb-0">2FA</h3>
                            <small class="text-muted">Security</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-info font-weight-bold mb-0">99.9%</h3>
                            <small class="text-muted">Uptime</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="text-center mb-5">
        <h2 class="text-white font-weight-bold">Select a Subscription Plan</h2>
        <p class="text-muted">Find the plan that matches your school size. Start with a 14-day free trial.</p>
    </div>
    
    <div class="row justify-content-center">
        @foreach ($plans as $plan)
            <div class="col-md-4 mb-4">
                <div class="card card-plan h-100 {{ $plan->slug === 'standard' ? 'popular' : '' }}">
                    <div class="card-body text-center p-4">
                        @if ($plan->slug === 'standard')
                            <span class="badge badge-info mb-3">RECOMMENDED</span>
                        @endif
                        <h4 class="text-white font-weight-bold">{{ $plan->name }}</h4>
                        <div class="my-4">
                            <span class="h1 text-white font-weight-bold">&#8358;{{ number_format($plan->monthly_price) }}</span>
                            <span class="text-muted">/ month</span>
                            <br>
                            <span class="text-muted">or &#8358;{{ number_format($plan->yearly_price) }}/year</span>
                        </div>
                        <p class="text-muted mb-4">Includes {{ $plan->trial_days }} days of free trial.</p>
                        
                        <ul class="list-unstyled text-left mb-4 pl-3">
                            <li class="mb-2"><span class="material-symbols-rounded text-success symbol-xs mr-2">check</span> Up to <strong>{{ $plan->max_students }}</strong> students</li>
                            <li class="mb-2"><span class="material-symbols-rounded text-success symbol-xs mr-2">check</span> Up to <strong>{{ $plan->max_staff }}</strong> staff members</li>
                            <li class="mb-2"><span class="material-symbols-rounded text-success symbol-xs mr-2">check</span> Academic grading and results</li>
                            @if ($plan->slug !== 'basic')
                                <li class="mb-2"><span class="material-symbols-rounded text-success symbol-xs mr-2">check</span> Timetable generator</li>
                                <li class="mb-2"><span class="material-symbols-rounded text-success symbol-xs mr-2">check</span> Messaging & collaboration</li>
                            @endif
                            @if ($plan->slug === 'premium')
                                <li class="mb-2"><span class="material-symbols-rounded text-success symbol-xs mr-2">check</span> Fees payment manager</li>
                                <li class="mb-2"><span class="material-symbols-rounded text-success symbol-xs mr-2">check</span> Helpdesk support tickets</li>
                            @endif
                        </ul>

                        <a href="{{ route('onboard.wizard', ['plan' => $plan->slug]) }}" class="btn btn-info btn-block">Choose {{ $plan->name }}</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
