<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MigrateSensitiveAttachments extends Command
{
    protected $signature = 'attachments:migrate-sensitive {--dry-run : Check every referenced file without changing storage}';

    protected $description = 'Move leave and notice attachments from public to private storage';

    public function handle(): int
    {
        $paths = $this->referencedPaths();
        $public = Storage::disk('public');
        $private = Storage::disk('private');
        $missing = [];

        foreach ($paths as $path) {
            if (! $public->exists($path) && ! $private->exists($path)) {
                $missing[] = $path;
            }
        }

        if ($missing !== []) {
            $this->error('Migration stopped: referenced attachment files are missing from both disks.');
            foreach ($missing as $path) {
                $this->line($path);
            }

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Validated '.count($paths).' referenced attachment path(s); no files changed.');

            return self::SUCCESS;
        }

        foreach ($paths as $path) {
            if (! $public->exists($path)) {
                continue;
            }

            $source = $public->get($path);
            if (! $private->exists($path)
                || ! hash_equals(hash('sha256', $source), hash('sha256', $private->get($path)))) {
                if (! $private->put($path, $source)) {
                    throw new RuntimeException("Could not write private attachment: {$path}");
                }
            }

            if (! $private->exists($path)
                || ! hash_equals(hash('sha256', $source), hash('sha256', $private->get($path)))) {
                throw new RuntimeException("Private attachment verification failed: {$path}");
            }
        }

        foreach ($paths as $path) {
            if ($public->exists($path) && ! $public->delete($path)) {
                throw new RuntimeException("Could not remove public attachment: {$path}");
            }
        }

        $this->info('Migrated and verified '.count($paths).' referenced attachment path(s).');

        return self::SUCCESS;
    }

    private function referencedPaths(): array
    {
        $sources = [
            ['leave_application_attachment', 'file_path', 'uploads/leave-applications/'],
            ['notices_notification', 'document', 'notice_docs/'],
        ];
        $paths = [];

        foreach ($sources as [$table, $column, $prefix]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                throw new RuntimeException("Required attachment source {$table}.{$column} is unavailable.");
            }

            foreach (DB::table($table)
                ->whereNotNull($column)
                ->where($column, '<>', '')
                ->distinct()
                ->orderBy($column)
                ->pluck($column) as $storedPath) {
                $path = str_replace('\\', '/', (string) $storedPath);
                if (! str_starts_with($path, $prefix)
                    || str_contains($path, "\0")
                    || in_array('..', explode('/', $path), true)) {
                    throw new RuntimeException("Unsupported stored attachment path: {$path}");
                }

                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }
}
