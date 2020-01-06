<?php

namespace kuiper\annotations;

use kuiper\annotations\exception\AnnotationException;
use kuiper\reflection\exception\ReflectionException;
use kuiper\reflection\FqcnResolver;
use kuiper\reflection\ReflectionFileFactoryInterface;

class AnnotationParser
{
    /**
     * An array of all valid tokens for a class name.
     *
     * @var array
     */
    private static $CLASS_IDENTIFIERS = [
        DocLexer::T_IDENTIFIER,
        DocLexer::T_TRUE,
        DocLexer::T_FALSE,
        DocLexer::T_NULL,
    ];

    /**
     * @var DocLexer
     */
    private $lexer;

    /**
     * @var ReflectionFileFactoryInterface
     */
    private $reflectionFileFactory;

    /**
     * @var array
     */
    private $context;

    public function __construct(DocLexer $lexer, ReflectionFileFactoryInterface $reflectionFileFactory)
    {
        $this->lexer = $lexer;
        $this->reflectionFileFactory = $reflectionFileFactory;
    }

    /**
     * @param string $doc
     * @param string $namespace
     * @param string $file
     * @param int $line
     * @return array
     */
    public function parse(string $doc, string $namespace, string $file, int $line)
    {
        $pos = $this->findInitialTokenPosition($doc);
        if ($pos === null) {
            return [];
        }

        $this->context = [
            'doc' => $doc,
            'namespace' => $namespace,
            'file' => $file,
            'line' => $line
        ];
        $this->lexer->setInput(trim(substr($doc, $pos), '* /'));
        $this->lexer->moveNext();

        return $this->matchAnnotations();
    }

    /**
     * Finds the first valid annotation.
     *
     * @param string $input The doc string to parse
     *
     * @return int|null
     */
    private function findInitialTokenPosition($input)
    {
        $pos = 0;

        // search for first valid annotation
        while (($pos = strpos($input, '@', $pos)) !== false) {
            // if the @ is preceded by a space or * it is valid
            if ($pos === 0 || $input[$pos - 1] === ' ' || $input[$pos - 1] === '*') {
                return $pos;
            }

            ++$pos;
        }

        return null;
    }

    /**
     * Annotations ::= Annotation {[ "*" ]* [Annotation]}*.
     *
     * @return array
     */
    private function matchAnnotations()
    {
        $annotations = [];

        while (null !== $this->lexer->lookahead) {
            if (DocLexer::T_AT !== $this->lexer->lookahead['type']) {
                $this->lexer->moveNext();
                continue;
            }

            // make sure the @ is preceded by non-catchable pattern
            if (null !== $this->lexer->token && $this->lexer->lookahead['position'] === $this->lexer->token['position'] + strlen($this->lexer->token['value'])) {
                $this->lexer->moveNext();
                continue;
            }

            // make sure the @ is followed by either a namespace separator, or
            // an identifier token
            if ((null === $peek = $this->lexer->glimpse())
                || (DocLexer::T_NAMESPACE_SEPARATOR !== $peek['type'] && !in_array($peek['type'], self::$CLASS_IDENTIFIERS, true))
                || $peek['position'] !== $this->lexer->lookahead['position'] + 1) {
                $this->lexer->moveNext();
                continue;
            }

            if (false !== $annotation = $this->matchAnnotation()) {
                $annotations[] = $annotation;
            }
        }

        return $annotations;
    }

    /**
     * Annotation     ::= "@" AnnotationName MethodCall
     * AnnotationName ::= QualifiedName | SimpleName
     * QualifiedName  ::= NameSpacePart "\" {NameSpacePart "\"}* SimpleName
     * NameSpacePart  ::= identifier | null | false | true
     * SimpleName     ::= identifier | null | false | true.
     *
     * @return mixed false if it is not a valid annotation
     *
     * @throws AnnotationException
     */
    private function matchAnnotation()
    {
        $this->match(DocLexer::T_AT);

        return new Annotation($this->matchIdentifier(), $this->matchMethodCall());
    }

    /**
     * Identifier ::= string.
     *
     * @return string
     */
    private function matchIdentifier()
    {
        // check if we have an annotation
        if (!$this->lexer->isNextTokenAny(self::$CLASS_IDENTIFIERS)) {
            $this->syntaxError('namespace separator or identifier');
        }

        $this->lexer->moveNext();

        $className = $this->lexer->token['value'];

        while ($this->lexer->lookahead['position'] === ($this->lexer->token['position'] + strlen($this->lexer->token['value']))
                && $this->lexer->isNextToken(DocLexer::T_NAMESPACE_SEPARATOR)) {
            $this->match(DocLexer::T_NAMESPACE_SEPARATOR);
            $this->matchAny(self::$CLASS_IDENTIFIERS);

            $className .= '\\'.$this->lexer->token['value'];
        }

        return $className;
    }

