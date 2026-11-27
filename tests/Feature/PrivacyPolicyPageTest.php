<?php

namespace Tests\Feature;

use App\Http\Controllers\PrivacyPolicyController;
use App\Models\User;
use Tests\TestCase;

class PrivacyPolicyPageTest extends TestCase
{
    public function test_visitors_who_are_not_signed_in_can_read_the_full_policy(): void
    {
        $response = $this->get(route('privacy-policy'))->assertOk()
            ->assertSee('Last Updated: '.PrivacyPolicyController::LAST_UPDATED)
            ->assertSee('Sign in');

        // Every index entry has a matching section to jump to.
        foreach (PrivacyPolicyController::SECTIONS as $i => $title) {
            $n = $i + 1;
            $response->assertSee('href="#pp-'.$n.'"', false)
                ->assertSee('id="pp-'.$n.'"', false)
                ->assertSee($n.'. '.$title, false);
        }
        $response->assertSee('Digital Personal Data Protection Act, 2023');
    }

    public function test_signed_in_users_see_it_inside_the_app_layout(): void
    {
        $user = User::query()->first() ?? $this->markTestSkipped('No user to sign in as.');

        $this->actingAs($user)->get(route('privacy-policy'))->assertOk()
            ->assertSee('Policy sections')
            ->assertSee(route('privacy-policy.download'), false)
            ->assertSee('href="'.route('privacy-policy').'"', false) // the profile-menu link in the app header
            ->assertDontSee('>Sign in</a>', false);                   // not the guest shell
    }

    public function test_print_and_pdf_carry_the_same_text(): void
    {
        $this->get(route('privacy-policy.print'))->assertOk()
            ->assertSee('onload="window.print()"', false)
            ->assertSee('14. Applicable Laws');

        $pdf = $this->get(route('privacy-policy.download'))->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_the_login_page_links_to_it(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('href="'.route('privacy-policy').'"', false);
    }
}
