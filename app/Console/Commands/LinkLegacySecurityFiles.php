<?php

namespace App\Console\Commands;

use App\Support\SecurityFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Link legacy security rows to the files copied from the E:\security archive.
 *
 * The archive is copied unchanged onto the security disk (config/security_files.php):
 *   contractual/, DuplicateCardRequest/, familyCard/   folder = a key column of the row,
 *                                                       resolved at read time — report only;
 *   permanent/, VechicalDoc/                            folder = a random number no table
 *                                                       records — linked here.
 *
 * For permanent and vehicle rows the stored bare file name is looked up across the whole
 * category tree. A match is linked only when it is unambiguous: exactly one file has that
 * name, and every row that names it belongs to the same owner (employee). Everything else
 * is written to the manifest as missing / ambiguous for a person to resolve.
 *
 * Dry run by default; --apply rewrites the column to the matched relative path, and only
 * while it still holds the value the manifest was built from.
 */
class LinkLegacySecurityFiles extends Command
{
    protected $signature = 'security:link-legacy-files
                            {--apply : Write matched paths to the database (default: dry run)}
                            {--category=* : permanent, vehicle, contractual, duplicate, family (default: all)}
                            {--manifest= : CSV path (default: storage/app/security-link-manifest-<time>.csv)}';

    protected $description = 'Build a manifest of legacy security files and link permanent/vehicle rows to them';

    /**
     * category => table => [owner column, file columns]. Owner = whose file it is, used to
     * refuse one archive file being linked to two different people.
     */
    private const LINKED = [
        'permanent' => [
            'security_parm_id_apply' => ['employee_master_pk', ['id_photo_path', 'joining_letter_path']],
            'security_parm_oth_id_apply' => ['id_card_no', ['id_photo_path', 'doc_path']],
        ],
        'vehicle' => [
            'vehicle_pass_tw_apply' => ['emp_master_pk', ['doc_upload']],
            'vehicle_pass_fw_apply' => ['emp_master_pk', ['doc_upload']],
            'vehicle_pass_duplicate_apply_twfw' => ['emp_master_pk', ['doc_upload', 'fir_doc', 'paid_recepit']],
        ],
    ];

    /** Categories resolved at read time by SecurityFiles::forRow(): checked and reported only. */
    private const REPORTED = [
        'contractual' => ['security_con_oth_id_apply' => ['id_photo_path', 'doc_path', 'aadhar_doc']],
        'duplicate' => [
            'security_dup_other_id_apply' => ['id_photo_path', 'aadhar_doc', 'fir_doc', 'payment_receipt', 'service_ext'],
            'security_dup_perm_id_apply' => ['id_photo_path', 'fir_doc', 'payment_receipt', 'service_ext'],
        ],
        'family' => ['security_family_id_apply' => ['id_photo_path', 'family_photo', 'dup_doc']],
    ];

    private const MAX_PATH = 255;

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $categories = $this->option('category') ?: array_merge(array_keys(self::LINKED), array_keys(self::REPORTED));
        $unknown = array_diff($categories, array_keys(self::LINKED), array_keys(self::REPORTED));
        if ($unknown !== []) {
            $this->error('Unknown category: ' . implode(', ', $unknown));

            return self::FAILURE;
        }

        $manifestPath = $this->option('manifest') ?: storage_path('app/security-link-manifest-' . date('Ymd-His') . '.csv');
        $out = fopen($manifestPath, 'w');
        if ($out === false) {
            $this->error("Cannot write manifest: {$manifestPath}");

            return self::FAILURE;
        }
        fputcsv($out, ['category', 'table', 'pk', 'column', 'stored_value', 'status', 'path', 'bytes', 'note']);

        $this->info(($apply ? '' : '[DRY RUN] ') . 'Security disk root: ' . config('filesystems.disks.' . config('security_files.disk', 'security') . '.root'));
        $totals = [];

        foreach ($categories as $category) {
            $counts = isset(self::LINKED[$category])
                ? $this->linkCategory($category, $apply, $out)
                : $this->reportCategory($category, $out);
            foreach ($counts as $status => $n) {
                $totals["{$category}:{$status}"] = $n;
            }
        }
        fclose($out);

