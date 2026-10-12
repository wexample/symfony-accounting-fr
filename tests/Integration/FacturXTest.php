<?php

namespace Wexample\SymfonyAccountingFr\Tests\Integration;

use Wexample\SymfonyAccounting\Service\Document\CiiInvoiceBuilder;
use Wexample\SymfonyAccounting\Service\Document\InvoicePdfService;
use Wexample\SymfonyAccounting\Service\Invoice\InvoiceEmissionService;
use Wexample\SymfonyAccounting\Service\Invoice\InvoiceFactory;
use Wexample\SymfonyMoney\Enum\PriceUnit;

class FacturXTest extends AbstractFrTestCase
{
    private function xpath(
        string $xml,
        string $path
    ): array {
        $document = simplexml_load_string($xml);
        $document->registerXPathNamespace('rsm', CiiInvoiceBuilder::NS_RSM);
        $document->registerXPathNamespace('ram', CiiInvoiceBuilder::NS_RAM);
        $document->registerXPathNamespace('udt', CiiInvoiceBuilder::NS_UDT);

        return array_map('strval', $document->xpath($path));
    }

    private function emitDiscounted(): \Wexample\SymfonyAccounting\Entity\Invoice
    {
        $ledger = $this->createLedger();
        $ledger->setPostalAddress('1 rue de la Paix')->setPostCode('75002')->setCity('Paris');
        $bank = $this->createBankAccount($ledger)->setIban('FR7630006000011234567890189')->setBic('AGRIFRPP');
        $ledger->setDefaultBankAccount($bank);
        $party = $this->createParty($ledger, 'Client SAS', 'FR', 'FR40303265045');
        $party->setLegalIdentifier('303265045')->setPostalAddress('5 avenue Foch')->setPostCode('69006')->setCity('Lyon');

        $factory = $this->service(InvoiceFactory::class);
        $invoice = $factory->create($ledger, party: $party, title: 'BC-12');
        $factory->addItem($invoice, 'Conseil', 50000, 150)->setPriceDiscount(1000, PriceUnit::Percent);
        $factory->addItem($invoice, 'Livre', 2000, 300, vatRate: 550, goods: true);
        $invoice->setPriceDiscount(1000, PriceUnit::Money);
        $this->em()->flush();

        return $this->service(InvoiceEmissionService::class)->emit($invoice);
    }

    public function testTheInvoiceInCii(): void
    {
        $invoice = $this->emitDiscounted();
        $xml = $this->service(CiiInvoiceBuilder::class)->build($invoice);

        $this->assertSame([$invoice->getNumber()], $this->xpath($xml, '//rsm:ExchangedDocument/ram:ID'));
        $this->assertSame(['380'], $this->xpath($xml, '//rsm:ExchangedDocument/ram:TypeCode'));
        $this->assertSame(['urn:cen.eu:en16931:2017'], $this->xpath($xml, '//ram:GuidelineSpecifiedDocumentContextParameter/ram:ID'));
        $this->assertSame(['73282932000009'], $this->xpath($xml, '//ram:SellerTradeParty/ram:URIUniversalCommunication/ram:URIID'));
        $this->assertSame(['0002'], $this->xpath($xml, '//ram:BuyerTradeParty/ram:SpecifiedLegalOrganization/ram:ID/@schemeID'));
        $this->assertSame(['FR40303265045'], $this->xpath($xml, '//ram:BuyerTradeParty/ram:SpecifiedTaxRegistration/ram:ID'));
        $this->assertSame(['FR7630006000011234567890189'], $this->xpath($xml, '//ram:IBANID'));
        $this->assertSame(['1.5', '3'], $this->xpath($xml, '//ram:BilledQuantity'));
        $this->assertSame(['5.50', '20.00'], $this->xpath($xml, '//ram:ApplicableHeaderTradeSettlement/ram:ApplicableTradeTax/ram:RateApplicablePercent'));

        $sum = fn (string $path) => array_sum(array_map('floatval', $this->xpath($xml, $path)));
        $total = fn (string $name) => (float) $this->xpath($xml, '//ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:'.$name)[0];

        // EN 16931 totals: BR-CO-10, BR-CO-13, BR-CO-14, BR-CO-15.
        $this->assertEqualsWithDelta($sum('//ram:SpecifiedTradeSettlementLineMonetarySummation/ram:LineTotalAmount'), $total('LineTotalAmount'), 0.001);
        $this->assertEqualsWithDelta($total('LineTotalAmount') - $total('AllowanceTotalAmount'), $total('TaxBasisTotalAmount'), 0.001);
        $this->assertEqualsWithDelta($sum('//ram:ApplicableHeaderTradeSettlement/ram:ApplicableTradeTax/ram:CalculatedAmount'), $total('TaxTotalAmount'), 0.001);
        $this->assertEqualsWithDelta($total('TaxBasisTotalAmount') + $total('TaxTotalAmount'), $total('GrandTotalAmount'), 0.001);
        $this->assertSame($invoice->calcPriceFinal(), (int) round($total('DuePayableAmount') * 100));
    }

    public function testACreditNoteInCii(): void
    {
        $ledger = $this->createLedger();
        $bill = $this->emitSale($ledger, $this->createParty($ledger, 'Client', 'FR'), 100000);
        $credit = $this->service(InvoiceFactory::class)->createCreditNote($bill);
        $this->em()->flush();
        $this->service(InvoiceEmissionService::class)->emit($credit);

        $xml = $this->service(CiiInvoiceBuilder::class)->build($credit);

        $this->assertSame(['381'], $this->xpath($xml, '//rsm:ExchangedDocument/ram:TypeCode'));
        $this->assertSame([], $this->xpath($xml, '//ram:SpecifiedTradePaymentTerms'));
        $this->assertSame(['1200.00'], $this->xpath($xml, '//ram:GrandTotalAmount'));
    }

    public function testTheFrenchPdfIsAFacturX(): void
    {
        $invoice = $this->emitDiscounted();
        $output = $this->service(InvoicePdfService::class)->prepare($invoice, 'fr')->output;

        $this->assertSame('pdf/a-3b', $output->variant);
        $this->assertSame('EN 16931', $output->facturxConformance);
        $this->assertSame('Alternative', $output->facturxRelationship);
        $this->assertSame($this->service(CiiInvoiceBuilder::class)->build($invoice), $output->facturxXml);
    }
}
