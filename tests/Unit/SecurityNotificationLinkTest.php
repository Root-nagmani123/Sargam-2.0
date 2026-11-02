<?php

namespace Tests\Unit;

use App\Services\NotificationService;
use App\Services\SecurityRequestNotifier;
use Tests\TestCase;

/**
 * Security notifications carry their click-through target as a hidden trailer on the message
 * (NotificationService::appendLinkPayload), so the bell opens the request itself.
 *
 * What is asserted: the link opens the right approval page and stage with the key encrypted
 * the way the approval lists encrypt it, the trailer never reaches the visible text, only
 * allow-listed routes are followed, and the existing mess receipt trailer still works.
 *
 * DB-free: only payload building / parsing and route generation.
 */
class SecurityNotificationLinkTest extends TestCase
{
    private function urlFor(array $link): ?string
    {
        $message = NotificationService::appendLinkPayload('Visible text.', $link['route'], $link['encrypt'], $link['query']);

        return NotificationService::linkPayloadUrl($message, SecurityRequestNotifier::LINK_ROUTES);
    }

    private function decryptedId(string $url): string
    {
        return decrypt(urldecode(basename(parse_url($url, PHP_URL_PATH))));
    }

    public function test_id_card_link_opens_the_request_at_its_stage(): void
    {
        $url = $this->urlFor(SecurityRequestNotifier::idCardApprovalLink('c-dup-DUO00012', 2));

        $this->assertNotNull($url);
        $this->assertStringContainsString('/security/employee-idcard-approval/show/', $url);
        $this->assertSame('stage=2', parse_url($url, PHP_URL_QUERY));
        $this->assertSame('c-dup-DUO00012', $this->decryptedId($url));
    }

    public function test_applicant_id_card_link_opens_their_request_with_the_plain_id(): void
    {
        $contractual = $this->urlFor(SecurityRequestNotifier::idCardStatusLink('c-42'));
        $permanent = $this->urlFor(SecurityRequestNotifier::idCardStatusLink('PID00523'));

        $this->assertStringEndsWith('/admin/employee-idcard/show/c-42', (string) $contractual);
        $this->assertStringEndsWith('/admin/employee-idcard/show/PID00523', (string) $permanent);
    }

    public function test_vehicle_link_opens_the_pass_by_kind_and_pk(): void
    {
        $url = $this->urlFor(SecurityRequestNotifier::vehicleApprovalLink('fw', 'FW00007'));

        $this->assertNotNull($url);
        $this->assertStringContainsString('/security/vehicle-pass-approval/show/', $url);
        $this->assertSame('fw-FW00007', $this->decryptedId($url));
    }

    public function test_trailer_is_hidden_from_the_visible_message(): void
    {
        $message = NotificationService::appendLinkPayload('Visible text.', 'admin.security.vehicle_pass_approval.show', ['id' => 'tw-TW1']);

        $this->assertSame('Visible text.', NotificationService::stripMessCombinedReceiptPayloadForDisplay($message));
        $this->assertSame('  plain  ', NotificationService::stripMessCombinedReceiptPayloadForDisplay('  plain  '));
    }

    public function test_route_outside_the_allow_list_is_not_followed(): void
    {
        $message = NotificationService::appendLinkPayload('x', 'admin.dashboard');

        $this->assertNull(NotificationService::linkPayloadUrl($message, SecurityRequestNotifier::LINK_ROUTES));
        $this->assertNull(NotificationService::linkPayloadUrl('no trailer', SecurityRequestNotifier::LINK_ROUTES));
    }

    public function test_mess_receipt_trailer_still_parses_and_strips(): void
    {
        $message = NotificationService::appendMessCombinedReceiptPayload('Bill', 'abc', '2026-01-01', '2026-01-31');

        $this->assertSame('Bill', NotificationService::stripMessCombinedReceiptPayloadForDisplay($message));
        $this->assertSame('abc', NotificationService::parseMessCombinedReceiptPayload($message)['i']);
    }
}
