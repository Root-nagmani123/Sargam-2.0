<?php

namespace App\Services\Messaging;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $accountSid;
    protected string $authToken;
    protected string $fromNumber;

    public function __construct()
    {
        $this->accountSid = (string) config('services.twilio.sid');
        $this->authToken = (string) config('services.twilio.token');
        $this->fromNumber = (string) config('services.twilio.from');
    }

    public function isConfigured(): bool
    {
        return !empty($this->accountSid) && !empty($this->authToken) && !empty($this->fromNumber);
    }

    /**
     * @param \Illuminate\Support\Collection<string> $recipients
     * @param string $message
     * @return array<string>
     */
    public function sendBulk(Collection $recipients, string $message): array
    {
        $failed = [];

        if (!$this->isConfigured()) {
            Log::error('SmsService: Twilio credentials are missing.');
            return $recipients->filter()->unique()->values()->all();
        }

        $recipients->filter()
            ->unique()
            ->each(function (string $recipient) use ($message, &$failed) {
                try {
                    $this->sendSingle($recipient, $message);
                } catch (\Throwable $exception) {
                    $failed[] = $recipient;
                    Log::error('SmsService: failed to send SMS.', [
                        'recipient' => $recipient,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });

        return $failed;
    }

    /**
     * @throws \RuntimeException
     */
    protected function sendSingle(string $to, string $message): void
    {
        $endpoint = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $this->accountSid);

        $response = Http::withBasicAuth($this->accountSid, $this->authToken)
            ->asForm()
            ->post($endpoint, [
                'From' => $this->fromNumber,
                'To' => $to,
                'Body' => $message,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($response->json('message') ?? $response->body());
        }
    }
}


