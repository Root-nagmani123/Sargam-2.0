<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Issues the Bearer key ज्ञानकोश uses to read the pull feed.
 *
 * Prints the key once. Only its SHA-256 goes into .env, so the key cannot be
 * read back from this server — issuing a new one replaces the old.
 */
class KnowledgeFeedKey extends Command
{
    protected $signature = 'knowledge:feed-key';

    protected $description = 'Generate a new Bearer key for the ज्ञानकोश pull feed (prints it once)';

    public function handle(): int
    {
        $key = 'sargam-kb-' . Str::random(48);

        $this->line('');
        $this->warn('Key for the AI Platform team — copy it now, it is not stored here:');
        $this->line('');
        $this->line('    ' . $key);
        $this->line('');
        $this->line('Put this line in .env (replacing any existing one), then php artisan config:clear:');
        $this->line('');
        $this->line('    KNOWLEDGE_FEED_KEY_SHA256=' . hash('sha256', $key));
        $this->line('');
        $this->line('The previous key stops working as soon as the hash is replaced.');

        return self::SUCCESS;
    }
}
