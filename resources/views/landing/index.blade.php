<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- {{ $site_title }} - --}}
    <title> {{ session('affiliate')->name }}</title>

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

        // Dynamic affiliate color integration
        $primary = Session::get('user_dashboard_primary_color') ?? '#4f46e5'; // Indigo-600 fallback
        $secondary = Session::get('user_dashboard_secondary_color') ?? '#4338ca'; // Indigo-700 fallback
        $accent = Session::get('user_dashboard_announcement_color') ?? '#3b82f6'; // Blue-500 fallback
    @endphp

    <link href="https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <style>
        :root {
            --brand: {{ $primary }};
            --brand-hover: {{ $secondary }};
            --brand-accent: {{ $accent }};
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }
        .animate-gradient {
            background-size: 200%;
            animation: gradientShift 6s ease infinite;
        }
        .glass {
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.7);
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>

<body class="font-sans bg-gray-50 text-gray-800 antialiased">

<!-- Navbar -->
<nav x-data="{ open: false, scrolled: false }"
     x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 50 })"
     :class="scrolled ? 'bg-white/90 shadow-md' : 'glass'"
     class="fixed w-full z-50 transition-all duration-300 border-b border-white/20">
    <div class="max-w-7xl mx-auto flex items-center justify-between px-6 py-4">
        <div class="flex items-center gap-3">
            @if (isset($logo))
                <img src="{{ $logo }}" alt="Logo" class="h-10">
            @else
                <span class="font-bold text-xl text-[var(--brand)]">{{ $site_logo_alt ?? 'OurBrand' }}</span>
            @endif
        </div>

        <div class="hidden md:flex items-center space-x-6">
            <a href="#home" class="hover:text-[var(--brand)] font-medium">Home</a>
            <a href="#about" class="hover:text-[var(--brand)] font-medium">About</a>
            <a href="#services" class="hover:text-[var(--brand)] font-medium">Services</a>
            <a href="#reviews" class="hover:text-[var(--brand)] font-medium">Testimonials</a>
            <a href="{{ url('/register') }}" class="bg-[var(--brand)] hover:bg-[var(--brand-hover)] text-white px-4 py-2 rounded-full text-sm font-semibold transition">Sign Up</a>
            <a href="{{ url('/login') }}" class="border border-gray-300 hover:bg-gray-100 rounded-full px-4 py-2 text-sm font-medium">Login</a>
        </div>

        <button @click="open = !open" class="md:hidden">
            <i class='bx bx-menu text-2xl'></i>
        </button>

        <div x-show="open" @click.away="open=false" class="absolute top-16 left-0 w-full bg-white shadow-md md:hidden flex flex-col space-y-3 p-4 text-center">
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#services">Services</a>
            <a href="#reviews">Testimonials</a>
            <a href="{{ url('/register') }}" class="bg-[var(--brand)] text-white rounded-full px-4 py-2">Sign Up</a>
            <a href="{{ url('/login') }}" class="border border-gray-300 rounded-full px-4 py-2">Login</a>
        </div>
    </div>
</nav>

<!-- Hero -->
<section id="home" class="relative flex flex-col justify-center items-center text-center text-white min-h-screen overflow-hidden"
         style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('{{ $hero1 }}'); background-size:cover; background-position:center;">
    
    <!-- Decorative elements -->
    <div class="absolute top-0 left-0 w-96 h-96 bg-[var(--brand)]/10 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-[var(--brand-accent)]/10 rounded-full blur-3xl translate-x-1/2 translate-y-1/2"></div>
    
    <div class="relative z-10 max-w-3xl mx-auto px-6 md:px-8 py-20" data-aos="fade-up">
        <!-- Eyebrow text -->
        <div class="flex items-center justify-center gap-2 mb-4">
            <span class="inline-block w-2 h-2 rounded-full bg-[var(--brand)]"></span>
            <p class="uppercase tracking-widest text-sm md:text-base text-gray-300 font-medium">{{ $sub_hero1 }}</p>
            <span class="inline-block w-2 h-2 rounded-full bg-[var(--brand)]"></span>
        </div>
        
        <!-- Main heading -->
        <h1 class="text-5xl md:text-7xl font-black leading-tight mb-6 tracking-tight text-white">
            {{ $hero1_part1 }}
            <br>
            {{ $hero1_part2 }}
        </h1>
        
        <!-- Subheading -->
        <p class="text-lg md:text-xl text-gray-200 max-w-2xl mx-auto mb-10 leading-relaxed font-light">
            {{ $service_intro ?? 'Simple, fast, and reliable digital solutions built for you.' }}
        </p>
        
        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <a href="{{ url('/register') }}" class="group relative px-8 py-4 bg-[var(--brand)] hover:bg-[var(--brand-hover)] text-white font-bold text-lg rounded-full shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                <span class="flex items-center gap-2">
                    Get Started Free
                    <i class="bx bx-arrow-to-right text-xl"></i>
                </span>
            </a>
            
            <a href="{{ url('/login') }}" class="px-8 py-4 border-2 border-white/60 text-white font-bold text-lg rounded-full backdrop-blur-md hover:bg-white/10 hover:border-white transition-all duration-300 transform hover:scale-105">
                <span class="flex items-center gap-2">
                    Sign In
                    <i class="bx bx-log-in text-xl"></i>
                </span>
            </a>
        </div>
        
        <!-- Trust indicators -->
        <div class="mt-14 pt-8 border-t border-white/20 flex flex-col md:flex-row items-center justify-center gap-8 text-sm md:text-base text-gray-300">
            <div class="flex items-center gap-2">
                <i class="bx bx-check-circle text-[var(--brand)] text-2xl"></i>
                <span>No credit card required</span>
            </div>
            <div class="flex items-center gap-2">
                <i class="bx bx-check-circle text-[var(--brand)] text-2xl"></i>
                <span>Instant activation</span>
            </div>
            <div class="flex items-center gap-2">
                <i class="bx bx-check-circle text-[var(--brand)] text-2xl"></i>
                <span>24/7 support</span>
            </div>
        </div>
    </div>
