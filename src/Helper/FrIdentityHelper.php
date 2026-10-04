<?php

namespace Wexample\SymfonyAccountingFr\Helper;

/**
 * French company identifiers: SIREN (9 digits), SIRET (SIREN + 5-digit
 * establishment number), intracommunity VAT number (FR + 2-digit key + SIREN).
 */
class FrIdentityHelper
{
    /** La Poste's SIREN: its SIRETs follow their own rule. */
    final public const string SIREN_LA_POSTE = '356000000';

    public static function normalize(string $identifier): string
    {
        return preg_replace('/[^0-9A-Z]/', '', strtoupper($identifier));
    }

    public static function isValidSiren(string $siren): bool
    {
        $siren = static::normalize($siren);

        return 1 === preg_match('/^\d{9}$/', $siren) && static::luhn($siren);
    }

    public static function isValidSiret(string $siret): bool
    {
        $siret = static::normalize($siret);

        if (! preg_match('/^\d{14}$/', $siret)) {
            return false;
        }

        if (str_starts_with($siret, self::SIREN_LA_POSTE)) {
            // La Poste: the sum of the digits is a multiple of 5.
            return 0 === array_sum(str_split($siret)) % 5;
        }

        return static::luhn($siret);
    }

    public static function sirenOf(string $identifier): ?string
    {
        $identifier = static::normalize($identifier);

        if (str_starts_with($identifier, 'FR')) {
            $identifier = substr($identifier, 4);
        }

        $siren = substr($identifier, 0, 9);

        return static::isValidSiren($siren) ? $siren : null;
    }

    public static function computeVatKey(string $siren): string
    {
        return str_pad((string) ((12 + 3 * ((int) $siren % 97)) % 97), 2, '0', STR_PAD_LEFT);
    }

    public static function buildVatNumber(string $siren): string
    {
        $siren = static::normalize($siren);

        return 'FR'.static::computeVatKey($siren).$siren;
    }

    /**
     * Numeric keys are checked; the older alphanumeric keys are accepted as is.
     */
    public static function isValidVatNumber(string $vatNumber): bool
    {
        $vatNumber = static::normalize($vatNumber);

        if (! preg_match('/^FR([0-9A-Z]{2})(\d{9})$/', $vatNumber, $matches)) {
            return false;
        }

        if (! ctype_digit($matches[1])) {
            return true;
        }

        return static::isValidSiren($matches[2]) && static::computeVatKey($matches[2]) === $matches[1];
    }

    public static function luhn(string $digits): bool
    {
        $sum = 0;
        $double = false;

        for ($i = strlen($digits) - 1; $i >= 0; --$i) {
            $digit = (int) $digits[$i];

            if ($double) {
                $digit *= 2;
                $digit = $digit > 9 ? $digit - 9 : $digit;
            }

            $sum += $digit;
            $double = ! $double;
        }

        return 0 === $sum % 10;
    }
}
