<?php

namespace kuiper\reflection;

use InvalidArgumentException;

class FqcnResolver
{
    /**
     * @var ReflectionFileInterface
     */
    private $reflectionFile;

    public function __construct(ReflectionFileInterface $file)
    {
        $this->reflectionFile = $file;
    }

    /**
     * Resolves class name to Full Qualified Class Name.
     *
     * @param string $name
     * @param string $namespace
     *
     * @return string
     *
     * @throws InvalidArgumentException
     * @throws exception\SyntaxErrorException
     */
    public function resolve($name, $namespace)
    {
        if ($this->isFqcn($name)) {
            return ltrim($name, '\\');
        }
        if (!preg_match(ReflectionType::CLASS_NAME_REGEX, $name)) {
            throw new InvalidArgumentException("Invalid class name '{$name}'");
        }
        $namespaces = $this->reflectionFile->getNamespaces();
        if (!in_array($namespace, $namespaces)) {
            throw new InvalidArgumentException(sprintf("namespace '%s' not defined in '%s'", $namespace, $this->reflectionFile->getFile()));
        }
        $imports = $this->reflectionFile->getImportedClasses($namespace);
        $parts = explode('\\', $name);
        $alias = array_shift($parts);
        if (isset($imports[$alias])) {
            $className = $imports[$alias].(empty($parts) ? '' : implode('\\', $parts));
        } else {
            $className = $namespace.'\\'.$name;
        }

        return ltrim($className, '\\');
    }

    private function isFqcn($name)
    {
        return $name[0] === '\\';
    }
}
