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

namespace kuiper\di;

use kuiper\di\attribute\ComponentScan;
use kuiper\reflection\ReflectionNamespaceFactoryInterface;
use ReflectionClass;

class ComponentScanner implements ComponentScannerInterface
{
    /**
     * @var array
     */
    private array $scannedNamespaces;

    /**
     * @var string[]
     */
    private array $excludeNamespaces = [];

    public function __construct(
        private readonly ContainerBuilderInterface $containerBuilder,
        private readonly ReflectionNamespaceFactoryInterface $reflectionNamespaceFactory)
    {
    }

    public function exclude(string $namespace): void
    {
        $this->excludeNamespaces[] = $namespace;
    }

    public function scan(array $namespaces): void
    {
        $namespaces = array_reverse($namespaces);
        while (!empty($namespaces)) {
            $namespace = array_pop($namespaces);
            if (isset($this->scannedNamespaces[$namespace])) {
                continue;
            }
            foreach ($this->reflectionNamespaceFactory->create($namespace)->getClasses() as $className) {
                if ($this->isExcluded($className)) {
                    continue;
                }
                $reflectionClass = new ReflectionClass($className);
                foreach ($reflectionClass->getAttributes() as $reflectionAttribute) {
                    if (is_a($reflectionAttribute->getName(), Component::class, true)) {
                        $attribute = $reflectionAttribute->newInstance();
                        $attribute->setTarget($reflectionClass);
                        if ($attribute instanceof ContainerBuilderAwareInterface) {
                            $attribute->setContainerBuilder($this->containerBuilder);
                        }
                        $attribute->handle();
                    } elseif (ComponentScan::class === $reflectionAttribute->getName()) {
                        $attribute = $reflectionAttribute->newInstance();
                        foreach ($attribute->getBasePackages() ?? [$reflectionClass->getNamespaceName()] as $ns) {
                            $namespaces[] = $ns;
                        }
                    }
                }
            }
            $this->scannedNamespaces[$namespace] = true;
        }
    }

    private function isExcluded(string $className): bool
    {
        foreach ($this->excludeNamespaces as $excludeNamespace) {
            if (str_starts_with($className, $excludeNamespace)) {
                return true;
            }
        }

        return false;
    }
}
