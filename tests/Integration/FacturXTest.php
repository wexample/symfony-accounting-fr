<?php

namespace Wexample\SymfonyAccountingFr\Tests\Integration;

use DateTimeImmutable;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Wexample\SymfonyAccounting\Exception\AccountingException;
use Wexample\SymfonyAccounting\Service\Document\CiiInvoiceBuilder;
use Wexample\SymfonyAccounting\Service\Document\CiiInvoiceReader;
use Wexample\SymfonyAccounting\Service\Document\FacturXInvoiceReader;
use Wexample\SymfonyAccounting\Service\Ledger\LedgerService;
use Wexample\SymfonyRemoteDocumentReader\Service\DocumentReaderClient;
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

    public function testAReceivedCiiInvoiceBecomesAPurchase(): void
    {
        $sale = $this->emitDiscounted();
        $xml = $this->service(CiiInvoiceBuilder::class)->build($sale);

        $result = $this->service(CiiInvoiceReader::class)->read($this->buyerLedger(), $xml);
        $purchase = $result->invoice;

        $this->assertSame([], $result->warnings);
        $this->assertTrue($result->partyCreated);
        $this->assertSame('Société Test', $purchase->getParty()->getName());
        $this->assertSame('FR7630006000011234567890189', $purchase->getParty()->getIban());
        $this->assertSame('FR44732829320', $purchase->getParty()->getVatNumber());
        $this->assertSame('Paris', $purchase->getParty()->getCity());
        $this->assertSame($sale->getNumber(), $purchase->getNumber());
        $this->assertSame($sale->getPaymentReference() ?? $sale->getNumber(), $purchase->getPaymentReference());
        $this->assertSame($sale->calcDateDue()->format('Y-m-d'), $purchase->calcDateDue()->format('Y-m-d'));
        $this->assertSame($sale->calcPriceFinal(), $purchase->calcPriceFinal());
        $this->assertSame('cii', $purchase->getOrigin());
    }

    public function testAReceivedFacturXPdfIsReadThroughTheDocumentReader(): void
    {
        $sale = $this->emitDiscounted();
        $xml = $this->service(CiiInvoiceBuilder::class)->build($sale);

        $result = $this->facturXReader($xml, declared: false)->read($this->buyerLedger(), '%PDF-1.7');

        $this->assertSame($sale->calcPriceFinal(), $result->invoice->calcPriceFinal());
        $this->assertSame('factur-x', $result->invoice->getOrigin());
        $this->assertSame(['The PDF embeds factur-x.xml without declaring it: not a valid Factur-X.'], $result->warnings);
    }

    public function testAPdfWithoutXmlIsNoElectronicInvoice(): void
    {
        $this->expectException(AccountingException::class);

        $this->facturXReader(null)->read($this->buyerLedger(), '%PDF-1.7');
    }

    private function buyerLedger(): \Wexample\SymfonyAccounting\Entity\Ledger
    {
        return $this->service(LedgerService::class)->create('Client SAS', $this->country('FR'), fiscalYearStart: new DateTimeImmutable('2026-01-01'));
    }

    private function facturXReader(
        ?string $xml,
        bool $declared = true
    ): FacturXInvoiceReader {
        $response = [
            'ok' => true,
            'information' => [],
            'language' => null,
            'pdfa' => null,
            'attachments' => $xml ? [['name' => 'factur-x.xml', 'mediaType' => 'text/xml', 'description' => '', 'relationship' => 'Alternative', 'size' => strlen($xml), 'content' => base64_encode($xml)]] : [],
            'facturx' => $xml ? ['fileName' => 'factur-x.xml', 'documentType' => 'INVOICE', 'version' => '1.0', 'conformance' => 'EN 16931', 'declared' => $declared] : null,
        ];

        return new FacturXInvoiceReader(
            new DocumentReaderClient(new MockHttpClient(new MockResponse(json_encode($response))), 'http://reader'),
            $this->service(CiiInvoiceReader::class),
        );
    }
}
