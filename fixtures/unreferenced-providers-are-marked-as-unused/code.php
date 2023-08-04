<?php

declare(strict_types=1);

namespace Vendor\Project;

use PHPUnit\Framework\TestCase;

class MyTestCase extends TestCase
{
    /**
     * @return iterable<string,array{int}>
     */
    public function provide()
    {
        yield "data set name" => [1];
    }

    /**
     * @return void
     */
    public function testSomething(int $int)
    {
        $this->assertEquals(1, $int);
    }
}
