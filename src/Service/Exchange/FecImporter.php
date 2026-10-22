<?php

namespace Wexample\SymfonyAccountingFr\Service\Exchange;

use DateTimeImmutable;
use Wexample\SymfonyAccounting\Class\ImportedLine;
use Wexample\SymfonyAccounting\Exception\AccountingException;
use Wexample\SymfonyAccounting\Interface\EntryImporterInterface;
use Wexample\SymfonyMoney\Helper\MoneyHelper;

/**
 * Reads a FEC: "|" or tab separated, header required, amounts either in
 * Debit/Credit columns or in Montant/Sens (MaCompta and others), dates as
 * YYYYMMDD (or DD/MM/YYYY), files in UTF-8 or ISO-8859-15.
 */
class FecImporter implements EntryImporterInterface
{
    public function getKey(): string
    {
        return 'fec';
    }

    public function getLabel(): string
    {
        return 'FEC (fichier des écritures comptables)';
    }

    public function supports(string $content): bool
    {
        $header = strtok(ltrim($content, "\xEF\xBB\xBF"), "\n") ?: '';

        return str_contains($header, 'JournalCode') && str_contains($header, 'EcritureNum') && str_contains($header, 'CompteNum');
    }

    public function read(
        string $content,
        array $options = []
    ): iterable {
        if ('' === trim($content)) {
            return;
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-15');
        }

        $lines = preg_split('/\r\n|\n|\r/', ltrim($content, "\xEF\xBB\xBF"));
        $headerLine = array_shift($lines);
        $separator = substr_count($headerLine, "\t") > substr_count($headerLine, '|') ? "\t" : '|';
        $header = array_map('trim', explode($separator, $headerLine));
        $index = array_flip($header);

        foreach (['JournalCode', 'EcritureNum', 'EcritureDate', 'CompteNum'] as $required) {
            if (! isset($index[$required])) {
                throw new AccountingException(sprintf('The FEC has no %s column.', $required));
            }
        }

        $hasDebitCredit = isset($index['Debit'], $index['Credit']);
        $hasAmountSign = isset($index['Montant'], $index['Sens']);

        if (! $hasDebitCredit && ! $hasAmountSign) {
            throw new AccountingException('The FEC has neither Debit/Credit nor Montant/Sens columns.');
        }

        foreach ($lines as $number => $raw) {
            if ('' === trim($raw)) {
                continue;
            }

            $cells = array_map('trim', explode($separator, $raw));
            $get = fn (string $name): ?string => isset($index[$name]) && isset($cells[$index[$name]]) && '' !== $cells[$index[$name]]
                ? $cells[$index[$name]]
                : null;

            [$debit, $credit] = $hasDebitCredit
                ? [$this->amount($get('Debit')), $this->amount($get('Credit'))]
                : $this->fromAmountSign($get('Montant'), $get('Sens'));

            $currencyAmount = $get('Montantdevise');

            yield new ImportedLine(
                journalCode: (string) $get('JournalCode'),
                entryNumber: (string) $get('EcritureNum'),
                date: $this->date($get('EcritureDate'), $number + 2),
                accountNumber: (string) $get('CompteNum'),
                label: (string) ($get('EcritureLib') ?? ''),
                debit: $debit,
                credit: $credit,
                journalLabel: $get('JournalLib'),
                accountLabel: $get('CompteLib'),
                auxiliaryCode: $get('CompAuxNum'),
                auxiliaryLabel: $get('CompAuxLib'),
                pieceReference: $get('PieceRef'),
                pieceDate: $get('PieceDate') ? $this->date($get('PieceDate'), $number + 2) : null,
                letter: $get('EcritureLet'),
                dateLettered: $get('DateLet') ? $this->date($get('DateLet'), $number + 2) : null,
                dateValidated: $get('ValidDate') ? $this->date($get('ValidDate'), $number + 2) : null,
                currencyAmount: null === $currencyAmount ? null : $this->amount($currencyAmount),
                currencyCode: $get('Idevise'),
            );
        }
    }

    private function amount(?string $value): int
    {
        return null === $value ? 0 : abs(MoneyHelper::fromDecimal($value, 'EUR'));
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function fromAmountSign(
        ?string $amount,
        ?string $sign
    ): array {
        $value = MoneyHelper::fromDecimal((string) $amount, 'EUR');
        $credit = in_array(strtoupper((string) $sign), ['C', '-1', '-'], true) || $value < 0 && null === $sign;

        return $credit ? [0, abs($value)] : [abs($value), 0];
    }

    private function date(
        ?string $value,
        int $line
    ): DateTimeImmutable {
        $value = (string) $value;
        $date = preg_match('/^\d{8}$/', $value)
            ? DateTimeImmutable::createFromFormat('!Ymd', $value)
            : DateTimeImmutable::createFromFormat('!d/m/Y', $value);

        if (! $date) {
            throw new AccountingException(sprintf('Line %d: "%s" is not a FEC date.', $line, $value));
        }

        return $date;
    }
}
