<?php

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
}
