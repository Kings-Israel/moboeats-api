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
    public function __construct(public string $receiver, public string $message, public string $country)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->country == 'GB') {
            $api_key = config('services.voodoo.API_KEY');

            $msg = json_encode(
                [
                    'to' => $this->receiver,
                    'from' => "Mobo Eats",
                    'msg' => $this->message,
                ]
            );

            $ch = curl_init('https://api.voodoosms.com/sendsms');

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: ' . $api_key
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            curl_close($ch);
        } else {
            $msg =
                [
                    'apiClientID' => config('services.bongasms.BONGA_CLIENT_ID'),
                    'key' => config('services.bongasms.BONGA_API_KEY'),
                    'secret' => config('services.bongasms.BONGA_API_SECRET'),
                    'MSISDN' => $this->receiver,
                    'txtMessage' => $this->message,
                    'serviceID' => config('services.bongasms.BONGA_SERVICE_ID')
                ];

            // $ch = curl_init(config('services.bongasms.BONGA_BASE_URL'));

            // curl_setopt($ch, CURLOPT_POST, true);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // $response = curl_exec($ch);

            $response = Http::asForm()->post(config('services.bongasms.BONGA_BASE_URL'), $msg);
        }

        info($response);
    }
}
