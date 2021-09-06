<?php

declare(strict_types=1);

namespace kuiper\di;

use Composer\Autoload\ClassLoader;
use DI\Definition\Definition;
use DI\Definition\ExtendsPreviousDefinition;
use DI\Definition\FactoryDefinition;
use DI\Definition\Helper\DefinitionHelper;
use DI\Definition\Helper\FactoryDefinitionHelper;
use DI\Definition\Source\AnnotationBasedAutowiring;
use DI\Definition\Source\Autowiring;
use DI\Definition\Source\DefinitionArray;
use DI\Definition\Source\DefinitionFile;
use DI\Definition\Source\DefinitionSource;
use DI\Definition\Source\MutableDefinitionSource;
use DI\Definition\Source\NoAutowiring;
use DI\Definition\Source\ReflectionBasedAutowiring;
use DI\Definition\Source\SourceCache;
use DI\Definition\Source\SourceChain;
use DI\Definition\ValueDefinition;
use DI\Proxy\ProxyFactory;
use InvalidArgumentException;
use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\reflection\ReflectionNamespaceFactory;
use kuiper\reflection\ReflectionNamespaceFactoryInterface;
use Psr\Container\ContainerInterface;

class ContainerBuilder implements ContainerBuilderInterface
{
    /**
     * Name of the container class, used to create the container.
     *
     * @var string
     */
    private $containerClass;

    /**
     * If PHP-DI is wrapped in another container, this references the wrapper.
     *
     * @var ContainerInterface
     */
    private $wrapperContainer;

    /**
     * @var bool
     */
    private $useAutowiring = true;

    /**
     * @var bool
     */
    private $useAnnotations = true;

    /**
     * @var bool
     */
    private $sourceCache = false;

    /**
     * @var string
     */
    protected $sourceCacheNamespace;

    /**
     * @var bool
     */
    private $ignorePhpDocErrors = false;

    /**
     * If true, write the proxies to disk to improve performances.
     *
     * @var bool
     */
    private $writeProxiesToFile = false;

    /**
     * Directory where to write the proxies (if $writeProxiesToFile is enabled).
     *
     * @var string|null
     */
    private $proxyDirectory;

    /**
     * @var array
     */
    private $definitions;

    /**
     * @var DefinitionSource[]|string[]|array[]
     */
    private $definitionSources = [];

    /**
     * @var AwareAutowiring|null
     */
    private $awareAutowiring;
    /**
     * Whether the container has already been built.
     *
     * @var bool
     */
    private $locked = false;

    /**
     * @var ComponentScannerInterface|null
     */
    private $componentScanner;

    /**
     * @var ClassLoader|null
     */
    private $classLoader;

    /**
     * @var ReflectionNamespaceFactoryInterface|null
     */
    private $reflectionNamespaceFactory;

    /**
     * @var ConfigurationDefinitionLoader|null
     */
    private $configurationDefinitionLoader;

    /**
     * @var ConditionDefinitionSource|null
     */
    private $conditionalDefinitionSource;

    /**
     * @var AnnotationReaderInterface|null
     */
    private $annotationReader;

    /**
     * @var object[]
     */
    private $configurations = [];

    /**
     * @var array
     */
    private $scanNamespaces = [];

    /**
     * @var callable[][]
     */
    private $deferCallbacks = [];

    /**
     * @var ConditionDefinition[][]
     */
    private $conditionDefinitions = [];

    /**
     * @var DefinitionArray|null
     */
    private $mutableDefinitionSource;

    /**
     * Build a container configured for the dev environment.
     */
    public static function buildDevContainer(): ContainerInterface
    {
        return new Container();
    }

    public static function create(string $projectPath): self
    {
        if (!file_exists($projectPath.'/vendor/autoload.php')
            || !file_exists($projectPath.'/composer.json')) {
            throw new \InvalidArgumentException("Cannot detect project path, expected composer.json in $projectPath");
        }
        $builder = new self();
        $builder->setClassLoader(require $projectPath.'/vendor/autoload.php');

        $composerJson = json_decode(file_get_contents($projectPath.'/composer.json'), true);
        $configFile = $projectPath.'/'.($composerJson['extra']['kuiper']['config-file'] ?? 'config/container.php');
        if (file_exists($configFile)) {
            $config = require $configFile;

            if (!empty($config['configuration'])) {
                foreach ($config['configuration'] as $configurationBean) {
                    if (is_string($configurationBean)) {
                        $configurationBean = new $configurationBean();
                    }
                    $builder->addConfiguration($configurationBean);
                }
            }
            if (!empty($config['component_scan'])) {
                $builder->componentScan($config['component_scan']);
            }
        }

        return $builder;
    }

