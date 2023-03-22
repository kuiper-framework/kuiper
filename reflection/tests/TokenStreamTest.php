<?php

declare(strict_types=1);

namespace kuiper\reflection;

use PHPUnit\Framework\TestCase;

class TokenStreamTest extends TestCase
{
    public function testAttribute()
    {
        TokenStream::fromFile(__DIR__.'/fixtures/TestAttribute.php');
        $this->assertTrue(true);
    }
}
