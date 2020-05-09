<?php

declare(strict_types=1);

namespace kuiper\di;

use Composer\Autoload\ClassLoader;
use DI\CompiledContainer;
use DI\Compiler\Compiler;
use DI\Container;
use DI\Definition\DecoratorDefinition;
use DI\Definition\Definition;
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
use DI\Proxy\ProxyFactory;
use InvalidArgumentException;
use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Conditional;
use kuiper\reflection\ReflectionFileFactory;
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
     * Name of the container parent class, used on compiled container.
     *
     * @var string
     */
    private $containerParentClass;

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
     * @var string|null
     */
    private $compileToDirectory;

    /**
     * @var DefinitionSource[]|string[]|array[]
     */
    private $definitionSources = [];

    /**
     * @var AwareAutowiring
     */
    private $awareAutowiring;
    /**
     * Whether the container has already been built.
     *
     * @var bool
     */
    private $locked = false;

    /**
     * @var ComponentScannerInterface
     */
    private $componentScanner;

    /**
     * @var ClassLoader
     */
    private $classLoader;

    /**
     * @var ReflectionNamespaceFactoryInterface
     */
    private $reflectionNamespaceFactory;

    /**
     * @var ConfigurationDefinitionLoader
     */
    private $configurationDefinition;

    /**
     * @var ConditionalDefinitionSource
     */
    private $conditionalDefinitionSource;

    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    /**
     * @var Definition[]
     */
    private $definitions = [];

    /**
     * @var ConditionalDefinition[]
     */
    private $conditionalDefinitions = [];

    /**
     * @var DecoratorDefinition[]
     */
    private $decorateDefinitions = [];

    /**
     * Build a container configured for the dev environment.
     */
    public static function buildDevContainer(): Container
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
        $source = $this->createDefinitionSource();
        $proxyFactory = new ProxyFactory($this->writeProxiesToFile, $this->proxyDirectory);
        $this->locked = true;
        $containerClass = $this->containerClass;
        if ($this->compileToDirectory) {
            $compiler = new Compiler($proxyFactory);
            $compiledContainerFile = $compiler->compile(
                $source,
                $this->compileToDirectory,
                $containerClass,
                $this->containerParentClass,
                $this->useAutowiring || $this->useAnnotations
            );
            // Only load the file if it hasn't been already loaded
            // (the container can be created multiple times in the same process)
            if (!class_exists($containerClass, false)) {
                require $compiledContainerFile;
            }
        }

        $container = new $containerClass($source, $proxyFactory, $this->wrapperContainer);
        if ($this->conditionalDefinitionSource) {
            $this->conditionalDefinitionSource->setContainer($container);
        }

        return $container;
    }

    /**
     * Compile the container for optimum performances.
     *
     * Be aware that the container is compiled once and never updated!
     *
     * Therefore:
     *
     * - in production you should clear that directory every time you deploy
     * - in development you should not compile the container
     *
     * @see http://php-di.org/doc/performances.html
     *
     * @param string $directory            directory in which to put the compiled container
     * @param string $containerClass       Name of the compiled class. Customize only if necessary.
     * @param string $containerParentClass Name of the compiled container parent class. Customize only if necessary.
     *
     * @return static
     */
    public function enableCompilation(
        string $directory,
        string $containerClass = 'CompiledContainer',
        string $containerParentClass = CompiledContainer::class
    ): self {
        $this->ensureNotLocked();

        $this->compileToDirectory = $directory;
        $this->containerClass = $containerClass;
        $this->containerParentClass = $containerParentClass;

        return $this;
    }

    public function getAwareAutowiring(): AwareAutowiring
    {
        if (!$this->awareAutowiring) {
            $this->awareAutowiring = new AwareAutowiring();
        }

        return $this->awareAutowiring;
    }

    public function addAwareInjection(AwareInjection $awareInjection): self
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
     * @param string|array|DefinitionSource ...$definitions Can be an array of definitions, the
     *                                                      name of a file containing definitions
     *                                                      or a DefinitionSource object.
     *
     * @return static
     */
    public function addDefinitions(...$definitions)
    {
        $this->ensureNotLocked();

        foreach ($definitions as $definition) {
            if (!is_string($definition) && !is_array($definition) && !($definition instanceof DefinitionSource)) {
                throw new InvalidArgumentException(sprintf('%s parameter must be a string, an array or a DefinitionSource object, %s given', 'ContainerBuilder::addDefinitions()', is_object($definition) ? get_class($definition) : gettype($definition)));
            }
            if (is_array($definition)) {
                foreach ($definition as $key => $def) {
                    if ($def instanceof ComponentDefinition) {
                        /** @var Conditional $condition */
                        $condition = AllCondition::create($this->getAnnotationReader(), $def->getComponent()->getTarget());
                        if ($condition) {
                            $def = new ConditionalDefinition($def->getDefinition(), $condition);
                        } else {
                            $def = $def->getDefinition();
                        }
                    }
                    if ($def instanceof ConditionalDefinition) {
                        $this->conditionalDefinitions[$def->getName()][] = $def;
                    } elseif ($def instanceof FactoryDefinitionHelper) {
                        $def = $def->getDefinition($key);
                        if ($def instanceof DecoratorDefinition) {
                            $this->decorateDefinitions[$key] = $def;
                        } else {
                            $this->definitions[$key] = $def;
                        }
                    } else {
                        $this->definitions[$key] = $def;
                    }
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

    /**
     * Are we building a compiled container?
     */
    public function isCompilationEnabled(): bool
    {
        return (bool) $this->compileToDirectory;
    }

    private function ensureNotLocked(): void
    {
        if ($this->locked) {
            throw new \LogicException('The ContainerBuilder cannot be modified after the container has been built');
        }
    }

    public function addConfiguration($configuration, bool $ignoreCondition = false): self
    {
        if ($configuration instanceof ContainerBuilderAwareInterface) {
            $configuration->setContainerBuilder($this);
        }
        $this->addDefinitions($this->getConfigurationDefinition()->getDefinitions($configuration, $ignoreCondition));

        return $this;
    }

    public function getAnnotationReader(): AnnotationReaderInterface
    {
        if (!$this->annotationReader) {
            $this->annotationReader = AnnotationReader::getInstance();
        }

        return $this->annotationReader;
    }

    public function setAnnotationReader(AnnotationReaderInterface $annotationReader): self
    {
        $this->annotationReader = $annotationReader;

        return $this;
    }

    public function getConfigurationDefinition(): ConfigurationDefinitionLoader
    {
        if (!$this->configurationDefinition) {
            $this->configurationDefinition = new ConfigurationDefinitionLoader($this->getAnnotationReader());
        }

        return $this->configurationDefinition;
    }

    public function setConfigurationDefinition(ConfigurationDefinitionLoader $configurationDefinition): self
    {
        $this->configurationDefinition = $configurationDefinition;

        return $this;
    }

    public function getClassLoader(): ClassLoader
    {
        if (!$this->classLoader) {
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
        if (!$this->reflectionNamespaceFactory) {
            $this->reflectionNamespaceFactory = ReflectionNamespaceFactory::createInstance(ReflectionFileFactory::createInstance())
                ->registerLoader($this->getClassLoader());
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
        if (!$this->componentScanner) {
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
        $this->getComponentScanner()->scan($namespaces);

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

    private function createDefinitionSource(): MutableDefinitionSource
    {
        $sources = array_reverse($this->definitionSources);

        $autowiring = $this->getAutowiring();
        if (!empty($this->decorateDefinitions)) {
            $sources[] = new DefinitionArray($this->decorateDefinitions, $autowiring);
        }
        if (!empty($this->definitions)) {
            $sources[] = new DefinitionArray($this->definitions, $autowiring);
        }
        if (!empty($this->conditionalDefinitions)) {
            $sources[] = $this->conditionalDefinitionSource = new ConditionalDefinitionSource($this->conditionalDefinitions, $autowiring);
        }
        $sources = array_map(static function ($definitions) use ($autowiring) {
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
        $source->setMutableDefinitionSource(new DefinitionArray([], $autowiring));

        if ($this->sourceCache) {
            if (!SourceCache::isSupported()) {
                throw new \Exception('APCu is not enabled, PHP-DI cannot use it as a cache');
            }
            // Wrap the source with the cache decorator
            $source = new SourceCache($source, $this->sourceCacheNamespace);
        }

        return $source;
    }
}
