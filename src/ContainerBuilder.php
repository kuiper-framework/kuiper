<?php
namespace kuiper\di;

use Interop\Container\ContainerInterface as BaseContainer;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use kuiper\annotations\ReaderInterface;
use kuiper\annotations\AnnotationReader;
use kuiper\annotations\DocReaderInterface;
use kuiper\annotations\DocReader;
use kuiper\di\source\ArraySource;
use kuiper\di\source\ObjectSource;
use kuiper\di\source\CachedSource;
use kuiper\di\source\SourceChain;
use kuiper\di\source\SourceInterface;
use kuiper\di\resolver\DispatchResolver;
use kuiper\di\definition\decorator\DefinitionDecorator;
use kuiper\di\definition\decorator\AutowireDecorator;
use kuiper\di\definition\AliasDefinition;
use kuiper\di\definition\ValueDefinition;
use ReflectionClass;
use RuntimeException;

class ContainerBuilder
{
    /**
     * Name of the container class, used to create the container.
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
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var array<SourceInterface>
     */
    private $sources = [];

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * Constructs the builder
     * 
     * @param string $containerClass Name of the container class, used to create the container.
     */
    public function __construct($containerClass = Container::class)
    {
        $this->containerClass = $containerClass;
        $this->definitions = new ArraySource();
        $this->proxyFactory = new ProxyFactory();
    }

    /**
     * Build empty container with default configuration
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
        $sources = array_merge([$this->definitions], $this->sources);
        $sources[] = new ObjectSource();
        if ($this->useAnnotations) {
            $decorator = new AutowireDecorator($this->getAnnotationReader(), $this->getDocReader());
        } elseif ($this->useAutowiring) {
            $decorator = new DefinitionDecorator();
        }
        $source = new SourceChain($sources, $this->definitions, $decorator);
        if ($this->cache !== null) {
            $source = new CachedSource($source, $this->cache);
        }
        $containerClass = $this->containerClass;
        $container = new $containerClass($source, $this->proxyFactory);
        $this->definitions->addDefinitions([
            ContainerInterface::class => $container,
            BaseContainer::class => $container,
            Container::class => $container
        ]);
        if ($this->useAnnotations) {
            $this->definitions->addDefinitions([
                ReaderInterface::class => $this->getAnnotationReader(),
                DocReaderInterface::class => $this->getDocReader()
            ]);
        }
        return $container;
    }

    /**
     * @param array $definitions
     * @param boolean $mergeDeeply
     * @return self
     */
    public function addDefinitions(array $definitions, $mergeDeeply = false)
    {
        $this->definitions->addDefinitions($definitions, $mergeDeeply);
        return $this;
    }

    /**
     * Enable or disable the use of autowiring to guess injections.
     *
     * Enabled by default.
     *
     * @param bool $bool
     * @return self
     */
    public function useAutowiring($bool)
    {
        $this->useAutowiring = $bool;
        return $this;
    }

    /**
     * Enable or disable the use of annotations to guess injections.
     *
     * Disabled by default.
     *
     * @param bool $bool
     * @return self
     */
    public function useAnnotations($bool)
    {
        $this->useAnnotations = $bool;
        return $this;
    }

    /**
     * @param SourceInterface $source
     * @return self
     */
    public function addSource(SourceInterface $source)
    {
        $this->sources[] = $source;
        return $this;
    }

    /**
     * @param ReaderInterface $reader
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
     * @return self
     */
    public function setDocReader(DocReaderInterface $docReader)
    {
        $this->docReader = $docReader;
        return $this;
    }

    /**
     * @param CacheItemPoolInterface $cache
     * @return self
     */
    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return ProxyFactory
     */
    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }
}
