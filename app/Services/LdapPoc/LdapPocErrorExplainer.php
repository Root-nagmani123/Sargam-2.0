<?php

namespace App\Services\LdapPoc;

/**
 * Turns an LDAP result code (+ Active Directory diagnostic text) into a
 * human-readable category, likely cause and recommended fix for the POC page.
 *
 * Stage-aware: the same code means different things at bind time and at
 * add time (e.g. 32 is "base DN wrong" on a search, "OU missing" on an add).
 */
class LdapPocErrorExplainer
{
    public const STAGE_ENVIRONMENT = 'environment';
    public const STAGE_CONNECT = 'connect';
    public const STAGE_BIND = 'bind';
    public const STAGE_CONTAINER = 'container';
    public const STAGE_DUPLICATE = 'duplicate_check';
    public const STAGE_CREATE = 'create';
    public const STAGE_PASSWORD = 'password';
    public const STAGE_ENABLE = 'enable';
    public const STAGE_VERIFY_LOGIN = 'verify_login';
    public const STAGE_DELETE = 'delete';

    /** AD "data xxx" sub-codes returned with result 49 on a failed bind. */
    private const AD_BIND_SUBCODES = [
        '525' => 'Bind account does not exist.',
        '52e' => 'Bind account password is wrong.',
        '530' => 'Bind account is not permitted to log on at this time.',
        '531' => 'Bind account is not permitted to log on from this workstation.',
        '532' => 'Bind account password has expired.',
        '533' => 'Bind account is disabled.',
        '701' => 'Bind account has expired.',
        '773' => 'Bind account must reset its password.',
        '775' => 'Bind account is locked out.',
    ];

