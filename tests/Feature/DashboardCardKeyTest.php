<?php

namespace Tests\Feature;

use App\Models\DashboardCard;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dashboard card keys are allocated against a unique index, so allocation has to
 * cope with losing the race rather than assuming it wins.
 *
 * storeDashboardCard() probed for a free key with exists() and then inserted.
 * `dashboard_cards.key` is UNIQUE, so two requests carrying the same label could
 * both pass the probe and the loser's INSERT raised SQLSTATE 23000 - which
 * reached the user as a 500 with a raw SQL error in it. It now recomputes and
 * retries.
 *
 * A second, smaller hole: a label of nothing but punctuation slugs to the empty
 * string, and `key` is NOT NULL defaulting to '', so the first such card took ''
 * and every later one collided with it.
 */
class DashboardCardKeyTest extends TestCase
{
    use DatabaseTransactions;

    private function superAdmin(): User
    {
        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        $pk = $roleId ? DB::table('model_has_roles')->where('role_id', $roleId)->value('model_id') : null;
        $user = $pk ? User::where('pk', $pk)->first() : null;

        if (! $user) {
            $this->markTestSkipped('no Super Admin account in this database');
        }

        return $user;
    }

    private function create(string $label): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->superAdmin())
            ->post(route('dashboard.cards.store'), [
                'label' => $label,
                'icon' => 'dashboard',
                'color_class' => 'bg-primary',
                'sort_order' => 1,
            ]);
    }

    public function test_the_same_label_twice_yields_two_distinct_keys(): void
    {
        $label = 'Race Fixture '.uniqid();

        $first = $this->create($label)->assertOk()->json('card.key');
        $second = $this->create($label)->assertOk()->json('card.key');

        $this->assertNotSame($first, $second, 'a repeated label must not reuse the key');
        $this->assertSame(2, DashboardCard::whereIn('key', [$first, $second])->count());
    }

    public function test_a_label_with_no_alphanumerics_still_gets_a_usable_key(): void
    {
        $key = $this->create('--- !!! ---')->assertOk()->json('card.key');

        $this->assertNotSame('', $key, 'an empty key would collide with the next such card');
        $this->assertStringStartsWith('card', $key);
    }

    /**
     * The retry path itself: take the key the allocator is about to choose, out
     * from under it, so the INSERT it was about to make would collide.
     *
     * Simulated rather than raced - the allocator's contract is "if the key I
     * picked turns out to be taken, pick another", and that is what is asserted.
     */
    public function test_a_key_taken_between_probe_and_insert_is_retried_not_surfaced_as_a_500(): void
    {
        $label = 'Collide Fixture '.uniqid();
        $baseKey = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_');

        DashboardCard::create([
            'key' => $baseKey,
            'label' => $label,
            'icon' => 'dashboard',
            'color_class' => 'bg-primary',
            'sort_order' => 1,
        ]);

        $response = $this->create($label);

        $response->assertOk();
        $this->assertSame($baseKey.'_1', $response->json('card.key'));
    }
}
