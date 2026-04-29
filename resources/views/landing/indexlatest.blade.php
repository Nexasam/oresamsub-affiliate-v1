<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/logo_imgs/favicon/android-chrome-192x192.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <title>{{ $site_title }} - {{ session('affiliate')->name }}</title>

    @php
        $hero1 = isset($hero_image1)
            ? env('APP_URL').'assets/landing_page_assets/img/hero_image1/'.$hero_image1
            : env('APP_URL').'assets/landing_page_assets/img/bg_banner1.jpg';
        $hero2 = isset($hero_image2)
            ? env('APP_URL').'assets/landing_page_assets/img/hero_image2/'.$hero_image2
            : env('APP_URL').'assets/landing_page_assets/img/bg_banner2.jpg';
        $logo = isset($site_logo)
            ? env('APP_URL').'assets/landing_page_assets/img/site_logo/'.$site_logo
            : null;
    @endphp

    <style>
        :root {
            --brand: {{ $site_primary_color ?? '#5a66f2' }};
            --brand-hover: {{ $site_landing_page_hover_color ?? '#4c56d3' }};
            --dark: #0f172a;
            --light: #f8fafc;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light);
            color: #1e293b;
            scroll-behavior: smooth;
        }

        .navbar {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.7);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .btn-brand {
            background: var(--brand);
            color: #fff;
            border-radius: 30px;
            padding: 0.6rem 1.5rem;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-brand:hover {
            background: var(--brand-hover);
            transform: translateY(-2px);
        }

        .hero {
            position: relative;
            min-height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url({{ $hero1 }}) center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
        }

        .hero p {
            font-family: 'Nunito', sans-serif;
            font-size: 1.1rem;
        }

        .section-title {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }

        .service {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .service:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        }

        footer {
            background: var(--brand);
            color: #fff;
            text-align: center;
            padding: 3rem 0;
        }

        .float {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background-color: #25d366;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            text-align: center;
            font-size: 30px;
            z-index: 999;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.2);
        }

        .my-float {
            margin-top: 16px;
        }

        [data-aos] {
            transition: transform 0.6s ease, opacity 0.6s ease;
        }
    </style>
</head>

