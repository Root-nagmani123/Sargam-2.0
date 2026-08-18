<?php

namespace Tests\Feature;

use Tests\TestCase;

/*
 * NB: RefreshDatabase is deliberately NOT imported here.
 *
 * phpunit.xml leaves DB_CONNECTION commented out, so this suite runs against
 * whatever database .env points at — the developer's, not a throwaway one.
 * Adding `use RefreshDatabase;` would run migrate:fresh against it and drop the
 * schema. Use DatabaseTransactions (see the FC and CENTCOM tests) instead.
 */

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
