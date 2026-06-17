<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $settings = Qs::getSettings();
        $colors = $settings->where('type', 'login_and_related_pgs_txts_and_bg_colors')->value('description');
        if ($colors !== null) {
            $colors_exploaded = explode(Qs::getDelimiter(), $colors);
            $texts_color = $colors_exploaded[0];
            $bg_color = $colors_exploaded[1];
        } else {
            $texts_color = 'white';
            $bg_color = 'rgb(35 39 53)';
        }
    @endphp

    <title>{{ Qs::getStringAbbreviation(config('app.name')) }} &#183; @yield('page_title')</title>

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />
    
    {{-- Limitless theme CSS --}}
    <link href="{{ asset('assets/css/bootstrap-min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/bootstrap_limitless-min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/layout-min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/components-min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/colors-min.css') }}" rel="stylesheet" type="text/css">

    <!-- Core JS files -->
    <script src="{{ asset('global_assets/js/main/jquery.min.js') }} "></script>
    <script src="{{ asset('global_assets/js/main/bootstrap.bundle.min.js') }} "></script>
    <script src="{{ asset('assets/js/app-min.js') }} "></script>
    <script src="{{ asset('global_assets/js/plugins/forms/styling/uniform.min.js') }} "></script>
    <script src="{{ asset('global_assets/js/main/color-modes.js') }}"></script>

    <style>
        html, body {
            color-scheme: dark;
            background-color: {{ $bg_color }} !important;
            font-family: 'Nunito Sans', sans-serif;
            color: #d1d5db;
        }
        .navbar-saas {
            background-color: rgba(35, 39, 53, 0.95) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }
        .text-info-custom {
            color: #00bcd4 !important;
        }
        .text-color-custom {
            color: {{ $texts_color }} !important;
        }
        .hero-section {
            padding: 80px 0 60px;
        }
        .footer-saas {
            background-color: #1a1d26;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding: 40px 0 20px;
            margin-top: 80px;
        }
        .card-plan {
            background-color: #2a2e3d;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-plan:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        .card-plan.popular {
            border: 2px solid #00bcd4;
        }
    </style>
</head>

<body>
    <!-- SaaS Navbar -->
    <nav class="navbar navbar-expand-md navbar-saas sticky-top">
        <div class="container">
            <a href="{{ route('saas.landing') }}" class="navbar-brand d-flex align-items-center">
                <span class="material-symbols-rounded text-info-custom mr-2">school</span>
                <span class="h4 font-weight-bold mb-0 text-color-custom">{{ $settings->where('type', 'system_name')->value('description') ?: 'Eduos' }}</span>
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#saasNavbar">
                <span class="material-symbols-rounded">menu</span>
            </button>

            <div class="collapse navbar-collapse" id="saasNavbar">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a href="{{ route('saas.landing') }}" class="nav-link {{ request()->routeIs('saas.landing') ? 'text-info-custom' : '' }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('saas.features') }}" class="nav-link {{ request()->routeIs('saas.features') ? 'text-info-custom' : '' }}">Features</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('saas.pricing') }}" class="nav-link {{ request()->routeIs('saas.pricing') ? 'text-info-custom' : '' }}">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('saas.contact') }}" class="nav-link {{ request()->routeIs('saas.contact') ? 'text-info-custom' : '' }}">Contact</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center">
                    <a href="{{ route('login') }}" class="btn btn-link text-white mr-3">Sign In</a>
                    <a href="{{ route('onboard.wizard') }}" class="btn bg-info-custom text-white">Start Free Trial</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @if (session('pop_success'))
            <div class="container mt-3">
                <div class="alert alert-success alert-styled-left alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span>{{ session('pop_success') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="container mt-3">
                <div class="alert alert-danger alert-styled-left alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span>{!! implode('<br>', $errors->all()) !!}</span>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- SaaS Footer -->
    <footer class="footer-saas">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="d-flex align-items-center mb-3">
                        <span class="material-symbols-rounded text-info-custom mr-2">school</span>
                        <h5 class="font-weight-bold mb-0 text-white">{{ $settings->where('type', 'system_name')->value('description') ?: 'Eduos' }}</h5>
                    </div>
                    <p class="text-muted">A comprehensive, secure, and multi-tenant solution to manage academic operations, schedules, grading, payments, and collaboration.</p>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h6 class="text-white font-weight-bold mb-3">Product</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('saas.features') }}" class="text-muted">Features</a></li>
                        <li><a href="{{ route('saas.pricing') }}" class="text-muted">Pricing</a></li>
                        <li><a href="{{ route('saas.demo') }}" class="text-muted">Request Demo</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6 class="text-white font-weight-bold mb-3">Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('saas.contact') }}" class="text-muted">Contact Us</a></li>
                        <li><a href="{{ route('privacy_policy') }}" class="text-muted">Privacy Policy</a></li>
                        <li><a href="{{ route('terms_of_use') }}" class="text-muted">Terms of Use</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="text-center text-muted">
                <p class="mb-0">&copy; {{ date('Y') }} {{ $settings->where('type', 'system_name')->value('description') ?: 'Eduos' }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof $.fn.uniform === 'function') {
                $('.form-input-styled').uniform();
            }
        });
    </script>
</body>

</html>
