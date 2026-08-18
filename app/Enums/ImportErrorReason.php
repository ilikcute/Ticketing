<?php

namespace App\Enums;

enum ImportErrorReason: string
{
    case DuplicatePin = 'duplicate_pin';
    case InvalidFormat = 'invalid_format';
    case MissingRequiredField = 'missing_required_field';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DuplicatePin => 'PIN sudah terdaftar di database',
            self::InvalidFormat => 'Format data tidak valid',
            self::MissingRequiredField => 'Kolom wajib kosong',
            self::Other => 'Kesalahan lain',
        };
    }
}
