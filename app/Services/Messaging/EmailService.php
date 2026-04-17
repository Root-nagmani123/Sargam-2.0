<?php

namespace App\Services\Messaging;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    protected string $defaultSubject;

    public function __construct(string $defaultSubject = 'Sargam Notification')
    {
        $this->defaultSubject = $defaultSubject;
    }

    /**
     * @param \Illuminate\Support\Collection<string> $emails
     * @param string $message
     * @return array<string>
     */
    public function sendBulk(Collection $emails, string $message): array
    {
        $failed = [];

        $emails
            ->map(fn ($email) => is_string($email) ? trim($email) : (string) $email)
            ->filter(fn ($email) => $email !== '')
            ->unique()
            ->each(function ($email) use ($message, &$failed) {
                try {
                    Mail::raw($message, function ($mail) use ($email) {
                        $mail->to($email)->subject($this->defaultSubject);
                    });
                } catch (\Throwable $exception) {
                    $failed[] = $email;
                    Log::error('EmailService: failed to send email.', [
                        'email' => $email,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });

        return $failed;
    }
}


