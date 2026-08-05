<?php

namespace App\Console\Commands;

use App\Services\Messaging\GupshupSmsService;
use Illuminate\Console\Command;

/**
 * One-off SMS connectivity test (Gupshup enterprise SMS API — not WhatsApp).
 *
 * Example:
 *  php artisan fc:sms-test --to=6284303466 --dlt=1407177798105405469 --driver=gupshup
 */
class FcSmsTestCommand extends Command
{
    protected $signature = 'fc:sms-test
        {--to= : Mobile (10 digits or 91XXXXXXXXXX)}
        {--dlt= : dltTemplateId (must match msg template)}
        {--msg= : Exact approved template text}
        {--driver= : Override SMS_DRIVER for this run (log|gupshup)}';

    protected $description = 'Test Gupshup SMS send (SMS only, not WhatsApp)';

    public function handle(GupshupSmsService $sms): int
    {
        $to = trim((string) $this->option('to'));
        $dlt = trim((string) $this->option('dlt'));
        $msg = (string) $this->option('msg');
        $driver = strtolower(trim((string) $this->option('driver')));

        if ($to === '') {
            $this->error('Provide --to=10digit mobile');
            return self::FAILURE;
        }

        if ($dlt === '') {
            $this->error('Provide --dlt=dltTemplateId (must match the message template)');
            return self::FAILURE;
        }

        if (trim($msg) === '') {
            $this->error('Provide --msg="exact approved template text"');
            return self::FAILURE;
        }

        if ($driver !== '') {
            config(['gupshup.driver' => $driver]);
        }

        $this->info('driver='.$sms->driver());
        $this->info('send_to='.$sms->normalizeSendTo($to));
        $this->info('dltTemplateId='.$dlt);

        $ok = $sms->send($to, $msg, $dlt);
        $this->info($ok ? 'Request finished — check phone / laravel.log for gateway response.' : 'Send returned false — check laravel.log');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