    /**
     * @return array{category:string, cause:string, fix:string, ad_code:?string}
     */
    public static function explain(int $code, string $stage, ?string $diagnostic = null): array
    {
        $diag = (string) $diagnostic;
        $adCode = self::extractAdErrorCode($diag);

        // Password-policy rejection: AD reports 0000052D (ERROR_PASSWORD_RESTRICTION)
        // under result 19 (constraint violation) or 53 (unwilling to perform).
        if (in_array($code, [19, 53], true) && $adCode === '0000052D') {
            return self::make('Password policy violation',
                'The password does not meet the domain policy (length, complexity, history or minimum age).',
                'Use a longer, more complex password. The "Domain password policy" panel shows the domain minimums.',
                $adCode);
        }

        // AD refuses to write unicodePwd over an unencrypted channel.
        if ($code === 53 && $stage === self::STAGE_PASSWORD) {
            return self::make('Password change refused by server',
                'Active Directory only accepts a password write over LDAPS (636) or StartTLS, or the password was rejected by policy.',
                'Set LDAP_POC_PORT=636 and LDAP_POC_USE_SSL=true (or LDAP_POC_USE_TLS=true) for the POC; the domain controller needs a valid certificate.',
                $adCode);
        }

        if ($code === 49) {
            $sub = self::extractBindSubCode($diag);
            $detail = $sub !== null && isset(self::AD_BIND_SUBCODES[$sub])
                ? self::AD_BIND_SUBCODES[$sub]
                : 'The bind username or password was rejected.';

            if ($stage === self::STAGE_VERIFY_LOGIN) {
                return self::make('Test account cannot log in',
                    'The new account exists but binding as it failed: '.$detail,
                    'Check that the password was set and the account enabled (userAccountControl 512).',
                    $sub);
            }

            return self::make('Bind / authentication failure', $detail,
                'Check LDAP_USERNAME / LDAP_PASSWORD in .env (UPN form, e.g. svc@domain). Login uses the same account.',
                $sub);
        }

        $map = [
            -1 => ['LDAP connection failure', 'The LDAP server could not be reached (host, port, firewall, or SSL handshake).',
                'Check LDAP_HOSTS / port and that this server can reach the domain controller. For LDAPS, check the certificate is trusted by PHP (TLS_REQCERT / ldap.conf).'],
            1 => ['Operations error', 'The server could not perform the operation in the current state — usually the connection is not bound.',
                'Confirm the bind succeeded before the operation.'],
            2 => ['Protocol error', 'The request was malformed for the server, often an LDAP v2/v3 mismatch.',
                'Keep LDAP protocol version 3.'],
            7 => ['Unsupported authentication method', 'The server does not accept this authentication method.',
                'Use a simple bind with a UPN, or move to LDAPS.'],
            8 => ['Stronger authentication required', 'The server requires signing/encryption for this operation (LDAP signing policy).',
                'Use LDAPS (636) or StartTLS for the POC connection.'],
            10 => ['Referral', 'The DN belongs to a different naming context or domain than the server queried.',
                'Check that the container DN and base DN are in the domain served by LDAP_HOSTS.'],
            12 => ['Unsupported operation', 'The server does not support a requested control or extension.',
                'Report the diagnostic message to the directory administrator.'],
            13 => ['Confidentiality required', 'The operation must be run over an encrypted channel.',
                'Use LDAPS (636) or StartTLS for the POC connection.'],
            16 => ['No such attribute', 'An attribute being changed does not exist on the entry.',
                'Check the attribute names sent (shown under "Attributes sent").'],
            17 => ['LDAP schema issue — undefined attribute', 'An attribute name is not defined in the directory schema.',
                'Remove or rename the attribute; this directory may not be Active Directory, or uses a custom schema.'],
            19 => ['Constraint violation', 'A value broke a schema or policy constraint (length, uniqueness, password policy).',
                'Shorten the values; if this was the password step, use a stronger password.'],
            20 => ['Attribute value already exists', 'A value being added is already present.',
                'Retry with a replace instead of an add.'],
            21 => ['Invalid attribute syntax', 'A value has the wrong format for its attribute (e.g. a malformed e-mail or UPN).',
                'Check the value formats under "Attributes sent".'],
            32 => $stage === self::STAGE_CREATE || $stage === self::STAGE_CONTAINER
                ? ['OU / container does not exist', 'The container in LDAP_POC_CONTAINER_DN was not found.',
                    'Ask the directory administrator to create the test OU, or point LDAP_POC_CONTAINER_DN at an existing one.']
                : ['No such object', 'The DN being read does not exist.',
                    'Check LDAP_BASE_DN and LDAP_POC_CONTAINER_DN.'],
            34 => ['Invalid DN', 'A distinguished name is syntactically invalid.',
                'Check LDAP_POC_CONTAINER_DN (e.g. OU=ERP-LDAP-POC,DC=lbsnaa,DC=gov,DC=in) and that the username has no special characters.'],
            48 => ['Inappropriate authentication', 'An anonymous or empty-password bind was attempted.',
                'Set LDAP_USERNAME and LDAP_PASSWORD.'],
            50 => ['Permission / access denied', 'The bind account lacks the right to perform this operation on this container.',
                'A directory administrator must delegate "Create/Delete User objects" (and "Reset password") on the test OU to the bind account. Do not work around this.'],
            51 => ['Server busy', 'The directory server is too busy.', 'Retry later.'],
            52 => ['Server unavailable', 'The directory server is shutting down or not serving requests.', 'Retry later or try another domain controller.'],
            53 => ['Server unwilling to perform', 'The server refused the operation (policy, or an unsupported change).',
                'Read the diagnostic message; for passwords, use LDAPS.'],
            64 => ['Naming violation', 'The RDN is not allowed in this container (e.g. creating a user under a non-container object).',
                'Point LDAP_POC_CONTAINER_DN at an OU or CN=Users-type container.'],
            65 => ['Missing objectClass / required attributes', 'The entry violates its object class: a mandatory attribute or objectClass value is missing or wrong.',
                'Check objectClass (top, person, organizationalPerson, user) and that cn and sAMAccountName are present.'],
            66 => ['Not allowed on non-leaf', 'The entry has children and cannot be deleted non-recursively.',
                'Inspect the entry; the POC never deletes recursively.'],
            67 => ['Not allowed on RDN', 'A change would alter the RDN attribute.', 'Do not modify cn on an existing entry.'],
            68 => ['Username already exists', 'An entry with this DN already exists in LDAP.',
                'Choose a different test username.'],
            69 => ['Object class modification prohibited', 'objectClass cannot be changed on an existing entry.', 'Recreate the entry instead.'],
            80 => ['Other LDAP error', 'The server returned an unspecified error.', 'Read the diagnostic message.'],
            81 => ['LDAP connection failure', 'The server is down or unreachable.',
                'Check LDAP_HOSTS / port / firewall, and the certificate for LDAPS.'],
            85 => ['Timeout', 'The operation timed out.', 'Check network reachability; LDAP_POC_TIMEOUT sets the limit.'],
            91 => ['LDAP connection failure', 'The client could not connect to the server.',
                'Check LDAP_HOSTS / port / firewall.'],
        ];

        [$category, $cause, $fix] = $map[$code] ?? ['Other LDAP error',
            'The server returned an error this page does not have a specific explanation for.',
            'Read the error message and diagnostic message below.'];

        return self::make($category, $cause, $fix, $adCode);
    }

    /** First 8-hex-digit AD error token, e.g. "0000052D: SvcErr: ..." → "0000052D". */
    public static function extractAdErrorCode(string $diagnostic): ?string
    {
        return preg_match('/\b([0-9A-Fa-f]{8}):/', $diagnostic, $m) ? strtoupper($m[1]) : null;
    }

    /** AD bind sub-code, e.g. "..., data 52e, v4563" → "52e". */
    public static function extractBindSubCode(string $diagnostic): ?string
    {
        return preg_match('/\bdata ([0-9a-fA-F]{3,4})\b/', $diagnostic, $m) ? strtolower($m[1]) : null;
    }

    private static function make(string $category, string $cause, string $fix, ?string $adCode): array
    {
        return ['category' => $category, 'cause' => $cause, 'fix' => $fix, 'ad_code' => $adCode];
    }
}
