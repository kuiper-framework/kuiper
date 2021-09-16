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

namespace kuiper\tars\client;

use kuiper\rpc\client\ProxyGenerator;
use kuiper\tars\annotation\TarsServant;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Reflection\DocBlockReflection;

class TarsProxyGenerator extends ProxyGenerator
{
    protected function createClassGenerator(string $interfaceName, array $context = []): ClassGenerator
    {
        $class = parent::createClassGenerator($interfaceName, $context);
        if (isset($context['service'])) {
            $class->setDocBlock(DocBlockGenerator::fromReflection(
                new DocBlockReflection($this->createDocBlock($context['service']))));
        }

        return $class;
    }

    private function createDocBlock(string $servantName): string
    {
        return "/**\n"
            .sprintf(' * @\\%s("%s")', TarsServant::class, $servantName)
            ."\n*/";
    }
}