<body>
    <!-- WhatsApp Floating -->
    <a href="https://api.whatsapp.com/send?phone={{ $support_whatsapp_number }}&text=Hello, I need help on your website" target="_blank" class="float">
        <i class="fa fa-whatsapp my-float"></i>
    </a>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            @if ($logo)
                <img src="{{ $logo }}" alt="Logo" class="me-3" style="height: 60px;">
            @else
                <a class="navbar-brand fw-bold" href="#">{{ $site_logo_alt ?? 'OurBrand' }}</a>
            @endif
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse text-center" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    <li><a href="#home" class="nav-link">Home</a></li>
                    <li><a href="#about" class="nav-link">About</a></li>
                    <li><a href="#services" class="nav-link">Services</a></li>
                    <li><a href="#reviews" class="nav-link">Testimonials</a></li>
                    <li><a href="{{ url('/register') }}" class="btn btn-brand">Sign Up</a></li>
                    <li><a href="{{ url('/login') }}" class="btn btn-outline-dark rounded-pill px-3">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero" id="home">
        <div data-aos="fade-up">
            <h6 class="text-uppercase mb-3">{{ $sub_hero1 }}</h6>
            <h1>{{ $hero1_part1 }} <br> {{ $hero1_part2 }}</h1>
            <p class="my-3">{{ $service_intro ?? 'Simple, fast, and reliable digital solutions built for you.' }}</p>
            <a href="{{ url('/register') }}" class="btn btn-brand">Get Started</a>
            <a href="{{ url('/login') }}" class="btn btn-outline-light ms-2 rounded-pill px-4">Login</a>
        </div>
    </section>

    <!-- ABOUT -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center gy-4">
                <div class="col-lg-6" data-aos="fade-right">
                    <img src="{{ isset($aboutus_image)
                        ? asset(env('APP_ASSETS_BASE_URL').'landing_page_assets/img/aboutus_image/'.$aboutus_image)
                        : asset(env('APP_ASSETS_BASE_URL').'img/authentication/auth11.jpg') }}"
                         class="img-fluid rounded-4 shadow" alt="About Us">
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <h6 class="text-uppercase text-brand">{{ __('messages.About Us') }}</h6>
                    <h2 class="section-title">{{ __('messages.Who we are') }}</h2>
                    <p>{{ $aboutus_introduction }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SERVICES -->
    <section id="services" class="py-5 text-center">
        <div class="container">
            <h6 class="text-uppercase">{{ __('messages.Our Services') }}</h6>
            <h2 class="section-title">{{ __('messages.Features you’ll love') }}</h2>
            <div class="row g-4 mt-4">
                <div class="col-md-4" data-aos="zoom-in">
                    <div class="service">
                        <i class="bx bx-wifi bx-lg text-brand mb-3"></i>
                        <h5>{{ $data_title }}</h5>
                        <p>{{ $data_description }}</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="100">
                    <div class="service">
                        <i class="bx bx-phone-call bx-lg text-brand mb-3"></i>
                        <h5>{{ $airtime_title }}</h5>
                        <p>{{ $airtime_description }}</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="200">
                    <div class="service">
                        <i class="bx bx-tv bx-lg text-brand mb-3"></i>
                        <h5>{{ $cable_tv_title }}</h5>
                        <p>{{ $cable_tv_description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- PRICING SECTION -->
 <!-- PRICING SECTION -->
<section id="pricing" class="py-16 bg-gradient-to-b from-gray-50 to-white dark:from-gray-950 dark:to-gray-900">
    <div class="container mx-auto px-4" x-data="pricingTabs()">
        <!-- Header -->
        <div class="text-center mb-12" data-aos="fade-up">
            <h6 class="uppercase tracking-wider text-brand font-semibold">Pricing</h6>
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-white mb-4">
                Our Best Value Plans
            </h2>
            <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Transparent, affordable, and reliable plans across all networks. Pick your preferred service below.
            </p>
        </div>

        @php
            $networkColors = [
                'MTN' => '#fbbf24',
                'AIRTEL' => '#ef4444',
                'GLO' => '#10b981',
                '9MOBILE' => '#22c55e',
                'GOTV' => '#f59e0b',
                'DSTV' => '#3b82f6',
                'STARTIMES' => '#9333ea',
                'PREPAID' => '#2563eb',
                'DEFAULT' => '#3b82f6',
            ];

            $grouped = collect($pricing ?? [])->groupBy('category')->map(fn($items) =>
                $items->groupBy('network')->map(fn($plans) =>
                    $plans->groupBy('plan_category')
                )
            );

            $categories = ['data', 'airtime', 'cable', 'electricity'];
        @endphp

        <!-- Category Tabs -->
        <div class="flex overflow-x-auto gap-3 pb-3 border-b border-gray-200 dark:border-gray-700 mb-10 no-scrollbar">
            @foreach($categories as $category)
                <button
                    @click="setCategory('{{ $category }}')"
                    :class="activeCategory === '{{ $category }}'
                        ? 'bg-gradient-to-r from-brand to-indigo-500 text-white shadow-md scale-105'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="px-6 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-all duration-200 focus:outline-none flex-shrink-0">
                    {{ ucfirst($category) }}
                </button>
            @endforeach
        </div>

        <!-- Category Panels -->
        @foreach($grouped as $category => $networks)
            <div x-show="activeCategory === '{{ $category }}'" x-transition>
                <div x-data="{ activeNetwork: '{{ array_key_first($networks->toArray()) }}' }">
                    <!-- Network Tabs -->
                    <div class="flex overflow-x-auto gap-3 pb-3 border-b border-gray-200 dark:border-gray-700 mb-8 no-scrollbar">
                        @foreach($networks as $network => $categories)
                            <button
                                @click="activeNetwork = '{{ $network }}'"
                                :class="activeNetwork === '{{ $network }}'
                                    ? 'text-white shadow-md scale-105 ring-2 ring-offset-2 ring-offset-white dark:ring-offset-gray-900'
                                    : 'text-gray-700 dark:text-gray-300 opacity-80 hover:opacity-100'"
                                class="px-6 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-all duration-200 flex-shrink-0"
                                style="background: linear-gradient(135deg, {{ $networkColors[$network] ?? $networkColors['DEFAULT'] }} 0%, {{ $networkColors[$network] ?? '#3b82f6' }}cc 100%)">
                                {{ strtoupper($network) }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Network Panels -->
                    @foreach($networks as $network => $categories)
                        <div x-show="activeNetwork === '{{ $network }}'" x-transition>
                            <div class="mb-12" data-aos="fade-up">
                                @foreach($categories as $planCategory => $plans)
                                    <div class="mb-10">
                                        <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-4 border-l-4 pl-3"
                                            style="border-color: {{ $networkColors[$network] ?? $networkColors['DEFAULT'] }}">
                                            {{ $planCategory }}
                                        </h4>

                                        <!-- Plans Grid -->
                                       <!-- Plans Grid -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 w-full">
                                            @foreach($plans as $plan)
                                                <div class="w-full rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/70 backdrop-blur-sm shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between">
                                                    <div>
                                                        <h5 class="font-semibold text-gray-900 dark:text-white text-sm md:text-base leading-snug mb-1 line-clamp-2">
                                                            {{ $plan['product_plan_name'] }}
                                                        </h5>
                                                        @if(!empty($plan['data_size_in_mb']))
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ $plan['data_size_in_mb'] }}MB • {{ $plan['validity_in_days'] ?? '30' }} Days
                                                            </p>
                                                        @endif
                                                    </div>

                                                    <div class="mt-4 flex items-center justify-between">
                                                        <span class="font-bold text-base md:text-lg text-gray-900 dark:text-gray-100">
                                                            ₦{{ number_format($plan['selling_price'], 0) }}
                                                        </span>
                                                        <a href="{{ url('/register') }}"
                                                        class="text-xs font-semibold px-3 py-1.5 rounded-full text-white shadow-sm"
                                                        style="background: linear-gradient(135deg, {{ $networkColors[$network] ?? $networkColors['DEFAULT'] }}, #1e3a8a)">
                                                            Buy
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>



                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <!-- AlpineJS Component -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pricingTabs', () => ({
                activeCategory: 'data',
                setCategory(cat) {
                    this.activeCategory = cat;
                }
            }))
        });
    </script>
