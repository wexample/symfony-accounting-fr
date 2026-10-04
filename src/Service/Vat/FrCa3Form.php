<?php

namespace Wexample\SymfonyAccountingFr\Service\Vat;

use Wexample\SymfonyAccounting\Class\VatReturn;
use Wexample\SymfonyAccounting\Entity\Ledger;
use Wexample\SymfonyAccounting\Enum\InvoiceDirection;
use Wexample\SymfonyAccounting\Enum\VatKind;
use Wexample\SymfonyAccounting\Enum\VatPeriodicity;
use Wexample\SymfonyAccounting\Interface\VatReturnFormInterface;

/**
 * Form 3310-CA3 (normal regime, monthly or quarterly). Box codes follow the
 * 2024 form; check them against the form of the year before filing.
 *
 * Bases and taxes are in cents; the form itself is filled in whole euros,
 * which rendering rounds.
 */
class FrCa3Form implements VatReturnFormInterface
{
    public const array RATE_LINES = [2000 => '08', 550 => '09', 1000 => '9B', 210 => '11'];

    public function getKey(): string
    {
        return 'fr_ca3';
    }

    public function supports(Ledger $ledger): bool
    {
        return 'FR' === strtoupper((string) $ledger->getCountryCode())
            && $ledger->isVatSubject()
            && VatPeriodicity::Annual !== $ledger->getVatPeriodicity()
            && 'fr_ca12' !== $ledger->getVatRegime();
    }

    public function fill(VatReturn $vatReturn): array
    {
        $sale = InvoiceDirection::Sale;
        $purchase = InvoiceDirection::Purchase;
        $boxes = [
            // A. Taxable operations (bases).
            'A1' => $vatReturn->sum($sale, VatKind::Domestic)['base'],
            'A3' => $vatReturn->sum($purchase, VatKind::ReverseCharge)['base'],
            'B2' => $vatReturn->sum($purchase, VatKind::IntraEuAcquisition)['base'],
            // B. Non-taxable operations.
            'E1' => $vatReturn->sum($sale, VatKind::Export)['base'],
            'E2' => $vatReturn->sum($sale, [VatKind::IntraEuServices, VatKind::Exempt, VatKind::OutOfScope])['base'],
            'F2' => $vatReturn->sum($sale, VatKind::IntraEuGoods)['base'],
        ];

        // Gross VAT, per rate, sales and self-assessed purchases together.
        foreach (self::RATE_LINES as $rate => $line) {
            $sales = $vatReturn->sum($sale, VatKind::Domestic, $rate);
            $selfAssessed = $vatReturn->sum($purchase, [VatKind::ReverseCharge, VatKind::IntraEuAcquisition], $rate);
            $boxes[$line.'_base'] = $sales['base'] + $selfAssessed['base'];
            $boxes[$line.'_tax'] = $sales['due'] + $selfAssessed['due'];
        }

        $due = $vatReturn->getDue();
        $deductible = $vatReturn->getDeductible() + $vatReturn->previousCredit;

        $boxes['16'] = $due;
        $boxes['17'] = $vatReturn->sum($purchase, VatKind::IntraEuAcquisition)['due'];
        $boxes['20'] = $vatReturn->getDeductible();
        $boxes['22'] = $vatReturn->previousCredit;
        $boxes['23'] = $deductible;
        $boxes['25'] = max(0, $deductible - $due);
        $boxes['28'] = max(0, $due - $deductible);
        $boxes['32'] = $boxes['28'];

        return $boxes;
    }
}
