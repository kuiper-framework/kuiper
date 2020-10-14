<?php

declare(strict_types=1);

namespace kuiper\reflection;

/**
 * Full Qualified Class Name Resolver.
 */
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
     * @throws \InvalidArgumentException       if $name is not valid class name, or namespace not in the file
     * @throws exception\FileNotFoundException
     * @throws exception\SyntaxErrorException
     */
    public function resolve(string $name, string $namespace): string
    {
        if ($this->isFqcn($name)) {
            return ltrim($name, ReflectionNamespaceInterface::NAMESPACE_SEPARATOR);
        }
        if (!preg_match(ReflectionType::CLASS_NAME_REGEX, $name)) {
            throw new \InvalidArgumentException("Invalid class name '{$name}'");
        }
        $namespaces = $this->reflectionFile->getNamespaces();
        if (!in_array($namespace, $namespaces, true)) {
            throw new \InvalidArgumentException(sprintf("namespace '%s' not defined in '%s'", $namespace, $this->reflectionFile->getFile()));
        }
        $imports = $this->reflectionFile->getImportedClasses($namespace);
        $parts = explode(ReflectionNamespaceInterface::NAMESPACE_SEPARATOR, $name);
        $alias = array_shift($parts);
        if (isset($imports[$alias])) {
            $className = $imports[$alias].(empty($parts) ? '' : ReflectionNamespaceInterface::NAMESPACE_SEPARATOR
                    .implode(ReflectionNamespaceInterface::NAMESPACE_SEPARATOR, $parts));
        } else {
            $className = $namespace.ReflectionNamespaceInterface::NAMESPACE_SEPARATOR.$name;
        }

        return ltrim($className, ReflectionNamespaceInterface::NAMESPACE_SEPARATOR);
    }

    private function isFqcn(string $name): bool
    {
        return ReflectionNamespaceInterface::NAMESPACE_SEPARATOR === $name[0];
    }
}
