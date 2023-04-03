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

namespace kuiper\reflection;

use kuiper\reflection\type\ArrayType;
use kuiper\reflection\type\CompositeType;
use kuiper\reflection\type\MapType;
use kuiper\reflection\type\MixedType;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

class PhpstanTypeParser implements TypeParserInterface
{
    private Lexer $typeLexer;
    private TypeParser $typeParser;
    private SimpleTypeParser $simpleTypeParser;

    public function __construct()
    {
        $this->typeLexer = new Lexer();
        $this->typeParser = new TypeParser(new ConstExprParser());
        $this->simpleTypeParser = new SimpleTypeParser();
    }

    private function fromTypeNode(TypeNode $typeNode): ReflectionTypeInterface
    {
        if ($typeNode instanceof NullableTypeNode) {
            return $this->fromTypeNode($typeNode->type)->withAllowsNull(true);
        }

        if ($typeNode instanceof IdentifierTypeNode) {
            return $this->simpleTypeParser->parse($typeNode->name);
        }

        if ($typeNode instanceof ArrayTypeNode) {
            return ArrayType::create($this->fromTypeNode($typeNode->type));
        }

        if (($typeNode instanceof GenericTypeNode) && 'array' === $typeNode->type->name) {
            $variances = array_unique($typeNode->variances);
            if (1 === count($variances) && GenericTypeNode::VARIANCE_INVARIANT === $variances[0]) {
                if (1 === count($typeNode->genericTypes)) {
                    return ArrayType::create($this->fromTypeNode($typeNode->genericTypes[0]));
                }

                if (2 === count($typeNode->genericTypes)) {
                    return new MapType(
                        $this->fromTypeNode($typeNode->genericTypes[0]),
                        $this->fromTypeNode($typeNode->genericTypes[1])
                    );
                }
            }
        }

        if ($typeNode instanceof UnionTypeNode) {
            $types = [];
            foreach ($typeNode->types as $type) {
                $types[] = $this->fromTypeNode($type);
            }

            return new CompositeType($types);
        }

        return new MixedType();
    }

    public function parse(string $typeString): ReflectionTypeInterface
    {
        $tokens = new TokenIterator($this->typeLexer->tokenize($typeString));
        $typeNode = $this->typeParser->parse($tokens);

        return $this->fromTypeNode($typeNode);
    }
}
