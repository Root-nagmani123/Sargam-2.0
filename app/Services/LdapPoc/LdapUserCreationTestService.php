<?php

namespace App\Services\LdapPoc;

use Adldap\Auth\BindException;
use Adldap\Connections\Ldap;
use Adldap\Connections\Provider;
use Adldap\Utilities;
use App\Services\LdapPoc\LdapPocErrorExplainer as E;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Isolated LDAP user-creation POC. NOT wired into FC Registration.
 *
 * Reuses the login's connection settings (config/ldap.php, "default") but opens
 * its own Adldap Provider per request, so the shared provider behind
 * Adldap::auth() in LoginController is never re-bound or modified.
 *
 * Creation is deliberately split into steps so each permission is proven on
 * its own:
 *   1. add the account DISABLED with no password (needs "Create User objects")
 *   2. set unicodePwd           (needs "Reset password" + LDAPS/StartTLS)
 *   3. enable the account        (needs write on userAccountControl)
 *   4. bind as the new account  (proves it can actually log in)
 * A failure at step 2+ leaves a disabled, marked account behind for inspection.
 *
 * Passwords are never logged, stored, or echoed back.
 */
class LdapUserCreationTestService
{
    private const UAC_DISABLED = '514'; // NORMAL_ACCOUNT (512) | ACCOUNTDISABLE (2)
    private const UAC_ENABLED = '512';  // NORMAL_ACCOUNT
    private const SAM_MAX_LENGTH = 20;  // AD sAMAccountName limit for users

    private array $config;
    private array $ldapSettings;

    public function __construct(private LdapPocRegistry $registry)
    {
        $this->config = (array) config('ldap_poc');
        $this->ldapSettings = (array) config('ldap.connections.default.settings');
    }

    /* ===================================================================
     |  Read-only summary (no network)
     * =================================================================== */

    /** Non-sensitive view of the POC configuration plus blocking problems. */
    public function settingsSummary(): array
    {
        $settings = $this->connectionSettings();

        return [
            'ext_ldap_loaded' => extension_loaded('ldap'),
            'container_dn' => $this->config['container_dn'] ?: null,
            'username_prefix' => $this->prefix(),
            'upn_suffix' => $this->upnSuffix(),
            'port' => (int) $settings['port'],
            'use_ssl' => (bool) $settings['use_ssl'],
            'use_tls' => (bool) $settings['use_tls'],
            'secure_channel' => (bool) $settings['use_ssl'] || (bool) $settings['use_tls'],
            'hosts_overridden' => trim((string) ($this->config['connection']['hosts'] ?? '')) !== '',
            'ca_cert' => $this->caCertPath() !== null ? basename($this->caCertPath()) : null,
            'problems' => $this->configurationProblems(),
        ];
    }

    /** @return string[] blocking configuration problems; empty when runnable */
    public function configurationProblems(): array
    {
        $problems = [];

        if (! extension_loaded('ldap')) {
            $problems[] = 'The PHP ldap extension is not loaded in this PHP runtime (enable extension=ldap in php.ini). Nothing LDAP-related can run here.';
        }

        $container = trim((string) ($this->config['container_dn'] ?? ''));
        $baseDn = trim((string) ($this->ldapSettings['base_dn'] ?? ''));
        if ($container === '') {
            $problems[] = 'LDAP_POC_CONTAINER_DN is not set. Set it to a dedicated test OU (e.g. OU=ERP-LDAP-POC,'.($baseDn ?: 'DC=...').').';
        } elseif (! self::isStrictlyUnder($container, $baseDn)) {
            $problems[] = 'LDAP_POC_CONTAINER_DN must be inside the LDAP base DN ('.$baseDn.').';
        }

        if (strlen($this->prefix()) < 3) {
            $problems[] = 'LDAP_POC_USERNAME_PREFIX must be at least 3 characters (it is a cleanup safeguard).';
        } elseif (! preg_match('/^[a-z][a-z0-9]*$/', $this->prefix())) {
            $problems[] = 'LDAP_POC_USERNAME_PREFIX may contain only lower-case letters and digits, starting with a letter.';
        }

        if ($this->upnSuffix() === '') {
            $problems[] = 'No UPN suffix: set LDAP_POC_UPN_SUFFIX or LDAP_ACCOUNT_SUFFIX.';
        }

        $settings = $this->connectionSettings();
        if ($settings['use_ssl'] && $settings['use_tls']) {
            $problems[] = 'Set only one of LDAP_POC_USE_SSL (LDAPS, port 636) and LDAP_POC_USE_TLS (StartTLS, port 389).';
        }

        $ca = $this->caCertPath();
        if ($ca !== null && ! is_readable($ca)) {
            $problems[] = 'LDAP_POC_CA_CERT points to a file that does not exist or is not readable by PHP: '.$ca;
        }

        return $problems;
    }

