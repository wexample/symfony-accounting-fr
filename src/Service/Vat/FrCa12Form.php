<?php

namespace Wexample\SymfonyAccountingFr\Service\Vat;

use Wexample\SymfonyAccounting\Class\VatReturn;
use Wexample\SymfonyAccounting\Entity\Ledger;
use Wexample\SymfonyAccounting\Enum\InvoiceDirection;
use Wexample\SymfonyAccounting\Enum\VatKind;
use Wexample\SymfonyAccounting\Enum\VatPeriodicity;
use Wexample\SymfonyAccounting\Interface\VatReturnFormInterface;

/**
 * Form 3517-S-CA12 (simplified regime, annual return with two deposits). Line
 * codes follow the form network filled; check them against the form of the year.
 *
 * Deposits paid during the year (booked on the deposits account) are deducted;
 * FrCa12DepositCalculator gives the July and December deposits to pay.
 */
class FrCa12Form implements VatReturnFormInterface
{
    public const array RATE_LINES = [2000 => '5A', 1000 => '06', 550 => '07', 210 => '08'];

    public function getKey(): string
    {
        return 'fr_ca12';
    }

    public function supports(Ledger $ledger): bool
    {
        return 'FR' === strtoupper((string) $ledger->getCountryCode())
            && $ledger->isVatSubject()
            && (VatPeriodicity::Annual === $ledger->getVatPeriodicity() || 'fr_ca12' === $ledger->getVatRegime());
    }

    public function fill(VatReturn $vatReturn): array
    {
        $boxes = [];

        foreach (self::RATE_LINES as $rate => $line) {
            $sales = $vatReturn->sum(InvoiceDirection::Sale, VatKind::Domestic, $rate);
            $selfAssessed = $vatReturn->sum(InvoiceDirection::Purchase, [VatKind::ReverseCharge, VatKind::IntraEuAcquisition], $rate);
            $boxes[$line.'_base'] = $sales['base'] + $selfAssessed['base'];
            $boxes[$line.'_tax'] = $sales['due'] + $selfAssessed['due'];
        }

        $due = $vatReturn->getDue();
        $deductible = $vatReturn->getDeductible();
        $totalDeductible = $deductible + $vatReturn->previousCredit;

        $boxes['16'] = $due;
        $boxes['20'] = $deductible;
        $boxes['22'] = $deductible;
        $boxes['24'] = $vatReturn->previousCredit;
        $boxes['26'] = $totalDeductible;
        $boxes['28'] = max(0, $due - $totalDeductible);
        $boxes['29'] = max(0, $totalDeductible - $due);
        $boxes['30'] = $vatReturn->deposits;
        $boxes['33'] = max(0, $boxes['28'] - $vatReturn->deposits);
        $boxes['35'] = $boxes['29'] + max(0, $vatReturn->deposits - $boxes['28']);
        // Base of next year's deposits: the net VAT of this year (line 57).
        $boxes['57'] = max(0, $due - $deductible);

        return $boxes;
    }
}
