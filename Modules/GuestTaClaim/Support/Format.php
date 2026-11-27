<?php

namespace Modules\GuestTaClaim\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class Format
{
    /** Indian digit grouping: 1234567 -> ₹12,34,567 */
    public static function money(float|int|string|null $amount): string
    {
        $n = (int) round((float) $amount);
        $neg = $n < 0;
        $s = (string) abs($n);
        if (strlen($s) > 3) {
            $last3 = substr($s, -3);
            $rest = substr($s, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $s = $rest.','.$last3;
        }

        return ($neg ? '-' : '').'₹'.$s;
    }

    public static function date(CarbonInterface|string|null $d): string
    {
        return $d ? Carbon::parse($d)->format('d-m-Y') : '';
    }

    public static function dateTime(CarbonInterface|string|null $d): string
    {
        return $d ? Carbon::parse($d)->format('d-m-Y H:i') : '';
    }

    public static function initials(?string $name): string
    {
        $name = trim($name ?? '');

        if ($name === '') {
            return 'NA';
        }

        $name = preg_replace('/^(Dr\.|Shri|Smt\.|Ms\.|Mr\.|Prof\.)\s+/i', '', $name);

        $parts = preg_split('/\s+/', $name) ?: [];

        return strtoupper(
            implode('', array_map(
                fn ($w) => mb_substr($w, 0, 1),
                array_slice($parts, 0, 2)
            ))
        ) ?: 'NA';
    }

    public static function css(): string
    {
        return (string) file_get_contents(dirname(__DIR__).'/Resources/assets/css/gtc.css');
    }

    public static function js(): string
    {
        return (string) file_get_contents(dirname(__DIR__).'/Resources/assets/js/gtc.js');
    }
}
