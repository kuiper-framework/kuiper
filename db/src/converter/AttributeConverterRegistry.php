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

namespace kuiper\db\converter;

use kuiper\reflection\ReflectionType;

class AttributeConverterRegistry
{
    /**
     * @var AttributeConverterInterface[]
     */
    private array $converters = [];

    public function get(string $name): ?AttributeConverterInterface
    {
        return $this->converters[$name] ?? null;
    }

    public function register(string $name, AttributeConverterInterface $converter): void
    {
        $this->converters[$name] = $converter;
    }

    public static function createDefault(): AttributeConverterRegistry
    {
        $registry = new self();
        $registry->register('bool', new BoolConverter());
        foreach (['int', 'string', 'float'] as $typeName) {
            $type = ReflectionType::parse($typeName);
            $registry->register($type->getName(), new PrimitiveConverter($type));
        }
        $registry->register(JsonConverter::class, new JsonConverter());
        $registry->register(JoinerConverter::class, new JoinerConverter());
        return $registry;
    }
}
