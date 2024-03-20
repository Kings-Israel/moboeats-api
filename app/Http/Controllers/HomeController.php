<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function contactSubmit(Request $request)
    {
        toastr()->success('', 'Thanks for contacting us. We\'ll get back to you soon');

        return back();
    }
}
