<?php

namespace Wexample\SymfonyAccountingFr\Tests\Integration;

use DateTimeImmutable;
use Wexample\SymfonyAccounting\Enum\VatPeriodicity;
use Wexample\SymfonyAccounting\Repository\AccountRepository;
use Wexample\SymfonyAccounting\Service\Bank\AllocationService;
use Wexample\SymfonyAccounting\Service\Exchange\EntryImportService;
use Wexample\SymfonyAccounting\Service\Exchange\LedgerExportService;
use Wexample\SymfonyAccounting\Service\Jurisdiction\JurisdictionRegistry;
use Wexample\SymfonyAccounting\Service\Ledger\FiscalYearClosingService;
use Wexample\SymfonyAccounting\Service\Ledger\FiscalYearService;
use Wexample\SymfonyAccounting\Service\Ledger\LedgerService;
use Wexample\SymfonyAccounting\Service\Ledger\LetteringService;
use Wexample\SymfonyAccounting\Service\Report\FinancialStatementService;
use Wexample\SymfonyAccounting\Service\Report\TrialBalanceService;
use Wexample\SymfonyAccounting\Service\Vat\VatReturnService;
use Wexample\SymfonyAccountingFr\Service\Exchange\FecExporter;
use Wexample\SymfonyAccountingFr\Service\Exchange\FecValidator;
use Wexample\SymfonyAccountingFr\Service\Tax\FrCorporateTaxService;
use Wexample\SymfonyAccountingFr\Service\Tax\FrForm2033DataProvider;
use Wexample\SymfonyAccountingFr\Service\Vat\FrCa12DepositCalculator;

class FrAccountingTest extends AbstractFrTestCase
{
    public function testPcgIsLoaded(): void
    {
        $ledger = $this->createLedger();
        $accounts = $this->service(AccountRepository::class)->findIndexedByNumber($ledger);

        $this->assertGreaterThan(1000, count($accounts));
        $this->assertSame('Clients', $accounts['411']->getLabel());
        $this->assertTrue($accounts['411']->isLettrable());
        $this->assertSame('fr_pcg', $accounts['44571']->getSource());
    }

    public function testSaleBookingAndMentions(): void
    {
        $ledger = $this->createLedger();
        $invoice = $this->emitSale($ledger, $this->createParty($ledger));

        $this->assertSame(['706:0/10000', '44571:0/2000', '411:12000/0'], $this->describeLines($this->entryOf($invoice)));

        $mentions = $this->service(JurisdictionRegistry::class)->forLedger($ledger)->getInvoiceMentions($invoice);
        $this->assertSame(['mention.fr.payment.l441'], array_map(fn ($m) => $m->key, $mentions));
    }

    public function testFranchiseAndReverseChargeMentions(): void
    {
        $ledger = $this->createLedger();
        $ledger->setVatSubject(false)->setLegalForm('EI');
        $invoice = $this->emitSale($ledger, $this->createParty($ledger));
        $keys = array_map(fn ($m) => $m->key, $this->service(JurisdictionRegistry::class)->forLedger($ledger)->getInvoiceMentions($invoice));
        $this->assertSame(['mention.fr.ei', 'mention.fr.vat.franchise', 'mention.fr.payment.l441'], $keys);

        $subject = $this->createLedger('2025-01-01');
        $belgian = $this->createParty($subject, 'Société belge', 'BE', 'BE0123456749');
        $export = $this->emitSale($subject, $belgian, 10000, '2025-05-01');
        $keys = array_map(fn ($m) => $m->key, $this->service(JurisdictionRegistry::class)->forLedger($subject)->getInvoiceMentions($export));
        $this->assertContains('mention.fr.vat.reverse_charge', $keys);
    }

    private function buildYear(): array
    {
        $ledger = $this->createLedger();
        $bank = $this->createBankAccount($ledger);
        $customer = $this->createParty($ledger);
        $supplier = $this->createParty($ledger, 'Fournisseur', customer: false, supplier: true);
        $allocations = $this->service(AllocationService::class);

        $sale = $this->emitSale($ledger, $customer, 300000, '2026-02-01');
        $allocations->allocate($this->createTransaction($bank, 360000, '2026-02-20'), $sale);
        $purchase = $this->emitPurchase($ledger, $supplier, 50000, '2026-03-01');
        $allocations->allocate($this->createTransaction($bank, -60000, '2026-03-10'), $purchase);
        $this->emitSale($ledger, $customer, 100000, '2026-12-01');
        $this->service(LetteringService::class)->letterLedger($ledger);

        return [$ledger, $this->service(FiscalYearService::class)->getForDate($ledger, new DateTimeImmutable('2026-06-01'))];
    }

