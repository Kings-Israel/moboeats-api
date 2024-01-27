@extends('layouts.app')
@section('content')
@section('css')
<style>
    h3 {
        font-size: 50px;
    }
    .masthead-entry-3 {
        background-image: url("/assets/img/nyegi-Tzu3Qsww1tQ-unsplash.jpg") !important;
    }
</style>
@endsection
<div class="masthead">
    <div class="masthead-entry-3 text-right parallax" data-stellar-background-ratio="0.3">
        <div class="inner rel-1">
            <div class="container">
                <h3 class="wow fadeIn">The Moboeats Journey:</h3>
                <h1 class="wow fadeInUp">Get to Know us better. </h1>
            </div>
            <div class="entry-line"></div>
        </div>
    </div>
</div>
<!-- Main -->
<main class="main">
    <section id="clients" class="clients bgc-light section">
        <div class="container">
            <div class="row">
                <header class="text-center col-md-8 col-md-offset-2">
                    <h2>The Team</h2>
                    <div class="delimiter-2"><img alt="" src="{{ asset('assets/img/delimiter.png') }}"></div>
                </header>
            </div>
            <div class="section-body">
                <div class="clients-carousel">
                    <div class="client">
                        <a href=""><img alt="" src="{{ asset('assets/img/bg/two.jpg') }}"></a>
                    </div>
                    <div class="client">
                        <a href=""><img alt="" src="{{ asset('assets/img/bg/two.jpg') }}"></a>
                    </div>
                    <div class="client">
                        <a href=""><img alt="" src="{{ asset('assets/img/bg/two.jpg') }}"></a>
                    </div>
                    <div class="client">
                        <a href=""><img alt="" src="{{ asset('assets/img/bg/two.jpg') }}"></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
