<?php

namespace kuiper\reflection;

/**
 * Full Qualified Class Name Resolver.
 */
class FqcnResolver
{
    const NAMESPACE_SEPARATOR = '\\';

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
     * @throws \InvalidArgumentException
     * @throws exception\FileNotFoundException
     * @throws exception\SyntaxErrorException
     */
    public function resolve(string $name, string $namespace): string
    {
        if ($this->isFqcn($name)) {
            return ltrim($name, self::NAMESPACE_SEPARATOR);
        }
        if (!preg_match(ReflectionType::CLASS_NAME_REGEX, $name)) {
            throw new \InvalidArgumentException("Invalid class name '{$name}'");
        }
        $namespaces = $this->reflectionFile->getNamespaces();
        if (!in_array($namespace, $namespaces)) {
            throw new \InvalidArgumentException(sprintf("namespace '%s' not defined in '%s'", $namespace, $this->reflectionFile->getFile()));
        }
        $imports = $this->reflectionFile->getImportedClasses($namespace);
        $parts = explode(self::NAMESPACE_SEPARATOR, $name);
        $alias = array_shift($parts);
        if (isset($imports[$alias])) {
            $className = $imports[$alias].(empty($parts) ? '' : self::NAMESPACE_SEPARATOR.implode(self::NAMESPACE_SEPARATOR, $parts));
        } else {
            $className = $namespace.self::NAMESPACE_SEPARATOR.$name;
        }

        return ltrim($className, self::NAMESPACE_SEPARATOR);
    }

    private function isFqcn($name)
    {
        return self::NAMESPACE_SEPARATOR === $name[0];
    }
}