    /**
     * @param string $containerClass name of the container class, used to create the container
     */
    public function __construct(string $containerClass = Container::class)
    {
        $this->containerClass = $containerClass;
    }

    /**
     * Build and return a container.
     */
    public function build(): ContainerInterface
    {
        $this->registerConfigurations();
        $source = $this->createDefinitionSource();
        $proxyFactory = new ProxyFactory($this->writeProxiesToFile, $this->proxyDirectory);
        $this->locked = true;
        $containerClass = $this->containerClass;
        $container = new $containerClass($source, $proxyFactory, $this->wrapperContainer);
        if (null !== $this->conditionalDefinitionSource) {
            $this->conditionalDefinitionSource->setContainer($container);
        }
        if (!empty($this->deferCallbacks)) {
            ksort($this->deferCallbacks);
            foreach ($this->deferCallbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    $callback($container);
                }
            }
        }
        foreach ($this->configurations as $configuration) {
            if ($configuration instanceof Bootstrap) {
                $condition = AllCondition::create($this->getAnnotationReader(), new \ReflectionClass($configuration));
                if (null !== $condition && !$condition->matches($container)) {
                    continue;
                }
                $configuration->boot($container);
            }
        }

        return $container;
    }

    /**
     * {@inheritDoc}
     */
    public function defer(callable $callback, int $priority = DefinitionConfiguration::LOW_PRIORITY): ContainerBuilderInterface
    {
        $this->deferCallbacks[$priority][] = $callback;

        return $this;
    }

    public function getAwareAutowiring(): AwareAutowiring
    {
        if (null === $this->awareAutowiring) {
            $this->awareAutowiring = new AwareAutowiring();
        }

        return $this->awareAutowiring;
    }

    /**
     * {@inheritDoc}
     */
    public function addAwareInjection(AwareInjection $awareInjection): ContainerBuilderInterface
    {
        $this->getAwareAutowiring()->add($awareInjection);

        return $this;
    }

    /**
     * Enable or disable the use of autowiring to guess injections.
     *
     * Enabled by default.
     *
     * @return static
     */
    public function useAutowiring(bool $bool): self
    {
        $this->ensureNotLocked();

        $this->useAutowiring = $bool;

        return $this;
    }

    /**
     * Enable or disable the use of annotations to guess injections.
     *
     * Disabled by default.
     *
     * @return static
     */
    public function useAnnotations(bool $bool): self
    {
        $this->ensureNotLocked();

        $this->useAnnotations = $bool;

        return $this;
    }

    /**
     * Enable or disable ignoring phpdoc errors (non-existent classes in `@param` or `@var`).
     *
     * @return $this
     */
    public function ignorePhpDocErrors(bool $bool): self
    {
        $this->ensureNotLocked();

        $this->ignorePhpDocErrors = $bool;

        return $this;
    }

    /**
     * Configure the proxy generation.
     *
     * For dev environment, use `writeProxiesToFile(false)` (default configuration)
     * For production environment, use `writeProxiesToFile(true, 'tmp/proxies')`
     *
     * @see http://php-di.org/doc/lazy-injection.html
     *
     * @param bool        $writeToFile    If true, write the proxies to disk to improve performances
     * @param string|null $proxyDirectory Directory where to write the proxies
     *
     * @throws InvalidArgumentException when writeToFile is set to true and the proxy directory is null
     *
     * @return $this
     */
    public function writeProxiesToFile(bool $writeToFile, string $proxyDirectory = null): self
    {
        $this->ensureNotLocked();

        $this->writeProxiesToFile = $writeToFile;

        if ($writeToFile && null === $proxyDirectory) {
            throw new InvalidArgumentException('The proxy directory must be specified if you want to write proxies on disk');
        }
        $this->proxyDirectory = $proxyDirectory;

        return $this;
    }

    /**
     * If PHP-DI's container is wrapped by another container, we can
     * set this so that PHP-DI will use the wrapper rather than itself for building objects.
     *
     * @return $this
     */
    public function wrapContainer(ContainerInterface $otherContainer): self
    {
        $this->ensureNotLocked();

        $this->wrapperContainer = $otherContainer;

        return $this;
    }

    /**
     * Add definitions to the container.
     *
     * @param string|array|DefinitionSource|mixed ...$definitions Can be an array of definitions, the
     *                                                            name of a file containing definitions
     *                                                            or a DefinitionSource object.
     *
     * @return static
     */
    public function addDefinitions(...$definitions): ContainerBuilderInterface
    {
        if ($this->locked) {
            foreach ($definitions as $definition) {
                if (!is_array($definition)) {
                    throw new \InvalidArgumentException('Definitions must be an array, got '.gettype($definition));
                }
                foreach ($definition as $name => $value) {
                    $this->addDefinition($name, $value);
                }
            }

            return $this;
        }

        foreach ($definitions as $definition) {
            if (!is_string($definition) && !is_array($definition) && !($definition instanceof DefinitionSource)) {
                throw new InvalidArgumentException(sprintf('%s parameter must be a string, an array or a DefinitionSource object, %s given', 'ContainerBuilder::addDefinitions()', is_object($definition) ? get_class($definition) : gettype($definition)));
            }
            if (is_array($definition)) {
                $simpleDefinition = [];
                foreach ($definition as $key => $def) {
                    if ($def instanceof ComponentDefinition) {
                        $condition = AllCondition::create($this->getAnnotationReader(), $def->getComponent()->getTarget());
                        if (null !== $condition) {
                            $def = new ConditionDefinition($condition, $def->getDefinition());
                        } else {
                            $def = $def->getDefinition();
                        }
                    } elseif ($def instanceof FactoryDefinitionHelper) {
                        $def = $def->getDefinition($key);
                    }
                    if ($def instanceof ConditionDefinition) {
                        $this->conditionDefinitions[$def->getName()][] = $def;
                    } elseif (!($def instanceof ExtendsPreviousDefinition)) {
                        $this->definitions[$key] = $def;
                    } else {
                        $simpleDefinition[$key] = $def;
                    }
                }
                if (!empty($simpleDefinition)) {
                    $this->definitionSources[] = $simpleDefinition;
                }
            } else {
                $this->definitionSources[] = $definition;
            }
        }

        return $this;
    }

    /**
     * Enables the use of APCu to cache definitions.
     *
     * You must have APCu enabled to use it.
     *
     * Before using this feature, you should try these steps first:
     * - enable compilation if not already done (see `enableCompilation()`)
     * - if you use autowiring or annotations, add all the classes you are using into your configuration so that
     *   PHP-DI knows about them and compiles them
     * Once this is done, you can try to optimize performances further with APCu. It can also be useful if you use
     * `Container::make()` instead of `get()` (`make()` calls cannot be compiled so they are not optimized).
     *
     * Remember to clear APCu on each deploy else your application will have a stale cache. Do not enable the cache
     * in development environment: any change you will make to the code will be ignored because of the cache.
     *
     * @see http://php-di.org/doc/performances.html
     *
     * @param string $cacheNamespace use unique namespace per container when sharing a single APC memory pool to prevent cache collisions
     *
     * @return $this
     */
    public function enableDefinitionCache(string $cacheNamespace = ''): self
    {
        $this->ensureNotLocked();

        $this->sourceCache = true;
        $this->sourceCacheNamespace = $cacheNamespace;

        return $this;
    }

    private function ensureNotLocked(): void
    {
        if ($this->locked) {
            throw new \LogicException('The ContainerBuilder cannot be modified after the container has been built');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addConfiguration(object $configuration): ContainerBuilderInterface
    {
        if ($configuration instanceof ContainerBuilderAwareInterface) {
            $configuration->setContainerBuilder($this);
        }
        $this->configurations[] = $configuration;

        return $this;
    }

    public function getAnnotationReader(): AnnotationReaderInterface
    {
        if (null === $this->annotationReader) {
            $this->annotationReader = AnnotationReader::getInstance();
        }

        return $this->annotationReader;
    }

    public function setAnnotationReader(AnnotationReaderInterface $annotationReader): self
    {
        $this->annotationReader = $annotationReader;

        return $this;
    }

    public function getConfigurationDefinitionLoader(): ConfigurationDefinitionLoader
    {
        if (null === $this->configurationDefinitionLoader) {
            $this->configurationDefinitionLoader = new ConfigurationDefinitionLoader($this, $this->getAnnotationReader());
        }

        return $this->configurationDefinitionLoader;
    }

    public function setConfigurationDefinitionLoader(ConfigurationDefinitionLoader $configurationDefinitionLoader): self
    {
        $this->configurationDefinitionLoader = $configurationDefinitionLoader;

        return $this;
    }

    public function getClassLoader(): ClassLoader
    {
        if (null === $this->classLoader) {
            throw new \InvalidArgumentException('class loader is not set yet');
        }

        return $this->classLoader;
    }

    public function setClassLoader(ClassLoader $classLoader): self
    {
        $this->classLoader = $classLoader;

        return $this;
    }

    public function getReflectionNamespaceFactory(): ReflectionNamespaceFactoryInterface
    {
        if (null === $this->reflectionNamespaceFactory) {
            /** @var ReflectionNamespaceFactory $reflectionNamespaceFactory */
            $reflectionNamespaceFactory = ReflectionNamespaceFactory::getInstance();
            $reflectionNamespaceFactory->registerLoader($this->getClassLoader());
            $this->reflectionNamespaceFactory = $reflectionNamespaceFactory;
        }

        return $this->reflectionNamespaceFactory;
    }

    /**
     * @return static
     */
    public function setReflectionNamespaceFactory(ReflectionNamespaceFactoryInterface $reflectionNamespaceFactory): self
    {
        $this->reflectionNamespaceFactory = $reflectionNamespaceFactory;

        return $this;
    }

    public function getComponentScanner(): ComponentScannerInterface
    {
        if (null === $this->componentScanner) {
            $this->componentScanner = new ComponentScanner($this, $this->getAnnotationReader(), $this->getReflectionNamespaceFactory());
        }

        return $this->componentScanner;
    }

    public function setComponentScanner(ComponentScannerInterface $componentScanner): self
    {
        $this->componentScanner = $componentScanner;

        return $this;
    }

    public function componentScan(array $namespaces): self
    {
        foreach ($namespaces as $namespace) {
            $this->scanNamespaces[$namespace] = true;
        }

        return $this;
    }

    private function getAutowiring(): Autowiring
    {
        if ($this->useAnnotations) {
            $autowiring = new AnnotationBasedAutowiring($this->ignorePhpDocErrors);
        } elseif ($this->useAutowiring) {
            $autowiring = new ReflectionBasedAutowiring();
        } else {
            $autowiring = new NoAutowiring();
        }
        if ($autowiring instanceof DefinitionSource && $this->getAwareAutowiring()->hasInjections()) {
            $this->getAwareAutowiring()->setAutowiring($autowiring);

            return $this->getAwareAutowiring();
        }

        return $autowiring;
    }

    private function registerConfigurations(): void
    {
        if (!empty($this->scanNamespaces)) {
            $this->getComponentScanner()->scan(array_keys($this->scanNamespaces));
        }
        if (!empty($this->configurations)) {
            foreach ($this->configurations as $configuration) {
                $this->addDefinitions($this->getConfigurationDefinitionLoader()->getDefinitions($configuration));
            }
        }
    }

    private function createDefinitionSource(): MutableDefinitionSource
    {
        $sources = array_reverse($this->definitionSources);
        if (!empty($this->definitions)) {
            $sources[] = $this->definitions;
        }

        $autowiring = $this->getAutowiring();
        if (!empty($this->conditionDefinitions)) {
            $sources[] = $this->conditionalDefinitionSource = new ConditionDefinitionSource($this->conditionDefinitions, $autowiring);
        }
        $sources = array_map(static function ($definitions) use ($autowiring): DefinitionSource {
            if (is_array($definitions)) {
                return new DefinitionArray($definitions, $autowiring);
            }
            if (is_string($definitions)) {
                // File
                return new DefinitionFile($definitions, $autowiring);
            }

            if ($definitions instanceof AutowiringAwareInterface) {
                $definitions->setAutowiring($autowiring);
            }

            return $definitions;
        }, $sources);
        if ($autowiring instanceof DefinitionSource) {
            $sources[] = $autowiring;
        }
        $source = new SourceChain($sources);

        // Mutable definition source
        $source->setMutableDefinitionSource($this->mutableDefinitionSource = new DefinitionArray([], $autowiring));

        if ($this->sourceCache) {
            if (!SourceCache::isSupported()) {
                throw new \Exception('APCu is not enabled, PHP-DI cannot use it as a cache');
            }
            // Wrap the source with the cache decorator
            $source = new SourceCache($source, $this->sourceCacheNamespace);
        }

        return $source;
    }

    /**
     * @param mixed $value
     */
    private function addDefinition(string $name, $value): void
    {
        if ($value instanceof DefinitionHelper) {
            $value = $value->getDefinition($name);
        } elseif ($value instanceof \Closure) {
            $value = new FactoryDefinition($name, $value);
        }
        if (!$value instanceof Definition) {
            $value = new ValueDefinition($value);
        }
        $value->setName($name);
        $this->mutableDefinitionSource->addDefinition($value);
    }
}
