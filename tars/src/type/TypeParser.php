<?php

declare(strict_types=1);

namespace kuiper\tars\type;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\helper\Enum;
use kuiper\reflection\exception\SyntaxErrorException;
use kuiper\tars\annotation\TarsProperty;

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
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    /**
     * @var StructType[]
     */
    private $cache = [];

    /**
     * TypeParser constructor.
     */
    public function __construct(AnnotationReaderInterface $annotationReader)
    {
        $this->annotationReader = $annotationReader;
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
            if (is_a($className, Enum::class, true)) {
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
            $reflectionClass = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new SyntaxErrorException("Class not found '${className}'");
        }
        // 防止递归类型错误
        $this->cache[$className] = $structType = new StructType($className, []);

        $namespace = $reflectionClass->getNamespaceName();
        $fields = [];
        foreach ($reflectionClass->getProperties() as $property) {
            /** @var TarsProperty|null $annotation */
            $annotation = $this->annotationReader->getPropertyAnnotation($property, TarsProperty::class);
            if (null !== $annotation) {
                $type = $this->parse($annotation->type, $namespace);
                $fields[] = new StructField($annotation->order, $property->getName(), $type, $annotation->required);
            }
        }
        $structType->setFields($fields);

        return $structType;
    }
}
