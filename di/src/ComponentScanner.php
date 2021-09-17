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

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentScan;
use kuiper\helper\Text;
use kuiper\reflection\ReflectionNamespaceFactoryInterface;

class ComponentScanner implements ComponentScannerInterface
{
    /**
     * @var array
     */
    private $scannedNamespaces;

    /**
     * @var ContainerBuilderInterface
     */
    private $containerBuilder;

    /**
     * @var ReflectionNamespaceFactoryInterface
     */
    private $reflectionNamespaceFactory;
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    /**
     * @var string[]
     */
    private $excludeNamespaces = [];

    public function __construct(ContainerBuilderInterface $containerBuilder, AnnotationReaderInterface $annotationReader, ReflectionNamespaceFactoryInterface $reflectionNamespaceFactory)
    {
        $this->containerBuilder = $containerBuilder;
        $this->annotationReader = $annotationReader;
        $this->reflectionNamespaceFactory = $reflectionNamespaceFactory;
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
                $reflectionClass = new \ReflectionClass($className);
                foreach ($this->annotationReader->getClassAnnotations($reflectionClass) as $annotation) {
                    if ($annotation instanceof ComponentInterface) {
                        $annotation->setTarget($reflectionClass);
                        if ($annotation instanceof ContainerBuilderAwareInterface) {
                            $annotation->setContainerBuilder($this->containerBuilder);
                        }
                        $annotation->handle();
                    } elseif ($annotation instanceof ComponentScan) {
                        foreach ($annotation->basePackages ?? [$reflectionClass->getNamespaceName()] as $ns) {
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
            if (Text::startsWith($className, $excludeNamespace)) {
                return true;
            }
        }

        return false;
    }
}
