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
                        class="relative lg:px-24 4xl:px-48 px-4 pb-32 sm:px-6 lg:flex lg:h-[390px] lg:items-center"
                    >
                </div>
            </section>

            <div class="px-2 lg:px-24 p-4">
                <div class="grid md:grid-cols-2 gap-4 px-4 lg:px-44">
                    <div class="border-2 border-primary-one p-2 rounded-md">
                        <h4 class="font-extrabold text-xl">Customer Support</h4>
                        <h5 class="font-bold">For assistance with orders, deliveries, or any other inquiries, our dedicated customer support is here to help.</h5>
                        <div class="flex flex-col">
                            <span class="font-bold">Email: <a href="mailto:support@moboeats.com" class="underline">support@moboeats.com</a></span>
                            <span class="font-bold">Phone: <a href="mailto:support@moboeats.com" class="">+44 345 454 5565</a></span>
                        </div>
                    </div>
                    <div class="border-2 border-primary-one p-2 rounded-md">
                        <h4 class="font-extrabold text-xl">Business Inquiries</h4>
                        <h5 class="font-bold">Interested in partnering with us or have questions about our services for restaurants? Get in touch with our business development team</h5>
                        <div class="flex flex-col">
                            <span class="font-bold">Email: <a href="mailto:support@moboeats.com" class="underline">sales@moboeats.com</a></span>
                            <span class="font-bold">Phone: <a href="mailto:support@moboeats.com" class="">+44 345 454 5565</a></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-2 lg:px-24">
                <form action="{{ route('submit.contact-us') }}" method="post">
                    @csrf
                    <div class="px-4 lg:px-44 md:grid md:grid-cols-2 gap-3 my-4">
                        <div class="flex flex-col">
                            <label class="text-black text-md font-bold">Full Name</label>
                            <input name="fullname" class="border-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-black text-md font-bold">Email Address</label>
                            <input name="email" class="border-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
                        </div>
                        <div class="md:col-span-2 flex flex-col">
                            <label class="text-black text-md font-bold">Subject</label>
                            <input name="subject" class="border-2 border-gray-300 dark:border-gray-300 dark:text-dark bg-slate-200 focus:border-gray-400 dark:focus:border-gray-400 focus:ring-gray-400 dark:focus:ring-gray-400 rounded-md shadow-sm h-10" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-black text-md font-bold">Message</label>
                            <textarea name="message" id="" class="w-full bg-gray-200 border-0 rounded-md" rows="5"></textarea>
                        </div>
                        <div class="col-span-3 flex justify-end">
                            <button type="submit" class="bg-primary-one mb-8 rounded-lg text-white px-4 py-1 font-bold tracking-wider">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
            @include('layouts.footer')
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.0/flowbite.min.js"></script>
    </body>
</html>
