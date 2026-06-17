@extends('layouts.saas_master')

@section('page_title', 'Onboarding Complete')

@section('content')
<div class="container my-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card bg-dark border-secondary p-5">
                <div class="card-body">
                    <span class="material-symbols-rounded text-success symbol-4x mb-4">check_circle</span>
                    
                    <h2 class="text-white font-weight-bold mb-3">Congratulations!</h2>
                    <h4 class="text-info-custom mb-4">{{ $tenant->name }} is Ready</h4>
                    
                    <p class="text-muted mb-4">Your dedicated secure school database has been configured. You can now access your management portal at your custom subdomain.</p>
                    
                    @php
                        $host = request()->getHost();
                        $tenantDomain = $tenant->domain ? $tenant->domain->domain : null;
                        // Build full absolute URL
                        $protocol = request()->secure() ? 'https://' : 'http://';
                        
                        // Check if port is in host (like localhost:8000)
                        $port = request()->getPort();
                        $redirectUrl = $protocol . $tenantDomain;
                        if ($port && !in_array($port, [80, 443]) && !str_contains($tenantDomain, ':')) {
                            // If port is active and not default, append it
                            $redirectUrl .= ':' . $port;
                        }
                    @endphp

                    <div class="p-3 bg-secondary rounded mb-4">
                        <a href="{{ $redirectUrl }}" class="h5 text-info font-weight-bold text-break">{{ $redirectUrl }}</a>
                    </div>

                    <a href="{{ $redirectUrl }}" class="btn btn-info btn-lg btn-block mb-3">
                        Go to School Portal <span class="material-symbols-rounded align-middle ml-1">arrow_forward</span>
                    </a>

                    <p class="text-muted small">Redirecting you to your portal dashboard in <span id="countdown">8</span> seconds...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var sec = 8;
    var timer = setInterval(function() {
        sec--;
        document.getElementById('countdown').innerHTML = sec;
        if (sec <= 0) {
            clearInterval(timer);
            window.location.href = "{{ $redirectUrl }}";
        }
    }, 1000);
</script>
@endsection
