<?php

namespace App\Jobs;

use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $notification;
    public $data;

    /**
     * Create a new job instance.
     */
    public function __construct($user, string $notification, $data = null)
    {
        $this->user = $user;
        $this->notification = $notification;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(FCMService $fcmService)
    {
        if ($this->user->device_token) {
            try {
                $response = $fcmService->sendNotification(
                    $this->user->device_token,
                    config('app.name'),
                    $this->notification,
                    $this->data ?? []
                );

                info('FCM Response: ' . json_encode($response));

                return $response;
            } catch (\Exception $e) {
                info('FCM Error: ' . $e->getMessage());
            }
        }
    }
}
