<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <style>
        .loader-brand img {
            width: 50% !important;
        }
    </style>
    @yield('css')
</head>
<body>
    <!-- Loader -->
    <div class="loader">
        <div class="loader-brand"><img alt="" class="img-responsive center-block" src="{{ asset('assets/img/mobo-logo.jpeg') }}"></div>
    </div>

    @include('layouts.nav')

    @yield('content')
    @include('layouts.footer')

    <!-- Scripts -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/smoothscroll.js') }}"></script>
    <script src="{{ asset('assets/js/wow.min.js') }}"></script>
    <script src="{{ asset('assets/js/imagesloaded.pkgd.js') }}"></script>
    <script src="{{ asset('assets/js/isotope.pkgd.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('assets/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.stellar.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.easypiechart.min.js') }}"></script>
    <script src="{{ asset('assets/js/interface.js') }}"></script>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp.js"></script>
    <script src="{{ asset('assets/js/gmap.js') }}"></script>

    @stack('scripts')
</body>
</html>
