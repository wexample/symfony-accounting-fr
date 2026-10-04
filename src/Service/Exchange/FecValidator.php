<?php

namespace Wexample\SymfonyAccountingFr\Service\Exchange;

use Wexample\SymfonyAccounting\Entity\FiscalYear;

/**
 * Checks a FEC before it is handed over, the way the administration's tool
 * does: the 18 columns in order, dates, amounts, balanced entries, entry dates
 * inside the fiscal year.
 */
class FecValidator
{
    /**
     * @return list<string> What is wrong; empty when the file passes.
     */
    public function validate(
        string $content,
        ?FiscalYear $fiscalYear = null
    ): array {
        $errors = [];
        $lines = preg_split('/\r\n|\n/', rtrim(mb_check_encoding($content, 'UTF-8') ? $content : mb_convert_encoding($content, 'UTF-8', 'ISO-8859-15')));
        $headerLine = array_shift($lines);
        $separator = str_contains($headerLine, "\t") ? "\t" : '|';
        $header = explode($separator, $headerLine);

        if ($header !== FecExporter::COLUMNS) {
            $errors[] = 'The header must be the 18 legal columns, in order.';

            return $errors;
        }

        $balances = [];

        foreach ($lines as $index => $line) {
            $number = $index + 2;
            $cells = explode($separator, $line);

            if (18 !== count($cells)) {
                $errors[] = sprintf('Line %d has %d columns instead of 18.', $number, count($cells));
                continue;
            }

            [$journal, , $entryNumber, $date, $account, , , , $piece, $pieceDate, $label, $debit, $credit] = $cells;

            foreach (['EcritureDate' => $date, 'PieceDate' => $pieceDate, 'ValidDate' => $cells[15]] as $name => $value) {
                if (! preg_match('/^\d{8}$/', $value) || ! \DateTimeImmutable::createFromFormat('!Ymd', $value)) {
                    $errors[] = sprintf('Line %d: %s "%s" is not YYYYMMDD.', $number, $name, $value);
                }
            }

            foreach (['Debit' => $debit, 'Credit' => $credit] as $name => $value) {
                if (! preg_match('/^-?\d+,\d{2}$/', $value)) {
                    $errors[] = sprintf('Line %d: %s "%s" is not an amount with a decimal comma.', $number, $name, $value);
                }
            }

            if ('' === $journal || '' === $entryNumber || '' === $account || '' === $piece || '' === $label) {
                $errors[] = sprintf('Line %d: JournalCode, EcritureNum, CompteNum, PieceRef and EcritureLib are required.', $number);
            }

            if ($fiscalYear && preg_match('/^\d{8}$/', $date)) {
                $day = \DateTimeImmutable::createFromFormat('!Ymd', $date);

                if ($day && ! $fiscalYear->contains($day)) {
                    $errors[] = sprintf('Line %d: entry date %s is outside the fiscal year.', $number, $date);
                }
            }

            $key = $journal.'/'.$entryNumber;
            $balances[$key] = ($balances[$key] ?? 0) + $this->cents($debit) - $this->cents($credit);
        }

        foreach ($balances as $key => $balance) {
            if (0 !== $balance) {
                $errors[] = sprintf('Entry %s is not balanced (%d).', $key, $balance);
            }
        }

        return $errors;
    }

    private function cents(string $amount): int
    {
        return (int) str_replace(',', '', $amount);
    }
}
