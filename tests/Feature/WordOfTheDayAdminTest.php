<?php

namespace Tests\Feature;

use Tests\TestCase;

class WordOfTheDayAdminTest extends TestCase
{
    public function test_guest_is_redirected_from_word_of_day_admin(): void
    {
        $response = $this->get('/word-of-day');

        $response->assertRedirect();
    }
}
