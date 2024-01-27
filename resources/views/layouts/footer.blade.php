<!-- Footer -->

<footer class="footer">
    <section class="section bgc-dark">
        <div class="container rel-1">
            <a href="#top" class="scroll-top hvr-wobble-vertical js-target-scroll">
                <i class="fa fa-chevron-up"></i>
            </a>
            <div class="row-base row">
                <aside class="bottom-widget-posts col-footer col-base col-md-6 col-lg-8">
                    <h2 class="bottom-widget-title">News Post</h2>
                    <ul class="bottom-post-list">
                        <li>
                            <div class="media-body">
                                <h3><a href="#">Become a Partner</a></h3>
                                <p>Find out how to work with us.</p>
                            </div>
                        </li>
                    </ul>
                </aside>
                {{-- <aside class="bottom-widget-gallery col-footer col-base col-md-6 col-lg-4">
                    <h2 class="bottom-widget-title">Favorites Flickr</h2>
                    <ul class="bottom-gallery-list">
                        <li>
                            <div class="link-overlay">
                                <a href="#">
                                    <img alt="" src="{{ asset('assets/img/widget-gallery/1.jpg') }}">
                                    <i class="fa fa-unlink"></i>
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="link-overlay">
                                <a href="#">
                                    <img alt="" src="{{ asset('assets/img/widget-gallery/2.jpg') }}">
                                    <i class="fa fa-unlink"></i>
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="link-overlay">
                                <a href="#">
                                    <img alt="" src="{{ asset('assets/img/widget-gallery/3.jpg') }}">
                                    <i class="fa fa-unlink"></i>
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="link-overlay">
                                <a href="#">
                                    <img alt="" src="{{ asset('assets/img/widget-gallery/4.jpg') }}">
                                    <i class="fa fa-unlink"></i>
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="link-overlay">
                                <a href="#">
                                    <img alt="" src="{{ asset('assets/img/widget-gallery/5.jpg') }}">
                                    <i class="fa fa-unlink"></i>
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="link-overlay">
                                <a href="#">
                                    <img alt="" src="{{ asset('assets/img/widget-gallery/6.jpg') }}">
                                    <i class="fa fa-unlink"></i>
                                </a>
                            </div>
                        </li>
                    </ul>
                    <div class="widget-gallery-control">
                        <a href="#" class="more text-white">
                            <i class="fa fa-chevron-circle-right"></i>
                            <span>View more</span>
                        </a>
                    </div>
                </aside> --}}
                <aside class="bottom-widget-text col-footer col-base col-md-12 col-lg-4">
                    <h2 class="bottom-widget-title">About us</h2>
                    <img class="brand-white" alt="" style="width: 125px;" src="{{ asset('assets/img/mobo-logo.jpeg') }}">
                    <div class="social social-round">
                        <a href="#" class="fa fa-facebook"></a>
                        <a href="#" class="fa fa-twitter"></a>
                        <a href="#" class="fa fa-linkedin"></a>
                    </div>
                </aside>
            </div>
        </div>
    </section>
    <div class="footer-bottom">
        <div class="container">
            <div class="row-base row">
                <div class="copy col-base col-md-6">
                    © {{ now()->format('Y') }}. All rights reserved. <a href="">Moboeats</a>
                </div>
                <div class="col-base col-md-6">
                    <nav class="navbar-bottom">
                        <ul>
                            <li><a href="{{ route('about') }}">About</a></li>
                            <li><a href="#">Partners</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</footer>
