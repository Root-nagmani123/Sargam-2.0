<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LBSNAA SMS via Gupshup (document path). Does not use the legacy Twilio SmsService.
 *
 * Drivers:
 * - log     → write message to log (local test without credentials)
 * - gupshup → GET enterprise.smsgupshup.com/GatewayAPI/rest
 */
class GupshupSmsService
{
    public function driver(): string
    {
        return strtolower((string) config('gupshup.driver', 'log'));
    }

    public function isConfigured(): bool
    {
        if ($this->driver() === 'log') {
            return true;
        }

        return $this->driver() === 'gupshup'
            && filled(config('gupshup.userid'))
            && filled(config('gupshup.password'))
            && filled(config('gupshup.mask'))
            && filled(config('gupshup.principal_entity_id'));
    }

    /**
     * Send one SMS. Best-effort callers may ignore false.
     *
     * @param  string  $to  10-digit or 91XXXXXXXXXX (normalized internally)
     * @param  string|null  $dltTemplateId  Required for gupshup driver (per DLT template)
     */
    public function send(string $to, string $message, ?string $dltTemplateId = null): bool
    {
        $message = trim($message);
        $sendTo = $this->normalizeSendTo($to);

        if ($sendTo === '' || $message === '') {
            return false;
        }

        if (!$this->isConfigured()) {
            Log::error('GupshupSmsService: driver is not configured.', [
                'driver' => $this->driver(),
            ]);
            return false;
        }

        return match ($this->driver()) {
            'log' => $this->sendViaLog($sendTo, $message, $dltTemplateId),
            'gupshup' => $this->sendViaGupshup($sendTo, $message, $dltTemplateId),
            default => $this->unsupportedDriver(),
        };
    }

    /**
     * Render a named template from config/gupshup.php and send it.
     *
     * @param  array<string, string|int|float|null>  $replacements
     */
    public function sendTemplate(string $templateKey, string $to, array $replacements = []): bool
    {
        $template = config("gupshup.templates.{$templateKey}");
        if (!is_array($template) || empty($template['body'])) {
            Log::error('GupshupSmsService: unknown template.', ['template' => $templateKey]);
            return false;
        }

        $body = (string) $template['body'];
        foreach ($replacements as $key => $value) {
            $body = str_replace('{'.$key.'}', (string) ($value ?? ''), $body);
        }

        $dltTemplateId = isset($template['dlt_template_id'])
            ? trim((string) $template['dlt_template_id'])
            : '';

        return $this->send($to, $body, $dltTemplateId !== '' ? $dltTemplateId : null);
    }

    /**
     * India mobile → 91XXXXXXXXXX (no +, no spaces).
     */
    public function normalizeSendTo(string $to): string
    {
        $digits = preg_replace('/\D+/', '', $to) ?? '';

        if (strlen($digits) === 10) {
            return '91'.$digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '91'.substr($digits, 1);
        }

        return $digits;
    }

    protected function sendViaLog(string $sendTo, string $message, ?string $dltTemplateId): bool
    {
        Log::info('GupshupSmsService [log driver] SMS captured (not sent).', [
            'send_to' => $sendTo,
            'dlt_template_id' => $dltTemplateId,
            'msg' => $this->redactForLog($message),
        ]);

        return true;
    }

    protected function sendViaGupshup(string $sendTo, string $message, ?string $dltTemplateId): bool
    {
        if ($dltTemplateId === null || trim($dltTemplateId) === '') {
            Log::error('GupshupSmsService: dltTemplateId is required for gupshup driver.');
            return false;
        }

        try {
            // Same as sample Python: GET with query params (not WhatsApp mediaapi URL).
            $response = Http::timeout(30)->get((string) config('gupshup.base_url'), [
                'method' => 'sendMessage',
                'send_to' => $sendTo,
                'msg' => $message,
                'msg_type' => 'TEXT',
                'userid' => config('gupshup.userid'),
                'auth_scheme' => 'plain',
                'password' => config('gupshup.password'),
                'v' => '1.1',
                'format' => 'text',
                'mask' => config('gupshup.mask'),
                'principalEntityId' => config('gupshup.principal_entity_id'),
                'dltTemplateId' => $dltTemplateId,
            ]);

            $body = trim((string) $response->body());

            Log::info('GupshupSmsService: SMS gateway response.', [
                'send_to' => $sendTo,
                'dlt_template_id' => $dltTemplateId,
                'msg' => $this->redactForLog($message),
                'http_status' => $response->status(),
                'response' => $body,
            ]);

            if ($response->failed()) {
                Log::error('GupshupSmsService: HTTP failure.', [
                    'send_to' => $sendTo,
                    'status' => $response->status(),
                    'body' => $body,
                ]);
                return false;
            }

            // Gupshup text format: "success | <mobile> | <id>" OR error text on HTTP 200.
            $accepted = str_starts_with(strtolower($body), 'success');
            if (!$accepted) {
                Log::error('GupshupSmsService: gateway did not accept SMS.', [
                    'send_to' => $sendTo,
                    'response' => $body,
                ]);
            }

            return $accepted;
        } catch (\Throwable $e) {
            Log::error('GupshupSmsService: send failed.', [
                'send_to' => $sendTo,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mask 6-digit OTPs before a rendered message body is written to any log.
     * Passwords are never included in outbound message bodies (see
     * FcNotifyService::credentialsCreated()); this only guards the OTP flows.
     */
    protected function redactForLog(string $message): string
    {
        return preg_replace('/\b\d{6}\b/', '[REDACTED]', $message) ?? $message;
    }

    protected function unsupportedDriver(): bool
    {
        Log::error('GupshupSmsService: unsupported SMS_DRIVER.', [
            'driver' => $this->driver(),
        ]);

        return false;
    }
}
