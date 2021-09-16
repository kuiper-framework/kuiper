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

namespace kuiper\db;

use kuiper\db\converter\AttributeConverterRegistry;
use kuiper\db\converter\BoolConverter;
use kuiper\db\converter\DateConverter;
use kuiper\db\converter\DateTimeConverter;
use kuiper\db\converter\PrimitiveConverter;
use kuiper\reflection\ReflectionType;
use PHPUnit\Framework\TestCase;

abstract class AbstractRepositoryTestCase extends TestCase
{
    public function createAttributeRegistry(): AttributeConverterRegistry
    {
        $registry = new AttributeConverterRegistry();
        $registry->register('bool', new BoolConverter());
        foreach (['int', 'string', 'float'] as $typeName) {
            $type = ReflectionType::parse($typeName);
            $registry->register($type->getName(), new PrimitiveConverter($type));
        }
        $registry->register(\DateTime::class, new DateTimeConverter(new DateTimeFactory()));
        $registry->register(DateConverter::class, new DateConverter(new DateTimeFactory()));

        return $registry;
    }
}
