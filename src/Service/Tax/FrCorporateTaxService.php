<?php

namespace Wexample\SymfonyAccountingFr\Service\Tax;

use DateTimeImmutable;
use Wexample\SymfonyAccounting\Class\EntryDraft;
use Wexample\SymfonyAccounting\Entity\FiscalYear;
use Wexample\SymfonyAccounting\Entity\JournalEntry;
use Wexample\SymfonyAccounting\Enum\JournalType;
use Wexample\SymfonyAccounting\Service\Ledger\FiscalYearClosingService;
use Wexample\SymfonyAccounting\Service\Ledger\PostingService;

/**
 * French corporate income tax (impôt sur les sociétés), by year of the
 * fiscal year's end: rate brackets, the four quarterly deposits, the booking.
 *
 * The reduced rate (SME: turnover under 10 M€, capital fully paid and held at
 * least 75 % by individuals) applies unless the ledger setting
 * `fr_is_reduced_rate` is false. Network's version always answered 15 %.
 */
class FrCorporateTaxService
{
    public const string SOURCE = 'fr_corporate_tax';

    /**
     * From a year on: [reduced-rate ceiling in cents, reduced rate, normal rate], basis points.
     */
    public const array SCHEDULE = [
        2019 => [3812000, 1500, 2800],
        2020 => [3812000, 1500, 2800],
        2021 => [3812000, 1500, 2650],
        2022 => [3812000, 1500, 2500],
        2023 => [4250000, 1500, 2500],
    ];

    /** Deposits on 15/03, 15/06, 15/09 and 15/12, each a quarter of last year's tax. */
    public const array DEPOSIT_DATES = ['03-15', '06-15', '09-15', '12-15'];

    /** No deposit when last year's tax was under 3 000 €. */
    public const int DEPOSIT_THRESHOLD = 300000;

    public function __construct(
        private readonly FiscalYearClosingService $closingService,
        private readonly PostingService $postingService,
    ) {
    }

    /**
     * @return array{0: int, 1: int, 2: int} Ceiling, reduced rate, normal rate.
     */
    public function getSchedule(int $year): array
    {
        $schedule = null;

        foreach (self::SCHEDULE as $from => $values) {
            if ($year >= $from) {
                $schedule = $values;
            }
        }

        return $schedule ?? reset(self::SCHEDULE);
    }

    /**
     * Tax on a taxable profit, in cents; nothing on a loss. The profit is
     * rounded down to the euro, as the form wants.
     */
    public function compute(
        int $taxableProfit,
        int $year,
        bool $reducedRate = true
    ): int {
        if ($taxableProfit <= 0) {
            return 0;
        }

        $profit = intdiv($taxableProfit, 100) * 100;
        [$ceiling, $reduced, $normal] = $this->getSchedule($year);

        if (! $reducedRate) {
            return intdiv($profit * $normal + 5000, 10000);
        }

        $low = min($profit, $ceiling);
        $high = max(0, $profit - $ceiling);

        return intdiv($low * $reduced + 5000, 10000) + intdiv($high * $normal + 5000, 10000);
    }

    /**
     * The tax of a fiscal year, from its result before tax.
     */
    public function computeForFiscalYear(FiscalYear $fiscalYear): int
    {
        return $this->compute(
            $this->closingService->computeResult($fiscalYear),
            (int) $fiscalYear->getDateEnd()->format('Y'),
            (bool) $fiscalYear->getLedger()->getSetting('fr_is_reduced_rate', true)
        );
    }

    /**
     * @return list<array{date: DateTimeImmutable, amount: int}>
     */
    public function computeDeposits(
        int $previousTax,
        int $year
    ): array {
        if ($previousTax < self::DEPOSIT_THRESHOLD) {
            return [];
        }

        $quarter = intdiv($previousTax, 4);
        $deposits = [];

        foreach (self::DEPOSIT_DATES as $index => $date) {
            $deposits[] = [
                'date' => new DateTimeImmutable($year.'-'.$date),
                // The last deposit takes the rounding.
                'amount' => 3 === $index ? $previousTax - 3 * $quarter : $quarter,
            ];
        }

        return $deposits;
    }

    /**
     * Books the year's tax: charge 695 against the State 444, at the year's end.
     */
    public function book(
        FiscalYear $fiscalYear,
        ?int $amount = null
    ): ?JournalEntry {
        $amount ??= $this->computeForFiscalYear($fiscalYear);

        if ($amount <= 0) {
            return null;
        }

        return $this->postingService->postDraft(
            $fiscalYear->getLedger(),
            (new EntryDraft(JournalType::Miscellaneous, $fiscalYear->getDateEnd(), 'Impôt sur les sociétés '.$fiscalYear->getLabel()))
                ->source(self::SOURCE, (string) $fiscalYear->getId())
                ->debit('695', $amount)
                ->credit('444', $amount)
        );
    }
}