    /* ===================================================================
     |  Diagnostics (read-only network checks)
     * =================================================================== */

    /** Connect, bind, inspect the container and password policy. Writes nothing. */
    public function diagnose(): array
    {
        $report = $this->newReport('diagnose');

        if (! $this->guardConfiguration($report)) {
            return $this->finish($report);
        }

        $provider = $this->connect($report);
        if (! $provider) {
            return $this->finish($report);
        }

        try {
            $conn = $provider->getConnection();
            $this->inspectContainer($conn, $report);
            $this->inspectPasswordPolicy($conn, $report);
            $report['ok'] = $report['error'] === null;
        } finally {
            $this->close($provider);
        }

        return $this->finish($report);
    }

    /* ===================================================================
     |  Create
     * =================================================================== */

    /**
     * @param array{username:string, first_name:string, last_name:string, email:string, enable:bool} $input
     */
    public function createTestUser(array $input, #[\SensitiveParameter] ?string $password, string $actor): array
    {
        $report = $this->newReport('create');
        $username = strtolower(trim($input['username']));

        if (! $this->guardConfiguration($report)) {
            return $this->finish($report);
        }

        if ($reason = $this->invalidUsernameReason($username)) {
            return $this->finish($this->fail($report, E::STAGE_ENVIRONMENT, 'Invalid test username', $reason,
                'Use '.$this->prefix().' followed by letters/digits/._- , at most '.self::SAM_MAX_LENGTH.' characters in total.'));
        }

        $container = trim($this->config['container_dn']);
        $dn = 'CN='.ldap_escape($username, '', LDAP_ESCAPE_DN).','.$container;
        $upn = $username.'@'.$this->upnSuffix();
        $displayName = trim($input['first_name'].' '.$input['last_name']);
        $createdAt = now()->toIso8601String();

        $attributes = [
            'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
            'cn' => $username,
            'sAMAccountName' => $username,
            'userPrincipalName' => $upn,
            'givenName' => $input['first_name'],
            'sn' => $input['last_name'],
            'displayName' => $displayName.' ['.$this->config['marker'].']',
            'mail' => $input['email'],
            'description' => $this->config['marker'].' test account - safe to delete. Created '.$createdAt.' from the ERP by '.$actor.'.',
            'info' => $this->config['marker'].' | not a real user | created '.$createdAt,
            'userAccountControl' => self::UAC_DISABLED,
        ];

        $report['attributes_sent'] = $attributes;
        $report['user'] = ['username' => $username, 'upn' => $upn, 'dn' => $dn, 'created_at' => null,
            'password_set' => false, 'enabled' => false, 'login_verified' => false];

        $provider = $this->connect($report);
        if (! $provider) {
            return $this->finish($report, $username, $actor);
        }

        try {
            $conn = $provider->getConnection();

            if (! $this->inspectContainer($conn, $report, true)) {
                return $this->finish($report, $username, $actor);
            }

            if (! $this->checkNotDuplicate($conn, $report, $username, $upn, $dn, $input['email'])) {
                return $this->finish($report, $username, $actor);
            }

            // Step 1: create, disabled, no password.
            if (! @$conn->add($dn, $attributes)) {
                $this->ldapFail($report, $conn, E::STAGE_CREATE);

                return $this->finish($report, $username, $actor);
            }

            $guid = $this->readGuid($conn, $dn);
            $this->registry->put($username, [
                'username' => $username, 'dn' => $dn, 'object_guid' => $guid,
                'created_at' => $createdAt, 'created_by' => $actor,
                'password_set' => false, 'enabled' => false, 'deleted_at' => null,
            ]);
            $report['user']['created_at'] = $createdAt;
            $this->step($report, E::STAGE_CREATE, 'ok', 'Account created (disabled) at '.$dn
                .($guid ? ' — objectGUID '.$guid : ' — objectGUID could not be read back'));

            // Step 2: password.
            if ($password === null || $password === '') {
                $this->step($report, E::STAGE_PASSWORD, 'skip', 'No password supplied; account left disabled.');
                $report['ok'] = true;

                return $this->finish($report, $username, $actor);
            }

            if (! $conn->canChangePasswords()) {
                $this->step($report, E::STAGE_PASSWORD, 'warn',
                    'Password NOT set: this connection is plain LDAP (no SSL/TLS). Active Directory only accepts password writes over LDAPS or StartTLS. '
                    .'The POC defaults to LDAPS on 636 — remove the LDAP_POC_PORT / LDAP_POC_USE_SSL overrides from .env (POC only — the login connection is unaffected), then delete and recreate the test user.');
                $report['warnings'][] = 'User creation works, but passwords cannot be set over the current (unencrypted) connection.';
                $report['ok'] = true;

                return $this->finish($report, $username, $actor);
            }

            if (! @$conn->modReplace($dn, ['unicodePwd' => Utilities::encodePassword($password)])) {
                $this->ldapFail($report, $conn, E::STAGE_PASSWORD);
                $report['warnings'][] = 'The account was created but is disabled with no password. Inspect it, then use Cleanup.';

                return $this->finish($report, $username, $actor);
            }
            $report['user']['password_set'] = true;
            $this->registry->update($username, ['password_set' => true]);
            $this->step($report, E::STAGE_PASSWORD, 'ok', 'Password set.');

            // Step 3: enable.
            if (empty($input['enable'])) {
                $this->step($report, E::STAGE_ENABLE, 'skip', 'Left disabled (enable was not ticked).');
                $report['ok'] = true;

                return $this->finish($report, $username, $actor);
            }

            if (! @$conn->modReplace($dn, ['userAccountControl' => self::UAC_ENABLED])) {
                $this->ldapFail($report, $conn, E::STAGE_ENABLE);

                return $this->finish($report, $username, $actor);
            }
            $report['user']['enabled'] = true;
            $this->registry->update($username, ['enabled' => true]);
            $this->step($report, E::STAGE_ENABLE, 'ok', 'Account enabled (userAccountControl 512).');
        } finally {
            $this->close($provider);
        }

        // Step 4: bind as the new user on a separate connection.
        $report['user']['login_verified'] = $this->verifyLogin($report, $upn, $password);
        $report['ok'] = true;

        return $this->finish($report, $username, $actor);
    }

    /* ===================================================================
     |  Cleanup
     * =================================================================== */

    public function deleteTestUser(string $username, string $actor): array
    {
        $report = $this->newReport('delete');
        $username = strtolower(trim($username));

        if (! $this->guardConfiguration($report)) {
            return $this->finish($report);
        }

        $registryEntry = $this->registry->find($username);

        // Refuse on local evidence alone before touching LDAP.
        if ($reason = self::deletionRefusalReason($username, $registryEntry, null, $this->prefix(),
            $this->config['container_dn'], $this->config['marker'], false)) {
            return $this->finish($this->fail($report, E::STAGE_DELETE, 'Deletion refused (safeguard)', $reason,
                'Only accounts created by this POC can be deleted here.'), $username, $actor);
        }

        $provider = $this->connect($report);
        if (! $provider) {
            return $this->finish($report, $username, $actor);
        }

        try {
            $conn = $provider->getConnection();
            $entries = $this->search($conn, (string) $this->ldapSettings['base_dn'],
                '(sAMAccountName='.ldap_escape($username, '', LDAP_ESCAPE_FILTER).')',
                ['distinguishedname', 'objectguid', 'description', 'memberof', 'admincount', 'objectclass']);

            if ($entries === null) {
                $this->ldapFail($report, $conn, E::STAGE_DELETE);

                return $this->finish($report, $username, $actor);
            }

            if (count($entries) > 1) {
                return $this->finish($this->fail($report, E::STAGE_DELETE, 'Deletion refused (safeguard)',
                    'More than one LDAP entry matched this username.', 'Inspect the directory manually.'), $username, $actor);
            }

            $ldapEntry = $entries ? $this->normaliseEntry($entries[0]) : null;

            if ($reason = self::deletionRefusalReason($username, $registryEntry, $ldapEntry, $this->prefix(),
                $this->config['container_dn'], $this->config['marker'], true)) {
                return $this->finish($this->fail($report, E::STAGE_DELETE, 'Deletion refused (safeguard)', $reason,
                    'Only unmodified accounts created by this POC can be deleted here.'), $username, $actor);
            }

            if (! @$conn->delete($ldapEntry['dn'])) {
                $this->ldapFail($report, $conn, E::STAGE_DELETE);

                return $this->finish($report, $username, $actor);
            }

            $this->registry->update($username, ['deleted_at' => now()->toIso8601String(), 'deleted_by' => $actor]);
            $this->step($report, E::STAGE_DELETE, 'ok', 'Deleted '.$ldapEntry['dn']);
            $report['user'] = ['username' => $username, 'dn' => $ldapEntry['dn']];
            $report['ok'] = true;
        } finally {
            $this->close($provider);
        }

        return $this->finish($report, $username, $actor);
    }

    /**
     * Every condition must hold before a POC deletion. Pure, so it is unit-tested
     * on its own. Returns the refusal reason, or null when deletion is allowed.
     *
     * @param array|null $ldapEntry normalised: dn, guid, description, member_of[], admin_count, object_classes[]
     */
    public static function deletionRefusalReason(
        string $username,
        ?array $registryEntry,
        ?array $ldapEntry,
        string $prefix,
        ?string $containerDn,
        string $marker,
        bool $ldapChecked
    ): ?string {
        if (strlen($prefix) < 3 || ! str_starts_with($username, strtolower($prefix))) {
            return "Username does not start with the POC prefix '{$prefix}'.";
        }
        if ($registryEntry === null) {
            return 'This username is not in the POC registry — it was not created by this POC.';
        }
        if (! empty($registryEntry['deleted_at'])) {
            return 'The POC registry shows this account was already deleted.';
        }
        if (empty($registryEntry['object_guid'])) {
            return 'The registry has no objectGUID for this account, so its identity cannot be proven. Delete it manually after inspection.';
        }
        $expectedDn = 'cn='.strtolower($username).','.self::normaliseDn((string) $containerDn);
        if (self::normaliseDn((string) $registryEntry['dn']) !== $expectedDn) {
            return 'The registry DN is not a direct child of the configured POC container.';
        }

        if (! $ldapChecked) {
            return null;
        }

        if ($ldapEntry === null) {
            return 'No LDAP account with this username exists (already removed?).';
        }
        if (self::normaliseDn($ldapEntry['dn']) !== $expectedDn) {
            return 'The LDAP account with this username is not in the POC container — it is not a POC account.';
        }
        if (strcasecmp((string) $ldapEntry['guid'], (string) $registryEntry['object_guid']) !== 0) {
            return 'objectGUID does not match the one recorded at creation — this is a different object.';
        }
        if (! str_contains((string) $ldapEntry['description'], $marker)) {
            return "The account's description no longer carries the {$marker} marker.";
        }
        $classes = array_map('strtolower', $ldapEntry['object_classes']);
        if (! in_array('user', $classes, true) || in_array('computer', $classes, true)) {
            return 'The entry is not a plain user object.';
        }
        if (! empty($ldapEntry['member_of'])) {
            return 'The account has been added to groups since creation; refusing to delete it automatically.';
        }
        if ((int) $ldapEntry['admin_count'] > 0) {
            return 'The account is (or was) protected by AdminSDHolder (adminCount set).';
        }

        return null;
    }

    public function registryEntries(): array
    {
        return $this->registry->all();
    }

    /* ===================================================================
     |  Steps
     * =================================================================== */

    private function connect(array &$report): ?Provider
    {
        try {
            $this->applyTlsTrust();
            $provider = new Provider($this->connectionSettings(), new Ldap('ldap-poc'));
        } catch (Throwable $e) {
            $this->fail($report, E::STAGE_CONNECT, 'LDAP connection failure', $this->safeMessage($e),
                'Check the LDAP settings in config/ldap.php / .env.');

            return null;
        }

        try {
            $provider->connect(); // binds as the configured service account
        } catch (BindException $e) {
            $detail = $e->getDetailedError();
            $code = $detail ? (int) $detail->getErrorCode() : (int) $e->getCode();
            $stage = in_array($code, [-1, 81, 91], true) ? E::STAGE_CONNECT : E::STAGE_BIND;
            $this->ldapError($report, $stage, $code,
                $detail ? $detail->getErrorMessage() : $this->safeMessage($e),
                $detail ? $detail->getDiagnosticMessage() : null);
            $this->close($provider);

            return null;
        } catch (Throwable $e) {
            $this->fail($report, E::STAGE_CONNECT, 'LDAP connection failure', $this->safeMessage($e),
                'Check host/port reachability, and the certificate when using SSL/TLS.');
            $this->close($provider);

            return null;
        }

        $secure = $provider->getConnection()->canChangePasswords();
        $this->step($report, E::STAGE_BIND, 'ok', 'Connected and bound as the configured service account over '
            .($secure ? 'an encrypted (SSL/TLS)' : 'a plain, unencrypted').' connection.');

        return $provider;
    }

    /** Container exists, is a container, and (if AD exposes it) the bind account may create users in it. */
    private function inspectContainer(Ldap $conn, array &$report, bool $blockOnNoRight = false): bool
    {
        $container = trim($this->config['container_dn']);
        $entries = $this->read($conn, $container, ['distinguishedname', 'objectclass', 'allowedchildclasseseffective']);

        if ($entries === null || $entries === []) {
            $this->ldapFail($report, $conn, E::STAGE_CONTAINER, 32);

            return false;
        }

        $entry = $entries[0];
        $allowed = array_map('strtolower', $this->values($entry, 'allowedchildclasseseffective'));
        $report['container'] = [
            'dn' => $container,
            'object_classes' => $this->values($entry, 'objectclass'),
            'can_create_user' => $allowed === [] ? null : in_array('user', $allowed, true),
        ];

        if ($allowed === []) {
            $this->step($report, E::STAGE_CONTAINER, 'warn', 'Container found. The server did not return allowedChildClassesEffective, '
                .'so create permission cannot be pre-checked — the bind account may have no create rights here at all, or this is not Active Directory.');

            return true;
        }

        if (! in_array('user', $allowed, true)) {
            $msg = 'Container found, but the bind account has no right to create user objects in it (allowedChildClassesEffective does not include "user").';
            if ($blockOnNoRight) {
                $this->fail($report, E::STAGE_CONTAINER, 'Permission / access denied (pre-check)', $msg,
                    'A directory administrator must delegate "Create User objects" (and "Reset password") on this OU to the bind account. Not attempted.');

                return false;
            }
            $this->step($report, E::STAGE_CONTAINER, 'fail', $msg);
            $report['error'] ??= ['stage' => E::STAGE_CONTAINER, 'code' => null, 'message' => $msg, 'diagnostic' => null]
                + E::explain(50, E::STAGE_CONTAINER);

            return true;
        }

        $this->step($report, E::STAGE_CONTAINER, 'ok', 'Container found and the bind account is allowed to create user objects in it.');

        return true;
    }

    private function inspectPasswordPolicy(Ldap $conn, array &$report): void
    {
        $entries = $this->read($conn, (string) $this->ldapSettings['base_dn'], ['minpwdlength', 'pwdproperties', 'pwdhistorylength']);
        if (! $entries) {
            $this->step($report, 'password_policy', 'warn', 'Domain password policy could not be read (not fatal).');

            return;
        }

        $e = $entries[0];
        $props = (int) ($this->values($e, 'pwdproperties')[0] ?? 0);
        $report['password_policy'] = [
            'min_length' => $this->values($e, 'minpwdlength')[0] ?? null,
            'complexity_required' => (bool) ($props & 1),
            'history_length' => $this->values($e, 'pwdhistorylength')[0] ?? null,
        ];
        $this->step($report, 'password_policy', 'ok', 'Domain password policy read.');
    }

    private function checkNotDuplicate(Ldap $conn, array &$report, string $username, string $upn, string $dn, string $email): bool
    {
        $esc = fn ($v) => ldap_escape($v, '', LDAP_ESCAPE_FILTER);
        $entries = $this->search($conn, (string) $this->ldapSettings['base_dn'],
            '(|(sAMAccountName='.$esc($username).')(userPrincipalName='.$esc($upn).'))', ['distinguishedname']);

        if ($entries === null) {
            $this->ldapFail($report, $conn, E::STAGE_DUPLICATE);

            return false;
        }

        if ($entries !== [] || ($this->read($conn, $dn, ['distinguishedname']) ?: []) !== []) {
            $this->fail($report, E::STAGE_DUPLICATE, 'Username already exists',
                "An LDAP account with sAMAccountName/UPN '{$username}' (or the DN {$dn}) already exists. Nothing was created.",
                'Choose a different test username.');

            return false;
        }

        $this->step($report, E::STAGE_DUPLICATE, 'ok', 'Username and UPN are free.');

        $byMail = $this->search($conn, (string) $this->ldapSettings['base_dn'], '(mail='.$esc($email).')', ['distinguishedname']);
        if ($byMail) {
            $report['warnings'][] = 'Another LDAP entry already uses this e-mail address. Not blocking, but use a dummy address for tests.';
        }

        return true;
    }

    private function verifyLogin(array &$report, string $upn, #[\SensitiveParameter] string $password): bool
    {
        try {
            $this->applyTlsTrust();
            $provider = new Provider($this->connectionSettings(), new Ldap('ldap-poc-verify'));
            $provider->connect($upn, $password);
            $this->step($report, E::STAGE_VERIFY_LOGIN, 'ok', 'Bound successfully as the new test account — it can log in.');
            $this->close($provider);

            return true;
        } catch (BindException $e) {
            $d = $e->getDetailedError();
            $code = $d ? (int) $d->getErrorCode() : (int) $e->getCode();
            $x = E::explain($code, E::STAGE_VERIFY_LOGIN, $d?->getDiagnosticMessage());
            $this->step($report, E::STAGE_VERIFY_LOGIN, 'warn', $x['category'].' (code '.$code.'): '.$x['cause']
                .' AD replication delay can also cause this — retry the bind in a minute.');
        } catch (Throwable $e) {
            $this->step($report, E::STAGE_VERIFY_LOGIN, 'warn', 'Login check could not run: '.$this->safeMessage($e));
        }

        return false;
    }

    /* ===================================================================
     |  Helpers
     * =================================================================== */

    /** The login connection's settings, with the POC-only transport overrides applied. */
    private function connectionSettings(): array
    {
        $s = $this->ldapSettings;
        $o = (array) ($this->config['connection'] ?? []);

        if (trim((string) ($o['hosts'] ?? '')) !== '') {
            $s['hosts'] = preg_split('/\s+/', trim($o['hosts']));
        }
        if (($o['port'] ?? null) !== null && $o['port'] !== '') {
            $s['port'] = (int) $o['port'];
        }
        foreach (['use_ssl', 'use_tls'] as $key) {
            if (($o[$key] ?? null) !== null && $o[$key] !== '') {
                $s[$key] = filter_var($o[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }
        $s['timeout'] = max(1, (int) ($o['timeout'] ?? 10));

        return $s;
    }

    private function caCertPath(): ?string
    {
        $path = trim((string) ($this->config['connection']['ca_cert'] ?? ''));

        return $path === '' ? null : $path;
    }

    /**
     * Point OpenLDAP at the CA for LDAPS and require a valid certificate.
     *
     * Set globally (null link) because this PHP build has no
     * LDAP_OPT_X_TLS_NEWCTX, so per-connection TLS options set after
     * ldap_connect() are not guaranteed to be honoured. The global only adds
     * trust in this CA for this PHP process; the login connection is plain
     * LDAP and does not use TLS. Without LDAP_POC_CA_CERT the system
     * ldap.conf (TLS_CACERT) applies.
     */
    private function applyTlsTrust(): void
    {
        $settings = $this->connectionSettings();
        if (! $settings['use_ssl'] && ! $settings['use_tls']) {
            return;
        }

        @ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_DEMAND);

        if ($ca = $this->caCertPath()) {
            @ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, $ca);
        }
    }

    private function guardConfiguration(array &$report): bool
    {
        if ($problems = $this->configurationProblems()) {
            $this->fail($report, E::STAGE_ENVIRONMENT, 'POC not runnable', implode(' ', $problems),
                'Fix the configuration listed above; nothing was sent to LDAP.');

            return false;
        }

        return true;
    }

    private function invalidUsernameReason(string $username): ?string
    {
        if (! str_starts_with($username, $this->prefix())) {
            return "Test usernames must start with '{$this->prefix()}' so they are recognisable and removable.";
        }
        if (strlen($username) <= strlen($this->prefix())) {
            return 'The username needs at least one character after the prefix.';
        }
        if (strlen($username) > self::SAM_MAX_LENGTH) {
            return 'sAMAccountName is limited to '.self::SAM_MAX_LENGTH.' characters.';
        }
        if (! preg_match('/^[a-z0-9][a-z0-9._-]*[a-z0-9]$/', $username)) {
            return 'Only lower-case letters, digits, dot, dash and underscore are allowed, and it must not end with a dot or dash.';
        }

        return null;
    }

    /** @return array<int, array>|null entries, [] when none, null on LDAP error */
    private function search(Ldap $conn, string $base, string $filter, array $fields): ?array
    {
        $result = @$conn->search($base, $filter, $fields);

        return $result === false ? null : $this->entries($conn, $result);
    }

    private function read(Ldap $conn, string $dn, array $fields): ?array
    {
        $result = @$conn->read($dn, '(objectClass=*)', $fields);

        return $result === false ? null : $this->entries($conn, $result);
    }

    private function entries(Ldap $conn, $result): array
    {
        $raw = @$conn->getEntries($result) ?: ['count' => 0];
        $out = [];
        for ($i = 0; $i < ($raw['count'] ?? 0); $i++) {
            if (isset($raw[$i]['dn'])) { // skip search referrals
                $out[] = $raw[$i];
            }
        }

        return $out;
    }

    private function values(array $entry, string $attr): array
    {
        $v = $entry[strtolower($attr)] ?? [];
        unset($v['count']);

        return array_values($v);
    }

    private function readGuid(Ldap $conn, string $dn): ?string
    {
        $entries = $this->read($conn, $dn, ['objectguid']);
        $bin = $entries ? ($this->values($entries[0], 'objectguid')[0] ?? null) : null;

        return $bin ? Utilities::binaryGuidToString($bin) : null;
    }

    private function normaliseEntry(array $entry): array
    {
        $guid = $this->values($entry, 'objectguid')[0] ?? null;

        return [
            'dn' => $entry['dn'],
            'guid' => $guid ? Utilities::binaryGuidToString($guid) : null,
            'description' => implode(' ', $this->values($entry, 'description')),
            'member_of' => $this->values($entry, 'memberof'),
            'admin_count' => (int) ($this->values($entry, 'admincount')[0] ?? 0),
            'object_classes' => $this->values($entry, 'objectclass'),
        ];
    }

    private static function normaliseDn(string $dn): string
    {
        return strtolower(preg_replace('/\s*,\s*/', ',', trim($dn)));
    }

    private static function isStrictlyUnder(string $dn, string $baseDn): bool
    {
        $dn = self::normaliseDn($dn);
        $base = self::normaliseDn($baseDn);

        return $base !== '' && str_ends_with($dn, ','.$base) && strlen($dn) > strlen($base) + 1;
    }

    private function prefix(): string
    {
        return strtolower(trim((string) ($this->config['username_prefix'] ?? '')));
    }

    private function upnSuffix(): string
    {
        $suffix = (string) ($this->config['upn_suffix'] ?: ($this->ldapSettings['account_suffix'] ?? ''));

        return ltrim(trim($suffix), '@');
    }

    private function close(Provider $provider): void
    {
        try {
            @$provider->getConnection()->close();
        } catch (Throwable) {
        }
    }

    /** Exception text with anything password-like removed (defence in depth). */
    private function safeMessage(Throwable $e): string
    {
        $msg = $e->getMessage();
        $secret = (string) ($this->ldapSettings['password'] ?? '');

        return $secret !== '' ? str_replace($secret, '[redacted]', $msg) : $msg;
    }

    private function newReport(string $action): array
    {
        return ['action' => $action, 'ok' => false, 'at' => now()->toIso8601String(),
            'steps' => [], 'warnings' => [], 'error' => null];
    }

    private function step(array &$report, string $stage, string $status, string $detail): void
    {
        $report['steps'][] = compact('stage', 'status', 'detail');
    }

    private function ldapFail(array &$report, Ldap $conn, string $stage, ?int $fallbackCode = null): void
    {
        $d = $conn->getDetailedError();
        $code = $d ? (int) $d->getErrorCode() : ($fallbackCode ?? (int) $conn->errNo());
        $this->ldapError($report, $stage, $code,
            $d ? $d->getErrorMessage() : (string) $conn->getLastError(),
            $d?->getDiagnosticMessage());
    }

    private function ldapError(array &$report, string $stage, int $code, ?string $message, ?string $diagnostic): void
    {
        $x = E::explain($code, $stage, $diagnostic);
        $report['error'] = ['stage' => $stage, 'code' => $code, 'message' => $message, 'diagnostic' => $diagnostic] + $x;
        $this->step($report, $stage, 'fail', $x['category'].' (LDAP code '.$code.')');
    }

    private function fail(array &$report, string $stage, string $category, string $cause, string $fix): array
    {
        $report['error'] = ['stage' => $stage, 'code' => null, 'message' => $cause, 'diagnostic' => null,
            'category' => $category, 'cause' => $cause, 'fix' => $fix, 'ad_code' => null];
        $this->step($report, $stage, 'fail', $category);

        return $report;
    }

    private function finish(array $report, ?string $username = null, ?string $actor = null): array
    {
        // Never logs passwords: neither input is passed here.
        Log::info('LDAP POC '.$report['action'], [
            'username' => $username,
            'actor' => $actor,
            'ok' => $report['ok'],
            'stage' => $report['error']['stage'] ?? null,
            'code' => $report['error']['code'] ?? null,
            'category' => $report['error']['category'] ?? null,
        ]);

        return $report;
    }
}
