<!-- Header -->
<header id="top">
    <div class="navbar navbar-2 affix">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a href="{{ route('home') }}" class="brand js-target-scroll">
                    <img class="brand-white" alt="" src="{{ asset('assets/img/mobo-logo.jpeg') }}">
                    <img class="brand-dark" alt="" src="{{ asset('assets/img/mobo-logo.jpeg') }}">
                </a>
            </div>
            <div class="collapse navbar-collapse" id="navbar-collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li class="@if(request()->routeIs('home')) active @endif">
                        <a href="{{ route('home') }}" class="js-target-scroll">Home</a>
                    </li>
                    <li class="@if(request()->routeIs('about')) active @endif">
                        <a href="{{ route('about') }}" class="js-target-scroll">About Us</a>
                    </li>
                    <li class="@if(request()->routeIs('contact-us')) active @endif">
                        <a href="{{ route('contact-us') }}" class="js-target-scroll">Contact Us</a>
                    </li>
                    {{-- <li class="@if(request()->routeIs('services')) active @endif">
                        <a href="{{ route('services') }}" class="js-target-scroll">Our Services</a>
                    </li>
                    <li class="@if(request()->routeIs('developer-hub')) active @endif">
                        <a href="{{ route('developer-hub') }}" class="js-target-scroll">Developer Hub</a>
                    </li>
                    <li class="@if(request()->routeIs('careers')) active @endif">
                        <a href="{{ route('careers') }}" class="js-target-scroll">Career</a>
                    </li>
                    <li class="@if(request()->routeIs('industries')) active @endif">
                        <a href="{{ route('industries') }}" class="js-target-scroll">Industries</a>
                    </li>

                    <li class="@if(request()->routeIs('login')) active @endif">
                        <a href="{{ route('home') }}" class="js-target-scroll"><i class="fa fa-sign-in" style="font-size: 24px" title="Login"></i></a>
                    </li> --}}
                </ul>
            </div>
        </div>
    </div>
</header>
