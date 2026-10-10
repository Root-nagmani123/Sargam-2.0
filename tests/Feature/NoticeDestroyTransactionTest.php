<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\NoticeNotificationController;
use Tests\TestCase;

/**
 * destroy() deleted a notice's audience rows and then the notice in two
 * statements outside a transaction; a failure between them left an inactive
 * notice with no audience rows, which the feed reads as "everyone"
 * (PR #334 F-065). Read from the method body: forcing the second delete to fail
 * against a real connection is not something this suite can do safely.
 */
class NoticeDestroyTransactionTest extends TestCase
{
    public function test_both_deletes_run_inside_one_transaction(): void
    {
        $m = new \ReflectionMethod(NoticeNotificationController::class, 'destroy');
        $lines = file($m->getFileName());
        $body = implode('', array_slice($lines, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1));

        $tx = strpos($body, 'DB::transaction(');
        $this->assertNotFalse($tx, 'destroy() opens a transaction');
        $this->assertGreaterThan($tx, strpos($body, '->audienceMaps()->delete()'));
        $this->assertGreaterThan($tx, strpos($body, '$data->delete()'));
    }
}
