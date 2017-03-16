<?php

namespace kuiper\di;

use kuiper\annotations\DocReaderInterface;
use kuiper\annotations\ReaderInterface;
use kuiper\di\source\SourceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CompositeContainerBuilder implements ContainerBuilderInterface
{
    const ROOT_NAMESPACE = '0';

    /**
     * Name of the container class, used to create the container.
     *
     * @var string
     */
    private $containerClass;

    /**
     * @var ContainerBuilder[]
     */
    private $builders = [];

    /**
     * @var array
     */
    private $calls = [];

    public function __construct($containerClass = Container::class)
    {
        $this->containerClass = $containerClass;
        $this->builders[self::ROOT_NAMESPACE] = new ContainerBuilder($containerClass);
    }

    public function build()
    {
        foreach ($this->calls as $method => $val) {
            foreach ($this->builders as $builder) {
                $builder->$method($val);
            }
        }
        $rootContainer = $this->root()->build();
        if (count($this->builders) == 1) {
            return $rootContainer;
        }
        $containers = [];
        foreach ($this->builders as $namespace => $builder) {
            if ($namespace == self::ROOT_NAMESPACE) {
                continue;
            }
            $builder->setParentContainer($rootContainer);
            $containers[$namespace] = $builder->build();
        }

        return new CompositeContainer($containers);
    }

    public function root()
    {
        return $this->builders[self::ROOT_NAMESPACE];
    }

    public function namespace($namespace)
    {
        $namespace = (string) $namespace;
        if ($namespace === self::ROOT_NAMESPACE) {
            throw new \InvalidArgumentException('Cannot set to namespace '.self::ROOT_NAMESPACE);
        }
        if (!isset($this->builders[$namespace])) {
            $this->builders[$namespace] = new ContainerBuilder($this->containerClass);
        }

        return $this->builders[$namespace];
    }

    public function useAutowiring($autowiring = true)
    {
        $this->calls['useAutowiring'] = $autowiring;

        return $this;
    }

    public function useAnnotations($annotations = true)
    {
        $this->calls['useAnnotations'] = $annotations;

        return $this;
    }

    public function setAnnotationReader(ReaderInterface $reader)
    {
        $this->calls['setAnnotationReader'] = $reader;

        return $this;
    }

    public function setDocReader(DocReaderInterface $docReader)
    {
        $this->calls['setDocReader'] = $docReader;

        return $this;
    }

    public function setProxyFactory(ProxyFactory $proxyFactory)
    {
        $this->calls['setProxyFactory'] = $proxyFactory;

        return $this;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->calls['setEventDispatcher'] = $eventDispatcher;

        return $this;
    }

    public function addSource(SourceInterface $source)
    {
        $this->root()->addSource($source);

        return $this;
    }
}
