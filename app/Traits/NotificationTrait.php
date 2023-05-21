<?php

namespace App\Traits;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\General;

trait NotificationTrait
{
    public function sendEmail($data){
       $data = array(
            'content' => $data['message'],
            'subject' => $data['subject'],
            'receiver' => $data['receiver'],
        );
        Mail::to($data['receiver'])->send(new General($data));
    }
}
