<?php

namespace Wexample\SymfonyAccountingFr\Service\Vat;

use DateTimeImmutable;

/**
 * Simplified regime deposits (CGI art. 1692): 55 % of last year's net VAT
 * (CA12 line 57) in July, 40 % in December. None when that VAT was under
 * 1 000 €, the threshold below which deposits are not due.
 */
class FrCa12DepositCalculator
{
    public const int THRESHOLD = 100000;

    /**
     * @return list<array{date: DateTimeImmutable, amount: int}>
     */
    public function compute(
        int $previousNetVat,
        int $year
    ): array {
        if ($previousNetVat < self::THRESHOLD) {
            return [];
        }

        return [
            ['date' => new DateTimeImmutable($year.'-07-15'), 'amount' => intdiv($previousNetVat * 55 + 50, 100)],
            ['date' => new DateTimeImmutable($year.'-12-15'), 'amount' => intdiv($previousNetVat * 40 + 50, 100)],
        ];
    }
}
