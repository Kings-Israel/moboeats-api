<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Mobo Eats') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-comfortaa">
        <div class="bg-primary-one">
            @include('layouts.navigation')

            {{-- <section
                class="relative bg-home-hero bg-cover bg-center bg-no-repeat"
            >
                <div
                    class="absolute inset-0"
                ></div>
                    <div
                        class="relative lg:px-24 4xl:px-48 px-4 pb-32 sm:px-6 lg:flex lg:h-[720px] lg:items-center"
                    >
                </div>
            </section> --}}
            <div id="default-carousel" class="relative w-full lg:-mt-8" data-carousel="slide">
                <!-- Carousel wrapper -->
                <div class="relative overflow-hidden rounded-lg md:h-96 lg:h-[820px]">
                    <div class="hidden duration-700 ease-in-out relative" data-carousel-item>
                        <img src="{{ asset('assets/img/Ad-copy.jpg') }}" class="object-contain" alt="...">
                        <div class="absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
                            <div class="inset-0 hidden">
                                <div class="relative lg:px-24 4xl:px-48 px-4 lg:pb-32 sm:px-6 lg:flex lg:items-center">
                                    <div class="max-w-[160px] md:max-w-md lg:max-w-2xl text-white text-left">
                                        <h1 class="text-md font-bold md:text-5xl mt-20 lg:mt-0">
                                            Your one stop app for variety, quality and affordability.
                                        </h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hidden duration-700 ease-in-out" data-carousel-item>
                        <img src="{{ asset('assets/img/Homeslider photos (3).png') }}" class="object-contain h-full" alt="...">
                        <div class="absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
                            <div class="inset-0">
                                <div class="relative lg:px-24 4xl:px-48 px-4 lg:pb-32 sm:px-6 lg:flex lg:items-center">
                                    <div class="max-w-[160px] md:max-w-md lg:max-w-2xl text-white text-left">
                                        <h1 class="text-md font-bold md:text-5xl mt-20 lg:mt-0">
                                            Receive and manage orders with ease.
                                        </h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="hidden duration-700 ease-in-out" data-carousel-item>
                        <img src="{{ asset('assets/img/Homeslider photos (2).png') }}" class="object-cover" alt="...">
                        <div class="absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
                            <div class="inset-0">
                                <div class="relative lg:px-24 4xl:px-48 px-4 lg:pb-32 sm:px-6 lg:flex lg:items-center">
                                    <div class="max-w-[160px] md:max-w-md lg:max-w-2xl text-black text-left">
                                        <h1 class="text-md font-bold md:text-5xl mt-20 lg:mt-0">

                                        </h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> --}}
                </div>
                <!-- Slider indicators -->
                <div class="absolute z-30 flex -translate-x-1/2 bottom-5 left-1/2 space-x-3 rtl:space-x-reverse">
                    <button type="button" class="w-3 h-3 rounded-full" aria-current="true" aria-label="Slide 1" data-carousel-slide-to="0"></button>
                    <button type="button" class="w-3 h-3 rounded-full" aria-current="false" aria-label="Slide 2" data-carousel-slide-to="1"></button>
                    {{-- <button type="button" class="w-3 h-3 rounded-full" aria-current="false" aria-label="Slide 3" data-carousel-slide-to="2"></button> --}}
                </div>
                <!-- Slider controls -->
                <button type="button" class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-prev>
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none">
                        <svg class="w-4 h-4 text-white dark:text-gray-800 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/>
                        </svg>
                        <span class="sr-only">Previous</span>
                    </span>
                </button>
                <button type="button" class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-next>
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none">
                        <svg class="w-4 h-4 text-white dark:text-gray-800 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                        </svg>
                        <span class="sr-only">Next</span>
                    </span>
                </button>
            </div>

            <div class="mt-4 md:mt-8 px-4 lg:px-24 md:grid md:grid-cols-2 md:gap-2">
                <div class="">
                    <img src="{{ asset('assets/img/partners1.png') }}" alt="" class="w-72 lg:w-[28rem] mx-auto object-cover rounded-lg">
                </div>
                <div class="lg:my-auto md:max-w-lg flex flex-col justify-center md:block text-white">
                    <h1 class="text-3xl text-center mt-4 lg:pt-14 font-semibold uppercase text-white">Why you should partner with us</h1>
                    <ul class="list-desc mb-8 space-y-4 text-center md:text-left">
                        <li class="flex flex-col mt-4 md:mt-0">
                            <h3 class="font-bold text-xl text-primary-one">Increased Visibility:</h3>
                            <span class="font-bold">
                                Joining us allows you to showcase your restaurant to a wider audience of food enthusiasts. Benefit from increased visibility and exposure in your local community and beyond.
                            </span>
                        </li>
                        <li class="flex flex-col">
                            <h3 class="font-bold text-xl text-primary-one">Expand Your Customer Base:</h3>
                            <span class="font-bold">
                                Tap into a diverse customer base seeking a variety of dining options. Whether you specialize in fine dining, casual eats, or quick bites, Moboeats provides a platform to attract new customers and grow your business.
                            </span>
                        </li>
                        <li class="flex flex-col">
                            <h3 class="font-bold text-xl text-primary-one">Seamless Integration:</h3>
                            <span class="font-bold">
                                Our dedicated team will work closely with you to ensure a smooth onboarding process and provide ongoing support to maximize your success.
                            </span>
                        </li>
                    </ul>
                    <a href="https://restaurant.moboeats.com/signup" class="hidden md:block md:w-fit bg-primary-two text-slate-900 hover:bg-black transition duration-300 ease-in-out hover:text-white px-6 py-4 rounded-lg text-center mt-2 lg:mt-20 font-semibold">Create you account here</a>
                </div>
            </div>

            <div class="mt-0 md:mt-24 px-4 lg:px-24 md:grid md:grid-cols-2 md:gap-2">
                <div class="lg:my-8 md:max-w-lg flex flex-col justify-center md:block text-white">
                    <h1 class="text-3xl text-center mt-4 lg:pt-14 font-semibold uppercase text-white">Why you should partner with us</h1>
                    <ul class="list-desc mb-8 space-y-4 text-center md:text-left">
                        <li class="flex flex-col">
                            <h3 class="font-bold text-xl text-primary-one">Flexible Ordering Options:</h3>
                            <span class="font-bold">
                                Offer customers the convenience of ordering from your restaurant. Whether they prefer dine-in, takeout, or scheduled/ instant delivery, our platform accommodates various ordering preferences, providing flexibility for both you and your customers.
                            </span>
                        </li>
                        <li class="flex flex-col">
                            <h3 class="font-bold text-xl text-primary-one">Marketing Support:</h3>
                            <span class="font-bold">
                                Take advantage of our marketing initiatives to promote your restaurant and attract more diners. Benefit from targeted promotions, featured listings, and other marketing opportunities to drive traffic and boost sales
                            </span>
                        </li>
                        <li class="flex flex-col">
                            <h3 class="font-bold text-xl text-primary-one">Multichannel Management:</h3>
                            <span class="font-bold">
                                Access various platforms seamlessly, from our Merchant app to a web-based management system. Streamline operations, update menus, and analyze performance easily with Moboeats' integrated approach.
                            </span>
                        </li>
                    </ul>
                    <a href="https://restaurant.moboeats.com/signup" class="my-2 bg-primary-two text-slate-900 hover:bg-black transition duration-300 ease-in-out hover:text-white px-6 py-4 rounded-lg text-center mt-2 lg:mt-20 font-semibold">Create you account here</a>
                </div>
                <div class="">
                    <img src="{{ asset('assets/img/partners2.png') }}" alt="" class="w-72 lg:w-[28rem] mx-auto object-cover rounded-lg">
                </div>
            </div>

            <div class="bg-white px-2">
                <h1 class="text-3xl text-center py-6 font-semibold">Partner Dashboard</h1>
                <div class="gap-4 hidden">
                    <div class="space-y-4 grid grid-col-2 gap-2">
                        <div class="flex gap-2">
                            <div class="hidden md:block md:basis-1/2 lg:basis-3/5">
                                <img src="{{ asset('assets/img/Sign Up.png') }}" alt="" class="w-[24rem] lg:w-[44rem] object-cover rounded-lg">
                            </div>
                            <div class="flex flex-col my-auto md:basis-1/2 lg:basis-2/5">
                                <span class="font-bold text-2xl">Sign Up</span>
                                <span class="font-bold">Joining Moboeats is quick and easy. Simply <a href="https://restaurant.moboeats.com/signup" class="text-primary-one hover:text-primary-two transition duration-300 ease-linear underline">Sign up</a> your business and provide necessary business documents needed.</span>
                            </div>
                        </div>
                        <div class="flex flex-row-reverse lg:flex-row gap-2 lg:gap-36">
                            <div class="flex flex-col my-auto md:basis-1/2 lg:basis-2/5 lg:px-8">
                                <span class="font-bold text-2xl">Menu Setup</span>
                                <span class="font-bold">Create and customize your menu to showcase your signature dishes, specials, and promotions. Add enticing descriptions, mouth-watering photos, and pricing to entice customers.</span>
                            </div>
                            <div class="hidden md:block md:basis-1/2 lg:basis-3/5">
                                <img src="{{ asset('assets/img/merchant-menu-management.png') }}" alt="" class="w-[24rem] lg:w-[44rem] object-cover rounded-lg">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="hidden md:block md:basis-1/2 lg:basis-3/5">
                                <img src="{{ asset('assets/img/merchant-order-management.png') }}" alt="" class="w-[24rem] lg:w-[44rem] object-cover rounded-lg">
                            </div>
                            <div class="flex flex-col my-auto md:basis-1/2 lg:basis-2/5">
                                <span class="font-bold text-2xl">Order Management</span>
                                <span class="font-bold">Receive and manage orders seamlessly through our user-friendly platform. Stay organized with real-time notifications, order tracking, and communication tools to ensure smooth operations.</span>
                            </div>
                        </div>
                        <div class="flex flex-row-reverse lg:flex-row gap-2 lg:gap-36">
                            <div class="flex flex-col my-auto md:basis-1/2 lg:basis-2/5">
                                <span class="font-bold text-2xl">Multibranch Management</span>
                                <span class="font-bold">Monitor and analyse performance of different branches as well as a centralised earning management for multiple shops.</span>
                            </div>
                            <div class="hidden md:block md:basis-1/2 lg:basis-3/5">
                                <img src="{{ asset('assets/img/Website_Proposal_Light.jpg') }}" alt="" class="w-[24rem] lg:w-[44rem] object-cover rounded-lg">
                            </div>
                        </div>
                        <div class="flex flex-row-reverse lg:flex-row gap-2 lg:gap-36">
                            <div class="hidden md:block md:basis-1/2 lg:basis-3/5">
                                <img src="{{ asset('assets/img/merchant-partner.png') }}" alt="" class="w-[24rem] lg:w-[44rem] object-cover rounded-lg">
                            </div>
                            <div class="flex flex-col my-auto md:basis-1/2 lg:basis-2/5">
                                <span class="font-bold text-2xl">Grow Your Business</span>
                                <span class="font-bold">Reach new heights by monitoring your performance, gather valuable insights, and adapt your strategy to meet the evolving needs of your customers.</span>
                            </div>
                        </div>
                    </div>
                    {{-- <p class="text-center font-bold text-sm pb-4 px-2 md:px-0">Get a bit more insight on how to export & import in Real Sources</p>
                    <div class="flex justify-center">
                        <video class="h-96 rounded-lg" controls>
                            <source src="">
                        </video>
                    </div> --}}
                </div>
                <div class="flex flex-nowrap overflow-x-auto px-4 md:px-4 4xl:px-72 no-scrollbar">
                    <div class="flex-shrink-0 my-4 relative">
                        <div class="w-40 absolute top-28 left-16 text-center z-10">
                            <h5 class="text-3xl font-black text-white">Sign Up</h5>
                            <p class="font-black text-white text-sm">
                                Joining Moboeats is quick and easy. Simply <a href="https://restaurant.moboeats.com/signup" class="text-primary-two hover:text-primary-two transition duration-300 ease-linear underline">Sign up</a> your business and provide necessary business documents needed.
                            </p>
                        </div>
                        <img src="{{ asset('assets/img/Merchant Process.png') }}" class="relative w-[18rem] 4xl:w-[16rem] object-cover my-4 rounded-lg mr-1" alt="">
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <div class="absolute w-48 top-28 left-14 text-center z-10">
                            <h5 class="text-3xl font-black text-white">Menu Setup</h5>
                            <p class="font-black text-white text-sm">
                                Create and customize your menu to showcase your signature dishes, specials, and promotions. Add enticing descriptions, mouth-watering photos, and pricing to entice customers.
                            </p>
                        </div>
                        <img src="{{ asset('assets/img/Merchant Process (1).png') }}" class="relative w-[18rem] 4xl:w-[16rem] object-cover my-4 rounded-lg mx-1" alt="">
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <div class="absolute w-48 top-28 left-11 text-center z-10">
                            <h5 class="text-3xl font-black text-white">Order Management</h5>
                            <p class="font-black text-white text-sm">
                                Receive and manage orders seamlessly through our user-friendly platform. Stay organized with real-time notifications, order tracking, and communication tools to ensure smooth operations.
                            </p>
                        </div>
                        <img src="{{ asset('assets/img/Merchant Process (2).png') }}" class="relative w-[18rem] 4xl:w-[16rem] object-cover my-4 rounded-lg mx-1" alt="">
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <div class="absolute w-52 top-28 left-11 text-center z-10">
                            <h5 class="text-3xl font-black text-white">Multibranch Management</h5>
                            <p class="font-black text-white text-sm">
                                Monitor and analyse performance of different branches as well as a centralised earning management for multiple shops.
                            </p>
                        </div>
                        <img src="{{ asset('assets/img/Merchant Process (3).png') }}" class="relative w-[18rem] 4xl:w-[16rem] object-cover my-4 rounded-lg mx-1" alt="">
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <div class="absolute w-48 top-28 left-12 text-center z-10">
                            <h5 class="text-3xl font-black text-white">Grow Your Business</h5>
                            <p class="font-black text-white text-sm">
                                Reach new heights by monitoring your performance, gather valuable insights, and adapt your strategy to meet the evolving needs of your customers.
                            </p>
                        </div>
                        <img src="{{ asset('assets/img/Merchant Process (4).png') }}" class="relative w-[18rem] 4xl:w-[16rem] object-cover my-4 rounded-lg mx-1" alt="">
                    </div>
                </div>
            </div>

            <div class="bg-white py-6">
                <h1 class="text-3xl pt-6 text-center font-semibold">How it works</h1>
                <div class="flex flex-nowrap gap-4 overflow-x-auto px-4 md:px-24 4xl:px-72 no-scrollbar">
                    <div class="flex-shrink-0 my-4 relative">
                        <div class="w-28 relative top-8 left-24 text-center z-10">
                            <h5 class="text-3xl font-black text-black">Browse & Select</h5>
                        </div>
                        <img src="{{ asset('assets/img/How it works images (3).jpg') }}" class="h-96 absolute top-0 w-[20rem] 4xl:w-[20rem] object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-black max-w-xs mt-[20rem]">
                            <p class="mb-6 text-sm whitespace-normal font-semibold px-2">Easily find what you need by
                                browsing and adding items to your cart with just a few taps.</p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <div class="w-60 relative top-8 left-9 text-center z-10">
                            <h5 class="text-3xl font-black text-black">Secure Checkout</h5>
                        </div>
                        <img src="{{ asset('assets/img/How it works images (4).jpg') }}" class="h-96 absolute top-0 w-[20rem] 4xl:w-[20rem] object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-black max-w-xs mt-[22rem]">
                            <p class="mb-6 text-sm whitespace-normal font-semibold px-2">Review your order, choose your
                                delivery time, and securely check out using our encrypted payment gateway</p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <div class="w-32 relative top-8 left-24 text-center z-10">
                            <h5 class="text-3xl font-black text-black">Track Your Delivery</h5>
                        </div>
                        <img src="{{ asset('assets/img/How it works images (5).jpg') }}" class="h-96 absolute top-0 w-[20rem] 4xl:w-[20rem] object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-black max-w-xs mt-[20rem]">
                            <p class="mb-6 text-sm whitespace-normal font-semibold px-2">Track the status of your delivery
                                in real-time and receive updates every step of the way.</p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <div class="w-52 relative top-8 left-16 text-center z-10">
                            <h5 class="text-3xl font-black">Enjoy Freshness</h5>
                        </div>
                        <img src="{{ asset('assets/img/How it works images (6).jpg') }}" class="h-96 absolute top-0 w-[20rem] 4xl:w-[20rem] object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-black max-w-xs mt-[22rem]">
                            <p class="mb-6 text-sm whitespace-normal font-semibold px-2"> Receive your meals and groceries
                                fresh and ready to use. </p>
                        </div>
                    </div>
                </div>

                <div class="px-8 lg:px-28 lg:flex lg:gap-10">
                    <div class="my-auto hidden lg:block">
                        <?xml version="1.0" encoding="utf-8"?>

                        <!-- Uploaded to: SVG Repo, www.svgrepo.com, Transformed by: SVG Repo Mixer Tools -->
                        <svg width="80px" height="80px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

                            <g id="SVGRepo_bgCarrier" stroke-width="0"/>

                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/>

                            <g id="SVGRepo_iconCarrier"> <path d="M3 5.5C3 14.0604 9.93959 21 18.5 21C18.8862 21 19.2691 20.9859 19.6483 20.9581C20.0834 20.9262 20.3009 20.9103 20.499 20.7963C20.663 20.7019 20.8185 20.5345 20.9007 20.364C21 20.1582 21 19.9181 21 19.438V16.6207C21 16.2169 21 16.015 20.9335 15.842C20.8749 15.6891 20.7795 15.553 20.6559 15.4456C20.516 15.324 20.3262 15.255 19.9468 15.117L16.74 13.9509C16.2985 13.7904 16.0777 13.7101 15.8683 13.7237C15.6836 13.7357 15.5059 13.7988 15.3549 13.9058C15.1837 14.0271 15.0629 14.2285 14.8212 14.6314L14 16C11.3501 14.7999 9.2019 12.6489 8 10L9.36863 9.17882C9.77145 8.93713 9.97286 8.81628 10.0942 8.64506C10.2012 8.49408 10.2643 8.31637 10.2763 8.1317C10.2899 7.92227 10.2096 7.70153 10.0491 7.26005L8.88299 4.05321C8.745 3.67376 8.67601 3.48403 8.55442 3.3441C8.44701 3.22049 8.31089 3.12515 8.15802 3.06645C7.98496 3 7.78308 3 7.37932 3H4.56201C4.08188 3 3.84181 3 3.63598 3.09925C3.4655 3.18146 3.29814 3.33701 3.2037 3.50103C3.08968 3.69907 3.07375 3.91662 3.04189 4.35173C3.01413 4.73086 3 5.11378 3 5.5Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/> </g>

                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl my-2 py-2 font-semibold text-center lg:text-left">Have any Complaint?</h1>
                        <div class="flex justify-center">
                            <span class="font-bold text-xl text-center lg:text-left">
                                If you encounter any issues or have feedback regarding your shopping experience, we're here to
                                help. To make a complaint, please visit our website and navigate to the <a
                                    href="{{ route('contact-us') }}" class="text-primary-one underline">Contact Us</a>
                                section. You can also reach out to our customer support team directly via email or phone. Your
                                satisfaction is our priority, and we're committed to addressing your concerns promptly and
                                effectively.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- <div class="px-4 lg:px-24 mt-10">
                <h1 class="text-3xl text-center my-3 font-semibold underline">Our Partners</h1>
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
            </div> --}}
            @include('layouts.footer')
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.0/flowbite.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        @if (config('app.env') == 'production')
            <!--Start of Tawk.to Script-->
            <script type="text/javascript">
                var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
                (function(){
                var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
                s1.async=true;
                s1.src='https://embed.tawk.to/6612d15e1ec1082f04dfc429/1hqso3mv2';
                s1.charset='UTF-8';
                s1.setAttribute('crossorigin','*');
                s0.parentNode.insertBefore(s1,s0);
                })();
            </script>
        @endif
        <script>
            $(window).scroll(function () {
                const scroll = $(window).scrollTop();

                let scrollThreshold = 0.5;

                if (scroll > scrollThreshold) {
                    // Apply the background color to the body element
                    $('#main-header').css('border-bottom', '4px solid #F7C410');
                } else {
                    $('#main-header').css('border-bottom', 'none');
                }
            });
        </script>
    </body>
</html>
