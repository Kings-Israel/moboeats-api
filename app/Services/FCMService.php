<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendNotification(string $deviceToken, string $title, string $body, array $data = []): array
    {
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        $response = $this->messaging->send($message);

        return ['success' => true, 'response' => $response];
    }

    public function sendToMultipleTokens(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        $report = $this->messaging->sendMulticast($message, $deviceTokens);

        return [
            'successes' => $report->successes()->count(),
            'failures' => $report->failures()->count(),
            'invalid_tokens' => array_map(
                fn($failure) => $failure->target()->value(),
                $report->invalidTokens(),
            ),
        ];
    }
}
