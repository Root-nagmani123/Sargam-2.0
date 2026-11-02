<?php

namespace App\Services;

use App\Support\IdCardSecurityMapper;
use App\Support\SecurityApproverRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * In-app (bell) notifications for the Security module: Employee ID Card, Family ID Card
 * and Vehicle Pass requests.
 *
 *  - Approvers are told when a request reaches their stage (Approval I authority,
 *    Approval II, Approval III).
 *  - The applicant is told when the request is finally approved or is rejected.
 *
 * Receivers are user_credentials.user_id values (= employee_master.pk), which is what the
 * bell reads (Auth::user()->user_id). A notification failure is logged and never blocks
 * the request or the approval that triggered it.
 *
 * Click-through: ID card and vehicle approver notifications, and the applicant's own ID card
 * (non-duplicate) notifications, carry a link to that request's detail page (see the *Link()
 * builders), so the bell opens the request itself. Everything
 * else opens the list mapped in config/notifications.php under the 'security' type — Family
 * ID cards included, since a group is approved together from the list, not the member page.
 */
class SecurityRequestNotifier
{
    public const TYPE = 'security';

    // Approver-side modules (open the approval list for that stage)
    public const MODULE_IDCARD_APPROVAL1 = 'IdCardApproval1';

    public const MODULE_IDCARD_APPROVAL2 = 'IdCardApproval2';

    public const MODULE_IDCARD_APPROVAL3 = 'IdCardApproval3';

    public const MODULE_FAMILY_APPROVAL = 'FamilyIdCardApproval';

    public const MODULE_VEHICLE_APPROVAL = 'VehiclePassApproval';

    // Applicant-side modules (open the applicant's own request list)
    public const MODULE_IDCARD_STATUS = 'IdCardStatus';

    public const MODULE_DUPLICATE_IDCARD_STATUS = 'DuplicateIdCardStatus';

    public const MODULE_FAMILY_STATUS = 'FamilyIdCardStatus';

    public const MODULE_VEHICLE_STATUS = 'VehiclePassStatus';

    /** Routes a notification link may open (checked again when the link is followed). */
    public const LINK_ROUTES = [
        'admin.security.employee_idcard_approval.show',
        'admin.security.vehicle_pass_approval.show',
        'admin.employee_idcard.show',
    ];

    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Employee / duplicate ID card approval page for one request.
     * $key is the id the approval lists encrypt: emp_id_apply (permanent), "c-{pk}" (contractual),
     * "p-dup-{emp_id_apply}" / "c-dup-{emp_id_apply}" (duplicates).
     *
     * @return array{route: string, encrypt: array<string, string>, query: array<string, int>}
     */
    public static function idCardApprovalLink(string $key, int $stage): array
    {
        return ['route' => 'admin.security.employee_idcard_approval.show', 'encrypt' => ['id' => $key], 'query' => ['stage' => $stage]];
    }

    /**
     * Applicant's own ID card request page (non-duplicate; duplicates have no applicant detail page).
     * Same key as idCardApprovalLink: emp_id_apply (permanent) or "c-{pk}" (contractual), not encrypted.
     *
     * @return array{route: string, encrypt: array<string, string>, query: array<string, string>}
     */
    public static function idCardStatusLink(string $key): array
    {
        return ['route' => 'admin.employee_idcard.show', 'encrypt' => [], 'query' => ['id' => $key]];
    }

    /**
     * @param  'tw'|'fw'  $kind
     * @return array{route: string, encrypt: array<string, string>, query: array<string, int>}
     */
    public static function vehicleApprovalLink(string $kind, string $vehiclePk): array
    {
        return ['route' => 'admin.security.vehicle_pass_approval.show', 'encrypt' => ['id' => "{$kind}-{$vehiclePk}"], 'query' => []];
    }

    /** Request has reached Approval II (Family / Vehicle Level 1, Employee ID Card Approval II). */
    public function toApproverII(string $module, int|string|null $referencePk, string $title, string $message, ?array $link = null): void
    {
        $this->send($this->userIdsWithRoles(SecurityApproverRoles::APPROVER_II), $module, $referencePk, $title, $message, $link);
    }

    /** Request has reached Approval III (final stage). */
    public function toApproverIII(string $module, int|string|null $referencePk, string $title, string $message, ?array $link = null): void
    {
        $this->send($this->userIdsWithRoles(SecurityApproverRoles::APPROVER_III), $module, $referencePk, $title, $message, $link);
    }

    /**
     * A single employee: the Approval I authority of a contractual request, or the applicant.
     * $employeePk may be employee_master.pk or a legacy pk_old; it is resolved to pk.
     */
    public function toEmployee(int|string|null $employeePk, string $module, int|string|null $referencePk, string $title, string $message, ?array $link = null): void
    {
        $canonical = $this->canonicalEmployeePk($employeePk);
        if ($canonical === null) {
            return;
        }
        $this->send([$canonical], $module, $referencePk, $title, $message, $link);
    }

    /** Display name of the logged-in user, for "X has submitted ..." messages. */
    public function actorName(): string
    {
        try {
            $employeePk = (int) (auth()->user()->user_id ?? 0);
            if ($employeePk > 0) {
                $row = DB::table('employee_master')->where('pk', $employeePk)->first(['first_name', 'last_name']);
                $name = $row ? trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) : '';
                if ($name !== '') {
                    return $name;
                }
            }
            $fallback = trim((string) (auth()->user()->name ?? auth()->user()->user_name ?? ''));

            return $fallback !== '' ? $fallback : 'An employee';
        } catch (\Throwable $e) {
            return 'An employee';
        }
    }

    /**
     * @param  list<int>  $receiverUserIds
     * @param  array{route: string, encrypt: array<string, string>, query: array<string, int>}|null  $link
     */
    private function send(array $receiverUserIds, string $module, int|string|null $referencePk, string $title, string $message, ?array $link = null): void
    {
        try {
            if ($link !== null) {
                $message = NotificationService::appendLinkPayload($message, $link['route'], $link['encrypt'], $link['query']);
            }

            $self = (int) (auth()->user()->user_id ?? 0);
            // Never notify the person who just acted (e.g. an approver who is also the applicant).
            $receivers = array_values(array_filter(
                array_unique(array_map('intval', $receiverUserIds)),
                fn (int $id) => $id > 0 && $id !== $self
            ));
            if ($receivers === []) {
                return;
            }

            $this->notifications->createMultiple(
                $receivers,
                self::TYPE,
                $module,
                is_numeric($referencePk) ? (int) $referencePk : 0,
                $title,
                $message
            );
        } catch (\Throwable $e) {
            Log::error('Security notification failed', [
                'module' => $module,
                'reference' => $referencePk,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Active logins holding any of the given Spatie role names.
     *
     * @param  list<string>  $roleNames
     * @return list<int>
     */
    private function userIdsWithRoles(array $roleNames): array
    {
        try {
            return DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->join('user_credentials as uc', 'uc.pk', '=', 'mhr.model_id')
                ->where('mhr.model_type', \App\Models\User::class)
                ->whereIn('r.name', $roleNames)
                ->whereNotNull('uc.user_id')
                ->pluck('uc.user_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::error('Security notification: approver lookup failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function canonicalEmployeePk(int|string|null $employeePk): ?int
    {
        if ($employeePk === null || $employeePk === '' || ! is_numeric($employeePk) || (int) $employeePk <= 0) {
            return null;
        }
        try {
            return IdCardSecurityMapper::resolveCanonicalEmployeeMasterPk($employeePk);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
