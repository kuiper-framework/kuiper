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

namespace kuiper\swoole\pool;

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use PHPUnit\Framework\TestCase;

class ConnectionProxyGeneratorTest extends TestCase
{
    public function testName()
    {
        $generator = new ConnectionProxyGenerator();
        $result = $generator->generate(AnnotationReaderInterface::class);
        $result->eval();
        $class = $result->getClassName();
        $reader = new $class(new SingleConnectionPool('reader', function () {
            return AnnotationReader::getInstance();
        }));
        $this->assertInstanceOf(AnnotationReaderInterface::class, $reader);
    }
}