    /**
     * MethodCall ::= ["(" [Values] ")"].
     *
     * @return array
     */
    private function matchMethodCall()
    {
        $values = [];

        if (!$this->lexer->isNextToken(DocLexer::T_OPEN_PARENTHESIS)) {
            return $values;
        }

        $this->match(DocLexer::T_OPEN_PARENTHESIS);

        if (!$this->lexer->isNextToken(DocLexer::T_CLOSE_PARENTHESIS)) {
            $values = $this->matchValues();
        }

        $this->match(DocLexer::T_CLOSE_PARENTHESIS);

        return $values;
    }

    /**
     * Values ::= Array | Value {"," Value}* [","].
     *
     * @return array
     */
    private function matchValues()
    {
        $values = [$this->matchValue()];

        while ($this->lexer->isNextToken(DocLexer::T_COMMA)) {
            $this->match(DocLexer::T_COMMA);

            if ($this->lexer->isNextToken(DocLexer::T_CLOSE_PARENTHESIS)) {
                break;
            }

            $token = $this->lexer->lookahead;
            $value = $this->matchValue();

            if (!is_object($value) && !is_array($value)) {
                $this->syntaxError('Value', $token);
            }

            $values[] = $value;
        }

        foreach ($values as $k => $value) {
            if (is_object($value) && $value instanceof \stdClass) {
                $values[$value->name] = $value->value;
            } elseif (!isset($values['value'])) {
                $values['value'] = $value;
            } else {
                if (!is_array($values['value'])) {
                    $values['value'] = [$values['value']];
                }

                $values['value'][] = $value;
            }

            unset($values[$k]);
        }

        return $values;
    }

    /**
     * Value ::= PlainValue | FieldAssignment.
     *
     * @return mixed
     */
    private function matchValue()
    {
        $peek = $this->lexer->glimpse();

        if (DocLexer::T_EQUALS === $peek['type']) {
            return $this->matchFieldAssignment();
        }

        return $this->matchPlainValue();
    }

    /**
     * PlainValue ::= integer | string | float | boolean | Array | Annotation.
     *
     * @return mixed
     */
    private function matchPlainValue()
    {
        if ($this->lexer->isNextToken(DocLexer::T_OPEN_CURLY_BRACES)) {
            return $this->matchArrays();
        }

        if ($this->lexer->isNextToken(DocLexer::T_AT)) {
            return $this->matchAnnotation();
        }

        if ($this->lexer->isNextToken(DocLexer::T_IDENTIFIER)) {
            return $this->matchConstant();
        }

        switch ($this->lexer->lookahead['type']) {
            case DocLexer::T_STRING:
                $this->match(DocLexer::T_STRING);

                return $this->lexer->token['value'];

            case DocLexer::T_INTEGER:
                $this->match(DocLexer::T_INTEGER);

                return (int) $this->lexer->token['value'];

            case DocLexer::T_FLOAT:
                $this->match(DocLexer::T_FLOAT);

                return (float) $this->lexer->token['value'];

            case DocLexer::T_TRUE:
                $this->match(DocLexer::T_TRUE);

                return true;

            case DocLexer::T_FALSE:
                $this->match(DocLexer::T_FALSE);

                return false;

            case DocLexer::T_NULL:
                $this->match(DocLexer::T_NULL);

                return null;

            default:
                $this->syntaxError('PlainValue');
                return null;
        }
    }

    /**
     * FieldAssignment ::= FieldName "=" PlainValue
     * FieldName ::= identifier.
     *
     * @return object
     */
    private function matchFieldAssignment()
    {
        $this->match(DocLexer::T_IDENTIFIER);
        $fieldName = $this->lexer->token['value'];

        $this->match(DocLexer::T_EQUALS);

        $item = new \stdClass();
        $item->name = $fieldName;
        $item->value = $this->matchPlainValue();

        return $item;
    }

