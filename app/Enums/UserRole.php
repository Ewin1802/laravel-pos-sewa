<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case OPERATOR = 'operator';
    case MERCHANT = 'merchant';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::OPERATOR => 'Operator',
            self::MERCHANT => 'Merchant',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    public function canAccessFilament(): bool
    {
        return match ($this) {
            self::ADMIN => true,
            self::OPERATOR, self::MERCHANT => false,
        };
    }
}
