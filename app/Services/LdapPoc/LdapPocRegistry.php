<?php

namespace App\Services\LdapPoc;

use RuntimeException;

/**
 * Local record of every LDAP account the POC created. Cleanup refuses any
 * account that is not listed here, so a real directory user can never be
 * reached through the POC even if it happens to match the username prefix.
 *
 * A JSON file (storage/app is git-ignored) rather than a table: the POC must
 * not need a migration or touch the application schema.
 */
class LdapPocRegistry
{
    public function __construct(private string $path)
    {
    }

    /** @return array<string, array<string, mixed>> keyed by lower-case username */
    public function all(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        return is_array($data) ? $data : [];
    }

    public function find(string $username): ?array
    {
        return $this->all()[strtolower($username)] ?? null;
    }

    public function put(string $username, array $entry): void
    {
        $this->mutate(function (array $data) use ($username, $entry) {
            $data[strtolower($username)] = $entry;

            return $data;
        });
    }

    public function update(string $username, array $fields): void
    {
        $this->mutate(function (array $data) use ($username, $fields) {
            $key = strtolower($username);
            if (isset($data[$key])) {
                $data[$key] = array_merge($data[$key], $fields);
            }

            return $data;
        });
    }

    private function mutate(callable $fn): void
    {
        $dir = dirname($this->path);
        if (! is_dir($dir) && ! mkdir($dir, 0750, true) && ! is_dir($dir)) {
            throw new RuntimeException('Cannot create the LDAP POC registry directory.');
        }

        $handle = fopen($this->path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open the LDAP POC registry file.');
        }

        try {
            flock($handle, LOCK_EX);
            $data = json_decode((string) stream_get_contents($handle), true);
            $data = $fn(is_array($data) ? $data : []);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
