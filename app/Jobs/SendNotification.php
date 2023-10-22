<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $notification;
    public $data;

    /**
     * Create a new job instance.
     */
    public function __construct($user, string $notification, $data = NULL)
    {
        $this->user = $user;
        $this->notification = $notification;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->user->device_token) {
            $response = Http::withHeaders([
                    'Authorization' => 'key='.config('services.firebase.key'),
                    'Content-Type' => 'application/json'
                ])->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $this->user->device_token,
                    'notification' => [
                        'title' => config('app.name'),
                        'body' => $this->notification
                    ],
                    'data' => $this->data
                ]);

            info($response);
        }
    }
}
