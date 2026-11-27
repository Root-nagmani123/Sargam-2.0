<?php

namespace Modules\GuestTaClaim\Enums;

enum ClaimStatus: string
{
    case Draft = 'draft';
    case PendingCc = 'pending_cc';
    case Returned = 'returned';
    case PendingAccounts = 'pending_accounts';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingCc => 'With course coordinator',
            self::Returned => 'Returned for correction',
            self::PendingAccounts => 'With Accounts',
            self::Paid => 'Paid',
        };
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::Draft => 't-grey',
            self::PendingCc => 't-amber',
            self::Returned => 't-red',
            self::PendingAccounts => 't-blue',
            self::Paid => 't-green',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Returned], true);
    }

    /** Index of the current step in the 4-step stepper (4 = all done). */
    public function stepIndex(): int
    {
        return match ($this) {
            self::Draft => 0,
            self::PendingCc, self::Returned => 1,
            self::PendingAccounts => 2,
            self::Paid => 4,
        };
    }
}
