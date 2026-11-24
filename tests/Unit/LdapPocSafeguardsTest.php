<?php

namespace Tests\Unit;

use App\Services\LdapPoc\LdapPocErrorExplainer as E;
use App\Services\LdapPoc\LdapUserCreationTestService as S;
use PHPUnit\Framework\TestCase;

/**
 * The LDAP POC cleanup must never be able to delete a real directory account.
 * These tests pin every safeguard in deletionRefusalReason() individually.
 */
class LdapPocSafeguardsTest extends TestCase
{
    private const CONTAINER = 'OU=ERP-LDAP-POC,DC=lbsnaa,DC=gov,DC=in';
    private const GUID = '2f1c7a9e-0b1d-4c3e-9a8b-112233445566';
    private const MARKER = 'ERP-LDAP-POC';

    private function registry(array $overrides = []): array
    {
        return array_merge([
            'username' => 'erppoc.test1',
            'dn' => 'CN=erppoc.test1,'.self::CONTAINER,
            'object_guid' => self::GUID,
            'deleted_at' => null,
        ], $overrides);
    }

    private function ldap(array $overrides = []): array
    {
        return array_merge([
            'dn' => 'CN=erppoc.test1,'.self::CONTAINER,
            'guid' => self::GUID,
            'description' => self::MARKER.' test account - safe to delete.',
            'member_of' => [],
            'admin_count' => 0,
            'object_classes' => ['top', 'person', 'organizationalPerson', 'user'],
        ], $overrides);
    }

    private function reason(string $username, ?array $registry, ?array $ldap, bool $ldapChecked = true): ?string
    {
        return S::deletionRefusalReason($username, $registry, $ldap, 'erppoc', self::CONTAINER, self::MARKER, $ldapChecked);
    }

    public function test_untouched_poc_account_may_be_deleted(): void
    {
        $this->assertNull($this->reason('erppoc.test1', $this->registry(), $this->ldap()));
    }

    public function test_dn_comparison_ignores_case_and_spaces_after_commas(): void
    {
        $ldap = $this->ldap(['dn' => 'cn=erppoc.test1, ou=erp-ldap-poc, dc=lbsnaa, dc=gov, dc=in']);
        $this->assertNull($this->reason('erppoc.test1', $this->registry(), $ldap));
    }

    public function test_username_without_prefix_is_refused_even_if_registered(): void
    {
        $this->assertNotNull($this->reason('administrator', $this->registry(['username' => 'administrator']), $this->ldap()));
    }

    public function test_unregistered_account_is_refused_before_ldap_is_contacted(): void
    {
        $this->assertNotNull($this->reason('erppoc.real', null, null, false));
    }

    public function test_already_deleted_account_is_refused(): void
    {
        $this->assertNotNull($this->reason('erppoc.test1', $this->registry(['deleted_at' => '2026-11-18T10:00:00+05:30']), $this->ldap()));
    }

    public function test_registry_entry_without_guid_is_refused(): void
    {
        $this->assertNotNull($this->reason('erppoc.test1', $this->registry(['object_guid' => null]), $this->ldap()));
    }

    public function test_account_outside_the_poc_container_is_refused(): void
    {
        $ldap = $this->ldap(['dn' => 'CN=erppoc.test1,CN=Users,DC=lbsnaa,DC=gov,DC=in']);
        $this->assertNotNull($this->reason('erppoc.test1', $this->registry(), $ldap));
    }

    public function test_account_in_a_sub_ou_of_the_container_is_refused(): void
    {
        $ldap = $this->ldap(['dn' => 'CN=erppoc.test1,OU=Nested,'.self::CONTAINER]);
        $this->assertNotNull($this->reason('erppoc.test1', $this->registry(), $ldap));
    }

    public function test_recreated_object_with_a_different_guid_is_refused(): void
    {
        $this->assertNotNull($this->reason('erppoc.test1', $this->registry(), $this->ldap(['guid' => 'aaaaaaaa-0000-0000-0000-000000000000'])));
    }

    public function test_missing_marker_is_refused(): void
    {
        $this->assertNotNull($this->reason('erppoc.test1', $this->registry(), $this->ldap(['description' => 'Real employee'])));
    }

    public function test_group_member_is_refused(): void
    {
        $ldap = $this->ldap(['member_of' => ['CN=Domain Admins,CN=Users,DC=lbsnaa,DC=gov,DC=in']]);
        $this->assertNotNull($this->reason('erppoc.test1', $this->registry(), $ldap));
    }

    public function test_admin_count_is_refused(): void
    {
        $this->assertNotNull($this->reason('erppoc.test1', $this->registry(), $this->ldap(['admin_count' => 1])));
    }

    public function test_computer_object_is_refused(): void
    {
        $ldap = $this->ldap(['object_classes' => ['top', 'person', 'organizationalPerson', 'user', 'computer']]);
        $this->assertNotNull($this->reason('erppoc.test1', $this->registry(), $ldap));
    }

    public function test_missing_ldap_entry_is_refused(): void
    {
        $this->assertNotNull($this->reason('erppoc.test1', $this->registry(), null));
    }

    public function test_short_prefix_disables_cleanup(): void
    {
        $this->assertNotNull(S::deletionRefusalReason('ab.x', $this->registry(), $this->ldap(), 'ab', self::CONTAINER, self::MARKER, true));
    }

    /* ---------------- error explainer ---------------- */

    public function test_bind_subcode_is_decoded(): void
    {
        $x = E::explain(49, E::STAGE_BIND, '80090308: LdapErr: DSID-0C09044E, comment: AcceptSecurityContext error, data 52e, v4563');
        $this->assertSame('Bind / authentication failure', $x['category']);
        $this->assertSame('52e', $x['ad_code']);
        $this->assertStringContainsString('password is wrong', $x['cause']);
    }

    public function test_insufficient_access_maps_to_permission_denied(): void
    {
        $this->assertSame('Permission / access denied', E::explain(50, E::STAGE_CREATE, '00000005: SecErr: DSID-03152857, problem 4003 (INSUFF_ACCESS_RIGHTS), data 0')['category']);
    }

    public function test_password_policy_is_recognised_from_ad_code(): void
    {
        $x = E::explain(19, E::STAGE_PASSWORD, '0000052D: Constraint violation - check_password_restrictions: the password does not meet the complexity criteria');
        $this->assertSame('Password policy violation', $x['category']);
        $this->assertSame('0000052D', $x['ad_code']);
    }

    public function test_missing_container_on_create(): void
    {
        $this->assertSame('OU / container does not exist', E::explain(32, E::STAGE_CREATE)['category']);
    }

    public function test_other_mappings(): void
    {
        $this->assertSame('Username already exists', E::explain(68, E::STAGE_CREATE)['category']);
        $this->assertSame('Missing objectClass / required attributes', E::explain(65, E::STAGE_CREATE)['category']);
        $this->assertSame('Invalid DN', E::explain(34, E::STAGE_CREATE)['category']);
        $this->assertSame('LDAP connection failure', E::explain(-1, E::STAGE_CONNECT)['category']);
        $this->assertSame('Other LDAP error', E::explain(999, E::STAGE_CREATE)['category']);
    }
}
