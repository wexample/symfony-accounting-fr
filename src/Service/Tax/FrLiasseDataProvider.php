<?php

namespace Wexample\SymfonyAccountingFr\Service\Tax;

use Wexample\SymfonyAccounting\Entity\FiscalYear;
use Wexample\SymfonyAccounting\Service\Report\FinancialStatementService;

/**
 * The data of the normal regime's tax return, forms 2050 to 2053 (balance sheet,
 * income statement), by box code, from the report definitions `liasse_2050` to
 * `liasse_2053`. Rendering belongs to pdf-factory. The simplified regime files
 * forms 2033 instead (FrForm2033DataProvider). Forms 2054 to 2059 (fixed assets,
 * depreciation, provisions, maturities, tax result) are not computed.
 */
class FrLiasseDataProvider
{
    public function __construct(
        private readonly FinancialStatementService $statementService,
    ) {
    }

    /**
     * @return array{2050: array<string, array{gross: int, deduct: int, net: int}>, 2051: array<string, int>, 2052: array<string, int>, 2053: array<string, int>}
     */
    public function build(FiscalYear $fiscalYear): array
    {
        $assets = [];
        foreach ($this->statementService->build($fiscalYear, 'liasse_2050')->lines as $box => $line) {
            $assets[$box] = ['gross' => $line['gross'], 'deduct' => $line['deduct'], 'net' => $line['amount']];
        }

        [$liabilities, $operations, $exceptional] = array_map(
            fn (string $key) => array_map(fn (array $line) => $line['amount'], $this->statementService->build($fiscalYear, $key)->lines),
            ['liasse_2051', 'liasse_2052', 'liasse_2053'],
        );

        // The totals of the year span both forms.
        $exceptional['HL'] = $operations['FR'] + $operations['GH'] + $operations['GP'] + $exceptional['HD'];
        $exceptional['HM'] = $operations['GF'] + $operations['GI'] + $operations['GU'] + $exceptional['HH'] + $exceptional['HJ'] + $exceptional['HK'];
        $exceptional['HN'] = $exceptional['HL'] - $exceptional['HM'];

        return ['2050' => $assets, '2051' => $liabilities, '2052' => $operations, '2053' => $exceptional];
    }
}
