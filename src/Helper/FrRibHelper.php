<?php

namespace Wexample\SymfonyAccountingFr\Helper;

/**
 * The French RIB (bank, branch, account number, key), still printed on invoices
 * by some, derived from or checked against a French IBAN.
 */
class FrRibHelper
{
    /**
     * @return array{bank: string, branch: string, account: string, key: string}|null
     */
    public static function fromIban(string $iban): ?array
    {
        $iban = strtoupper(str_replace(' ', '', $iban));

        if (! preg_match('/^FR\d{2}(\d{5})(\d{5})([0-9A-Z]{11})(\d{2})$/', $iban, $matches)) {
            return null;
        }

        return ['bank' => $matches[1], 'branch' => $matches[2], 'account' => $matches[3], 'key' => $matches[4]];
    }

    public static function computeKey(
        string $bank,
        string $branch,
        string $account
    ): string {
        // Letters of the account number stand for digits.
        $account = strtr(strtoupper($account), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '12345678912345678923456789');
        $key = 97 - ((89 * (int) $bank + 15 * (int) $branch + 3 * (int) $account) % 97);

        return str_pad((string) $key, 2, '0', STR_PAD_LEFT);
    }

    public static function isValid(
        string $bank,
        string $branch,
        string $account,
        string $key
    ): bool {
        return static::computeKey($bank, $branch, $account) === $key;
    }
}
