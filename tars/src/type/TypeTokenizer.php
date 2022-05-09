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

use kuiper\tars\exception\SyntaxErrorException;
use OutOfBoundsException;

class TypeTokenizer
{
    public const LEFT_BRACKET = '<';
    public const RIGHT_BRACKET = '>';
    public const COMMA = ',';
    public const VECTOR = 'vector';
    public const MAP = 'map';
    public const VOID = 'void';
    public const UNSIGNED = 'unsigned';

    public const T_VOID = 0;
    public const T_VECTOR = 1;
    public const T_MAP = 2;
    public const T_PRIMITIVE = 3;
    public const T_STRUCT = 4;

    public const T_LEFT_BRACKET = 10;
    public const T_RIGHT_BRACKET = 11;
    public const T_COMMA = 12;

    /**
     * @var int[]
     */
    private const STOP_CHARS = [
        self::LEFT_BRACKET => self::T_LEFT_BRACKET,
        self::RIGHT_BRACKET => self::T_RIGHT_BRACKET,
        self::COMMA => self::T_COMMA,
    ];

    /**
     * @var int[]
     */
    private const RESERVE_WORDS = [
        self::VOID => self::T_VOID,
        self::VECTOR => self::T_VECTOR,
        self::MAP => self::T_MAP,
    ];

    private readonly int $length;

    private int $pos = 0;

    public function __construct(private readonly string $input)
    {
        $this->length = strlen($input);
    }

    private function nextChar(): string
    {
        if ($this->pos >= $this->length) {
            throw new OutOfBoundsException('no more char');
        }
        $char = $this->input[$this->pos];
        ++$this->pos;

        return $char;
    }

    private function putBack(): void
    {
        --$this->pos;
    }

    private function createToken(int $tokenType, int|string|null $tokenValue = null): array
    {
        return [$tokenType, $tokenValue];
    }

    /**
     * @throws SyntaxErrorException
     */
    public function tokenize(): array
    {
        $tokens = [];
        while ($token = $this->nextToken()) {
            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * @throws SyntaxErrorException
     */
    private function nextToken(): ?array
    {
        $this->skipWhitespace();
        if ($this->isEnd()) {
            return null;
        }
        $char = $this->nextChar();
        if (isset(self::STOP_CHARS[$char])) {
            return $this->createToken(self::STOP_CHARS[$char]);
        }

        $this->putBack();
        $word = $this->readIdentifier();
        if (isset(self::RESERVE_WORDS[$word])) {
            return $this->createToken(self::RESERVE_WORDS[$word]);
        }

        if (self::UNSIGNED === $word) {
            $this->skipWhitespace();
            $unsignedType = $word.' '.$this->readIdentifier();
            if (!PrimitiveType::has($unsignedType)) {
                $this->raiseSyntaxError('expect byte|short|int for unsigned type');
            }

            return $this->createToken(self::T_PRIMITIVE, $unsignedType);
        }

        if (PrimitiveType::has($word)) {
            return $this->createToken(self::T_PRIMITIVE, $word);
        }

        return $this->createToken(self::T_STRUCT, $word);
    }

    private function isWhitespace(string $char): bool
    {
        return in_array($char, [' ', "\t", "\n"], true);
    }

    private function isEnd(): bool
    {
        return $this->pos >= $this->length;
    }

    private function skipWhitespace(): void
    {
        while (!$this->isEnd()) {
            $char = $this->nextChar();
            if (!$this->isWhitespace($char)) {
                $this->putBack();
                break;
            }
        }
    }

    /**
     * @throws SyntaxErrorException
     */
    private function readIdentifier(): string
    {
        $word = '';
        while (!$this->isEnd()) {
            $char = $this->nextChar();
            if (!$this->isIdentifier($char)) {
                $this->putBack();
                break;
            }
            $word .= $char;
        }
        if (empty($word)) {
            $this->raiseSyntaxError('expected identifier');
        }

        return $word;
    }

    private function isIdentifier(string $char): bool
    {
        return (bool) preg_match('/\w/', $char);
    }

    /**
     * @throws SyntaxErrorException
     */
    private function raiseSyntaxError(string $message): void
    {
        throw new SyntaxErrorException($message.' at '.$this->pos.', type='.$this->input);
    }
}
