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

        <section class="">
            <img src="{{ asset('assets/img/Moboeats Company Profile (1).jpeg') }}" class="w-full object-cover"
                alt="">
        </section>

        <div class="mt-4 md:mt-24 px-4 lg:px-32 flex flex-col-reverse md:grid md:grid-cols-2 md:gap-4">
            <div class="flex gap-4">
                <div class="wow fadeInUp" data-wow-duration="1s" data-wow-delay=".5s">
                    <img src="{{ asset('assets/img/convenience.png') }}" alt=""
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

        <div class="bg-white lg:h-[52rem]">
            <h1 class="text-3xl text-center my-5 pt-6 font-semibold">How it works</h1>
            <div class="flex flex-nowrap gap-4 overflow-x-auto px-4 md:px-24 4xl:px-72 no-scrollbar">
                <div class="flex-shrink-0 my-4">
                    {{-- <img src="{{ asset('assets/img/app-img.jpg') }}" class="h-96 w-[20rem] 4xl:w-[20rem] object-cover my-4 rounded-lg" alt=""> --}}
                    <?xml version="1.0" encoding="utf-8"?>
                    <!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->
                    <svg class="mx-auto" width="140px" height="140px" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_429_10987)">
                            <path
                                d="M4 4.00104H20V18.001C20 19.1056 19.1046 20.001 18 20.001H6C4.89543 20.001 4 19.1056 4 18.001V4.00104Z"
                                stroke="#292929" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M12.5 17V7L10.5 9" stroke="#292929" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </g>
                        <defs>
                            <clipPath id="clip0_429_10987">
                                <rect width="24" height="24" fill="white" />
                            </clipPath>
                        </defs>
                    </svg>
                    <div class="text-center text-black max-w-xs">
                        <h5 class="text-3xl font-black">Browse & Select</h5>
                        <p class="mb-6 text-sm whitespace-normal font-semibold px-2">Easily find what you need by
                            browsing and adding items to your cart with just a few taps.</p>
                    </div>
                </div>
                <div class="flex-shrink-0 my-4">
                    {{-- <img src="{{ asset('assets/img/app-img.jpg') }}" class="w-[20rem] 4xl:w-[20rem] h-96 object-cover my-4 rounded-lg" alt=""> --}}
                    <?xml version="1.0" encoding="utf-8"?>
                    <!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->
                    <svg class="mx-auto" width="140px" height="140px" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_429_11264)">
                            <path
                                d="M4 4.00104H20V18.001C20 19.1056 19.1046 20.001 18 20.001H6C4.89543 20.001 4 19.1056 4 18.001V4.00104Z"
                                stroke="#292929" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path
                                d="M9.50012 9.49997C9.50013 8.86017 9.7442 8.22037 10.2324 7.73221C11.2087 6.7559 12.7916 6.7559 13.7679 7.73221C14.7442 8.70852 14.7442 10.2914 13.7679 11.2677L9.93946 15.0962C9.65816 15.3775 9.50012 15.759 9.50012 16.1568L9.50012 17H14.5001"
                                stroke="#292929" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        </g>
                        <defs>
                            <clipPath id="clip0_429_11264">
                                <rect width="24" height="24" fill="white" />
                            </clipPath>
                        </defs>
                    </svg>
                    <div class="text-center text-black max-w-xs">
                        <h5 class="text-3xl font-black">Secure Checkout</h5>
                        <p class="mb-6 text-sm whitespace-normal font-semibold px-2">Review your order, choose your
                            delivery time, and securely check out using our encrypted payment gateway</p>
                    </div>
                </div>
                <div class="flex-shrink-0 my-4">
                    {{-- <img src="{{ asset('assets/img/app-img.jpg') }}" class="w-[20rem] 4xl:w-[20rem] h-96 object-cover my-4 rounded-lg" alt=""> --}}
                    <?xml version="1.0" encoding="utf-8"?>

                    <!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->
                    <svg class="mx-auto" width="140px" height="140px" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_429_11144)">
                            <path
                                d="M4 4.00104H20V18.001C20 19.1056 19.1046 20.001 18 20.001H6C4.89543 20.001 4 19.1056 4 18.001V4.00104Z"
                                stroke="#292929" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path
                                d="M10 16.2361C10.5308 16.7111 11.2316 17 12 17C13.6569 17 15 15.6569 15 14C15 12.3431 13.6569 11 12 11L15 7H10"
                                stroke="#292929" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </g>
                        <defs>
                            <clipPath id="clip0_429_11144">
                                <rect width="24" height="24" fill="white" />
                            </clipPath>
                        </defs>
                    </svg>
                    <div class="text-center text-black max-w-xs">
                        <h5 class="text-3xl font-black">Track Your Delivery</h5>
                        <p class="mb-6 text-sm whitespace-normal font-semibold px-2">Track the status of your delivery
                            in real-time and receive updates every step of the way.</p>
                    </div>
                </div>
                <div class="flex-shrink-0 my-4">
                    {{-- <img src="{{ asset('assets/img/app-img.jpg') }}" class="w-[20rem] 4xl:w-[20rem] h-96 object-cover my-4 rounded-lg" alt=""> --}}
                    <?xml version="1.0" encoding="utf-8"?>
                    <!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->
                    <svg class="mx-auto" width="140px" height="140px" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_429_10985)">
                            <path
                                d="M4 4.00104H20V18.001C20 19.1056 19.1046 20.001 18 20.001H6C4.89543 20.001 4 19.1056 4 18.001V4.00104Z"
                                stroke="#292929" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path
                                d="M10.5 7L9.22841 11.4506C8.86337 12.7282 9.8227 14 11.1515 14H14.5M14.5 14V10M14.5 14V17"
                                stroke="#292929" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </g>
                        <defs>
                            <clipPath id="clip0_429_10985">
                                <rect width="24" height="24" fill="white" />
                            </clipPath>
                        </defs>
                    </svg>
                    <div class="text-center text-black max-w-xs">
                        <h5 class="text-3xl font-black">Enjoy Freshness</h5>
                        <p class="mb-6 text-sm whitespace-normal font-semibold px-2"> Receive your meals and groceries
                            fresh and ready to use. </p>
                    </div>
                </div>
            </div>

            <div class="px-8 mt-14 lg:px-44">
                <div>
                    <?xml version="1.0" encoding="utf-8"?>

                    <!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->
                    <svg class="mx-auto" width="180px" height="180px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_429_11087)">
                    <path d="M4 4.00098H20V18.001C20 19.1055 19.1046 20.001 18 20.001H6C4.89543 20.001 4 19.1055 4 18.001V4.00098Z" stroke="#292929" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="12" y="8" width="0.01" height="0.01" stroke="#292929" stroke-width="3.75" stroke-linejoin="round"/>
                    <path d="M12 12V16" stroke="#292929" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </g>
                    <defs>
                    <clipPath id="clip0_429_11087">
                    <rect width="24" height="24" fill="white"/>
                    </clipPath>
                    </defs>
                    </svg>
                </div>
                <h1 class="text-3xl text-center my-2 py-2 font-semibold">Have any Complaint</h1>
                <div class="flex justify-center">
                    {{-- <video class="h-96 rounded-lg" controls>
                            <source src="">
                        </video> --}}
                    <span class="font-bold text-xl">
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
