<?php

namespace Tests\Feature;

use Tests\TestCase;

class ClearCacheRouteTest extends TestCase
{
    public function test_guest_cannot_clear_cache(): void
    {
        $this->get('/clear-cache')->assertRedirect();
    }
}
