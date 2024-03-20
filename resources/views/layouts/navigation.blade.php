<div class="sticky top-0 bg-primary-one flex lg:flex-wrap lg:items-center justify-between mx-auto py-1 lg:px-24 md:px-2 z-40 duration-200" id="main-header">
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
            <li class="font-bold text-white @if(request()->routeIs('home')) active @endif">
                <a href="{{ url('/') }}">Home</a>
            </li>
            <li class="font-bold text-white @if(request()->routeIs('partners')) active @endif">
                <a href="{{ route('partners') }}">
                    Merchants
                </a>
            </li>
            <li class="font-bold text-white @if(request()->routeIs('contact-us')) active @endif">
                <a href="{{ route('contact-us') }}">
                    Contact us
                </a>
            </li>
            <li>
                <a href="{{ route('login') }}" class="">
                    <x-primary-button class="py-2 bg-primary-two">Sign In</x-primary-button>
                </a>
            </li>
        </ul>
    </div>

    <div class="hidden lg:block my-auto partners">
        <ul class="flex gap-16">
            <li class="font-bold text-white @if(request()->routeIs('home')) active @endif">
                <a href="{{ url('/') }}">Home</a>
            </li>
            <li class="font-bold text-white @if(request()->routeIs('partners')) active @endif">
                <a href="{{ route('partners') }}">
                    Merchants
                </a>
            </li>
            <li class="font-bold text-white @if(request()->routeIs('contact-us')) active @endif">
                <a href="{{ route('contact-us') }}">
                    Contact us
                </a>
            </li>
        </ul>
    </div>
    <div class="flex space-x-2">
        @guest
            <a href="https://restaurant.moboeats.com/signup" class="my-auto">
                <x-primary-button class="py-2 bg-primary-two">Sign In</x-primary-button>
            </a>
        @endguest
    </div>
</div>
