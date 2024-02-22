<?php

namespace App\Jobs;

use App\Mail\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCommunication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $type, public string $receipient, public string $sender = '', public array $content = array())
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        switch ($this->type) {
            case 'mail':
                if ($this->sender == 'ResetPassword') {
                    Mail::to($this->receipient)->send(new ResetPassword($this->content['code']));
                }
                break;
            case 'sms':
                SendSMS::dispatch($this->receipient, 'Your verification code is: '.$this->content['code']);
                break;
            default:
                # code...
                break;
        }
    }
}