    /**
     * Array ::= "{" ArrayEntry {"," ArrayEntry}* [","] "}".
     *
     * @return array
     */
    private function matchArrays()
    {
        $array = $values = [];

        $this->match(DocLexer::T_OPEN_CURLY_BRACES);

        // If the array is empty, stop parsing and return.
        if ($this->lexer->isNextToken(DocLexer::T_CLOSE_CURLY_BRACES)) {
            $this->match(DocLexer::T_CLOSE_CURLY_BRACES);

            return $array;
        }

        $values[] = $this->matchArrayEntry();

        while ($this->lexer->isNextToken(DocLexer::T_COMMA)) {
            $this->match(DocLexer::T_COMMA);

            // optional trailing comma
            if ($this->lexer->isNextToken(DocLexer::T_CLOSE_CURLY_BRACES)) {
                break;
            }

            $values[] = $this->matchArrayEntry();
        }

        $this->match(DocLexer::T_CLOSE_CURLY_BRACES);

        foreach ($values as $value) {
            list($key, $val) = $value;

            if ($key !== null) {
                $array[$key] = $val;
            } else {
                $array[] = $val;
            }
        }

        return $array;
    }

    /**
     * ArrayEntry ::= Value | KeyValuePair
     * KeyValuePair ::= Key ("=" | ":") PlainValue | Constant
     * Key ::= string | integer | Constant.
     *
     * @return array
     */
    private function matchArrayEntry()
    {
        $peek = $this->lexer->glimpse();

        if (DocLexer::T_EQUALS === $peek['type']
                || DocLexer::T_COLON === $peek['type']) {
            if ($this->lexer->isNextToken(DocLexer::T_IDENTIFIER)) {
                $key = $this->matchConstant();
            } else {
                $this->matchAny([DocLexer::T_INTEGER, DocLexer::T_STRING]);
                $key = $this->lexer->token['value'];
            }

            $this->matchAny([DocLexer::T_EQUALS, DocLexer::T_COLON]);

            return [$key, $this->matchPlainValue()];
        }

        return [null, $this->matchValue()];
    }

    /**
     * Constant ::= integer | string | float | boolean.
     *
     * @return mixed
     *
     * @throws AnnotationException
     */
    private function matchConstant()
    {
        $identifier = $this->matchIdentifier();

        if (!defined($identifier) && false !== strpos($identifier, '::') && '\\' !== $identifier[0]) {
              list($className, $const) = explode('::', $identifier);
              $identifier = $this->resolveClassName($className) . '::' . $const;
        }

        // checks if identifier ends with ::class, \strlen('::class') === 7
        $classPos = stripos($identifier, '::class');
        if ($classPos === strlen($identifier) - 7) {
            return substr($identifier, 0, $classPos);
        }

        if (!defined($identifier)) {
            throw new AnnotationException("[Semantic Error] Couldn't find constant $identifier");
        }

        return constant($identifier);
    }

    /**
     * Generates a new syntax error.
     *
     * @param string     $expected expected string
     * @param array|null $token    optional token
     *
     * @throws AnnotationException
     */
    private function syntaxError($expected, $token = null)
    {
        if ($token === null) {
            $token = $this->lexer->lookahead;
        }

        $message = sprintf('Expected %s, got ', $expected);
        $message .= ($this->lexer->lookahead === null)
            ? 'end of string'
            : sprintf("'%s' at position %s", $token['value'], $token['position']);

        $message .= sprintf(' in %s on line %d.', $this->context['file'], $this->context['line']);

        throw new AnnotationException("[Syntax Error] $message");
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     * If they match, updates the lookahead token; otherwise raises a syntax error.
     *
     * @param int $token type of token
     *
     * @return bool true if tokens match; false otherwise
     */
    private function match($token)
    {
        if (!$this->lexer->isNextToken($token)) {
            $this->syntaxError($this->lexer->getLiteral($token));
        }

        return $this->lexer->moveNext();
    }

    /**
     * Attempts to match the current lookahead token with any of the given tokens.
     *
     * If any of them matches, this method updates the lookahead token; otherwise
     * a syntax error is raised.
     *
     * @param array $tokens
     *
     * @return bool
     */
    private function matchAny(array $tokens)
    {
        if (!$this->lexer->isNextTokenAny($tokens)) {
            $this->syntaxError(implode(' or ', array_map([$this->lexer, 'getLiteral'], $tokens)));
        }

        return $this->lexer->moveNext();
    }

    /**
     * @param string $className
     * @return string
     */
    private function resolveClassName(string $className): string
    {
        $file = $this->reflectionFileFactory->create($this->context['file']);
        $resolver = new FqcnResolver($file);
        try {
            $fullClassName = $resolver->resolve($className, $this->context['namespace']);
            if (class_exists($fullClassName) || interface_exists($fullClassName)) {
                return $fullClassName;
            }
        } catch (ReflectionException $e) {
            trigger_error("Cannot resolve class '{$className}' in " . $this->context['file']);
        }
        return $className;
    }
}