    public function testFecExportValidatesAndRoundTrips(): void
    {
        [$ledger, $fiscalYear] = $this->buildYear();
        $file = $this->service(LedgerExportService::class)->export($fiscalYear, 'fec');

        $this->assertSame('732829320FEC20261231.txt', $file->filename);
        $lines = explode("\r\n", trim($file->content));
        $this->assertSame(implode('|', FecExporter::COLUMNS), $lines[0]);
        $this->assertSame([], $this->service(FecValidator::class)->validate($file->content, $fiscalYear));

        // The customer line carries its auxiliary account and its letter.
        $customerLines = array_values(array_filter($lines, fn ($line) => str_contains($line, '|411|')));
        $cells = explode('|', $customerLines[0]);
        $this->assertSame('CCLIENTA', $cells[6]);
        $this->assertSame('Client A', $cells[7]);
        $this->assertSame('A', $cells[13]);
        $this->assertSame('3600,00', $cells[11]);

        $target = $this->service(LedgerService::class)->create('Dossier repris', $this->country('FR'), loadChart: false);
        $result = $this->service(EntryImportService::class)->importContent($target, $file->content, options: ['create_fiscal_years' => true]);
        $this->assertTrue($result->isComplete());
        $this->assertSame(
            $this->service(TrialBalanceService::class)->build($ledger, $fiscalYear)->getBalances(),
            $this->service(TrialBalanceService::class)->build($target)->getBalances()
        );
    }

    public function testFecWithAmountAndSign(): void
    {
        $ledger = $this->createLedger();
        $fec = "JournalCode\tJournalLib\tEcritureNum\tEcritureDate\tCompteNum\tCompteLib\tCompAuxNum\tCompAuxLib\tPieceRef\tPieceDate\tEcritureLib\tMontant\tSens\tEcritureLet\tDateLet\tValidDate\tMontantdevise\tIdevise\n"
            ."VT\tVentes\t1\t20260105\t41100000\tClients\tCDUPONT\tDupont\tF1\t20260105\tFacture 1\t120,00\tD\t\t\t20260131\t\t\n"
            ."VT\tVentes\t1\t20260105\t70600000\tPrestations\t\t\tF1\t20260105\tFacture 1\t100,00\tC\t\t\t20260131\t\t\n"
            ."VT\tVentes\t1\t20260105\t44571000\tTVA\t\t\tF1\t20260105\tFacture 1\t20,00\tC\t\t\t20260131\t\t\n";

        $result = $this->service(EntryImportService::class)->importContent($ledger, $fec);

        $this->assertTrue($result->isComplete());
        $this->assertSame(1, $result->entries);
        $this->assertSame(1, $result->partiesCreated);
        $balance = $this->service(TrialBalanceService::class)->build($ledger);
        $this->assertSame(12000, $balance->getBalance('411'));
        $this->assertSame(-10000, $balance->getBalance('706'));
    }

    public function testFrenchStatementsBalance(): void
    {
        [$ledger, $fiscalYear] = $this->buildYear();
        $statements = $this->service(FinancialStatementService::class);

        $income = $statements->build($fiscalYear, 'income_statement');
        $this->assertSame(400000, $income->get('sales_services'));
        $this->assertSame(50000, $income->get('external_charges'));
        $this->assertSame(350000, $income->get('result'));

        $sheet = $statements->build($fiscalYear, 'balance_sheet');
        $this->assertSame($sheet->get('assets'), $sheet->get('liabilities'));
        $this->assertSame(120000, $sheet->get('trade_receivables'));

        $forms = $this->service(FrForm2033DataProvider::class)->build($fiscalYear);
        $this->assertSame(350000, $forms['2033-B']['310']);
        $this->assertSame($forms['2033-A']['110'], $forms['2033-A']['180']);
    }

