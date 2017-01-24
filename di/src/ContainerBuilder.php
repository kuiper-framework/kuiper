<?php

namespace kuiper\di;

use Interop\Container\ContainerInterface as InteropContainer;
use kuiper\annotations\AnnotationReader;
use kuiper\annotations\DocReader;
use kuiper\annotations\DocReaderInterface;
use kuiper\annotations\ReaderInterface;
use kuiper\di\definition\decorator\AutowireDecorator;
use kuiper\di\definition\decorator\DefinitionDecorator;
use kuiper\di\definition\decorator\DummyDecorator;
use kuiper\di\source\ArraySource;
use kuiper\di\source\MutableSourceInterface;
use kuiper\di\source\ObjectSource;
use kuiper\di\source\SourceChain;
use kuiper\di\source\SourceInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContainerBuilder
{
    /**
     * Name of the container class, used to create the container.
     *
     * @var string
     */
    private $containerClass;

    /**
     * @var bool
     */
    private $useAutowiring = true;

    /**
     * @var bool
     */
    private $useAnnotations = false;

    /**
     * @var ArraySource
     */
    private $definitions;

    /**
     * @var ReaderInterface
     */
    private $annotationReader;

    /**
     * @var DocReaderInterface
     */
    private $docReader;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array<SourceInterface>
     */
    private $sources = [];

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * Constructs the builder.
     *
     * @param string $containerClass name of the container class, used to create the container
     */
    public function __construct($containerClass = Container::class)
    {
        $this->containerClass = $containerClass;
    }

    /**
     * Build empty container with default configuration.
     *
     * @return Container
     */
    public static function buildDevContainer()
    {
        $builder = new self();

        return $builder->build();
    }

    /**
     * Build and return a container.
     *
     * @return Container
     */
    public function build()
    {
        $sources = $this->sources;
        array_unshift($sources, $definitions = $this->getDefinitions());
        $sources[] = new ObjectSource();
        if ($this->useAnnotations) {
            $definitions->addDefinitions([
                ReaderInterface::class => $this->getAnnotationReader(),
                DocReaderInterface::class => $this->getDocReader(),
            ]);
            $decorator = new AutowireDecorator($this->getAnnotationReader(), $this->getDocReader());
        } elseif ($this->useAutowiring) {
            $decorator = new DefinitionDecorator();
        } else {
            $decorator = new DummyDecorator();
        }
        $source = new SourceChain($sources, $definitions, $decorator);

        $containerClass = $this->containerClass;
        $container = new $containerClass($source, $this->getProxyFactory(), $this->getEventDispatcher());
        $definitions->addDefinitions([
            ContainerInterface::class => $container,
            InteropContainer::class => $container,
            Container::class => $container,
        ]);

        return $container;
    }

    /**
     * @param array $definitions
     * @param bool  $mergeDeeply
     *
     * @return self
     */
    public function addDefinitions(array $definitions, $mergeDeeply = false)
    {
        $this->getDefinitions()->addDefinitions($definitions, $mergeDeeply);

        return $this;
    }

    /**
     * Enable or disable the use of autowiring to guess injections.
     *
     * Enabled by default.
     *
     * @param bool $autowiring
     *
     * @return self
     */
    public function useAutowiring($autowiring = true)
    {
        $this->useAutowiring = $autowiring;

        return $this;
    }

    /**
     * Enable or disable the use of annotations to guess injections.
     *
     * Disabled by default.
     *
     * @param bool $annotations
     *
     * @return self
     */
    public function useAnnotations($annotations = true)
    {
        $this->useAnnotations = $annotations;

        return $this;
    }

    /**
     * @param SourceInterface $source
     *
     * @return self
     */
    public function addSource(SourceInterface $source)
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * @param ReaderInterface $reader
     *
     * @return self
     */
    public function setAnnotationReader(ReaderInterface $reader)
    {
        $this->annotationReader = $reader;

        return $this;
    }

    /**
     * @return ReaderInterface annotation reader
     */
    public function getAnnotationReader()
    {
        if ($this->annotationReader === null) {
            $this->setAnnotationReader(new AnnotationReader());
        }

        return $this->annotationReader;
    }

    /**
     * @return DocReaderInterface
     */
    public function getDocReader()
    {
        if ($this->docReader === null) {
            $this->setDocReader(new DocReader());
        }

        return $this->docReader;
    }

    /**
     * @param DocReaderInterface $docReader
     *
     * @return self
     */
    public function setDocReader(DocReaderInterface $docReader)
    {
        $this->docReader = $docReader;

        return $this;
    }

    /**
     * @return ProxyFactory
     */
    public function getProxyFactory()
    {
        if ($this->proxyFactory === null) {
            $this->proxyFactory = new ProxyFactory();
        }

        return $this->proxyFactory;
    }

    public function setProxyFactory(ProxyFactory $proxyFactory)
    {
        $this->proxyFactory = $proxyFactory;

        return $this;
    }

    public function getDefinitions()
    {
        if ($this->definitions === null) {
            $this->definitions = new ArraySource();
        }

        return $this->definitions;
    }

    public function setDefinitions(MutableSourceInterface $definitions)
    {
        $this->definitions = $definitions;

        return $this;
    }

    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }
}
