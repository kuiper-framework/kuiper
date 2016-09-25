<?php
namespace kuiper\di;

use Interop\Container\ContainerInterface as BaseContainer;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use kuiper\annotations\ReaderInterface;
use kuiper\annotations\AnnotationReader;
use kuiper\reflection\ClassScanner;
use kuiper\di\source\ArraySource;
use kuiper\di\source\ObjectSource;
use kuiper\di\source\CachedSource;
use kuiper\di\source\SourceChain;
use kuiper\di\source\SourceInterface;
use kuiper\di\resolver\DispatchResolver;
use kuiper\di\definition\DefinitionDecorator;
use kuiper\di\definition\AliasDefinition;
use kuiper\di\definition\ValueDefinition;
use kuiper\di\annotation\Component;
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
     * @var array
     */
    private $componentNamespaces = [];

    /**
     * @var ArraySource
     */
    private $definitions;

    /**
     * @var ReaderInterface
     */
    private $annotationReader;

    /**
     * @var ClassScanner
     */
    private $classScanner;

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
     * @param string $containerClass Name of the container class, used to create the container.
     */
    public function __construct($containerClass = Container::class)
    {
        $this->containerClass = $containerClass;
        $this->definitions = new ArraySource;
        $this->proxyFactory = new ProxyFactory;
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
            $decorator = new DefinitionDecorator($this->getAnnotationReader());
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
        if (!empty($this->componentNamespaces)) {
            $this->scanComponents($container);
        }
        if ($this->useAnnotations && $container->has(LoggerInterface::class)) {
            $decorator->setLogger($container->get(LoggerInterface::class));
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
     * @param string $namespace
     * @return self
     */
    public function componentScan($namespace)
    {
        if (!$this->useAnnotations) {
            throw new RuntimeException("componentScan only available when annotations enabled");
        }
        $this->componentNamespaces[] = trim($namespace, "\\");
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
     * @return ClassScanner
     */
    public function getClassScanner()
    {
        if ($this->classScanner === null) {
            $this->setClassScanner(new ClassScanner());
        }
        return $this->classScanner;
    }

    /**
     * @param ClassScanner $classScanner
     * @return self
     */
    public function setClassScanner(ClassScanner $classScanner)
    {
        $this->classScanner = $classScanner;
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

    protected function scanComponents($container)
    {
        if ($container->has(LoggerInterface::class)) {
            $logger = $container->get(LoggerInterface::class);
        }
        $components = [];
        $namespaces = array_unique($this->componentNamespaces);
        
        if ($this->cache) {
            $item = $this->cache->getItem('kuiper:di-components:'.implode(';', $namespaces));
            if (!$item) {
                $this->cache->save($item->set($this->scanComponentsIn($namespaces)));
            }
            $components = $item->get();
        } else {
            $components = $this->scanComponentsIn($namespaces);
        }
        $definitions = [];
        foreach ($components as $i => $scope) {
            $name = isset($scope['name']) ? $scope['name'] : $scope['interface'];
            if ($this->definitions->has($name)) {
                isset($logger) && $logger->debug("[ContainerBuilder] ignore registered component '{$name}'");
            } elseif (isset($definitions[$name])) {
                isset($logger) && $logger->debug(sprintf(
                    "[ContainerBuilder] ignore conflict component '%s' for '%s', previous was %s",
                    $scope['definition']->getAlias(),
                    $name,
                    $definitions[$name]->getAlias()
                ));
            } else {
                $definitions[$name] = $scope['definition'];
            }
        }
        $this->definitions->addDefinitions($definitions);
    }

    protected function scanComponentsIn($namespaces)
    {
        $components = [];
        $reader = $this->getAnnotationReader();
        foreach ($namespaces as $namespace) {
            foreach ($this->getClassScanner()->scan($namespace) as $className) {
                $class = new ReflectionClass($className);
                $annot = $reader->getClassAnnotation($class, Component::class);
                if ($annot === null) {
                    continue;
                }
                $definition = new AliasDefinition($className);
                if ($annot->name) {
                    $components[] = ['name' => $annot->name, 'definition' => $definition];
                } else {
                    $interfaces = $class->getInterfaceNames();
                    if (!empty($interfaces)) {
                        foreach ($interfaces as $interface) {
                            $components[] = ['interface' => $interface, 'definition' => $definition];
                        }
                    }
                }
            }
        }
        usort($components, function ($a, $b) {
            if (isset($a['name'])) {
                return 1;
            } elseif (isset($b['name'])) {
                return -1;
            }
            return 0;
        });
        return $components;
    }
}
