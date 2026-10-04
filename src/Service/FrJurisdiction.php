<?php

namespace Wexample\SymfonyAccountingFr\Service;

use Wexample\SymfonyAccounting\Class\ChartAccountDefinition;
use Wexample\SymfonyAccounting\Class\LatePenaltyPolicy;
use Wexample\SymfonyAccounting\Class\LegalMention;
use Wexample\SymfonyAccounting\Class\ReportDefinition;
use Wexample\SymfonyAccounting\Entity\Invoice;
use Wexample\SymfonyAccounting\Entity\Ledger;
use Wexample\SymfonyAccounting\Enum\AccountNature;
use Wexample\SymfonyAccounting\Enum\InvoiceDirection;
use Wexample\SymfonyAccounting\Enum\InvoiceType;
use Wexample\SymfonyAccounting\Enum\JournalType;
use Wexample\SymfonyAccounting\Enum\VatKind;
use Wexample\SymfonyAccounting\Service\Jurisdiction\AbstractJurisdiction;
use Wexample\SymfonyAccountingFr\Helper\FrIdentityHelper;

/**
 * French law and practice: the PCG (and the association chart), rates, invoice
 * mentions of the Code général des impôts and the Code de commerce.
 *
 * Ledger settings read here:
 * - `fr_rcs_exempt` (bool): micro-entrepreneur exempt from RCS/RM registration;
 * - `fr_vat_debits_option` (bool): a service provider who opted for VAT on debits;
 * - `late_penalty_rate` (basis points per year), `late_penalty_flat_fee` (cents).
 */
class FrJurisdiction extends AbstractJurisdiction
{
    public const string DATASET_PCG = 'fr_pcg';
    public const string DATASET_PCA = 'fr_pca';

    /** @var array<string, list<ChartAccountDefinition>> */
    private array $charts = [];

    public function getCountryCode(): ?string
    {
        return 'FR';
    }

    public function getLegalIdentifierLabel(): string
    {
        return 'identity.fr.siret';
    }

    public function getVatNumberLabel(): string
    {
        return 'identity.fr.vat_number';
    }

    public function isLegalIdentifierValid(string $identifier): bool
    {
        return FrIdentityHelper::isValidSiret($identifier) || FrIdentityHelper::isValidSiren($identifier);
    }

    public function isVatNumberValid(string $vatNumber): bool
    {
        return str_starts_with(strtoupper(trim($vatNumber)), 'FR')
            ? FrIdentityHelper::isValidVatNumber($vatNumber)
            : parent::isVatNumberValid($vatNumber);
    }

    public function getVatRates(): array
    {
        return [2000, 1000, 550, 210, 0];
    }

    public function getChartDatasets(): array
    {
        return [self::DATASET_PCG, self::DATASET_PCA];
    }

    public function getChartAccounts(string $dataset): iterable
    {
        if (! in_array($dataset, $this->getChartDatasets(), true)) {
            return [];
        }

        return $this->charts[$dataset] ??= $this->readChart(__DIR__.'/../Resources/data/charts/'.$dataset.'.csv');
    }

    protected function getAccountNumbers(): array
    {
        return [
            'customers' => '411',
            'customer_advances' => '4191',
            'suppliers' => '401',
            'sales_goods' => '707',
            'sales_services' => '706',
            'purchases' => '607',
            'purchases_services' => '604',
            'bank' => '512',
            'cash' => '530',
            'internal_transfer' => '580',
            'payment_provider' => '511',
            'bank_fees' => '627',
            'vat_collected' => '44571',
            'vat_collected_pending' => '44574',
            'vat_deductible' => '44566',
            'vat_deductible_pending' => '44564',
            'vat_deductible_assets' => '44562',
            'vat_self_assessed' => '4452',
            'vat_payable' => '44551',
            'vat_credit' => '44567',
            'vat_deposits' => '44581',
            'doubtful_customers' => '416',
            'bad_debts' => '654',
            'late_penalties_income' => '7638',
            'depreciation_expense' => '6811',
            'asset_disposal_value' => '675',
            'prepaid_expenses' => '486',
            'deferred_income' => '487',
            'rounding_gain' => '758',
            'rounding_loss' => '658',
            'result_profit' => '120',
            'result_loss' => '129',
            'retained_earnings' => '110',
            'retained_losses' => '119',
            'owner_account' => '108',
            'suspense' => '471',
        ];
    }

