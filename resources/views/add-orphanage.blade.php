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

            <h3 class="lg:flex lg:gap-6 px-2 lg:px-44 mt-4 text-black text-3xl font-extrabold py-1">Add Orphanage</h3>
            <form action="{{ route('web.orphanage.store') }}" method="post" enctype="multipart/form-data">
                <div class="block lg:flex lg:gap-6 px-2 lg:px-44 mt-4">
                    <div class="lg:basis-1/2 py-1">
                        @csrf
                        <div class="flex flex-col">
                            <label class="text-black text-md font-bold">Name</label>
                            <input name="name" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-black text-md font-bold">Email Address</label>
                            <input name="email" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-black text-md font-bold">Phone Number</label>
                            <input type="tel" name="phone_number" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-black text-md font-bold">Contact Person's Name</label>
                            <input type="text" name="contact_name" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-black text-md font-bold">Contact Person's Email</label>
                            <input type="email" name="contact_email" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-black text-md font-bold">Contact Person's Phone Number</label>
                            <input type="tel" name="contact_phone_number" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-black text-md font-bold">Contact Person's Email</label>
                            <input type="email" name="contact_email" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
                        </div>
                    </div>
                    <div class="lg:basis-1/2 py-1">
                        <div class="flex flex-col">
                            <label class="text-black text-md font-bold">Logo</label>
                            <input type="file" accept=".jpg,.png" name="logo" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-black text-md font-bold">Location</label>
                            <input id="place_id" type="hidden" name="place_id">
                            <input type="text" name="delivery_location" id="pac-input" placeholder="Start typing here to search for location" class="border-2 px-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10">
                            <div id="gmap_markers" class="gmap block h-[96%]"></div>
                            <input name="location_lat" id="location_lat" class="hidden" />
                            <input name="location_long" id="location_long" class="hidden" />
                            <input name="location" id="location" class="hidden" />
                        </div>
                    </div>
                </div>
                <div class="flex justify-end py-2 px-2 lg:px-44">
                    <button type="submit" class="bg-primary-one mb-8 rounded-lg text-white px-4 py-1 font-bold tracking-wider w-full lg:w-1/4">Submit</button>
                </div>
            </form>
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
        <script>
            function initMap() {
                var map = new google.maps.Map(document.getElementById('gmap_markers'), {
                    center: {lat: -1.270104, lng: 36.80814},
                    zoom: 3
                });
                var input = document.getElementById('pac-input');

                var autocomplete = new google.maps.places.Autocomplete(input);
                autocomplete.bindTo('bounds', map);

                var infowindow = new google.maps.InfoWindow();

                autocomplete.addListener('place_changed', function() {
                    infowindow.close();
                    var place = autocomplete.getPlace();

                    if (!place.geometry) {
                        window.alert("Autocomplete's returned place contains no geometry");
                        return;
                    }

                    document.getElementById('place_id').value = place.place_id
                    document.getElementById('location_lat').value = place.geometry.location.lat()
                    document.getElementById('location_long').value = place.geometry.location.lng()
                    document.getElementById('location').value = place.formatted_address
                });
            }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.map.key') }}&libraries=places&callback=initMap" async defer></script>
    </body>
</html>
