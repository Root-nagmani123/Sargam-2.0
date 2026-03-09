<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Helper to resolve Security ID-card master/config mapping.
 *
 * Tables:
 * - sec_id_cardno_master (card types like LBSNAA/CPWD/...)
 * - sec_id_cardno_config_map (sub-type mapping per card + p/c)
 */
class IdCardSecurityLookup
{
    /** Normalize for loose matching (case/space/hyphen insensitive). */
    public static function norm(?string $value): string
    {
        $v = strtolower(trim((string) $value));
        // keep only a-z0-9 for robust comparisons
        return preg_replace('/[^a-z0-9]+/i', '', $v) ?? '';
    }

    /**
     * Resolve sec_id_cardno_master.pk by sec_card_name using normalized match.
     */
    public static function resolveCardMasterPk(string $cardName): ?int
    {
        $needle = self::norm($cardName);
        if ($needle === '') {
            return null;
        }

        $rows = DB::table('sec_id_cardno_master')
            ->select(['pk', 'sec_card_name'])
            ->get();

        foreach ($rows as $r) {
            if (self::norm($r->sec_card_name) === $needle) {
                return (int) $r->pk;
            }
        }

        return null;
    }

    /**
     * Resolve config mapping row for given card_name ('p'/'c'), card master pk,
     * and sub-type label provided from UI.
     *
     * Returns object: { map_pk, config_pk, config_name } or null.
     */
    public static function resolveConfigMapRow(string $cardNameCode, int $cardMasterPk, string $subTypeLabel): ?object
    {
        $needle = self::norm($subTypeLabel);
        if ($needle === '') {
            return null;
        }

        $rows = DB::table('sec_id_cardno_config_map')
            ->select(['pk', 'sec_id_cardno_config_pk', 'config_name'])
            ->where('card_name', $cardNameCode)
            ->where('sec_id_cardno_master', $cardMasterPk)
            ->get();

        $candidates = [];
        foreach ($rows as $r) {
            $cfg = (string) ($r->config_name ?? '');
            $cfgNorm = self::norm($cfg);
            if ($cfgNorm === '') {
                continue;
            }
            if ($cfgNorm === $needle) {
                return (object) [
                    'map_pk' => (int) $r->pk,
                    'config_pk' => (int) $r->sec_id_cardno_config_pk,
                    'config_name' => $cfg,
                ];
            }
            if (str_contains($cfgNorm, $needle) || str_contains($needle, $cfgNorm)) {
                $candidates[] = $r;
            }
        }

        if (!empty($candidates)) {
            $r = $candidates[0];
            return (object) [
                'map_pk' => (int) $r->pk,
                'config_pk' => (int) $r->sec_id_cardno_config_pk,
                'config_name' => (string) ($r->config_name ?? ''),
            ];
        }

        return null;
    }
}