    public function getDefaultJournals(): array
    {
        return [
            'VE' => ['label' => 'Ventes', 'type' => JournalType::Sales],
            'AC' => ['label' => 'Achats', 'type' => JournalType::Purchases],
            'BQ' => ['label' => 'Banque', 'type' => JournalType::Bank],
            'CA' => ['label' => 'Caisse', 'type' => JournalType::Cash],
            'OD' => ['label' => 'Opérations diverses', 'type' => JournalType::Miscellaneous],
            'AN' => ['label' => 'À-nouveaux', 'type' => JournalType::Opening],
        ];
    }

    public function getInvoiceMentions(Invoice $invoice): array
    {
        if (InvoiceDirection::Sale !== $invoice->getDirection()) {
            return [];
        }

        $ledger = $invoice->getLedger();
        $mentions = [];

        if (in_array(strtoupper((string) $ledger->getLegalForm()), ['EI', 'EIRL', 'ME', 'MICRO'], true)) {
            $mentions[] = new LegalMention('mention.fr.ei', LegalMention::PLACEMENT_HEADER);
        }

        if ($ledger->getSetting('fr_rcs_exempt', false)) {
            $mentions[] = new LegalMention('mention.fr.rcs_exempt', LegalMention::PLACEMENT_HEADER);
        }

        foreach ($invoice->getVatKinds() as $kind) {
            $key = match ($kind) {
                VatKind::Franchise => 'mention.fr.vat.franchise',
                VatKind::IntraEuServices => 'mention.fr.vat.reverse_charge',
                VatKind::IntraEuGoods => 'mention.fr.vat.intra_eu_goods',
                VatKind::Export => 'mention.fr.vat.export',
                VatKind::Exempt => 'mention.fr.vat.exempt',
                default => null,
            };

            if ($key) {
                $mentions[] = new LegalMention($key, LegalMention::PLACEMENT_VAT);
            }
        }

        if ($ledger->isVatSubject() && $ledger->getSetting('fr_vat_debits_option', false)) {
            $mentions[] = new LegalMention('mention.fr.vat.debits_option', LegalMention::PLACEMENT_VAT);
        }

        if (in_array($invoice->getType(), [InvoiceType::Bill, InvoiceType::Penalty], true)) {
            $policy = $this->getLatePenaltyPolicy($ledger);
            $mentions[] = new LegalMention('mention.fr.payment.l441', LegalMention::PLACEMENT_PAYMENT, [
                'days' => $invoice->getPaymentTermDays(),
                'rate' => $policy->rate / 100,
                'flat_fee' => $policy->flatFee / 100,
            ]);
        }

        if (InvoiceType::Quotation === $invoice->getType()) {
            $mentions[] = new LegalMention('mention.fr.quotation.signature', LegalMention::PLACEMENT_FOOTER, [
                'date' => $invoice->getDateValidUntil()?->format('d/m/Y'),
            ]);
        }

        return $mentions;
    }

    /**
     * Code de commerce L441-10: the agreed rate, at least three times the legal
     * interest rate; by default the ECB rate plus 10 points, here 12 % a year
     * unless the ledger sets its own. D441-5: 40 € flat recovery fee.
     */
    public function getLatePenaltyPolicy(Ledger $ledger): LatePenaltyPolicy
    {
        return new LatePenaltyPolicy(
            mode: $ledger->getSetting('late_penalty_mode', LatePenaltyPolicy::MODE_ANNUAL),
            rate: (int) $ledger->getSetting('late_penalty_rate', 1200),
            flatFee: (int) $ledger->getSetting('late_penalty_flat_fee', 4000),
        );
    }

    public function getReportDefinitions(): array
    {
        return [
            ReportDefinition::fromArray(require __DIR__.'/../Resources/data/reports/balance_sheet.php'),
            ReportDefinition::fromArray(require __DIR__.'/../Resources/data/reports/income_statement.php'),
        ];
    }

    /**
     * @return list<ChartAccountDefinition>
     */
    private function readChart(string $path): array
    {
        $accounts = [];
        $handle = fopen($path, 'r');
        fgetcsv($handle, null, ';', '"', '');

        while (false !== ($row = fgetcsv($handle, null, ';', '"', ''))) {
            [$number, $label, $nature, $lettrable] = $row;
            $accounts[] = new ChartAccountDefinition(
                number: $number,
                label: $label,
                nature: AccountNature::from($nature),
                lettrable: '1' === $lettrable,
            );
        }

        fclose($handle);

        return $accounts;
    }
}
