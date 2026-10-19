<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Storage and lookup for security-module files (ID cards, family cards, vehicle passes).
 *
 * Layout and folder names: config/security_files.php. Two kinds of stored value exist:
 *  - a relative path ("contractual/1000290590/1776539572_ab12cd.jpg", or a pre-layout
 *    "idcard/photos/<hash>.jpg" on the public disk);
 *  - a bare file name from the legacy system ("vikash kumar0001.jpg"), whose folder is
 *    derived from a key column of the same row (LEGACY_FOLDERS).
 *
 * Lookups return a "ref" — "<disk>:<path>" — for a file that exists, or null. Views turn
 * a ref into a link with security_file_url(), which goes through the authenticated
 * admin.security.file route; the files are never exposed as a static directory.
 */
class SecurityFiles
{
    /**
     * table => list of [category, key column]: where a bare file name lives. Tables listed
     * here store only the file name, as the legacy system did; storeForRow() writes into
     * the first folder whose key is set, and forRow() looks in each in order.
     */
    public const LEGACY_FOLDERS = [
        'security_con_oth_id_apply' => [['contractual', 'photo_path'], ['duplicate', 'id_card_no']],
        // emp_id_apply (DUP/DUO number) covers a request raised without a card number.
        'security_dup_other_id_apply' => [['duplicate', 'id_card_no'], ['duplicate', 'emp_id_apply']],
        'security_dup_perm_id_apply' => [['duplicate', 'id_card_no'], ['duplicate', 'emp_id_apply']],
        'security_family_id_apply' => [['family', 'emp_id_apply']],
    ];

    /** column => public-disk directory that pre-layout code saved bare names into. */
    public const LEGACY_PUBLIC_DIRS = [
        'id_photo_path' => 'idcard/photos',
        'doc_path' => 'idcard/documents',
        'joining_letter_path' => 'idcard/joining_letters',
        'fir_doc' => 'idcard/dup_docs',
        'payment_receipt' => 'idcard/dup_docs',
        'service_ext' => 'idcard/dup_docs',
        'aadhar_doc' => 'idcard/dup_docs',
        'name_change_doc' => 'idcard/dup_docs',
    ];

    /** @var array<string, string|false> per-request memo: ref => ref as on disk, false = missing */
    private static array $exists = [];

    public static function disk(): Filesystem
    {
        return Storage::disk(config('security_files.disk', 'security'));
    }

    public static function folderName(string $category): string
    {
        $folders = config('security_files.folders', []);
        if (! isset($folders[$category])) {
            throw new \InvalidArgumentException("Unknown security file category [{$category}].");
        }

        return $folders[$category];
    }

    /**
     * Folder for a record: "<Category>/<key>". A null/unsafe key gets a fresh random
     * 10-digit number (the legacy scheme for contractual/permanent/vehicle folders).
     */
    public static function folder(string $category, int|string|null $key = null): string
    {
        $key = self::safeSegment($key);
        if ($key === null) {
            return self::newFolder($category);
        }

        return self::folderName($category) . '/' . $key;
    }

    /** A new, not-yet-used "<Category>/<random 10-digit number>" folder. */
    public static function newFolder(string $category): string
    {
        $base = self::folderName($category);
        do {
            $folder = $base . '/' . random_int(1000000000, 9999999999);
        } while (self::disk()->exists($folder));

        return $folder;
    }

    /**
     * The folder a record already uses, taken from any of its stored paths that sit under
     * $category; otherwise folder($category, $key). Keeps a record's files together on edit.
     *
     * @param  list<string|null>  $storedPaths
     */
    public static function folderOf(array $storedPaths, string $category, int|string|null $key = null): string
    {
        $prefix = self::folderName($category) . '/';
        foreach ($storedPaths as $stored) {
            $stored = self::normalisePath((string) $stored);
            if ($stored !== null && str_starts_with($stored, $prefix) && substr_count($stored, '/') >= 2) {
                return dirname($stored);
            }
        }

        return self::folder($category, $key);
    }

    /** The numeric part of a folder made by newFolder(), e.g. for security_con_oth_id_apply.photo_path. */
    public static function folderKey(string $folder): string
    {
        return basename($folder);
    }

    /**
     * Store an upload in $folder as "<timestamp>_<random>.<ext>" and return its relative path,
     * which is what the table column should hold.
     */
    public static function store(UploadedFile $file, string $folder): string
    {
        $name = time() . '_' . Str::lower(Str::random(6)) . '.' . safe_upload_extension($file);
        $path = $file->storeAs($folder, $name, config('security_files.disk', 'security'));
        if ($path === false) {
            throw new \RuntimeException('Could not store the uploaded file.');
        }

        return $path;
    }

