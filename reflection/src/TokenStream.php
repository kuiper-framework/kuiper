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

use kuiper\reflection\exception\FileNotFoundException;
use kuiper\reflection\exception\InvalidTokenException;
use kuiper\reflection\exception\TokenStoppedException;

final class TokenStream
{
    public const IMPORT_CLASS = 0;
    public const IMPORT_CONST = 1;
    public const IMPORT_FUNCTION = 2;

    private const IMPORT_TYPE = [
        T_STRING => self::IMPORT_CLASS,
        T_NAME_QUALIFIED => self::IMPORT_CLASS,
        T_FUNCTION => self::IMPORT_FUNCTION,
        T_CONST => self::IMPORT_CONST,
    ];

    private string|array|null $current;

    private bool $end = false;

    private int $line = 0;

    private function __construct(private readonly \ArrayIterator $tokens)
    {
    }

    public static function createFromFile(string $file): self
    {
        $code = file_get_contents($file);
        if (false === $code) {
            throw new FileNotFoundException("Cannot read file '{$file}'");
        }
        return self::createFromCode($code);
    }

    public static function createFromCode(string $code): self
    {
        return self::create(token_get_all($code));
    }

    public static function create(array $tokens): self
    {
        return new self(new \ArrayIterator($tokens));
    }

    /**
     * Gets next token.
     *
     * @return array|string|null
     *
     * @throws TokenStoppedException
     */
    public function next(): array|string|null
    {
        if ($this->end || !$this->tokens->valid()) {
            $this->end = true;
            throw new TokenStoppedException('no more token');
        }
        if (isset($this->current)) {
            $this->tokens->next();
        }
        $this->current = $this->tokens->current();
        if (is_array($this->current)) {
            $this->line = $this->current[2];
        }
        // error_log($this->describe($this->current));
        return $this->current;
    }

    /**
     * Gets current token.
     *
     * @return array|string|null
     */
    public function current(): array|string|null
    {
        if (!isset($this->current)) {
            throw new \BadMethodCallException('call next first');
        }

        return $this->current;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * Skips whitespace or comment.
     *
     * @throws InvalidTokenException
     * @throws TokenStoppedException
     */
    public function skipWhitespaceAndCommentMaybe(): void
    {
        $this->skipWhitespaceAndComment(false);
    }

    /**
     * Skips whitespace and comment.
     * Stops when first token that is not whitespace or comment.
     *
     * @throws InvalidTokenException
     * @throws TokenStoppedException
     */
    public function skipWhitespaceAndComment(bool $required = true): void
    {
        $whitespace = '';
        while (true) {
            if (is_array($this->current) && in_array($this->current[0], [T_WHITESPACE, T_COMMENT], true)) {
                $whitespace .= $this->current[1];
                $this->next();
            } else {
                break;
            }
        }
        if ($required && empty($whitespace)) {
            throw new InvalidTokenException('Expected whitespace');
        }
    }

    /**
     * Reads the identifiers at current position.
     * Stops when first token that not belong to identifier (not string or ns_separator).
     *
     * @throws InvalidTokenException if there not identifier at current position
     * @throws TokenStoppedException
     */
    public function matchIdentifier(): string
    {
        $identifier = '';
        while (true) {
            if (is_array($this->current) && in_array($this->current[0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED], true)) {
                $identifier .= $this->current[1];
                $this->next();
            } else {
                break;
            }
        }

        if (empty($identifier)) {
            throw new InvalidTokenException('Expected identifier');
        }

        return $identifier;
    }

    /**
     * Reads use statement.
     *
     * @return array the import type is the first element: T_FUNCTION, T_CONST, T_STRING (class),
     *               the import list is the second element
     *
     * @throws InvalidTokenException
     * @throws TokenStoppedException
     */
    public function matchUseStatement(): array
    {
        $this->next();
        $this->skipWhitespaceAndComment();
        if (!is_array($this->current) || !in_array($this->current[0], [T_FUNCTION, T_CONST, T_STRING, T_NAME_QUALIFIED], true)) {
            throw new InvalidTokenException("expected class name or the keyword 'function' or 'const'");
        }
        $importType = self::IMPORT_TYPE[$this->current[0]];
        if (in_array($importType, [self::IMPORT_CONST, self::IMPORT_FUNCTION], true)) {
            $this->next();
            $this->skipWhitespaceAndComment();
        }
        $imports = $this->matchImportList(';');

        return [$importType, $imports];
    }

    /**
     * @param string $stopToken
     * @param bool   $hasSubList
     *
     * @return array
     *
     * @throws InvalidTokenException
     * @throws TokenStoppedException
     */
    private function matchImportList(string $stopToken, bool $hasSubList = true): array
    {
        $imports = [];
        while (true) {
            foreach ($this->matchUseList($hasSubList) as $alias => $name) {
                if (isset($imports[$alias])) {
                    throw new InvalidTokenException(sprintf("Duplicated import alias '%s' for '%s', previous '%s'", $name, $alias, $imports[$alias]));
                }
                $imports[$alias] = $name;
            }
            $this->skipWhitespaceAndCommentMaybe();
            if (',' === $this->current) {
                $this->next();
                $this->skipWhitespaceAndCommentMaybe();
            } elseif ($this->current === $stopToken) {
                $this->next();
                break;
            } else {
                throw new InvalidTokenException('Expected comma or semicolon here');
            }
        }

        return $imports;
    }

    /**
     * @throws InvalidTokenException
     * @throws TokenStoppedException
     */
    private function matchUseList(bool $hasSubList): array
    {
        $imports = [];
        $name = $this->matchIdentifier();
        if ('{' === $this->current) {
            if (!$hasSubList) {
                throw new InvalidTokenException('Unexpected token');
            }
            $this->next();
            $imports = $this->matchImportList('}', false);
            $prefix = $name;
            foreach ($imports as $alias => $fqcn) {
                $imports[$alias] = $prefix.$fqcn;
            }
        } else {
            $this->skipWhitespaceAndCommentMaybe();
            if (is_array($this->current) && T_AS === $this->current[0]) {
                $this->next();
                $this->skipWhitespaceAndComment();
                $alias = $this->matchIdentifier();
                if (str_contains($alias, ReflectionNamespaceInterface::NAMESPACE_SEPARATOR)) {
                    throw new InvalidTokenException("import alias '{$alias}' cannot contain namespace separator");
                }
            } else {
                $alias = $this->getSimpleName($name);
            }
            $imports[$alias] = $name;
        }

        return $imports;
    }

    /**
     * match begin and end of parentheses.
     *
     * @throws TokenStoppedException
     * @throws InvalidTokenException
     */
    public function matchParentheses(): void
    {
        $stack = [];
        while (true) {
            $this->next();
            if (is_array($this->current) && T_CURLY_OPEN === $this->current[0]) {
                $stack[] = '{';
            } elseif ('{' === $this->current) {
                $stack[] = '{';
            } elseif ('}' === $this->current) {
                array_pop($stack);
                if (empty($stack)) {
                    break;
                }
            }
        }
        if (!empty($stack)) {
            throw new InvalidTokenException('parentheses not match');
        }
    }

    private function getSimpleName(string $name): string
    {
        $parts = explode(ReflectionNamespaceInterface::NAMESPACE_SEPARATOR, $name);

        return end($parts);
    }

    /**
     * describes the token value.
     *
     * @param array|string $token
     *
     * @return string
     */
    public static function describe(array|string $token): string
    {
        if (is_array($token)) {
            return '['.implode(', ', [token_name($token[0]), json_encode($token[1]), $token[2]]).']';
        }

        return json_encode($token);
    }
}
