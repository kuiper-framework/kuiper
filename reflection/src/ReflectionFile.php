<?php

declare(strict_types=1);

namespace kuiper\reflection;

use kuiper\reflection\exception\FileNotFoundException;
use kuiper\reflection\exception\InvalidTokenException;
use kuiper\reflection\exception\SyntaxErrorException;
use kuiper\reflection\exception\TokenStoppedException;

class ReflectionFile implements ReflectionFileInterface
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var string[]|null
     */
    private $namespaces;

    /**
     * @var string[]|null
     */
    private $classes;

    /**
     * @var string[]|null
     */
    private $traits;

    /**
     * @var array|null
     */
    private $imports;

    /**
     * @var string
     */
    private $currentNamespace = '';

    /**
     * @var bool
     */
    private $hasMultipleClasses = false;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespaces(): array
    {
        $this->parse();

        return $this->namespaces;
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses(): array
    {
        $this->parse();

        return $this->classes;
    }

    /**
     * {@inheritdoc}
     */
    public function getTraits(): array
    {
        $this->parse();

        return $this->traits;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportedClasses(string $namespace): array
    {
        $this->parse();

        return $this->getImported($namespace, T_STRING);
    }

    /**
     * {@inheritdoc}
     */
    public function getImportedFunctions(string $namespace): array
    {
        $this->parse();

        return $this->getImported($namespace, T_FUNCTION);
    }

    /**
     * {@inheritdoc}
     */
    public function getImportedConstants(string $namespace): array
    {
        $this->parse();

        return $this->getImported($namespace, T_CONST);
    }

    /**
     * @throws FileNotFoundException
     * @throws SyntaxErrorException
     */
    private function parse(): void
    {
        if (isset($this->namespaces)) {
            return;
        }
        if (!defined('T_NAME_QUALIFIED')) {
            define('T_NAME_QUALIFIED', 24001);
        }
        if (!defined('T_NAME_FULLY_QUALIFIED')) {
            define('T_NAME_FULLY_QUALIFIED', 24002);
        }
        $code = file_get_contents($this->file);
        if (false === $code) {
            throw new FileNotFoundException("Cannot read file '{$this->file}'");
        }
        $tokens = new TokenStream(token_get_all($code));

        $this->namespaces = [];
        $this->classes = [];
        $this->traits = [];
        $this->imports = [];
        $this->currentNamespace = '';
        $this->hasMultipleClasses = $this->detectMultipleClasses($code);

        try {
            while (true) {
                $token = $tokens->next();
                if (!is_array($token)) {
                    continue;
                }
                switch ($token[0]) {
                    case T_NAMESPACE:
                        $this->currentNamespace = $this->matchNamespace($tokens);
                        if (!in_array($this->currentNamespace, $this->namespaces, true)) {
                            $this->namespaces[] = $this->currentNamespace;
                        }
                        break;
                    case T_USE:
                        $this->matchUse($tokens);
                        break;
                    case T_CLASS:
                    case T_INTERFACE:
                        $this->classes[] = $this->matchClass($tokens);
                        if (!$this->hasMultipleClasses) {
                            throw new TokenStoppedException('no more token');
                        }
                        break;
                    case T_TRAIT:
                        $this->traits[] = $this->matchClass($tokens);
                        if (!$this->hasMultipleClasses) {
                            throw new TokenStoppedException('no more token');
                        }
                        break;
                    case T_DOUBLE_COLON:
                        // prevent '::class'
                        $tokens->skipWhitespaceAndCommentMaybe();
                        $tokens->next();
                }
            }
        } catch (TokenStoppedException $e) {
        } catch (InvalidTokenException $e) {
            throw new SyntaxErrorException(sprintf('%s, got %s in %s on line %d', $e->getMessage(), $tokens->describe($tokens->current()), $this->file, $tokens->getLine()), 0, $e);
        }
    }

    /**
     * @return string
     *
     * @throws InvalidTokenException
     * @throws TokenStoppedException
     */
    private function matchNamespace(TokenStream $tokens)
    {
        $tokens->next();
        $tokens->skipWhitespaceAndCommentMaybe();
        $token = $tokens->current();
        if ('{' === $token) {
            return '';
        }

        return $tokens->matchIdentifier();
    }

    /**
     * @throws InvalidTokenException
     * @throws TokenStoppedException
     */
    private function matchClass(TokenStream $tokens): string
    {
        $tokens->next();
        $tokens->skipWhitespaceAndComment();
        $class = $tokens->matchIdentifier();
        if ($this->hasMultipleClasses) {
            $tokens->matchParentheses();
        }

        return '' !== $this->currentNamespace
            ? $this->currentNamespace.ReflectionNamespaceInterface::NAMESPACE_SEPARATOR.$class
            : $class;
    }

    /**
     * @throws InvalidTokenException
     * @throws TokenStoppedException
     */
    private function matchUse(TokenStream $tokens): void
    {
        [$type, $stmtImports] = $tokens->matchUseStatement();
        if (!isset($this->imports[$this->currentNamespace])) {
            $this->imports[$this->currentNamespace] = [];
        }
        $imports = &$this->imports[$this->currentNamespace];

        if (isset($imports[$type])) {
            $prev = $imports[$type];
            foreach ($stmtImports as $alias => $name) {
                if (isset($prev[$alias])) {
                    throw new InvalidTokenException(sprintf("Duplicated import alias '%s' for '%s', previous '%s'", $name, $alias, $prev[$alias]));
                }
            }
            $imports[$type] = array_merge($prev, $stmtImports);
        } else {
            $imports[$type] = $stmtImports;
        }
    }

    private function getImported(string $namespace, int $type): array
    {
        if (isset($this->imports[$namespace][$type])) {
            return $this->imports[$namespace][$type];
        }

        return [];
    }

    /**
     * @param string $code
     */
    private function detectMultipleClasses($code): bool
    {
        return preg_match_all('/^\s*((abstract|final)+ )?(class|interface|trait)\s+/sm', $code) > 1;
    }
}
