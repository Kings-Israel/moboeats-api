<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Real Sources Africa') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&family=Titillium+Web:wght@400;900&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-outfit">
        <div class="bg-white">
            <div class="sticky top-0 bg-white flex lg:flex-wrap lg:items-center justify-between mx-auto py-1 lg:px-24 md:px-2 z-40 duration-200" id="main-header">
                <div class="flex gap-2">
                    <button data-drawer-target="home-sidebar" data-drawer-toggle="home-sidebar" aria-controls="home-sidebar" type="button" class="inline-flex items-center p-2 mt-2 ml-3 text-sm text-gray-500 rounded-lg lg:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
                        </svg>
                    </button>

                    <a href="{{ url('/') }}" class="">
                        <x-application-logo class="w-[10rem] object-contain" />
                    </a>
                </div>

                <div id="home-sidebar" class="fixed top-0 left-0 z-40 h-screen p-4 overflow-y-auto transition-transform -translate-x-full bg-white w-64 dark:bg-gray-800" tabindex="-1" aria-labelledby="home-sidebar-label">
                    <a href="{{ url('/') }}" class="">
                        <x-application-logo class="w-[10rem] object-contain" />
                    </a>
                    <button type="button" data-drawer-hide="home-sidebar" aria-controls="home-sidebar" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 absolute top-2.5 end-2.5 inline-flex items-center justify-center dark:hover:bg-gray-600 dark:hover:text-white" >
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">Close menu</span>
                    </button>
                    <ul class="space-y-4 text-center mt-2">
                        <li class="font-bold @if(request()->routeIs('home')) active @endif">
                            <a href="{{ url('/home') }}">Home</a>
                        </li>
                        <li class="font-bold @if(request()->routeIs('about')) active @endif">
                            <a href="{{ route('about') }}">
                                About Us
                            </a>
                        </li>
                        <li class="font-bold @if(request()->routeIs('contact-us')) active @endif">
                            <a href="{{ route('contact-us') }}">
                                Contact us
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('login') }}" class="">
                                <x-primary-button class="py-2">Sign In</x-primary-button>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="hidden lg:block my-auto partners">
                    <ul class="flex gap-16">
                        <li class="font-bold @if(request()->routeIs('home')) active @endif">
                            <a href="{{ url('/home') }}">Home</a>
                        </li>
                        <li class="font-bold @if(request()->routeIs('about')) active @endif">
                            <a href="{{ route('about') }}">
                                About Us
                            </a>
                        </li>
                        <li class="font-bold @if(request()->routeIs('contact-us')) active @endif">
                            <a href="{{ route('contact-us') }}">
                                Contact us
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="flex space-x-2">
                    @guest
                        <a href="{{ route('login') }}" class="hidden my-auto md:block">
                            <x-primary-button class="py-2">Sign In</x-primary-button>
                        </a>
                    @endguest
                </div>
            </div>

            <section
                class="relative bg-home-hero bg-cover bg-center bg-no-repeat"
            >
                <div
                    class="absolute inset-0"
                ></div>
                    <div
                        class="relative lg:px-24 4xl:px-48 px-4 pb-32 sm:px-6 lg:flex lg:h-[820px] lg:items-center"
                    >
                    <div class="max-w-4xl text-white text-center md:text-left">
                        <h1 class="text-3xl font-bold sm:text-5xl">
                            A technology approach to logistics, warehousing & distribution
                        </h1>

                        <p class="mt-4 max-w-lg sm:text-xl/relaxed font-semibold">
                            Real Sources has helped distributors move goods worth over 30 Billion in the last one year.
                        </p>

                        <div class="mt-8 flex flex-wrap justify-center md:justify-start gap-4 text-center">
                            <button class="px-8 py-4 bg-white text-black font-semibold rounded-md">Schedule A Call</button>
                        </div>
                    </div>
                </div>
            </section>
            <div class="mt-4 lg:mt-0 lg:absolute lg:top-[42rem] 4xl:top-[52rem] grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 lg:flex lg:justify-between gap-4 px-4 lg:px-24 4xl:px-48">
                <div class="bg-white rounded-lg flex flex-col text-center p-4 border border-gray-300 w-[23rem]">
                    <i class="fas fa-book bg-[#ecb7b6bf] text-[#6E1210] p-4 rounded-lg w-fit mx-auto"></i>
                    <h3 class="text-xl font-bold">Logistics & Shipping</h3>
                    <span class="font-semibold">We offer best practices for effcient transportation of bulky goods</span>
                    <button class="border-2 border-primary-one w-3/4 mx-auto py-4 px-4 text-center font-bold text-primary-one rounded-lg my-3">Read More</button>
                </div>
                <div class="bg-white rounded-lg flex flex-col text-center p-4 border border-gray-300 w-[23rem]">
                    <i class="fas fa-credit-card bg-[#ecb7b6bf] text-[#6E1210] p-4 rounded-lg w-fit mx-auto"></i>
                    <h3 class="text-xl font-bold">Trade Financing</h3>
                    <span class="font-semibold">We provide secure financial instruments for transactions</span>
                    <button class="border-2 border-primary-one w-3/4 mx-auto py-4 px-4 text-center font-bold text-primary-one rounded-lg my-3">Read More</button>
                </div>
                <div class="bg-white rounded-lg flex flex-col text-center p-4 border border-gray-300 w-[23rem]">
                    <i class="fas fa-cubes bg-[#ecb7b6bf] text-[#6E1210] p-4 rounded-lg w-fit mx-auto"></i>
                    <h3 class="text-xl font-bold">Networking Opportunities</h3>
                    <span class="font-semibold">Connect with businesses, potential partners & suppliers in Africa</span>
                    <button class="border-2 border-primary-one w-3/4 mx-auto py-4 px-4 text-center font-bold text-primary-one rounded-lg my-3">Read More</button>
                </div>
                <div class="bg-white rounded-lg flex flex-col text-center p-4 border border-gray-300 w-[23rem]">
                    <i class="fas fa-briefcase bg-[#ecb7b6bf] text-[#6E1210] p-4 rounded-lg w-fit mx-auto"></i>
                    <h3 class="text-xl font-bold">Tarrifs & Duties</h3>
                    <span class="font-semibold">We provide insights to tarrids, taxes duties for different African countries</span>
                    <button class="border-2 border-primary-one w-3/4 mx-auto py-4 px-4 text-center font-bold text-primary-one rounded-lg my-3">Read More</button>
                </div>
            </div>

            <div class="mt-4 md:mt-24 px-4 lg:px-24 md:grid md:grid-cols-2 md:gap-2">
                <div class="">
                    <img src="{{ asset('assets/img/melody-zimmerman-baNjp1eJAyo-unsplash.jpg') }}" alt="" class="w-[38rem]">
                </div>
                <div class="lg:my-auto md:max-w-md flex flex-col justify-center md:block">
                    <h2 class="text-2xl font-bold lg:mt-12 lg:mb-6 text-center md:text-left">The Marketplace</h2>
                    <p class="font-semibold text-center md:text-left">Real Sources Africa now gives SMEs an opportunity to compete in an international market by gaining visbility on the platform. The marketplace offers ease and convenience of trading accross a wide variety and accessibility of goods and products at a competitive rate.</p>
                    <button class="bg-primary-one text-white px-6 py-4 rounded-lg text-center mt-2 lg:mt-16 font-semibold">Visit our Marketplace</button>
                </div>
            </div>

            <div class="bg-[#eae1e1bf] h-[52rem]">
                <h1 class="text-3xl text-center my-5 pt-6 font-semibold">What we Offer</h1>
                <div class="flex flex-nowrap gap-4 overflow-x-auto px-2 md:px-24 no-scrollbar">
                    <div class="flex-shrink-0 my-4 relative">
                        <img src="{{ asset('assets/img/melody-zimmerman-baNjp1eJAyo-unsplash.jpg') }}" class="h-96 w-[18rem] 4xl:w-[20rem] object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-white absolute bottom-8 left-8">
                            <h5 class="text-xl font-bold">Financing</h5>
                            <p class="mb-6 text-sm whitespace-normal font-semibold">Invoice Financing and Discounting</p>
                            <button class="mx-auto text-amber-950 bg-white rounded-lg px-5 py-4 font-bold">View more</button>
                        </div>
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <img src="{{ asset('assets/img/melody-zimmerman-baNjp1eJAyo-unsplash.jpg') }}" class="w-[18rem] 4xl:w-[20rem] h-96 object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-white absolute bottom-8 left-8 4xl:left-10">
                            <h5 class="text-xl font-bold">Inusrance</h5>
                            <p class="mb-6 text-sm whitespace-normal font-semibold">Business and Hustler Opportunities</p>
                            <button class="mx-auto text-amber-950 bg-white rounded-lg px-5 py-4 font-bold">View more</button>
                        </div>
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <img src="{{ asset('assets/img/inspection.jpg') }}" class="w-[18rem] 4xl:w-[20rem] h-96 object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-white absolute bottom-8 left-8 4xl:left-10">
                            <h5 class="text-xl font-bold">Inspection</h5>
                            <p class="mb-6 text-sm whitespace-normal font-semibold">Built Environment and Urban Planning Center</p>
                            <button class="mx-auto text-amber-950 bg-white rounded-lg px-5 py-4 font-bold">View more</button>
                        </div>
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <img src="{{ asset('assets/img/logistics3.jpg') }}" class="w-[18rem] 4xl:w-[20rem] h-96 object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-white absolute bottom-8 left-8 4xl:left-10">
                            <h5 class="text-xl font-bold">Logistics</h5>
                            <p class="mb-6 text-sm whitespace-normal font-semibold">Finance and Economic Planning Affairs</p>
                            <button class="mx-auto text-amber-950 bg-white rounded-lg px-5 py-4 font-bold">View more</button>
                        </div>
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <img src="{{ asset('assets/img/warehousing.jpg') }}" class="w-[18rem] 4xl:w-[20rem] h-96 object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-white absolute bottom-8 left-8 4xl:left-10">
                            <h5 class="text-xl font-bold">Warehousing</h5>
                            <p class="mb-6 text-sm whitespace-normal font-semibold">Finance and Economic Planning Affairs</p>
                            <button class="mx-auto text-amber-950 bg-white rounded-lg px-5 py-4 font-bold">View more</button>
                        </div>
                    </div>
                </div>

                <h1 class="text-3xl text-center my-3 py-2 font-semibold">Learn More About Us</h1>
                <p class="text-center font-bold text-sm pb-4 px-2 md:px-0">Get a bit more insight on how to export & import in Real Sources</p>
                <div class="flex justify-center">
                    <video class="h-96 rounded-lg" controls>
                        <source src="">
                    </video>
                </div>
            </div>

            <div class="px-4 lg:px-24 mt-60 md:mt-[20rem]">
                <h1 class="text-3xl text-center my-3 font-semibold">Our Partners</h1>
                <p class="text-center font-bold text-sm pb-6">Join a wide range of companies operating within our network</p>
                <div class="flex flex-nowrap gap-4 overflow-x-auto mt-4 no-scrollbar">
                    <div class="flex-shrink-0 p-8 bg-slate-50 rounded-lg border-2">
                        <img src="{{ asset('assets/img/favicon/android-chrome-512x512.png') }}" class="h-20 w-52 object-contain" alt="">
                    </div>
                    <div class="flex-shrink-0 p-8 bg-slate-50 rounded-lg border-2">
                        <img src="{{ asset('assets/img/favicon/android-chrome-512x512.png') }}" class="h-20 w-52 object-contain" alt="">
                    </div>
                    <div class="flex-shrink-0 p-8 bg-slate-50 rounded-lg border-2">
                        <img src="{{ asset('assets/img/favicon/android-chrome-512x512.png') }}" class="h-20 w-52 object-contain" alt="">
                    </div>
                    <div class="flex-shrink-0 p-8 bg-slate-50 rounded-lg border-2">
                        <img src="{{ asset('assets/img/favicon/android-chrome-512x512.png') }}" class="h-20 w-52 object-contain" alt="">
                    </div>
                    <div class="flex-shrink-0 p-8 bg-slate-50 rounded-lg border-2">
                        <img src="{{ asset('assets/img/favicon/android-chrome-512x512.png') }}" class="h-20 w-52 object-contain" alt="">
                    </div>
                </div>
            </div>
            <div class="px-4 lg:px-24 mt-4 md:mt-[8rem]">
                <h1 class="text-3xl text-center my-2 font-semibold">Join Our Upcoming Events</h1>
                <p class="text-center font-bold text-sm">Join us.</p>
                <div class="mt-16 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="">
                        <img src="{{ asset('assets/img/melody-zimmerman-baNjp1eJAyo-unsplash.jpg') }}" alt="" class="w-[920px] h-[18rem] object-cover rounded-lg">
                    </div>
                    <div class="grid grid-cols-1 lg:flex gap-3">
                        <div class="my-auto text-center md:text-left">
                            <h2 class="text-8xl font-bold">20-24</h2>
                            <p class="text-center font-semibold">Feb</p>
                        </div>
                        <div class="my-auto lg:mx-8 text-center md:text-left">
                            <p class="font-semibold">Moving By Design</p>
                            <p class="font-semibold">11:00 AM - 02:30PM</p>
                            <p class="font-semibold mb-2">Norfolk Hotel</p>
                            <span class="bg-slate-300 text-slate-500 p-1 font-semibold rounded-md">Logistcs & Shipping</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-950 px-2 lg:px-24 mt-4 pb-12">
                <img src="{{ asset('assets/img/logo-alt.png') }}" class="w-[10rem] h-[10rem] object-contain" alt="">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 lg:gap-0 text-white">
                    <p class="md:max-w-xs text-center md:text-left font-thin">Real Sources is an online marketplace tailored with African businesses in mind that is redefining business to business trade in Africa</p>
                    <div class="text-center md:text-left">
                        <ul>
                            <li><p class="font-bold">Company</p></li>
                            <li><p class="font-thin">About</p></li>
                            <li><p class="font-thin">Support</p></li>
                        </ul>
                    </div>
                    <div class="text-center md:text-left">
                        <ul>
                            <li><p class="font-bold">Products</p></li>
                            <li><p class="font-thin">Marketplace</p></li>
                            <li><p class="font-thin">Logistics</p></li>
                        </ul>
                    </div>
                    <div class="text-center md:text-left">
                        <ul>
                            <li><p class="font-bold">Quick Links</p></li>
                            <li><p class="font-thin">Home</p></li>
                            <li><p class="font-thin">Partners</p></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.0/flowbite.min.js"></script>
    </body>
</html>
