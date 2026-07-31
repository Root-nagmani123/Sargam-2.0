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

    /** Replacement keys whose values must never appear in plaintext in any log. */
    protected const SENSITIVE_REPLACEMENT_KEYS = ['Password'];

    /**
     * Send one SMS. Best-effort callers may ignore false.
     *
     * @param  string  $to  10-digit or 91XXXXXXXXXX (normalized internally)
     * @param  string|null  $dltTemplateId  Required for gupshup driver (per DLT template)
     * @param  array<int, string>  $sensitiveValues  Literal values (e.g. a real password) to
     *         redact from the message before it is ever written to a log, regardless of the
     *         surrounding template wording.
     */
    public function send(string $to, string $message, ?string $dltTemplateId = null, array $sensitiveValues = []): bool
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
            'log' => $this->sendViaLog($sendTo, $message, $dltTemplateId, $sensitiveValues),
            'gupshup' => $this->sendViaGupshup($sendTo, $message, $dltTemplateId, $sensitiveValues),
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

        $maxLengths = is_array($template['max_lengths'] ?? null) ? $template['max_lengths'] : [];

        $body = (string) $template['body'];
        $sensitiveValues = [];
        foreach ($replacements as $key => $value) {
            $value = (string) ($value ?? '');

            if (isset($maxLengths[$key])) {
                $value = $this->fitToDltFieldWidth($templateKey, $key, $value, (int) $maxLengths[$key]);
            }

            if ($value !== '' && in_array($key, self::SENSITIVE_REPLACEMENT_KEYS, true)) {
                $sensitiveValues[] = $value;
            }

            $body = str_replace('{'.$key.'}', $value, $body);
        }

        $dltTemplateId = isset($template['dlt_template_id'])
            ? trim((string) $template['dlt_template_id'])
            : '';

        return $this->send($to, $body, $dltTemplateId !== '' ? $dltTemplateId : null, $sensitiveValues);
    }

    /**
     * Truncate a placeholder value to its DLT-registered tag width (e.g. #alp# = 40
     * chars). URL-shaped values are never truncated — a cut-off link is broken and
     * useless, so an oversized URL is sent as-is and logged rather than mangled.
     */
    protected function fitToDltFieldWidth(string $templateKey, string $placeholder, string $value, int $maxLength): string
    {
        if ($maxLength <= 0 || mb_strlen($value) <= $maxLength) {
            return $value;
        }

        if (preg_match('#^https?://#i', $value)) {
            Log::warning('GupshupSmsService: URL placeholder exceeds DLT field width, sending untruncated.', [
                'template' => $templateKey,
                'placeholder' => $placeholder,
                'length' => mb_strlen($value),
                'max_length' => $maxLength,
            ]);

            return $value;
        }

        Log::warning('GupshupSmsService: placeholder truncated to fit DLT field width.', [
            'template' => $templateKey,
            'placeholder' => $placeholder,
            'original_length' => mb_strlen($value),
            'max_length' => $maxLength,
        ]);

        return mb_substr($value, 0, $maxLength);
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

    /**
     * @param  array<int, string>  $sensitiveValues
     */
    protected function sendViaLog(string $sendTo, string $message, ?string $dltTemplateId, array $sensitiveValues = []): bool
    {
        Log::info('GupshupSmsService [log driver] SMS captured (not sent).', [
            'send_to' => $sendTo,
            'dlt_template_id' => $dltTemplateId,
            'msg' => $this->redactForLog($message, $sensitiveValues),
        ]);

        return true;
    }

    /**
     * @param  array<int, string>  $sensitiveValues
     */
    protected function sendViaGupshup(string $sendTo, string $message, ?string $dltTemplateId, array $sensitiveValues = []): bool
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
                'msg' => $this->redactForLog($message, $sensitiveValues),
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
     * Mask 6-digit OTPs and any known-sensitive replacement values (e.g. the
     * credentials-created password) before a rendered message body is written to any log.
     *
     * $sensitiveValues are redacted by their literal value, not by guessing a "Password:"
     * label in the surrounding template text — a wording change in the DLT-approved template
     * (subject, label text, punctuation) can never cause a real password to slip through.
     *
     * @param  array<int, string>  $sensitiveValues
     */
    protected function redactForLog(string $message, array $sensitiveValues = []): string
    {
        foreach ($sensitiveValues as $value) {
            if ($value !== '') {
                $message = str_replace($value, '[REDACTED]', $message);
            }
        }

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
