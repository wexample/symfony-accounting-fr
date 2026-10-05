<?php

namespace Wexample\SymfonyAccountingFr\Service\Bank\Parser;

use Wexample\SymfonyAccounting\Class\ParsedBalance;
use Wexample\SymfonyAccounting\Class\ParsedStatement;
use Wexample\SymfonyAccounting\Class\ParsedTransaction;
use Wexample\SymfonyAccounting\Service\Bank\Parser\AbstractBankStatementParser;

/**
 * La Banque Postale, 2023 CSV: metadata lines (account, type, download date,
 * period, "Solde comptable au …;10251,13 EUR"), a blank line, then
 * "Date;Libellé;Montant" with signed amounts like "-292,91".
 */
class FrLbp2023BankStatementParser extends AbstractBankStatementParser
{
    public function getKey(): string
    {
        return 'fr_lbp_2023';
    }

    public function getLabel(): string
    {
        return 'La Banque Postale (2023, CSV)';
    }

    public function supports(
        string $content,
        ?string $filename = null
    ): bool {
        $head = $this->toUtf8(substr($this->removeBom($content), 0, 600));

        return str_contains($head, 'Fichier téléchargé') && str_contains($head, 'Solde comptable');
    }

    public function parse(
        string $content,
        array $options = []
    ): ParsedStatement {
        $lines = preg_split('/\R/', $this->toUtf8($this->removeBom($content)));
        $statement = new ParsedStatement();
        $dataStart = null;

        foreach ($lines as $index => $line) {
            if (preg_match('/^Solde comptable au (\d\d\/\d\d\/\d{4});(.*)$/u', $line, $matches)) {
                $statement->addBalance(new ParsedBalance(
                    $this->parseDate($matches[1], 'd/m/Y'),
                    $this->parseAmount($matches[2])
                ));
            }

            if (str_starts_with($line, 'Date;Libellé;Montant')) {
                $dataStart = $index + 1;

                break;
            }
        }

        foreach (array_slice($lines, (int) $dataStart) as $line) {
            if ('' === trim($line)) {
                continue;
            }

            $row = str_getcsv($line, ';', '"', '\\');
            $statement->addTransaction(new ParsedTransaction(
                date: $this->parseDate($row[0], 'd/m/Y'),
                amount: $this->parseAmount($row[2]),
                label: $this->cleanLabel($row[1]),
            ));
        }

        return $statement;
    }
}