    public function testEveryPcgIncomeAndChargeAccountIsReported(): void
    {
        [$ledger, $fiscalYear] = $this->buildYear();
        $definition = $this->service(FinancialStatementService::class)->getDefinition($fiscalYear, 'income_statement');
        $balances = [];

        foreach ($this->service(AccountRepository::class)->findByLedger($ledger) as $account) {
            if (in_array($account->getClass(), [6, 7], true)) {
                $balances[$account->getNumber()] = new \Wexample\SymfonyAccounting\Class\TrialBalanceRow($account->getNumber(), '', 100, 0);
            }
        }

        $result = $this->service(FinancialStatementService::class)->compute($definition, new \Wexample\SymfonyAccounting\Class\TrialBalance($balances));
        $counted = [];
        foreach ($result->lines as $line) {
            $counted += $line['accounts'];
        }

        $this->assertSame([], array_values(array_diff(array_keys($balances), array_map('strval', array_keys($counted)))));
    }

    public function testCa3(): void
    {
        $ledger = $this->createLedger();
        $ledger->setVatPeriodicity(VatPeriodicity::Monthly);
        $this->emitSale($ledger, $this->createParty($ledger), 100000, '2026-03-02');
        $this->emitSale($ledger, $this->createParty($ledger, 'Livres'), 10000, '2026-03-03', 550);
        $this->emitPurchase($ledger, $this->createParty($ledger, 'Fournisseur', customer: false, supplier: true), 30000, '2026-03-04');
        $this->emitSale($ledger, $this->createParty($ledger, 'Belge', 'BE', 'BE0123456749'), 50000, '2026-03-05');

        $vat = $this->service(VatReturnService::class);
        $return = $vat->compute($ledger, new DateTimeImmutable('2026-03-01'), new DateTimeImmutable('2026-03-31'));
        $boxes = $vat->fillForms($return)['fr_ca3'];

        $this->assertSame(110000, $boxes['A1']);
        $this->assertSame(50000, $boxes['E2']);
        $this->assertSame(100000, $boxes['08_base']);
        $this->assertSame(20000, $boxes['08_tax']);
        $this->assertSame(550, $boxes['09_tax']);
        $this->assertSame(20550, $boxes['16']);
        $this->assertSame(6000, $boxes['20']);
        $this->assertSame(14550, $boxes['28']);
        $this->assertArrayNotHasKey('fr_ca12', $vat->fillForms($return));
    }

    public function testCa12AndDeposits(): void
    {
        $ledger = $this->createLedger();
        $ledger->setVatPeriodicity(VatPeriodicity::Annual);
        $this->emitSale($ledger, $this->createParty($ledger), 100000, '2026-03-02');

        $vat = $this->service(VatReturnService::class);
        $boxes = $vat->fillForms($vat->compute($ledger, new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31')))['fr_ca12'];
        $this->assertSame(20000, $boxes['5A_tax']);
        $this->assertSame(20000, $boxes['57']);

        $deposits = $this->service(FrCa12DepositCalculator::class)->compute(20000 * 10, 2027);
        $this->assertSame([110000, 80000], array_column($deposits, 'amount'));
        $this->assertSame([], $this->service(FrCa12DepositCalculator::class)->compute(90000, 2027));
    }

    public function testCorporateTax(): void
    {
        $tax = $this->service(FrCorporateTaxService::class);

        $this->assertSame(450000, $tax->compute(3000000, 2023));
        // 42 500 € at 15 %, 57 500 € at 25 %.
        $this->assertSame(637500 + 1437500, $tax->compute(10000000, 2023));
        // 2021: 38 120 € at 15 %, the rest at 26,5 %.
        $this->assertSame(571800 + 1639820, $tax->compute(10000000, 2021));
        $this->assertSame(2500000, $tax->compute(10000000, 2024, reducedRate: false));
        $this->assertSame(0, $tax->compute(-500, 2024));

        $this->assertSame([], $tax->computeDeposits(200000, 2027));
        $deposits = $tax->computeDeposits(1000001, 2027);
        $this->assertSame([250000, 250000, 250000, 250001], array_column($deposits, 'amount'));
        $this->assertSame('2027-03-15', $deposits[0]['date']->format('Y-m-d'));

        [, $fiscalYear] = $this->buildYear();
        $entry = $tax->book($fiscalYear);
        $this->assertSame(['695:52500/0', '444:0/52500'], $this->describeLines($entry));
    }

    public function testClosingCarriesToFrenchResultAccounts(): void
    {
        [$ledger, $fiscalYear] = $this->buildYear();
        $this->service(FiscalYearClosingService::class)->close($fiscalYear, force: true);

        $this->assertSame(350000, $fiscalYear->getResult());
    }
}
