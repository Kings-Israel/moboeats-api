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

        {{-- <section class="relative">
            <img src="{{ asset('assets/img/Moboeats Company Profile.jpg') }}" class="w-full object-cover" alt="">
            <div class="absolute inset-0">
                <div class="relative lg:px-24 4xl:px-48 px-4 lg:pb-32 sm:px-6 lg:flex lg:h-[820px] lg:items-center">
                    <div class="max-w-[160px] md:max-w-md lg:max-w-2xl text-white text-left">
                        <h1 class="text-md font-bold md:text-5xl mt-20 lg:mt-0">
                            Your one stop app for variety, quality and affordability.
                        </h1>
                    </div>
                </div>
            </div>
        </section> --}}
        <div id="default-carousel" class="relative w-full lg:-mt-8" data-carousel="slide">
            <!-- Carousel wrapper -->
            <div class="relative h-48 overflow-hidden rounded-lg md:h-96 lg:h-[820px]">
                <div class="hidden duration-700 ease-in-out relative" data-carousel-item>
                    <img src="{{ asset('assets/img/Homeslider photos (1).png') }}" class="object-cover" alt="...">
                    <div class="absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
                        <div class="inset-0">
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
                    <img src="{{ asset('assets/img/Homeslider photos (2).png') }}" class="object-cover" alt="...">
                    <div class="absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
                        <div class="inset-0">
                            <div class="relative lg:px-24 4xl:px-48 px-4 lg:pb-32 sm:px-6 lg:flex lg:items-center">
                                <div class="max-w-[160px] md:max-w-md lg:max-w-2xl text-black text-left">
                                    <h1 class="text-md font-bold md:text-5xl mt-20 lg:mt-0">
                                        Find and order from your favorite restaurants and convenient stores.
                                    </h1>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden duration-700 ease-in-out" data-carousel-item>
                    <img src="{{ asset('assets/img/Homeslider photos.png') }}" class="object-cover" alt="...">
                    <div class="absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
                        <div class="inset-0">
                            <div class="relative lg:px-24 4xl:px-48 px-4 lg:pb-32 sm:px-6 lg:flex lg:items-center">
                                <div class="max-w-[160px] md:max-w-md lg:max-w-2xl text-white text-left">
                                    <h1 class="text-md font-bold md:text-5xl mt-20 lg:mt-0">
                                        Get delivery with no hassle.
                                    </h1>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- <div class="hidden duration-700 ease-in-out" data-carousel-item>
                    <img src="{{ asset('assets/img/Homeslider photos.png') }}" class="absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2 object-cover" alt="...">
                </div>
                <div class="hidden duration-700 ease-in-out" data-carousel-item>
                    <img src="{{ asset('assets/img/Homeslider photos (1).png') }}" class="absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2 object-cover" alt="...">
                </div> --}}
            </div>
            <!-- Slider indicators -->
            <div class="absolute z-30 flex -translate-x-1/2 bottom-5 left-1/2 space-x-3 rtl:space-x-reverse">
                <button type="button" class="w-3 h-3 rounded-full" aria-current="true" aria-label="Slide 1" data-carousel-slide-to="0"></button>
                <button type="button" class="w-3 h-3 rounded-full" aria-current="false" aria-label="Slide 2" data-carousel-slide-to="1"></button>
                <button type="button" class="w-3 h-3 rounded-full" aria-current="false" aria-label="Slide 3" data-carousel-slide-to="2"></button>
                {{-- <button type="button" class="w-3 h-3 rounded-full" aria-current="false" aria-label="Slide 4" data-carousel-slide-to="3"></button>
                <button type="button" class="w-3 h-3 rounded-full" aria-current="false" aria-label="Slide 5" data-carousel-slide-to="4"></button> --}}
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

        <div class="mt-4 md:mt-24 px-4 lg:px-32 flex flex-col-reverse md:grid md:grid-cols-2 md:gap-4">
            <div class="flex gap-4">
                <div class="wow fadeInUp" data-wow-duration="1s" data-wow-delay=".5s">
                    <img src="{{ asset('assets/img/convenience-copy.png') }}" alt=""
                        class="w-[18rem] md:w-[18rem] lg:w-[24rem] mx-auto object-cover rounded-lg">
                </div>
                <div class="">
                    <img src="{{ asset('assets/img/quality-guaranteed.png') }}" alt=""
                        class="w-[18rem] md:w-[18rem] lg:w-[24rem] mx-auto object-cover rounded-lg">
                </div>
            </div>
            <div class="md:my-auto md:max-w-md flex flex-col justify-center md:block text-white">
                <div class="flex flex-col gap-4">
                    <div class="">
                        <h2 class="text-2xl font-bold lg:mt-12 lg:mb-6 text-center md:text-left">Convenience</h2>
                        <p class="font-semibold text-center md:text-left">With Mobo Eats you can shop anytime, anywhere,
                            and have your items delivered straight to your doorstep.</p>
                    </div>
                    <div class="">
                        <h2 class="text-2xl font-bold lg:mt-12 lg:mb-6 text-center md:text-left">Quality Guaranteed</h2>
                        <p class="font-semibold text-center md:text-left">We source our products from trusted merchants
                            to ensure freshness and quality with every purchase. From farm-fresh fruits and vegetables
                            to premium meals.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 md:mt-24 px-4 lg:px-24 md:grid md:grid-cols-2 md:gap-2">
            <div class="md:my-auto md:max-w-md flex flex-col justify-center md:block lg:ml-24 text-white">
                <div class="flex flex-col gap-4">
                    <div>
                        <h2 class="text-2xl font-bold lg:mt-12 lg:mb-6 text-center md:text-left">Flexible Delivery
                            Options</h2>
                        <p class="font-semibold text-center md:text-left">Choose from a range of delivery options to
                            suit your schedule. Whether you need your groceries ASAP or prefer to schedule a delivery
                            for later, we've got you covered.</p>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold lg:mt-12 lg:mb-6 text-center md:text-left">Personalized Experience
                        </h2>
                        <p class="font-semibold text-center md:text-left">Tailor your shopping experience with
                            personalized recommendations based on your preferences and past purchases.</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="">
                    <img src="{{ asset('assets/img/delivery-options.png') }}" alt=""
                        class="w-[18rem] md:w-[18rem] lg:w-[24rem] mx-auto object-cover rounded-lg">
                </div>
                <div class="">
                    <img src="{{ asset('assets/img/personalized-experience.png') }}" alt=""
                        class="w-[18rem] md:w-[18rem] lg:w-[24rem] mx-auto object-cover rounded-lg">
                </div>
            </div>
        </div>

        <div class="bg-white pb-8">
            <h1 class="text-3xl text-center my-5 pt-6 font-semibold">How it works</h1>
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

            <div class="px-8 mt-14 lg:px-28 lg:flex lg:gap-10">
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

        @include('layouts.footer')
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.0/flowbite.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    @if (config('app.env') == 'production')
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
            var Tawk_API = Tawk_API || {},
                Tawk_LoadStart = new Date();
            (function() {
                var s1 = document.createElement("script"),
                    s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src = 'https://embed.tawk.to/6612d15e1ec1082f04dfc429/1hqso3mv2';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
    @endif
    <!--End of Tawk.to Script-->
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
