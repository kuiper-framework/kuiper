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

namespace kuiper\tars\type;

use kuiper\tars\attribute\TarsProperty;
use kuiper\tars\exception\SyntaxErrorException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use UnitEnum;

/**
 * tars_type: vector< vector_sub_type > :
 *            map< key_type, value_type > :
 *            primitive_type :
 *            custom_type.
 *
 * Class TypeParser
 */
class TypeParser
{
    /**
     * @var StructType[]
     */
    private array $cache = [];

    public function fromPhpType(ReflectionNamedType $type): Type
    {
        if ($type->isBuiltin()) {
            return $this->parse($type->getName());
        }

        $pos = strrpos($type->getName(), '\\');

        return $this->parse(substr($type->getName(), $pos + 1), substr($type->getName(), 0, $pos));
    }

    /**
     * @throws SyntaxErrorException
     */
    public function parse(string $type, string $namespace = ''): Type
    {
        $tokens = (new TypeTokenizer($type))->tokenize();

        return $this->createType($tokens, $namespace);
    }

    /**
     * @throws SyntaxErrorException
     */
    private function createType(array &$tokens, string $namespace): Type
    {
        if (empty($tokens)) {
            throw new SyntaxErrorException('expect one type');
        }
        $token = array_shift($tokens);
        if (TypeTokenizer::T_PRIMITIVE === $token[0]) {
            return PrimitiveType::of($token[1]);
        }

        if (TypeTokenizer::T_STRUCT === $token[0]) {
            $className = $namespace.'\\'.$token[1];
            if (is_a($className, UnitEnum::class, true)) {
                return new EnumType($className);
            }

            return $this->createStructType($className);
        }

        if (TypeTokenizer::T_VOID === $token[0]) {
            return VoidType::instance();
        }

        if (TypeTokenizer::T_VECTOR === $token[0]) {
            $this->match(array_shift($tokens), TypeTokenizer::T_LEFT_BRACKET);
            $subType = $this->createType($tokens, $namespace);
            $this->match(array_shift($tokens), TypeTokenizer::T_RIGHT_BRACKET);

            return new VectorType($subType);
        }

        if (TypeTokenizer::T_MAP === $token[0]) {
            $this->match(array_shift($tokens), TypeTokenizer::T_LEFT_BRACKET);
            $keyType = $this->createType($tokens, $namespace);
            $this->match(array_shift($tokens), TypeTokenizer::T_COMMA);
            $valueType = $this->createType($tokens, $namespace);
            $this->match(array_shift($tokens), TypeTokenizer::T_RIGHT_BRACKET);

            return new MapType($keyType, $valueType);
        }
        throw new SyntaxErrorException('unknown type');
    }

    /**
     * @throws SyntaxErrorException
     */
    private function match(array $token, int $tokenType): void
    {
        if ($token[0] !== $tokenType) {
            throw new SyntaxErrorException("token not match $tokenType");
        }
    }

    private function createStructType(string $className): StructType
    {
        if (isset($this->cache[$className])) {
            return $this->cache[$className];
        }

        try {
            $reflectionClass = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw new SyntaxErrorException("Class not found '{$className}'");
        }
        $constructor = $reflectionClass->getConstructor();
        // 防止递归类型错误
        $this->cache[$className] = $structType = new StructType($className, [], null !== $constructor);

        $namespace = $reflectionClass->getNamespaceName();
        $fields = [];
        $addField = function ($i, $property) use ($namespace, &$fields) {
            $attributes = $property->getAttributes(TarsProperty::class);
            if (count($attributes) > 0 && null !== $property->getType()) {
                /** @var TarsProperty $attribute */
                $attribute = $attributes[0]->newInstance();
                $type = $this->parse($attribute->getType(), $namespace);
                $tag = $attribute->getOrder() ?? $i;
                $fields[$property->getName()] = new StructField($tag, $property->getName(), $type, !$property->getType()->allowsNull());
            }
        };
        if (null !== $constructor) {
            foreach ($constructor->getParameters() as $i => $parameter) {
                $addField($i, $parameter);
            }
        }
        $i = 0;
        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $addField($i, $property);
            ++$i;
        }
        $structType->setFields($fields);

        return $structType;
    }
}
