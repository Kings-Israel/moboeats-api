@extends('layouts.app')
@section('content')
<section id="contact" class="contact masked contact-2 section">
    <div class="container">
        <div class="row">
            <header class="text-center col-md-8 col-md-offset-2">
                <h2 class="text-white">Contact Us</h2>
            </header>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
                    <form class="js-ajax-form">
                        <div class="form-group">
                            <div class="form-control-layout">
                                <i class="fa fa-user"></i>
                                <input type="text" class="form-control" name="name"
                                    placeholder="Name">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-control-layout">
                                <i class="fa fa-envelope-o"></i>
                                <input type="text" class="form-control" name="email" required
                                    placeholder="Email address *">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-control-layout">
                                <i class="fa fa-phone"></i>
                                <input type="text" class="form-control" name="phone"
                                    placeholder="Phone number">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-control-layout">
                                <i class="fa fa-pencil"></i>
                                <textarea class="form-control" name="message" required placeholder="Write message *"></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn-yellow-2 btn-lg btn-block btn">
                                <span class="text">Send message</span>
                                <span class="flip-front">Send message</span>
                                <span class="flip-back">Submit</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
