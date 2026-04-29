<?php

declare(strict_types=1);

namespace App;

/**
 * Utility methods for backed enums.
 *
 * Provides fromName(), names(), and values() — the natural complements to
 * the built-in from() / tryFrom() methods on backed enums.
 *
 * @phpstan-require-implements \BackedEnum
 */
trait EnumTrait
{
    /** @return list<\BackedEnum> */
    abstract public static function cases(): array;

    /** Return the case whose name matches $name, or null if not found. */
    public static function tryFromName(string $name): static|null
    {
        foreach (static::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }

    /** Return the case whose name matches $name, or throw \ValueError if not found. */
    public static function fromName(string $name): static
    {
        return static::tryFromName($name) ?? throw new \ValueError(
            '"' . $name . '" is not a valid name for enum "' . static::class . '"'
        );
    }

    /**
     * Return an array of all case names.
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(static::cases(), 'name');
    }

    /**
     * Return an array of all case values.
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }
}
