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
        <div class="bg-white">

            <section
                class="relative bg-home-hero bg-cover bg-center bg-no-repeat"
            >
                <div
                    class="absolute inset-0"
                ></div>
                    <div
                        class="relative lg:px-24 4xl:px-48 px-4 pb-32 sm:px-6 lg:flex lg:h-[390px] lg:items-center"
                    >
                </div>
            </section>

            <div class="flex justify-center flex-col">
                <h4 class="mt-4 text-black text-4xl text-center font-extrabold py-4">The Orphanage {{ $orpanage->name }} has been added successfully</h4>
                <div class="flex justify-center">
                    <a href="{{ route('web.orphanage.create') }}" class="text-center bg-primary-one mb-8 rounded-lg text-white px-4 py-1 font-bold tracking-wider w-full lg:w-1/4">Click Here to add</a>
                </div>
            </div>
            @include('layouts.footer')
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.0/flowbite.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

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