    /**
     * Store an upload for a row of a LEGACY_FOLDERS table and return what the column should
     * hold: the bare file name (legacy format, fits the varchar(50) document columns), in the
     * folder of the first set key — e.g. DuplicateCardRequest/<id_card_no>/. $keys holds the
     * row's key columns (the row itself, or an array before it is inserted). With no usable
     * key the file goes to a new folder and the relative path is returned instead.
     *
     * @param  object|array<string, mixed>  $keys
     */
    public static function storeForRow(UploadedFile $file, string $table, object|array $keys): string
    {
        $keys = (object) $keys;
        foreach (self::LEGACY_FOLDERS[$table] ?? [] as [$category, $keyColumn]) {
            $key = self::safeSegment($keys->{$keyColumn} ?? null);
            if ($key !== null) {
                return basename(self::store($file, self::folderName($category) . '/' . $key));
            }
        }
        $category = self::LEGACY_FOLDERS[$table][0][0] ?? throw new \InvalidArgumentException("No legacy folder for [{$table}].");

        return self::store($file, self::newFolder($category));
    }

    /**
     * Ref for the file in $row->$column of $table. When no file is found the stored value is
     * returned unchanged (null if empty), so views can still say "No file available in storage"
     * — security_file_exists() is false for it. The row must carry the key columns listed in
     * LEGACY_FOLDERS for bare legacy names.
     */
    public static function forRow(string $table, ?object $row, string $column): ?string
    {
        if ($row === null) {
            return null;
        }
        $value = $row->{$column} ?? null;
        $dirs = [];
        foreach (self::LEGACY_FOLDERS[$table] ?? [] as [$category, $keyColumn]) {
            $key = self::safeSegment($row->{$keyColumn} ?? null);
            if ($key !== null) {
                $dirs[] = 'security:' . self::folderName($category) . '/' . $key;
            }
        }
        if (isset(self::LEGACY_PUBLIC_DIRS[$column])) {
            $dirs[] = config('security_files.legacy_public_disk', 'public') . ':' . self::LEGACY_PUBLIC_DIRS[$column];
        }
        $value = trim((string) $value);

        return self::locate($value, $dirs) ?? ($value !== '' ? $value : null);
    }

    /**
     * Ref for a stored value. A ref passes through (if it still exists); a relative path is
     * looked up on the security disk, then the legacy public disk; a bare name is looked up
     * in each "<disk>:<dir>" of $dirs in order.
     *
     * @param  list<string>  $dirs
     */
    public static function locate(?string $value, array $dirs = []): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (self::parseRef($value) !== null) {
            return self::existingRef($value);
        }

        $path = self::normalisePath($value);
        if ($path === null) {
            return null;
        }

        $candidates = [];
        if (str_contains($path, '/')) {
            $candidates[] = 'security:' . $path;
            $candidates[] = config('security_files.legacy_public_disk', 'public') . ':' . $path;
        } else {
            foreach ($dirs as $dir) {
                $candidates[] = rtrim($dir, '/') . '/' . $path;
            }
        }

        foreach ($candidates as $ref) {
            if (($found = self::existingRef($ref)) !== null) {
                return $found;
            }
        }

