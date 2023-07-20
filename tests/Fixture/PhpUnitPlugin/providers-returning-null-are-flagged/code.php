<?php

namespace Vendor\Package;

use PHPUnit\Framework\TestCase;

class MyTestCase extends TestCase
{
    /** @return null */
    public function provide()
    {
        return null;
    }
    /** @dataProvider provide */
    public function testSomething(): void
    {
    }
}
