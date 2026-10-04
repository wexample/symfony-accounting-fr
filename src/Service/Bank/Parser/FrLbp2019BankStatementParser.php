<?php

namespace Wexample\SymfonyAccountingFr\Service\Bank\Parser;

use Wexample\SymfonyAccounting\Class\ParsedBalance;
use Wexample\SymfonyAccounting\Class\ParsedStatement;
use Wexample\SymfonyAccounting\Class\ParsedTransaction;
use Wexample\SymfonyAccounting\Service\Bank\Parser\AbstractBankStatementParser;

/**
 * La Banque Postale, 2019 format, in two flavours:
 *
 * - the CSV export: 7 metadata lines (account, type, currency, date, balance
 *   in euros and francs), a blank line, then "Date;Libellé;Montant(EUROS);…";
 * - the text copied from a PDF statement (`format: text`, or detected), since
 *   those PDFs cannot be read: records start with "dd/mm", amounts are "1 234,56".
 *   The text loses the debit/credit columns, so the side is guessed from the
 *   label (outgoing transfers, direct debits, card payments, fees are debits);
 *   the new balance is kept, so reconciliation shows any wrong guess.
 */
class FrLbp2019BankStatementParser extends AbstractBankStatementParser
{
    public const array DEBIT_PATTERNS = [
        '/^VIREMENT (POUR|INSTANTANE A|A )/i',
        '/^PRELEVEMENT/i',
        '/^PRLV/i',
        '/^(ACHAT )?CB\b/i',
        '/^CARTE\b/i',
        '/^ACHAT\b/i',
        '/^RETRAIT/i',
        '/^COTISATION/i',
        '/^FRAIS/i',
        '/^COMMISSION/i',
        '/^CHEQUE N/i',
        '/^ECHEANCE/i',
    ];

    public function getKey(): string
    {
        return 'fr_lbp_2019';
    }

    public function getLabel(): string
    {
        return 'La Banque Postale (2019, CSV or PDF text)';
    }

    public function supports(
        string $content,
        ?string $filename = null
    ): bool {
        $head = $this->toUtf8(substr($content, 0, 600));

        return str_contains($head, 'Montant(EUROS)') || str_contains($head, 'Relevé de votre CCP');
    }

    public function parse(
        string $content,
        array $options = []
    ): ParsedStatement {
        $content = $this->toUtf8($this->removeBom($content));

        if (($options['format'] ?? null) === 'text' || str_contains(substr($content, 0, 600), 'Relevé de votre CCP')) {
            return $this->parseText($content);
        }

        return $this->parseCsv($content);
    }

    private function parseCsv(string $content): ParsedStatement
    {
        $lines = preg_split('/\R/', $content);
        $statement = new ParsedStatement();
        $date = trim(explode(';', $lines[3] ?? '')[1] ?? '');
        $balance = trim(explode(';', $lines[4] ?? '')[1] ?? '');

        if ($date && '' !== $balance) {
            $statement->addBalance(new ParsedBalance($this->parseDate($date, 'd/m/Y'), $this->parseAmount($balance)));
        }

        foreach (array_slice($lines, 8) as $line) {
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

    private function parseText(string $content): ParsedStatement
    {
        $statement = new ParsedStatement();
        $year = null;
        $records = [];
        $current = null;
        $started = false;
        $new = null;

        foreach (preg_split('/\R/', $content) as $line) {
            $line = trim($line);

            if (preg_match('/Arrêté mensuel du.*(\d{4})$/u', $line, $matches)) {
                $year = $matches[1];
            }

            if (preg_match('/^Nouveau solde au (\d\d\/\d\d\/\d{4})/u', $line, $matches)) {
                $new = ['date' => $matches[1]];
                continue;
            }

            if (is_array($new) && ! isset($new['amount']) && preg_match('/^([+-])\s*([\d\s]+,\d\d)/u', $line, $matches)) {
                $new['amount'] = ('-' === $matches[1] ? -1 : 1) * $this->parseAmount($matches[2]);
                continue;
            }

            if (str_starts_with($line, 'Ancien solde au ')) {
                $started = true;
                continue;
            }

            if (! $started) {
                continue;
            }

            if (str_starts_with($line, 'Total des opérations')) {
                break;
            }

            if (in_array($line, ['Crédit (¤)', 'Débit (¤)', 'Crédit (€)', 'Débit (€)'], true)) {
                continue;
            }

            if (preg_match('/^(\d\d\/\d\d)\s*(.*)$/u', $line, $matches)) {
                if ($current) {
                    $records[] = $current;
                }

                $current = ['date' => $matches[1].'/'.$year, 'label' => $matches[2], 'amount' => null];
            } elseif ($current && null === $current['amount'] && preg_match('/^([\d\s]+,\d\d)$/u', $line, $matches)) {
                $current['amount'] = $this->parseAmount($matches[1]);
            } elseif ($current) {
                $current['label'] .= ' '.$line;
            }
        }

        if ($current) {
            $records[] = $current;
        }

        foreach ($records as $record) {
            $label = $this->cleanLabel($record['label']);
            $amount = (int) $record['amount'];

            $statement->addTransaction(new ParsedTransaction(
                date: $this->parseDate($record['date'], 'd/m/Y'),
                amount: $this->isDebit($label) ? -$amount : $amount,
                label: $label,
            ));
        }

        if (isset($new['amount'])) {
            $statement->addBalance(new ParsedBalance($this->parseDate($new['date'], 'd/m/Y'), $new['amount']));
        }

        return $statement;
    }

    private function isDebit(string $label): bool
    {
        $label = preg_replace('/^\[[^\]]*\]/', '', $label);

        foreach (self::DEBIT_PATTERNS as $pattern) {
            if (preg_match($pattern, $label)) {
                return true;
            }
        }

        return false;
    }
}