        return null;
    }

    /** True when $refOrPath resolves to an existing file. */
    public static function exists(?string $refOrPath): bool
    {
        return self::locate($refOrPath) !== null;
    }

    /** Authenticated URL for a ref or relative path, or null when there is no such file. */
    public static function url(?string $refOrPath): ?string
    {
        $ref = self::locate($refOrPath);

        return $ref === null ? null : route('admin.security.file', ['f' => Crypt::encryptString($ref)]);
    }

    /** Absolute filesystem path of an existing file (ref or relative path), or null. */
    public static function absolutePath(?string $refOrPath): ?string
    {
        $ref = self::locate($refOrPath);

        return $ref === null ? null : self::refToAbsolute($ref);
    }

    /** Delete a stored file (ref or relative path). Missing files are ignored. */
    public static function delete(?string $refOrPath): void
    {
        $ref = self::locate($refOrPath);
        $absolute = $ref === null ? null : self::refToAbsolute($ref);
        if ($absolute !== null) {
            @unlink($absolute);
            unset(self::$exists[$ref]);
        }
    }

    /**
     * Delete the file in $row->$column, but only one this application wrote: a pre-layout
     * public-disk upload or a "<timestamp>_<rand>.<ext>" name from store(). Files copied from
     * the E:\security archive keep their original names and are never removed here.
     */
    public static function deleteForRow(string $table, ?object $row, string $column): void
    {
        $ref = self::locate(self::forRow($table, $row, $column));
        if ($ref !== null && (str_starts_with($ref, config('security_files.legacy_public_disk', 'public') . ':')
            || preg_match('/^\d{10}_[a-z0-9]{6}\.[a-z0-9]+$/', basename($ref)))) {
            self::delete($ref);
        }
    }

    /**
     * Absolute path for a ref, or null when the ref is not allowed. Native paths rather than
     * Flysystem: legacy archive names contain characters (e.g. U+202F from scanner apps) that
     * Flysystem rejects as "corrupted", and both disks are local.
     */
    public static function refToAbsolute(string $ref): ?string
    {
        $parsed = self::parseRef($ref);
        if ($parsed === null) {
            return null;
        }
        $root = rtrim((string) config("filesystems.disks.{$parsed[0]}.root"), '/\\');

        return $root === '' ? null : $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $parsed[1]);
    }

    /**
     * [disk name, path] for a well-formed ref on an allowed disk, else null. Paths with
     * traversal segments, and public-disk paths outside the security prefixes, are refused.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function parseRef(string $ref): ?array
    {
        if (! preg_match('/^([a-z_]+):(.+)$/', $ref, $m)) {
            return null;
        }
        [$disk, $path] = [$m[1], self::normalisePath($m[2])];
        if ($path === null) {
            return null;
        }
        $securityDisk = config('security_files.disk', 'security');
        $publicDisk = config('security_files.legacy_public_disk', 'public');
        if ($disk === 'security') {
            return [$securityDisk, $path];
        }
        if ($disk === $publicDisk) {
            foreach (config('security_files.legacy_public_prefixes', []) as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return [$publicDisk, $path];
                }
            }
        }

        return null;
    }

    /**
     * The ref as it exists on disk, or null. When the exact spelling is missing, each path
     * segment is matched ignoring case: the archive comes from a Windows server, where
     * DB values and folder/file names disagree on case for thousands of rows ("LBR00367" vs
     * folder "lbr00367", "amlesh.jpg" vs "Amlesh.jpg"). On a case-sensitive (Linux) disk
     * they would otherwise all be missing; on Windows the exact check already succeeds.
     */
    private static function existingRef(string $ref): ?string
    {
        if (array_key_exists($ref, self::$exists)) {
            return self::$exists[$ref] ?: null;
        }
        if (count(self::$exists) >= 5000) {
            self::$exists = [];
        }
        $absolute = self::refToAbsolute($ref);
        $found = null;
        if ($absolute !== null && is_file($absolute)) {
            $found = $ref;
        } elseif ($absolute !== null && ($parsed = self::parseRef($ref)) !== null) {
            $path = self::caseInsensitivePath($parsed[0], $parsed[1]);
            $found = $path === null ? null : strstr($ref, ':', true) . ':' . $path;
        }
        self::$exists[$ref] = $found ?? false;

        return $found;
    }

    /** @var array<string, array<string, string>> directory => [lower-cased entry => entry] */
    private static array $dirListings = [];

    /** $path with each segment spelled as on disk (case-insensitive match), or null. */
    private static function caseInsensitivePath(string $disk, string $path): ?string
    {
        $dir = rtrim((string) config("filesystems.disks.{$disk}.root"), '/\\');
        $actual = [];
        $segments = explode('/', $path);
        foreach ($segments as $i => $segment) {
            if (! isset(self::$dirListings[$dir])) {
                if (count(self::$dirListings) >= 2000) {
                    self::$dirListings = [];
                }
                $entries = is_dir($dir) ? (@scandir($dir) ?: []) : [];
                self::$dirListings[$dir] = [];
                foreach ($entries as $entry) {
                    if ($entry !== '.' && $entry !== '..') {
                        self::$dirListings[$dir][mb_strtolower($entry)] ??= $entry;
                    }
                }
            }
            $match = self::$dirListings[$dir][mb_strtolower($segment)] ?? null;
            if ($match === null) {
                return null;
            }
            $actual[] = $match;
            $dir .= DIRECTORY_SEPARATOR . $match;
            if ($i === count($segments) - 1 && ! is_file($dir)) {
                return null;
            }
        }

        return implode('/', $actual);
    }

    /** Forward slashes, no leading "/" or "storage/", no "." / ".." segments; null if unsafe. */
    private static function normalisePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#^/+#', '', $path);
        $path = preg_replace('#^storage/(app/public/)?#', '', $path);
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        return $path;
    }

    /** A key usable as one folder segment, or null. */
    private static function safeSegment(int|string|null $key): ?string
    {
        $key = trim((string) $key);

        return ($key !== '' && $key !== '0' && preg_match('/^[A-Za-z0-9_-]+$/', $key)) ? $key : null;
    }
}
