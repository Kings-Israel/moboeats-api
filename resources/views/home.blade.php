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
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&family=Titillium+Web:wght@400;900&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-outfit">
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
                            Discover the Moboeats Advantage
                        </h1>
                    </div>
                </div>
            </section>

            <div class="mt-4 md:mt-24 px-4 lg:px-24 flex flex-col-reverse md:grid md:grid-cols-2 md:gap-2">
                <div class="">
                    <img src="{{ asset('assets/img/convenience.jpg') }}" alt="" class="w-[38rem] h-[20rem] object-cover rounded-lg">
                </div>
                <div class="lg:my-auto md:max-w-md flex flex-col justify-center md:block">
                    <h2 class="text-2xl font-bold lg:mt-12 lg:mb-6 text-center md:text-left">Convenience</h2>
                    <p class="font-semibold text-center md:text-left">With Moboeats you can shop anytime, anywhere, and have your items delivered straight to your doorstep.</p>
                </div>
            </div>

            <div class="mt-4 md:mt-24 px-4 lg:px-24 md:grid md:grid-cols-2 md:gap-2">
                <div class="lg:my-auto md:max-w-md flex flex-col justify-center md:block">
                    <h2 class="text-2xl font-bold lg:mt-12 lg:mb-6 text-center md:text-left">Quality Guaranteed</h2>
                    <p class="font-semibold text-center md:text-left">We source our products from trusted merchants to ensure freshness and quality with every purchase. From farm-fresh fruits and vegetables to premium meals.</p>
                </div>
                <div class="">
                    <img src="{{ asset('assets/img/quality.jpg') }}" alt="" class="w-[38rem] h-[20rem] object-cover rounded-lg">
                </div>
            </div>

            <div class="mt-4 md:mt-24 px-4 lg:px-24 flex flex-col-reverse md:grid md:grid-cols-2 md:gap-2">
                <div class="">
                    <img src="{{ asset('assets/img/personalized.jpg') }}" alt="" class="w-[38rem] h-[20rem] object-cover rounded-lg">
                </div>
                <div class="lg:my-auto md:max-w-md flex flex-col justify-center md:block">
                    <h2 class="text-2xl font-bold lg:mt-12 lg:mb-6 text-center md:text-left">Personalized Experience</h2>
                    <p class="font-semibold text-center md:text-left">Tailor your shopping experience with personalized recommendations based on your preferences and past purchases.</p>
                </div>
            </div>

            <div class="mt-4 md:mt-24 px-4 lg:px-24 md:grid md:grid-cols-2 md:gap-2">
                <div class="lg:my-auto md:max-w-md flex flex-col justify-center md:block">
                    <h2 class="text-2xl font-bold lg:mt-12 lg:mb-6 text-center md:text-left">Flexible Delivery Options</h2>
                    <p class="font-semibold text-center md:text-left">Choose from a range of delivery options to suit your schedule. Whether you need your groceries ASAP or prefer to schedule a delivery for later, we've got you covered.</p>
                </div>
                <div class="">
                    <img src="{{ asset('assets/img/delivery.jpg') }}" alt="" class="w-[38rem] h-[20rem] object-cover rounded-lg">
                </div>
            </div>

            <div class="bg-[#eae1e1bf] lg:h-[52rem]">
                <h1 class="text-3xl text-center my-5 pt-6 font-semibold">How it works</h1>
                <div class="flex flex-nowrap gap-4 overflow-x-auto px-4 md:px-24 4xl:px-72 no-scrollbar">
                    <div class="flex-shrink-0 my-4 relative">
                        <img src="{{ asset('assets/img/app-img.jpg') }}" class="h-96 w-[20rem] 4xl:w-[20rem] object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-white absolute bottom-8 left-2">
                            <h5 class="text-xl font-bold">Browse & Select</h5>
                            <p class="mb-6 text-sm whitespace-normal font-semibold px-2">Easily find what you need by browsing and adding items to your cart with just a few taps.</p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <img src="{{ asset('assets/img/app-img.jpg') }}" class="w-[20rem] 4xl:w-[20rem] h-96 object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-white absolute bottom-8 left-2 4xl:left-10">
                            <h5 class="text-xl font-bold">Secure Checkout</h5>
                            <p class="mb-6 text-sm whitespace-normal font-semibold px-2">Review your order, choose your delivery time, and securely check out using our encrypted payment gateway</p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <img src="{{ asset('assets/img/app-img.jpg') }}" class="w-[20rem] 4xl:w-[20rem] h-96 object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-white absolute bottom-8 left-2 4xl:left-10">
                            <h5 class="text-xl font-bold">Track Your Delivery</h5>
                            <p class="mb-6 text-sm whitespace-normal font-semibold px-2">Track the status of your delivery in real-time and receive updates every step of the way.</p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 my-4 relative">
                        <img src="{{ asset('assets/img/app-img.jpg') }}" class="w-[20rem] 4xl:w-[20rem] h-96 object-cover my-4 rounded-lg" alt="">
                        <div class="text-center text-white absolute bottom-8 left-2 4xl:left-10">
                            <h5 class="text-xl font-bold">Enjoy Freshness</h5>
                            <p class="mb-6 text-sm whitespace-normal font-semibold px-2"> Receive your meals and groceries fresh and ready to use. </p>
                        </div>
                    </div>
                </div>

                <div class="px-8 mb-12 lg:px-44">
                    <h1 class="text-3xl text-center my-2 py-2 font-semibold">Have any Complains</h1>
                    <div class="flex justify-center">
                        {{-- <video class="h-96 rounded-lg" controls>
                            <source src="">
                        </video> --}}
                        <span class="font-bold text-xl">
                            If you encounter any issues or have feedback regarding your shopping experience, we're here to help. To make a complaint, please visit our website and navigate to the <a href="{{ route('contact-us') }}" class="text-primary-one underline">Contact Us</a> section. You can also reach out to our customer support team directly via email or phone. Your satisfaction is our priority, and we're committed to addressing your concerns promptly and effectively.
                        </span>
                    </div>
                </div>
            </div>

            @include('layouts.footer')
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.0/flowbite.min.js"></script>
    </body>
</html>
