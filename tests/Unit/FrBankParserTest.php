<?php

namespace Wexample\SymfonyAccountingFr\Tests\Unit;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;
use Wexample\SymfonyAccountingFr\Service\Bank\Parser\FrCa2023BankStatementParser;
use Wexample\SymfonyAccountingFr\Service\Bank\Parser\FrLbp2019BankStatementParser;
use Wexample\SymfonyAccountingFr\Service\Bank\Parser\FrLbp2023BankStatementParser;

class FrBankParserTest extends TestCase
{
    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/Bank/'.$name);
    }

    public function testLbp2019Csv(): void
    {
        $parser = new FrLbp2019BankStatementParser();
        $content = $this->fixture('lbp2019.csv');
        $this->assertTrue($parser->supports($content));

        $statement = $parser->parse($content);
        $this->assertSame([-1299, 125000], array_map(fn ($t) => $t->amount, $statement->transactions));
        $this->assertSame(123450, $statement->balances[0]->balance);
        $this->assertSame('2021-07-14', $statement->balances[0]->date->format('Y-m-d'));
    }

    public function testLbp2019PdfText(): void
    {
        $parser = new FrLbp2019BankStatementParser();
        $content = $this->fixture('lbp2019.txt');
        $this->assertTrue($parser->supports($content));

        $statement = $parser->parse($content);
        // Debits recognized from their label: direct debit and outgoing transfer.
        $this->assertSame([253500, -4590, -25200], array_map(fn ($t) => $t->amount, $statement->transactions));
        $this->assertSame('2018-12-14', $statement->transactions[0]->date->format('Y-m-d'));
        $this->assertSame('VIREMENT DE CLIENT A REFERENCE : 1', $statement->transactions[0]->label);
        $this->assertSame(233710, $statement->balances[0]->balance);
        // The guess reconciles: old balance 100,00 + lines = new balance.
        $this->assertSame(233710 - 10000, $statement->getTotal());
    }

    public function testLbp2023(): void
    {
        $parser = new FrLbp2023BankStatementParser();
        $content = $this->fixture('lbp2023.csv');
        $this->assertTrue($parser->supports($content));

        $statement = $parser->parse($content);
        $this->assertSame([24900, -29291], array_map(fn ($t) => $t->amount, $statement->transactions));
        $this->assertSame(1025113, $statement->balances[0]->balance);
    }

    public function testCa2023(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Téléchargement du 05/09/2023');
        $sheet->setCellValue('C7', 1520.4);
        $sheet->setCellValue('A11', 45170.0);
        $sheet->setCellValue('B11', "PRLV SEPA FOURNISSEUR\nREF 12");
        $sheet->setCellValue('C11', 80.6);
        $sheet->setCellValue('A12', 45171.0);
        $sheet->setCellValue('B12', 'VIR CLIENT');
        $sheet->setCellValue('D12', 1200);
        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($file);
        $content = file_get_contents($file);
        unlink($file);

        $parser = new FrCa2023BankStatementParser();
        $this->assertTrue($parser->supports($content, 'export.xlsx'));
        $statement = $parser->parse($content);

        $this->assertSame([-8060, 120000], array_map(fn ($t) => $t->amount, $statement->transactions));
        $this->assertSame('2023-09-01', $statement->transactions[0]->date->format('Y-m-d'));
        $this->assertSame('PRLV SEPA FOURNISSEUR REF 12', $statement->transactions[0]->label);
        $this->assertSame(152040, $statement->balances[0]->balance);
    }
}
