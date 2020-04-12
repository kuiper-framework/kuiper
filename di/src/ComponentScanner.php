<?php

declare(strict_types=1);

namespace kuiper\di;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentScan;
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

    public function __construct(ContainerBuilderInterface $containerBuilder, AnnotationReaderInterface $annotationReader, ReflectionNamespaceFactoryInterface $reflectionNamespaceFactory)
    {
        $this->containerBuilder = $containerBuilder;
        $this->annotationReader = $annotationReader;
        $this->reflectionNamespaceFactory = $reflectionNamespaceFactory;
    }

    public function scan(array $namespaces): void
    {
        while (!empty($namespaces)) {
            $namespace = array_pop($namespaces);
            if (isset($this->scannedNamespaces[$namespace])) {
                continue;
            }
            foreach ($this->reflectionNamespaceFactory->create($namespace)->getClasses() as $className) {
                $reflectionClass = new \ReflectionClass($className);
                foreach ($this->annotationReader->getClassAnnotations($reflectionClass) as $annotation) {
                    if ($annotation instanceof ComponentInterface) {
                        $annotation->setTarget($reflectionClass);
                        if ($annotation instanceof ContainerBuilderAwareInterface) {
                            $annotation->setContainerBuilder($this->containerBuilder);
                        }
                        $annotation->handle();
                    } elseif ($annotation instanceof ComponentScan) {
                        foreach ($annotation->basePackages ?: [$reflectionClass->getNamespaceName()] as $ns) {
                            $namespaces[] = $ns;
                        }
                    }
                }
            }
            $scannedNamespaces[$namespace] = true;
        }
    }
}