        $this->table(['category:status', 'rows'], collect($totals)->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info("Manifest: {$manifestPath}");
        if (! $apply) {
            $this->comment('Dry run — nothing written. Review the manifest, then re-run with --apply.');
        }

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    private function linkCategory(string $category, bool $apply, $out): array
    {
        $index = $this->indexCategory($category);
        $counts = [];

        // Pass 1: candidate per (row, column); collect who claims each archive file.
        $candidates = [];
        $claims = [];
        foreach (self::LINKED[$category] as $table => [$ownerCol, $columns]) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $columns = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
            $select = array_unique(array_merge(['pk', $ownerCol], $columns, $table === 'security_parm_id_apply' ? ['employee_master_pk'] : []));
            foreach (DB::table($table)->select($select)->orderBy('pk')->cursor() as $row) {
                foreach ($columns as $column) {
                    $value = trim((string) ($row->{$column} ?? ''));
                    if ($value === '' || str_contains($value, '/') || str_contains($value, '\\')) {
                        continue; // empty, or already a path written by the application
                    }
                    $matches = $index[mb_strtolower($value)] ?? [];
                    $note = '';
                    // Legacy permanent photos were also saved as <employee_master_pk>.<ext>.
                    if ($matches === [] && $column === 'id_photo_path' && ! empty($row->employee_master_pk)) {
                        foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
                            $matches = array_merge($matches, $index[mb_strtolower($row->employee_master_pk . '.' . $ext)] ?? []);
                        }
                        $note = $matches !== [] ? 'matched by employee_master_pk file name' : '';
                    }
                    $owner = trim((string) ($row->{$ownerCol} ?? ''));
                    $candidates[] = compact('table', 'column', 'value', 'matches', 'note', 'owner') + ['pk' => $row->pk];
                    if (count($matches) === 1) {
                        $claims[$matches[0]][$owner === '' ? "{$table}#{$row->pk}" : $owner] = true;
                    }
                }
            }
        }

        // Pass 2: decide, write the manifest, apply.
        foreach ($candidates as $c) {
            $path = '';
            $note = $c['note'];
            if ($c['matches'] === []) {
                $status = 'missing';
            } elseif (count($c['matches']) > 1) {
                $status = 'ambiguous';
                $note = count($c['matches']) . ' files share this name: ' . implode(' | ', array_slice($c['matches'], 0, 5));
            } elseif (count($claims[$c['matches'][0]]) > 1) {
                $status = 'ambiguous';
                $path = $c['matches'][0];
                $note = 'file is named by rows of ' . count($claims[$c['matches'][0]]) . ' different owners';
            } elseif (strlen($c['matches'][0]) > self::MAX_PATH) {
                $status = 'too_long';
                $path = $c['matches'][0];
            } else {
                $status = 'matched';
                $path = $c['matches'][0];
            }

            if ($status === 'matched' && $apply) {
                $updated = DB::table($c['table'])
                    ->where('pk', $c['pk'])
                    ->where($c['column'], $c['value'])
                    ->update([$c['column'] => $path]);
                $status = $updated ? 'linked' : 'changed_since_scan';
            }

            $counts[$status] = ($counts[$status] ?? 0) + 1;
            fputcsv($out, [
                $category, $c['table'], $c['pk'], $c['column'], $c['value'], $status, $path,
                $path !== '' ? (@filesize((string) SecurityFiles::refToAbsolute('security:' . $path)) ?: '') : '', $note,
            ]);
        }

        return $counts;
    }

    /** @return array<string, int> */
    private function reportCategory(string $category, $out): array
    {
        $counts = [];
        foreach (self::REPORTED[$category] as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $columns = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
            $keys = collect(SecurityFiles::LEGACY_FOLDERS[$table] ?? [])->pluck(1)->filter(fn ($c) => Schema::hasColumn($table, $c))->all();
            foreach (DB::table($table)->select(array_unique(array_merge(['pk'], $keys, $columns)))->orderBy('pk')->cursor() as $row) {
                foreach ($columns as $column) {
                    $value = trim((string) ($row->{$column} ?? ''));
                    if ($value === '') {
                        continue;
                    }
                    $ref = SecurityFiles::locate(SecurityFiles::forRow($table, $row, $column));
                    $status = $ref !== null ? 'resolved' : 'missing';
                    $counts[$status] = ($counts[$status] ?? 0) + 1;
                    fputcsv($out, [$category, $table, $row->pk, $column, $value, $status, $ref ?? '', '', 'resolved at read time; not written']);
                }
            }
        }

        return $counts;
    }

    /**
     * lower-cased file name => relative paths, for every file under the category folder.
     * Partial downloads (.filepart / .opdownload) are left out. Walked natively: Flysystem
     * rejects some legacy names (see SecurityFiles::refToAbsolute()).
     *
     * @return array<string, list<string>>
     */
    private function indexCategory(string $category): array
    {
        $folder = SecurityFiles::folderName($category);
        $base = SecurityFiles::refToAbsolute('security:' . $folder);
        $index = [];
        $n = 0;
        if ($base === null || ! is_dir($base)) {
            $this->warn("  {$folder}/ not found on the security disk — copy the archive first.");

            return [];
        }
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (! $file->isFile() || preg_match('/\.(filepart|opdownload)$/i', $file->getFilename())) {
                continue;
            }
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($base) + 1));
            $index[mb_strtolower($file->getFilename())][] = $folder . '/' . $relative;
            $n++;
        }
        $this->line("  indexed {$n} files under {$folder}/");

        return $index;
    }
}
