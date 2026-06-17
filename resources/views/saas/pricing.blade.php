@extends('layouts.saas_master')

@section('page_title', 'Pricing & Plans')

@section('content')
<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="text-white font-weight-bold">Simple, Transparent Pricing</h1>
        <p class="lead text-muted">No hidden fees. Every plan includes a 14-day free trial. Choose the best tier for your school size.</p>
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
