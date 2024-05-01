<?php

namespace App\Http\Controllers;

use App\Mail\NewMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class HomeController extends Controller
{
    public function contactSubmit(Request $request)
    {
        $request->validate([
            'message' => ['required']
        ],[
            'message.required' => 'Enter a message'
        ]);

        toastr()->success('', 'Thanks for contacting us. We\'ll get back to you soon');

        Mail::to('info@moboeats.com')->send(new NewMessage($request->name, $request->email, $request->message));

        return back();
    }
}