</section>

<!-- About -->
<section id="about" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto grid md:grid-cols-2 gap-10 px-6 items-center">
        <div data-aos="fade-right">
            <img src="{{ isset($aboutus_image)
                ? asset(env('APP_ASSETS_BASE_URL').'landing_page_assets/img/aboutus_image/'.$aboutus_image)
                : asset(env('APP_ASSETS_BASE_URL').'img/authentication/auth11.jpg') }}"
                class="rounded-3xl shadow-lg" alt="About Us">
        </div>
        <div data-aos="fade-left">
            <h6 class="text-[var(--brand)] uppercase mb-2 font-semibold">About Us</h6>
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Who we are</h2>
            <p class="text-gray-600 leading-relaxed">{{ $aboutus_introduction }}</p>
        </div>
    </div>
</section>

<!-- Services -->
<section id="services" class="py-20 bg-white text-center">
    <div class="max-w-6xl mx-auto px-6">
        <h6 class="text-[var(--brand)] uppercase mb-2 font-semibold">Our Services</h6>
        <h2 class="text-3xl md:text-4xl font-bold mb-10">{{__('messages.Features')}} you'll love</h2>
        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-8">
            <div class="p-8 rounded-2xl shadow-md hover:shadow-xl transition transform hover:-translate-y-2" data-aos="zoom-in">
                <i class="bx bx-wifi text-4xl text-[var(--brand)] mb-3"></i>
                <h5 class="font-semibold mb-2">{{ $data_title }}</h5>
                <p class="text-gray-500 text-sm">{{ $data_description }}</p>
            </div>
            <div class="p-8 rounded-2xl shadow-md hover:shadow-xl transition transform hover:-translate-y-2" data-aos="zoom-in" data-aos-delay="100">
                <i class="bx bx-phone-call text-4xl text-[var(--brand)] mb-3"></i>
                <h5 class="font-semibold mb-2">{{ $airtime_title }}</h5>
                <p class="text-gray-500 text-sm">{{ $airtime_description }}</p>
            </div>
            <div class="p-8 rounded-2xl shadow-md hover:shadow-xl transition transform hover:-translate-y-2" data-aos="zoom-in" data-aos-delay="200">
                <i class="bx bx-tv text-4xl text-[var(--brand)] mb-3"></i>
                <h5 class="font-semibold mb-2">{{ $cable_tv_title }}</h5>
                <p class="text-gray-500 text-sm">{{ $cable_tv_description }}</p>
            </div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section id="pricing" class="py-20 bg-gray-50 text-center">
    <div class="max-w-7xl mx-auto px-6">
        <h6 class="text-[var(--brand)] uppercase mb-2 font-semibold">Our Pricing</h6>
        <h2 class="text-3xl md:text-4xl font-bold mb-10">Affordable Plans You'll Love</h2>

        @php
            $grouped = [];
            foreach ($productPlans as $plan) {
                $cat = strtolower($plan['category'] ?? 'others');
                $net = strtoupper($plan['network'] ?? 'OTHER');
                $pcat = $plan['plan_category'] ?? 'General';
                $grouped[$cat][$net][$pcat][] = $plan;
            }

            $networkColors = [
                'MTN' => '#fbbf24',
                'AIRTEL' => '#ef4444',
                'GLO' => '#10b981',
                '9MOBILE' => '#22c55e',
                'DEFAULT' => 'var(--brand)',
            ];
        @endphp

        <div x-data="{ tab: '{{ array_key_first($grouped) }}' }" class="max-w-7xl mx-auto px-4 sm:px-8 py-12 space-y-10 bg-gray-50">
            <!-- Tabs -->
            <div class="overflow-x-auto no-scrollbar py-4">
                <div class="flex gap-3 justify-start sm:justify-center px-4">
                    @foreach(array_keys($grouped) as $category)
                        <button
                            @click="tab = '{{ $category }}'"
                            :class="tab === '{{ $category }}'
                                ? 'bg-[var(--brand)] text-white shadow-lg scale-105'
                                : 'bg-gray-200 text-gray-800 hover:bg-gray-300'"
                            class="flex-shrink-0 px-6 py-2 rounded-full font-semibold transition-all duration-300 shadow-sm whitespace-nowrap">
                            {{ ucwords(str_replace('_',' ',$category)) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Tab Content -->
            @foreach($grouped as $category => $networks)
                <div x-show="tab === '{{ $category }}'" x-cloak x-transition.opacity.duration.400ms class="space-y-10">
                    @foreach($networks as $network => $categories)
                        <div class="space-y-6">
                            <h3 class="font-bold text-xl sm:text-2xl tracking-tight px-2"
                                style="color: {{ $networkColors[$network] ?? $networkColors['DEFAULT'] }}">
                                {{ $network }}
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                                @foreach($categories as $catName => $plans)
                                    @foreach($plans as $plan)
                                        @php
                                            $showPercent = in_array(strtolower($category), ['airtime', 'utility_bills']);
                                            $accent = $networkColors[$network] ?? $networkColors['DEFAULT'];
                                        @endphp

                                        <div class="relative p-5 rounded-2xl bg-white border shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group"
                                            style="border-top: 4px solid {{ $accent }};">
                                            <div class="font-semibold text-gray-900 text-sm sm:text-base">
                                                {{ $plan['product_plan_name'] }}
                                            </div>
                                            @if(!in_array(strtolower($category), ['utility_bills','airtime']))
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ $plan['data_size_in_mb'] ?? '' }} MB • {{ $plan['validity_in_days'] ?? '' }} days
                                                </div>
                                            @endif
                                            <div class="mt-3 font-extrabold text-xl" style="color: {{ $accent }};">
                                                @if($showPercent)
                                                    {{ number_format($plan['selling_price']) }}% off
                                                @else
                                                    ₦{{ number_format($plan['selling_price']) }}
                                                @endif
                                            </div>
                                            <div class="mt-4">
                                                <button class="w-full py-2 rounded-full text-sm font-medium border transition-all duration-300 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-offset-2"
                                                    style="border-color: {{ $accent }}; color: {{ $accent }};">
                                                    Buy Now
                                                </button>
                                            </div>
                                            <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition">
                                                <div class="w-8 h-8 flex items-center justify-center rounded-full" style="background-color: {{ $accent }};">
                                                    <i class='bx bx-shopping-bag text-white text-lg'></i>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Testimonials -->
