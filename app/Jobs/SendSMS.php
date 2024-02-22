<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $receiver, public string $message)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.voodoo.API_KEY')
        ])
        ->post(config('services.voodoo.BASE_URL').'/sendsms', [
            'to' => $this->receiver,
            'from' => 'VoodooSMS',
            'msg' => $this->message,
            'sandbox' => true
        ]);

        info($response);
    }
}
