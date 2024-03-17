<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Moboeats') }}</title>

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
        <div class="bg-white">
            @include('layouts.navigation')

            <section
                class="relative bg-home-hero bg-cover bg-center bg-no-repeat"
            >
                <div
                    class="absolute inset-0"
                ></div>
                    <div
                        class="relative lg:px-24 4xl:px-48 px-4 pb-32 sm:px-6 lg:flex lg:h-[690px] lg:items-center"
                    >
                    <div class="max-w-4xl text-white text-center md:text-left">
                        <h1 class="text-3xl font-bold sm:text-5xl">
                            Want to Work With Us?
                        </h1>
                    </div>
                </div>
            </section>

            <div class="mt-4 md:mt-24 px-4 lg:px-24 md:grid md:grid-cols-2 md:gap-2">
                <div class="">
                    <img src="{{ asset('assets/img/convenience.jpg') }}" alt="" class="w-88 h-96 lg:w-[38rem] lg:h-[40rem] object-cover rounded-lg">
                </div>
                <div class="lg:my-auto md:max-w-lg flex flex-col justify-center md:block">
                    <h2 class="text-2xl font-bold lg:mb-6 text-center md:text-left">Why you should partner with us</h2>
                    <ul class="list-desc mb-8 space-y-4">
                        <li class="flex flex-col">
                            <h3 class="font-bold text-xl text-primary-one underline">Increased Visibility:</h3>
                            <span class="font-bold">
                                Joining us allows you to showcase your restaurant to a wider audience of food enthusiasts. Benefit from increased visibility and exposure in your local community and beyond.
                            </span>
                        </li>
                        <li class="flex flex-col">
                            <h3 class="font-bold text-xl text-primary-one underline">Expand Your Customer Base:</h3>
                            <span class="font-bold">
                                Tap into a diverse customer base seeking a variety of dining options. Whether you specialize in fine dining, casual eats, or quick bites, Moboeats provides a platform to attract new customers and grow your business.
                            </span>
                        </li>
                        <li class="flex flex-col">
                            <h3 class="font-bold text-xl text-primary-one underline">Seamless Integration:</h3>
                            <span class="font-bold">
                                Our dedicated team will work closely with you to ensure a smooth onboarding process and provide ongoing support to maximize your success.
                            </span>
                        </li>
                    </ul>
                    <a href="https://restaurant.moboeats.com/register" class="hidden md:block bg-primary-two text-slate-900 hover:bg-primary-one hover:text-white px-6 py-4 rounded-lg text-center mt-2 lg:mt-20 font-semibold">Create you account here</a>
                </div>
            </div>

            <div class="mt-4 md:mt-24 px-4 lg:px-24 md:grid md:grid-cols-2 md:gap-2">
                <div class="lg:my-8 md:max-w-lg flex flex-col justify-center md:block">
                    <h2 class="hidden md:block text-2xl font-bold lg:mb-6 text-center md:text-left">Why you should partner with us</h2>
                    <ul class="list-desc mb-8 space-y-4">
                        <li class="flex flex-col">
                            <h3 class="font-bold text-xl text-primary-one underline">Flexible Ordering Options:</h3>
                            <span class="font-bold">
                                Offer customers the convenience of ordering from your restaurant. Whether they prefer dine-in, takeout, or scheduled/ instant delivery, our platform accommodates various ordering preferences, providing flexibility for both you and your customers.
                            </span>
                        </li>
                        <li class="flex flex-col">
                            <h3 class="font-bold text-xl text-primary-one underline">Marketing Support:</h3>
                            <span class="font-bold">
                                Take advantage of our marketing initiatives to promote your restaurant and attract more diners. Benefit from targeted promotions, featured listings, and other marketing opportunities to drive traffic and boost sales
                            </span>
                        </li>
                        <li class="flex flex-col">
                            <h3 class="font-bold text-xl text-primary-one underline">Multichannel Management:</h3>
                            <span class="font-bold">
                                Access various platforms seamlessly, from our Merchant app to a web-based management system. Streamline operations, update menus, and analyze performance easily with Moboeats' integrated approach.
                            </span>
                        </li>
                    </ul>
                    <a href="https://restaurant.moboeats.com/register" class="my-2 bg-primary-two text-slate-900 hover:bg-primary-one hover:text-white px-6 py-4 rounded-lg text-center mt-2 lg:mt-20 font-semibold">Create you account here</a>
                </div>
                <div class="">
                    <img src="{{ asset('assets/img/personalized.jpg') }}" alt="" class="w-88 h-96 lg:w-[38rem] lg:h-[40rem] object-cover rounded-lg">
                </div>
            </div>

            <div class="bg-[#eae1e1bf] px-4 lg:px-24 pb-4">
                <h1 class="text-3xl text-center my-3 py-2 font-semibold underline">Partner Dashboard</h1>
                <div class="flex gap-4">
                    <div class="space-y-4 grid grid-col-2 gap-2">
                        <div class="flex gap-2">
                            <div class="hidden md:block md:basis-1/2 lg:basis-3/5">
                                <img src="{{ asset('assets/img/Sign Up.png') }}" alt="" class="w-[24rem] lg:w-[44rem] object-cover rounded-lg">
                            </div>
                            <div class="flex flex-col my-auto md:basis-1/2 lg:basis-2/5">
                                <span class="font-bold text-2xl">Sign Up</span>
                                <span class="font-bold">Joining Moboeats is quick and easy. Simply <a href="https://restaurant.moboeats.com/signup" class="text-primary-one hover:p-1 hover:rounded-lg hover:bg-primary-two hover:text-slate-900 underline">Sign up</a> your business and provide necessary business documents needed.</span>
                            </div>
                        </div>
                        <div class="flex flex-row-reverse lg:flex-row gap-2 lg:gap-36">
                            <div class="flex flex-col my-auto md:basis-1/2 lg:basis-2/5 lg:px-8">
                                <span class="font-bold text-2xl">Menu Setup</span>
                                <span class="font-bold">Create and customize your menu to showcase your signature dishes, specials, and promotions. Add enticing descriptions, mouth-watering photos, and pricing to entice customers.</span>
                            </div>
                            <div class="hidden md:block md:basis-1/2 lg:basis-3/5">
                                <img src="{{ asset('assets/img/Menu Management.png') }}" alt="" class="w-[24rem] lg:w-[44rem] object-cover rounded-lg">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="hidden md:block md:basis-1/2 lg:basis-3/5">
                                <img src="{{ asset('assets/img/Order Management.png') }}" alt="" class="w-[24rem] lg:w-[44rem] object-cover rounded-lg">
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
                                <img src="{{ asset('assets/img/Multibranch Management.png') }}" alt="" class="w-[24rem] lg:w-[44rem] object-cover rounded-lg">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="hidden md:block md:basis-1/2 lg:basis-3/5">
                                <img src="{{ asset('assets/img/Partner Dashboard.png') }}" alt="" class="w-[24rem] lg:w-[44rem] object-cover rounded-lg">
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
            </div>

            <div class="px-4 lg:px-24 mt-10">
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
            </div>
            @include('layouts.footer')
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.0/flowbite.min.js"></script>
    </body>
</html>
