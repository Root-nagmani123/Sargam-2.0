<?php

namespace App\Services\FC;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Transliterates Latin text (e.g. an English name) to Hindi / Devanagari using the
 * free Google Input Tools endpoint — the same engine behind Google's transliteration
 * widget, which handles Indian names well.
 *
 * Best-effort by design: any network/parse failure returns null so callers degrade
 * gracefully (the field is simply left for the user to type manually).
 */
class HindiTransliterationService
{
    private const ENDPOINT = 'https://inputtools.google.com/request';

    /** @var array<string, string|null> per-request memo so repeated names don't re-hit the API. */
    private array $cache = [];

    /**
     * @return string|null  Devanagari transliteration, or null when unavailable.
     */
    public function toHindi(string $text): ?string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', $text));
        if ($text === '') {
            return null;
        }

        if (array_key_exists($text, $this->cache)) {
            return $this->cache[$text];
        }

        try {
            $response = Http::timeout(4)->get(self::ENDPOINT, [
                'text' => $text,
                'itc'  => 'hi-t-i0-und',
                'num'  => 1,
                'cp'   => 0,
                'cs'   => 1,
                'ie'   => 'utf-8',
                'oe'   => 'utf-8',
            ]);

            if ($response->ok()) {
                $data = $response->json();
                // Shape: ["SUCCESS", [["<input>", ["<hindi>"], [], {...}]]]
                if (is_array($data) && ($data[0] ?? null) === 'SUCCESS') {
                    $hindi = $data[1][0][1][0] ?? null;
                    if (is_string($hindi) && trim($hindi) !== '') {
                        return $this->cache[$text] = trim($hindi);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Hindi transliteration failed: '.$e->getMessage(), ['text' => $text]);
        }

        return $this->cache[$text] = null;
    }
}