<section id="reviews" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <h6 class="text-[var(--brand)] uppercase font-semibold mb-2">Testimonials</h6>
        <h2 class="text-3xl md:text-4xl font-bold mb-6">What Our Users Say</h2>
        <p class="text-gray-500 max-w-2xl mx-auto mb-10">Real feedback from our satisfied customers.</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8" data-aos="fade-up">
            @foreach($testimonials ?? [
                ['name'=>'John Adeyemi','role'=>'Student','content'=>'I love how fast and reliable their data service is. Highly recommend!','rating'=>5],
                ['name'=>'Ngozi Obi','role'=>'Entrepreneur','content'=>'Their customer support is amazing. Transactions are seamless.','rating'=>4],
                ['name'=>'Tunde Alabi','role'=>'Freelancer','content'=>'Affordable pricing and great uptime. My go-to platform for all utilities.','rating'=>5],
            ] as $t)
                <div class="bg-gray-50 rounded-2xl shadow-md hover:shadow-xl p-6 text-left transition transform hover:-translate-y-2">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-[var(--brand)] flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr($t['name'], 0, 1)) }}
                        </div>
                        <div class="ml-3">
                            <h4 class="font-semibold">{{ $t['name'] }}</h4>
                            <p class="text-gray-500 text-sm">{{ $t['role'] }}</p>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-3">“{{ $t['content'] }}”</p>
                    <div class="flex">
                        @for($i=0; $i<$t['rating']; $i++)
                            <i class='bx bxs-star text-yellow-400 text-sm'></i>
                        @endfor
                        @for($i=$t['rating']; $i<5; $i++)
                            <i class='bx bx-star text-gray-300 text-sm'></i>
                        @endfor
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- WhatsApp -->
<a href="https://api.whatsapp.com/send?phone={{ $support_whatsapp_number }}&text=Hello, I need help on your website"
   target="_blank"
   class="fixed bottom-8 right-8 bg-green-500 hover:bg-green-600 text-white p-4 rounded-full shadow-lg text-3xl animate-bounce z-50">
   <i class="bx bxl-whatsapp"></i>
</a>

<!-- Footer -->
<footer class="bg-gradient-to-r from-[var(--brand)] to-[var(--brand-hover)] text-white py-8 text-center">
    <p>&copy; {{ date('Y') }} {{ $site_title }}. All rights reserved.</p>
</footer>

<script>
  AOS.init({ duration: 900, once: true });
</script>

</body>
</html>
