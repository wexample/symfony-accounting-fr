<?php

namespace Wexample\SymfonyAccountingFr\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\SymfonyAccountingFr\Helper\FrIdentityHelper;
use Wexample\SymfonyAccountingFr\Helper\FrRibHelper;

class FrIdentityTest extends TestCase
{
    public function testSirenAndSiret(): void
    {
        $this->assertTrue(FrIdentityHelper::isValidSiren('732 829 320'));
        $this->assertFalse(FrIdentityHelper::isValidSiren('732829321'));
        $this->assertTrue(FrIdentityHelper::isValidSiret('732 829 320 00009'));
        $this->assertFalse(FrIdentityHelper::isValidSiret('73282932000008'));
        $this->assertSame('732829320', FrIdentityHelper::sirenOf('73282932000009'));
    }

    public function testVatNumber(): void
    {
        $this->assertSame('FR44732829320', FrIdentityHelper::buildVatNumber('732829320'));
        $this->assertTrue(FrIdentityHelper::isValidVatNumber('FR 44 732829320'));
        $this->assertFalse(FrIdentityHelper::isValidVatNumber('FR45732829320'));
        $this->assertSame('732829320', FrIdentityHelper::sirenOf('FR44732829320'));
    }

    public function testRib(): void
    {
        $this->assertSame('85', FrRibHelper::computeKey('30001', '00794', '12345678901'));
        $this->assertFalse(FrRibHelper::isValid('30001', '00794', '12345678901', '84'));
        $rib = FrRibHelper::fromIban('FR76 3000 1007 9412 3456 7890 185');
        $this->assertSame(['bank' => '30001', 'branch' => '00794', 'account' => '12345678901', 'key' => '85'], $rib);
    }
}
