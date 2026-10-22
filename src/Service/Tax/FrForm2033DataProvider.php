<?php

namespace Wexample\SymfonyAccountingFr\Service\Tax;

use Wexample\SymfonyAccounting\Class\ReportResult;
use Wexample\SymfonyAccounting\Entity\FiscalYear;
use Wexample\SymfonyAccounting\Service\Report\FinancialStatementService;

/**
 * The data of forms 2033-A (balance sheet) and 2033-B (income statement) of the
 * simplified regime, by box code, from the French statements. Rendering belongs
 * to pdf-factory. Box codes follow the 2033 forms; check them against the form
 * of the year, as their layout changes.
 */
class FrForm2033DataProvider
{
    /** 2033-A box → [balance sheet line, column]: column "gross", "deduct" or "amount". */
    public const array FORM_A = [
        '010' => ['intangible_assets', 'gross'],
        '012' => ['intangible_assets', 'deduct'],
        '028' => ['tangible_assets', 'gross'],
        '030' => ['tangible_assets', 'deduct'],
        '040' => ['financial_assets', 'gross'],
        '042' => ['financial_assets', 'deduct'],
        '050' => ['stocks', 'gross'],
        '052' => ['stocks', 'deduct'],
        '064' => ['advances_paid', 'amount'],
        '068' => ['trade_receivables', 'gross'],
        '070' => ['trade_receivables', 'deduct'],
        '072' => ['other_receivables', 'gross'],
        '074' => ['other_receivables', 'deduct'],
        '080' => ['investments', 'gross'],
        '084' => ['cash', 'amount'],
        '092' => ['prepaid_expenses', 'amount'],
        '110' => ['assets', 'amount'],
        '120' => ['capital', 'amount'],
        '132' => ['reserves', 'amount'],
        '134' => ['retained_earnings', 'amount'],
        '136' => ['year_result', 'amount'],
        '140' => ['regulated_provisions', 'amount'],
        '142' => ['equity', 'amount'],
        '154' => ['provisions', 'amount'],
        '156' => ['borrowings', 'amount'],
        '164' => ['advances_received', 'amount'],
        '166' => ['suppliers', 'amount'],
        '169' => ['tax_social_debts', 'amount'],
        '172' => ['other_debts', 'amount'],
        '174' => ['deferred_income', 'amount'],
        '180' => ['liabilities', 'amount'],
    ];

    /** 2033-B box → income statement line. */
    public const array FORM_B = [
        '210' => 'sales_goods',
        '218' => 'sales_services',
        '222' => 'production',
        '226' => 'operating_grants',
        '230' => 'other_operating_income',
        '232' => 'operating_income',
        '234' => 'purchases_goods',
        '236' => 'stock_change_goods',
        '238' => 'raw_materials',
        '240' => 'stock_change_raw',
        '242' => 'external_charges',
        '244' => 'taxes',
        '250' => 'wages',
        '252' => 'social_charges',
        '254' => 'depreciation',
        '262' => 'other_operating_charges',
        '264' => 'operating_charges',
        '270' => 'operating_result',
        '280' => 'financial_income',
        '290' => 'exceptional_income',
        '294' => 'financial_charges',
        '300' => 'exceptional_charges',
        '306' => 'income_tax',
        '310' => 'result',
    ];

    public function __construct(
        private readonly FinancialStatementService $statementService,
    ) {
    }

    /**
     * @return array{2033-A: array<string, int>, 2033-B: array<string, int>}
     */
    public function build(FiscalYear $fiscalYear): array
    {
        $sheet = $this->statementService->build($fiscalYear, 'balance_sheet');
        $income = $this->statementService->build($fiscalYear, 'income_statement');

        $formA = [];
        foreach (self::FORM_A as $box => [$line, $column]) {
            $formA[$box] = $this->value($sheet, $line, $column);
        }

        $formB = [];
        foreach (self::FORM_B as $box => $line) {
            $formB[$box] = $income->get($line);
        }

        return ['2033-A' => $formA, '2033-B' => $formB];
    }

    private function value(
        ReportResult $result,
        string $line,
        string $column
    ): int {
        return $result->lines[$line][$column] ?? 0;
    }
}
