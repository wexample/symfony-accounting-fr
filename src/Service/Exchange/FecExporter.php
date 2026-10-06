<?php

namespace Wexample\SymfonyAccountingFr\Service\Exchange;

use Wexample\SymfonyAccounting\Class\ExportFile;
use Wexample\SymfonyAccounting\Entity\EntryLine;
use Wexample\SymfonyAccounting\Entity\FiscalYear;
use Wexample\SymfonyAccounting\Entity\JournalEntry;
use Wexample\SymfonyAccounting\Enum\AccountRole;
use Wexample\SymfonyAccounting\Interface\LedgerExporterInterface;
use Wexample\SymfonyAccounting\Repository\JournalEntryRepository;
use Wexample\SymfonyAccounting\Service\Ledger\ChartService;
use Wexample\SymfonyAccountingFr\Helper\FrIdentityHelper;

/**
 * Fichier des écritures comptables (art. A47 A-1 of the Livre des procédures
 * fiscales): every entry of the fiscal year, in the 18 legal columns and their
 * order, "|" separated, dates as YYYYMMDD, amounts with a decimal comma.
 *
 * Auxiliary accounts (CompAuxNum/CompAuxLib) carry the parties of the customer
 * and supplier accounts, lettering is exported, and ValidDate is the date the
 * entry was validated (its date when it was not).
 *
 * Options: `separator` ("|" or "\t"), `encoding` ("UTF-8" by default,
 * "ISO-8859-15" accepted by the administration too), `account_length` (pad
 * account numbers with zeros, e.g. 6 or 8, as some accountants require).
 */
class FecExporter implements LedgerExporterInterface
{
    public const array COLUMNS = [
        'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate', 'CompteNum', 'CompteLib',
        'CompAuxNum', 'CompAuxLib', 'PieceRef', 'PieceDate', 'EcritureLib', 'Debit', 'Credit',
        'EcritureLet', 'DateLet', 'ValidDate', 'Montantdevise', 'Idevise',
    ];

    public function __construct(
        private readonly JournalEntryRepository $entryRepository,
        private readonly ChartService $chartService,
    ) {
    }

    public function getKey(): string
    {
        return 'fec';
    }

    public function getLabel(): string
    {
        return 'FEC (fichier des écritures comptables)';
    }

    public function supports(FiscalYear $fiscalYear): bool
    {
        return 'FR' === $fiscalYear->getLedger()->getCountry()?->getIsoAlpha2Code();
    }

    public function export(
        FiscalYear $fiscalYear,
        array $options = []
    ): ExportFile {
        $separator = $options['separator'] ?? '|';
        $accountLength = $options['account_length'] ?? null;
        $ledger = $fiscalYear->getLedger();
        $customersPrefix = $this->chartService->getRoleNumber($ledger, AccountRole::Customers);
        $rows = [implode($separator, self::COLUMNS)];

        foreach ($this->sortedEntries($fiscalYear) as $entry) {
            foreach ($entry->getLines() as $line) {
                $rows[] = implode($separator, array_map(
                    fn ($value) => $this->clean((string) $value, $separator),
                    $this->buildRow($entry, $line, $customersPrefix, $accountLength)
                ));
            }
        }

        $content = implode("\r\n", $rows)."\r\n";
        $encoding = $options['encoding'] ?? 'UTF-8';

        if ('UTF-8' !== strtoupper($encoding)) {
            $content = mb_convert_encoding($content, $encoding, 'UTF-8');
        }

        return new ExportFile($this->buildFilename($fiscalYear), $content, 'text/plain');
    }

    /**
     * <SIREN>FEC<closing date YYYYMMDD>.txt
     */
    public function buildFilename(FiscalYear $fiscalYear): string
    {
        $siren = FrIdentityHelper::sirenOf((string) $fiscalYear->getLedger()->getLegalIdentifier()) ?? '000000000';

        return $siren.'FEC'.$fiscalYear->getDateEnd()->format('Ymd').'.txt';
    }

    /**
     * Entries in chronological order: by date, then by number.
     *
     * @return list<JournalEntry>
     */
    private function sortedEntries(FiscalYear $fiscalYear): array
    {
        $entries = $this->entryRepository->findBooked($fiscalYear);
        usort($entries, fn (JournalEntry $a, JournalEntry $b) => [$a->getDate(), $a->getNumber()] <=> [$b->getDate(), $b->getNumber()]);

        return $entries;
    }

    private function buildRow(
        JournalEntry $entry,
        EntryLine $line,
        string $customersPrefix,
        ?int $accountLength
    ): array {
        $account = $line->getAccount();
        $number = $accountLength ? str_pad($account->getNumber(), $accountLength, '0') : $account->getNumber();
        $auxiliary = $account->isLettrable() ? $line->getAuxiliaryCode($customersPrefix) : null;

        return [
            $entry->getJournal()->getCode(),
            $entry->getJournal()->getLabel(),
            $entry->getNumber(),
            $entry->getDate()->format('Ymd'),
            $number,
            $account->getLabel(),
            $auxiliary,
            $auxiliary ? $line->getParty()?->getName() : null,
            $entry->getPieceReference() ?? $entry->getJournal()->getCode().$entry->getNumber(),
            ($entry->getPieceDate() ?? $entry->getDate())->format('Ymd'),
            $line->getLabel(),
            $this->amount($line->getDebit()),
            $this->amount($line->getCredit()),
            $line->getLetter(),
            $line->getDateLettered()?->format('Ymd'),
            ($entry->getDateValidated() ?? $entry->getDate())->format('Ymd'),
            null === $line->getCurrencyAmount() ? null : $this->amount(abs($line->getCurrencyAmount())),
            $line->getCurrencyCode(),
        ];
    }

    private function amount(int $minor): string
    {
        $sign = $minor < 0 ? '-' : '';
        $minor = abs($minor);

        return $sign.intdiv($minor, 100).','.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * No separator, line break or tab may appear inside a field.
     */
    private function clean(
        string $value,
        string $separator
    ): string {
        return trim(str_replace([$separator, "\r", "\n", "\t"], ' ', $value));
    }
}
