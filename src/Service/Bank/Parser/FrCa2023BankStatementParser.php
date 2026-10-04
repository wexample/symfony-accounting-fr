<?php

namespace Wexample\SymfonyAccountingFr\Service\Bank\Parser;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Wexample\SymfonyAccounting\Class\ParsedBalance;
use Wexample\SymfonyAccounting\Class\ParsedStatement;
use Wexample\SymfonyAccounting\Class\ParsedTransaction;
use Wexample\SymfonyAccounting\Service\Bank\Parser\AbstractBankStatementParser;

/**
 * Crédit Agricole, 2023 spreadsheet export: "Téléchargement du dd/mm/YYYY" in A1,
 * the balance in `balance_cell` (C7 by default), then rows from `first_row` (11):
 * A date, B label (several lines), C debit, D credit. Reading stops at the first
 * row without a date.
 */
class FrCa2023BankStatementParser extends AbstractBankStatementParser
{
    public function getKey(): string
    {
        return 'fr_ca_2023';
    }

    public function getLabel(): string
    {
        return 'Crédit Agricole (2023, XLSX)';
    }

    public function supports(
        string $content,
        ?string $filename = null
    ): bool {
        return str_starts_with($content, "PK\x03\x04") && (null === $filename || preg_match('/\.xlsx?$/i', $filename));
    }

    public function parse(
        string $content,
        array $options = []
    ): ParsedStatement {
        $file = tempnam(sys_get_temp_dir(), 'ca');
        file_put_contents($file, $content);

        try {
            $sheet = IOFactory::load($file)->getActiveSheet();
        } finally {
            unlink($file);
        }

        return $this->parseSheet($sheet, $options);
    }

    public function parseSheet(
        Worksheet $sheet,
        array $options = []
    ): ParsedStatement {
        $statement = new ParsedStatement();
        $firstRow = (int) ($options['first_row'] ?? 11);

        if (preg_match('/(\d\d\/\d\d\/\d{4})/', (string) $sheet->getCell('A1')->getValue(), $matches)) {
            $balance = $sheet->getCell($options['balance_cell'] ?? 'C7')->getValue();

            if (null !== $balance && '' !== $balance) {
                $statement->addBalance(new ParsedBalance(
                    $this->parseDate($matches[1], 'd/m/Y'),
                    is_numeric($balance) ? (int) round($balance * 100) : $this->parseAmount((string) $balance)
                ));
            }
        }

        for ($row = $firstRow; $row <= $sheet->getHighestRow(); ++$row) {
            $date = $sheet->getCell('A'.$row)->getValue();

            if (null === $date || '' === $date) {
                break;
            }

            $debit = $sheet->getCell('C'.$row)->getValue();
            $credit = $sheet->getCell('D'.$row)->getValue();
            $amount = $this->cellAmount($credit) - abs($this->cellAmount($debit));

            $statement->addTransaction(new ParsedTransaction(
                date: is_numeric($date)
                    ? \DateTimeImmutable::createFromMutable(Date::excelToDateTimeObject($date))->setTime(0, 0)
                    : $this->parseDate((string) $date, 'd/m/Y'),
                amount: $amount,
                label: $this->cleanLabel((string) $sheet->getCell('B'.$row)->getValue()),
            ));
        }

        return $statement;
    }

    private function cellAmount(mixed $value): int
    {
        if (null === $value || '' === $value) {
            return 0;
        }

        return is_numeric($value) ? (int) round($value * 100) : $this->parseAmount((string) $value);
    }
}
