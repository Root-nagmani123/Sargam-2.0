<?php
namespace Tests\Feature;
use Tests\TestCase;
class TmpDumpTest extends TestCase {
    public function test_dump(): void {
        foreach (['incomplete','registered'] as $tab) {
            $this->refreshApplication();
            $res = $this->get('/fc/status?tab='.$tab);
            file_put_contents(getenv('DUMP_DIR').'/'.$tab.'.html', $res->getContent());
        }
        $this->assertTrue(true);
    }
}
