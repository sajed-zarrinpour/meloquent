<?php
namespace SajedZarinpour\Meloquent\Enums;

enum RedisTypesEnum: string
{
    case STRING = 'string';
    case HASH = 'hash';

    public static function toArray(): array
    {
        $array = [];
        foreach (self::cases() as $case) {
            $array[$case->name] = $case->value;
        }

        return $array;
    }

    public function label(): string
    {
        return match ($this) {
            self::STRING => 'String',
            self::HASH => 'Hash',
        };
    }
}
