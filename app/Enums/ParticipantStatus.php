<?php

namespace App\Enums;

enum ParticipantStatus: string
{
    case Unclaimed = 'unclaimed';
    case Claimed = 'claimed';
    case CheckedIn = 'checked_in';

    public function label(): string
    {
        return match ($this) {
            self::Unclaimed => 'Belum Ditukar',
            self::Claimed => 'Sudah Ditukar (BIB Assigned)',
            self::CheckedIn => 'Sudah Check-In',
        };
    }
}
