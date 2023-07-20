<?php

namespace Vendor\Package;

use PHPUnit\Framework\TestCase;

class MyTestCase extends TestCase
{
    /** @return void */
    public function testSomething()
    {
        $this->expectException(MyTestCase::class);
    }
}
