<?php

namespace Wexample\SymfonyAccountingFr\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\SymfonyAccountingFr\Helper\FrRibHelper;

class FrRibHelperTest extends TestCase
{
    public function testRib(): void
    {
        $this->assertSame('85', FrRibHelper::computeKey('30001', '00794', '12345678901'));
        $this->assertFalse(FrRibHelper::isValid('30001', '00794', '12345678901', '84'));
        $rib = FrRibHelper::fromIban('FR76 3000 1007 9412 3456 7890 185');
        $this->assertSame(['bank' => '30001', 'branch' => '00794', 'account' => '12345678901', 'key' => '85'], $rib);
    }
}