</section>

    
    {{-- <script>
    function pricingTabs() {
        return {
            activeCategory: 'data',
            setCategory(cat) {
                this.activeCategory = cat;
            },
        }
    }
    </script>
    
    <style>
    /* Optional: Hide scrollbars for tab rows */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
     --}}
    
    
    
    

<!-- TESTIMONIAL SECTION -->
<section id="reviews" class="py-5 bg-gray-50 dark:bg-gray-950">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h6 class="uppercase tracking-wider text-brand">Testimonials</h6>
            <h2 class="section-title text-gray-900 dark:text-white">What Our Users Say</h2>
            <p class="text-gray-500 dark:text-gray-400">Real feedback from our satisfied customers.</p>
        </div>

        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3" data-aos="fade-up">
            @foreach($testimonials ?? [
                ['name' => 'John Adeyemi', 'role' => 'Student', 'content' => 'I love how fast and reliable their data service is. Highly recommend!', 'rating' => 5],
                ['name' => 'Ngozi Obi', 'role' => 'Entrepreneur', 'content' => 'Their customer support is amazing. Transactions are seamless.', 'rating' => 4],
                ['name' => 'Tunde Alabi', 'role' => 'Freelancer', 'content' => 'Affordable pricing and great uptime. My go-to platform for all utilities.', 'rating' => 5],
            ] as $testimonial)
                <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-5 shadow-sm hover:shadow-lg transition duration-200">
                    <div class="flex items-center mb-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-brand to-brand-hover flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr($testimonial['name'], 0, 1)) }}
                        </div>
                        <div class="ml-3">
                            <h4 class="text-gray-900 dark:text-gray-100 text-sm font-semibold">{{ $testimonial['name'] }}</h4>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $testimonial['role'] }}</p>
                        </div>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed mb-3">“{{ $testimonial['content'] }}”</p>
                    <div class="flex items-center">
                        @for($i = 0; $i < $testimonial['rating']; $i++)
                            <i class='bx bxs-star text-yellow-400 text-xs'></i>
                        @endfor
                        @for($i = $testimonial['rating']; $i < 5; $i++)
                            <i class='bx bx-star text-gray-400 text-xs'></i>
                        @endfor
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>


    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p class="mb-0">&copy; {{ date('Y') }} {{ $site_title }}. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>AOS.init({ duration: 800, once: true });</script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

</body>
</html>
