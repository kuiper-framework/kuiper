<?php

namespace kuiper\reflection;

use ArrayIterator;
use InvalidArgumentException;
use Iterator;
use kuiper\reflection\exception\SyntaxErrorException;

class ReflectionFile implements ReflectionFileInterface
{
    const T_CLASSES = 'classes';
    const T_CONSTANTS = 'constants';
    const T_FUNCTIONS = 'functions';

    /**
     * @var array
     */
    private static $TOKEN_TYPES;

    /**
     * @var string
     */
    private $file;

    /**
     * @var string[]
     */
    private $namespaces;

    /**
     * @var string[]
     */
    private $classes;

    /**
     * @var array
     */
    private $imports;

    /**
     * @param string $file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespaces()
    {
        $this->parse();

        return $this->namespaces;
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses()
    {
        $this->parse();

        return $this->classes;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportedClasses($namespace)
    {
        $this->parse();
        if (isset($this->imports[$namespace][self::T_CLASSES])) {
            return $this->imports[$namespace][self::T_CLASSES];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getImportedFunctions($namespace)
    {
        $this->parse();
        if (isset($this->imports[$namespace][self::T_FUNCTIONS])) {
            return $this->imports[$namespace][self::T_FUNCTIONS];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getImportedConstants($namespace)
    {
        $this->parse();
        if (isset($this->imports[$namespace][self::T_CONSTANTS])) {
            return $this->imports[$namespace][self::T_CONSTANTS];
        }

        return [];
    }

    private function parse()
    {
        if (isset($this->namespaces)) {
            return;
        }
        $code = file_get_contents($this->file);
        if ($code === false) {
            throw new InvalidArgumentException("Cannot read file '{$this->file}'");
        }
        $tokens = new ArrayIterator(token_get_all($code));
        $imports = [];
        $classes = [];
        $namespaces = [];
        $line = 1;
        $prevToken = null;
        $namespace = '';
        while ($tokens->valid()) {
            $token = $tokens->current();
            $tokens->next();
            if (!is_array($token)) {
                $tokens->next();
                continue;
            }
            if ($token[0] === T_NAMESPACE) {
                $this->skipWhitespaceAndComment($tokens);
                $namespace = $this->matchIdentifier($tokens);
                $namespaces[] = $namespace;
            } elseif ($token[0] === T_USE) {
                $line = $token[2];
                $this->skipWhitespaceAndComment($tokens);
                try {
                    list($type, $stmtImports) = $this->parseUseStatement($tokens);
                    if (isset($imports[$namespace][$type])) {
                        $prev = $imports[$namespace][$type];
                        foreach ($stmtImports as $alias => $name) {
                            if (isset($prev[$alias])) {
                                throw new SyntaxErrorException(sprintf(
                                    "duplicated import alias '%s' for '%s', previous '%s'",
                                    $name,
                                    $alias,
                                    $prev[$alias]
                                ));
                            }
                        }
                        $imports[$namespace][$type] = array_merge($prev, $stmtImports);
                    } else {
                        $imports[$namespace][$type] = $stmtImports;
                    }
                } catch (SyntaxErrorException $e) {
                    throw new SyntaxErrorException("Syntax error at '{$this->file}' line {$line}: ".$e->getMessage());
                }
            } elseif (in_array($token[0], [T_CLASS, T_INTERFACE])) {
                if ($token[0] === T_CLASS) {
                    if ($prevToken && $prevToken[0] === T_DOUBLE_COLON) {
                        // ignore  Foo::class
                        continue;
                    }
                }
                $this->skipWhitespaceAndComment($tokens);
                $class = $this->matchIdentifier($tokens);
                if (!$class) {
                    throw new SyntaxErrorException(
                        "Syntax error at '{$this->file}' line {$line}: expected class name or interface name, got "
                        .$this->describeToken($tokens->current())
                    );
                }
                $classes[] = $namespace ? $namespace.'\\'.$class : $class;

                try {
                    $this->matchParentheses($tokens);
                } catch (SyntaxErrorException $e) {
                    throw new SyntaxErrorException(sprintf(
                        "Syntax error at '%s' line %d: %s",
                        $this->file,
                        $token[2],
                        $e->getMessage()
                    ));
                }
            } elseif ($token[0] !== T_WHITESPACE) {
                $prevToken = $token;
            }
        }
        $this->namespaces = array_unique(array_merge($namespaces, array_keys($imports)));
        $this->classes = $classes;
        $this->imports = $imports;
    }

    /**
     * Skips whitespace and comment.
     * Stops when first token that is not whitespace or comment is occured.
     */
    private function skipWhitespaceAndComment(Iterator $tokens)
    {
        while ($tokens->valid()) {
            $token = $tokens->current();
            if (!is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT])) {
                break;
            }
            $tokens->next();
        }
    }

    /**
     * Reads the identifiers at current position.
     * Stops when first token that not belong to identifier (not string or ns_separator).
     *
     * @return string
     */
    private function matchIdentifier(Iterator $tokens)
    {
        $identifier = '';
        while ($tokens->valid()) {
            $token = $tokens->current();
            if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                $identifier .= $token[1];
            } else {
                break;
            }
            $tokens->next();
        }

        return $identifier;
    }

    /**
     * Reads use statment.
     *
     * @return array first element is type string, "classes" or "functions" or "constants"
     *               and the second is an array, key is alias, value is import symbol
     */
    private function parseUseStatement(Iterator $tokens)
    {
        if (!$tokens->valid()) {
            throw new SyntaxErrorException('expected use statement here');
        }
        $token = $tokens->current();
        if (!is_array($token) || !in_array($token[0], [T_FUNCTION, T_CONST, T_STRING])) {
            throw new SyntaxErrorException(
                'expected class name or keyword function or const, got '
                .$this->describeToken($token)
            );
        }
        if ($token[0] === T_FUNCTION) {
            $type = 'functions';
            $tokens->next();
            $this->skipWhitespaceAndComment($tokens);
        } elseif ($token[0] === T_CONST) {
            $type = 'constants';
            $tokens->next();
            $this->skipWhitespaceAndComment($tokens);
        } else {
            $type = 'classes';
        }
        $imports = $this->matchImportList($tokens, ';');

        return [$type, $imports];
    }

    private function matchImportList(Iterator $tokens, $stopToken, $hasSubList = true)
    {
        $imports = [];
        do {
            foreach ($this->matchUseList($tokens, $hasSubList) as $alias => $name) {
                if (isset($imports[$alias])) {
                    throw new SyntaxErrorException(sprintf(
                        "duplicated import alias '%s' for '%s', previous '%s'",
                        $name,
                        $alias,
                        $imports[$alias]
                    ));
                }
                $imports[$alias] = $name;
            }
            $this->skipWhitespaceAndComment($tokens);
            $token = $tokens->current();
            if ($token === ',') {
                $tokens->next();
                $this->skipWhitespaceAndComment($tokens);
            } elseif ($token === $stopToken) {
                $tokens->next();
                break;
            } else {
                throw new SyntaxErrorException(
                    'expected comma or semicolon here, got '.$this->describeToken($token)
                );
            }
        } while (true);

        return $imports;
    }

    private function matchUseList(Iterator $tokens, $hasSubList)
    {
        $imports = [];
        $name = $this->matchIdentifier($tokens);
        if (empty($name)) {
            throw new SyntaxErrorException(
                'expected imported identifier, got '
                .$this->describeToken($tokens->current())
            );
        }
        $token = $tokens->current();
        if ($token === '{') {
            if (!$hasSubList) {
                throw new SyntaxErrorException('unexpected token '.$this->describeToken($token));
            }
            $tokens->next();
            $imports = $this->matchImportList($tokens, '}', false);
            $prefix = $name;
            foreach ($imports as $alias => $name) {
                $imports[$alias] = $prefix.$name;
            }
        } else {
            $this->skipWhitespaceAndComment($tokens);
            $token = $tokens->current();
            if (is_array($token) && $token[0] === T_AS) {
                $tokens->next();
                $this->skipWhitespaceAndComment($tokens);
                $alias = $this->matchIdentifier($tokens);
                if (strpos($alias, '\\') !== false) {
                    throw new SyntaxErrorException("import alias '{$alias}' cannot contain namespace seperator");
                }
            } else {
                $alias = $this->getSimpleName($name);
            }
            $imports[$alias] = $name;
        }

        return $imports;
    }

    private function matchParentheses(Iterator $tokens)
    {
        $stack = [];
        while ($tokens->valid()) {
            $token = $tokens->current();
            $tokens->next();
            if (is_array($token) && $token[0] == T_CURLY_OPEN) {
                $stack[] = '{';
            } elseif (is_string($token)) {
                if ($token === '{') {
                    $stack[] = '{';
                } elseif ($token === '}') {
                    array_pop($stack);
                    if (empty($stack)) {
                        break;
                    }
                }
            }
        }
        if (!empty($stack)) {
            throw new SyntaxErrorException('parentheses not match');
        }
    }

    private function getSimpleName($name)
    {
        $parts = explode('\\', $name);

        return end($parts);
    }

    private function describeToken($token)
    {
        if (is_array($token)) {
            return '['.implode(', ', [$this->getTokenName($token), json_encode($token[1]), $token[2]]).']';
        } else {
            return json_encode($token);
        }
    }

    private function getTokenName($token)
    {
        if (self::$TOKEN_TYPES === null) {
            $contants = get_defined_constants();
            $tokenTypes = [];
            foreach ($contants as $key => $val) {
                if (strpos($key, 'T_') === 0) {
                    $tokenTypes[$val] = $key;
                }
            }
            self::$TOKEN_TYPES = $tokenTypes;
        }

        return self::$TOKEN_TYPES[$token[0]];
    }
}
