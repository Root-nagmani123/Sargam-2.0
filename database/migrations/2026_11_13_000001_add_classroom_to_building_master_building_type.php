<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * COE Building Master saves building_type = 'Classroom' (BuildingMaster::TYPE_CLASSROOM),
 * but the legacy ENUM had no such value, so every save failed with SQLSTATE 1265
 * "Data truncated for column 'building_type'".
 *
 * building_master has no create migration, so the value list is read from the live
 * column and 'Classroom' appended: values another environment added are kept, and
 * an environment that already has 'Classroom' is left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $values = $this->enumValues();
        if ($values === null || in_array('Classroom', $values, true)) {
            return;
        }
        $this->setEnum([...$values, 'Classroom']);
    }

    public function down(): void
    {
        $values = $this->enumValues();
        // Removing the value would blank every Classroom row, so only when none use it.
        if ($values === null || ! in_array('Classroom', $values, true)
            || DB::table('building_master')->where('building_type', 'Classroom')->exists()) {
            return;
        }
        $this->setEnum(array_values(array_diff($values, ['Classroom'])));
    }

    /** @return list<string>|null the column's ENUM values, or null if it is not an ENUM */
    private function enumValues(): ?array
    {
        $col = DB::selectOne("SHOW COLUMNS FROM building_master WHERE Field = 'building_type'");
        if (! $col || ! preg_match('/^enum\((.*)\)$/i', $col->Type, $m)) {
            return null;
        }
        preg_match_all("/'((?:[^']|'')*)'/", $m[1], $v);

        return array_map(fn ($s) => str_replace("''", "'", $s), $v[1]);
    }

    /** @param list<string> $values */
    private function setEnum(array $values): void
    {
        $col = DB::selectOne("SHOW COLUMNS FROM building_master WHERE Field = 'building_type'");
        $pdo = DB::getPdo();
        $list = implode(',', array_map(fn ($v) => $pdo->quote($v), $values));
        $null = $col->Null === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $col->Default === null ? ($col->Null === 'YES' ? ' DEFAULT NULL' : '') : ' DEFAULT ' . $pdo->quote($col->Default);

        DB::statement("ALTER TABLE building_master MODIFY building_type ENUM($list) $null$default");
    }
};
