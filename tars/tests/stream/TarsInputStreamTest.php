<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\tars\stream;

use kuiper\annotations\AnnotationReader;
use kuiper\tars\fixtures\Request;
use kuiper\tars\type\TypeParser;
use PHPUnit\Framework\TestCase;

class TarsInputStreamTest extends TestCase
{
    public function testOptional()
    {
        $request = new Request();
        $typeParser = new TypeParser(AnnotationReader::getInstance());
        $type = $typeParser->parse('Request', 'kuiper\\tars\\fixtures');
        $data = TarsOutputStream::pack($type, $request);
        /** @var Request $obj */
        $obj = TarsInputStream::unpack($type, $data);
        $this->assertNull($obj->boolOpt);
        $this->assertNull($obj->stringOpt);
        $this->assertNull($obj->intOpt);
    }

    public function testInt()
    {
        $request = new Request();
        $request->intOpt = -10;
        $typeParser = new TypeParser(AnnotationReader::getInstance());
        $type = $typeParser->parse('Request', 'kuiper\\tars\\fixtures');
        $data = TarsOutputStream::pack($type, $request);
        /** @var Request $obj */
        $obj = TarsInputStream::unpack($type, $data);
        $this->assertEquals(-10, $obj->intOpt);
    }
}
