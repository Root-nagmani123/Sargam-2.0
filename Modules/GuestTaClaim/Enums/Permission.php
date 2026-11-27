<?php

namespace Modules\GuestTaClaim\Enums;

enum Permission: string
{
    case CreateClaim = 'claim.create';
    case EditSessions = 'claim.edit';
    case Forward = 'claim.forward';
    case GenerateReport = 'claim.report';
    case Approve = 'cc.approve';
    case ViewPayments = 'acc.view';
    case MarkPaid = 'acc.pay';
    case Export = 'export';
    case ManagePermissions = 'perm.manage';

    public function label(): string
    {
        return match ($this) {
            self::CreateClaim => 'Create claim',
            self::EditSessions => 'Edit session details',
            self::Forward => 'Forward to course coordinator',
            self::GenerateReport => 'Generate report',
            self::Approve => 'Approve or return claims',
            self::ViewPayments => 'View payment requests',
            self::MarkPaid => 'Mark claim as paid',
            self::Export => 'Print and export',
            self::ManagePermissions => 'Assign permission',
        };
    }

    /** @return array<string, array<int, self>> */
    public static function groups(): array
    {
        return [
            'Claim preparation (TA & Training)' => [self::CreateClaim, self::EditSessions, self::Forward, self::GenerateReport],
            'Course coordinator' => [self::Approve],
            'Accounts' => [self::ViewPayments, self::MarkPaid],
            'Common' => [self::Export, self::ManagePermissions],
        ];
    }

    /** Department tabs on the permission screen, keyed by the permission that defines the group. */
    public static function departments(): array
    {
        return [
            'ta' => ['label' => 'TA & Training', 'permission' => self::CreateClaim],
            'cc' => ['label' => 'Course Coordinator', 'permission' => self::Approve],
            'accounts' => ['label' => 'Accounts', 'permission' => self::ViewPayments],
        ];
    }

    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }
}
