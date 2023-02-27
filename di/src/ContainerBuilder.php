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

use Closure;
use Composer\Autoload\ClassLoader;
use DI\Definition\Definition;
use DI\Definition\ExtendsPreviousDefinition;
use DI\Definition\FactoryDefinition;
use DI\Definition\Helper\DefinitionHelper;
use DI\Definition\Helper\FactoryDefinitionHelper;
use DI\Definition\Source\AttributeBasedAutowiring;
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
use JsonException;
use kuiper\di\attribute\Configuration;
use kuiper\reflection\ReflectionFileFactory;
use kuiper\reflection\ReflectionNamespaceFactory;
use kuiper\reflection\ReflectionNamespaceFactoryInterface;
use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use RuntimeException;

class ContainerBuilder implements ContainerBuilderInterface
{
    private bool $useAutowiring = true;

    private bool $useAttribute = true;

    private bool $sourceCache = false;

    protected ?string $sourceCacheNamespace = null;

    /**
     * If PHP-DI is wrapped in another container, this references the wrapper.
     */
    private ?ContainerInterface $wrapperContainer = null;

    /**
     * Directory where to write the proxies (if $writeProxiesToFile is enabled).
     */
    private ?string $proxyDirectory = null;

    /**
     * @var array
     */
    private array $definitions = [];

    /**
     * @var DefinitionSource[]|string[]|array[]
     */
    private array $definitionSources = [];

    /**
     * @var AwareAutowiring|null
     */
    private ?AwareAutowiring $awareAutowiring;

    /**
     * Whether the container has already been built.
     */
    private bool $locked = false;

    /**
     * @var ComponentScannerInterface|null
     */
    private ?ComponentScannerInterface $componentScanner = null;

    /**
     * @var ClassLoader|null
     */
    private ?ClassLoader $classLoader = null;

    /**
     * @var ReflectionNamespaceFactoryInterface|null
     */
    private ?ReflectionNamespaceFactoryInterface $reflectionNamespaceFactory = null;

    /**
     * @var ConfigurationDefinitionLoader|null
     */
    private ?ConfigurationDefinitionLoader $configurationDefinitionLoader = null;

    /**
     * @var ConditionDefinitionSource|null
     */
    private ?ConditionDefinitionSource $conditionalDefinitionSource = null;

    /**
     * @var object[]
     */
    private array $configurations = [];

    /**
     * @var int[]
     */
    private array $configurationPriorities = [];

    /**
     * @var array
     */
    private array $scanNamespaces = [];

    /**
     * @var callable[][]
     */
    private array $deferCallbacks = [];

    /**
     * @var ConditionDefinition[][]
     */
    private array $conditionDefinitions = [];

    /**
     * @var DefinitionArray|null
     */
    private ?DefinitionArray $mutableDefinitionSource = null;

    /**
     * Build a container configured for the dev environment.
     */
    public static function buildDevContainer(): ContainerInterface
    {
        return new Container();
    }

    /**
     * @throws JsonException
     */
    public static function create(string $projectPath): self
    {
        if (!file_exists($projectPath.'/vendor/autoload.php')
            || !file_exists($projectPath.'/composer.json')) {
            throw new InvalidArgumentException("Cannot detect project path, expected composer.json in $projectPath");
        }
        $builder = new self();
        $builder->setClassLoader(require $projectPath.'/vendor/autoload.php');

        $composerJson = json_decode(file_get_contents($projectPath.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
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

    public function __construct(private readonly string $containerClass = Container::class)
    {
    }

    /**
     * Build and return a container.
     */
    public function build(): ContainerInterface
    {
        $this->registerConfigurations();
        $source = $this->createDefinitionSource();
        $proxyFactory = new ProxyFactory($this->proxyDirectory);
        $this->locked = true;
        $containerClass = $this->containerClass;
        $container = new $containerClass($source, $proxyFactory, $this->wrapperContainer);
        if (null !== $this->conditionalDefinitionSource) {
            $this->conditionalDefinitionSource->setContainer($container);
        }
        if (!empty($this->deferCallbacks)) {
            ksort($this->deferCallbacks);
            foreach ($this->deferCallbacks as $callbacks) {
                foreach ($callbacks as $callback) {
                    $callback($container);
                }
            }
        }
        foreach ($this->configurations as $configuration) {
            if ($configuration instanceof Bootstrap) {
                $condition = AllCondition::create(new ReflectionClass($configuration));
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
    public function defer(callable $callback, int $priority = null): ContainerBuilderInterface
    {
        $this->deferCallbacks[$priority ?? DefinitionConfiguration::LOW_PRIORITY][] = $callback;

        return $this;
    }

    public function getAwareAutowiring(): AwareAutowiring
    {
        if (!isset($this->awareAutowiring)) {
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
     * Enable or disable the use of attribute to guess injections.
     *
     * Enabled by default
     *
     * @return static
     */
    public function useAttribute(bool $useAttribute): self
    {
        $this->ensureNotLocked();

        $this->useAttribute = $useAttribute;

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
     * @return $this
     *
     * @throws InvalidArgumentException when writeToFile is set to true and the proxy directory is null
     */
    public function writeProxiesToFile(bool $writeToFile, string $proxyDirectory = null): self
    {
        $this->ensureNotLocked();

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
     * @return static
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
                    throw new InvalidArgumentException('Definitions must be an array, got '.gettype($definition));
                }
                foreach ($definition as $name => $value) {
                    $this->addDefinition($name, $value);
                }
            }

            return $this;
        }

        foreach ($definitions as $definition) {
            if (!is_string($definition) && !is_array($definition) && !($definition instanceof DefinitionSource)) {
                throw new InvalidArgumentException(sprintf('%s parameter must be a string, an array or a DefinitionSource object, %s given', 'ContainerBuilder::addDefinitions()', get_debug_type($definition)));
            }
            if (is_array($definition)) {
                $simpleDefinition = [];
                foreach ($definition as $key => $def) {
                    if ($def instanceof ComponentDefinition) {
                        $condition = AllCondition::create($def->getComponent()->getTarget());
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
            throw new LogicException('The ContainerBuilder cannot be modified after the container has been built');
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
        $configurationClass = new ReflectionClass($configuration);
        $configurationAttributes = $configurationClass->getAttributes(Configuration::class);
        if (!empty($configurationAttributes)) {
            foreach ($configurationAttributes as $reflectionAttribute) {
                /** @var Configuration $attribute */
                $attribute = $reflectionAttribute->newInstance();
                if (count($attribute->getDependOn()) > 0) {
                    foreach ($attribute->getDependOn() as $dependConfigurationClass) {
                        $this->addConfiguration(new $dependConfigurationClass());
                    }
                }
            }
        }
        $this->configurations[get_class($configuration)] = $configuration;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeConfiguration(string|object $configuration): ContainerBuilderInterface
    {
        unset($this->configurations[is_string($configuration) ? $configuration : get_class($configuration)]);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setConfigurationPriorities(array $priorities): ContainerBuilderInterface
    {
        $this->configurationPriorities = array_merge($this->configurationPriorities, $priorities);

        return $this;
    }

    public function getConfigurationDefinitionLoader(): ConfigurationDefinitionLoader
    {
        if (!isset($this->configurationDefinitionLoader)) {
            $this->configurationDefinitionLoader = new ConfigurationDefinitionLoader($this);
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
        if (!isset($this->classLoader)) {
            throw new InvalidArgumentException('class loader is not set yet');
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
        if (!isset($this->reflectionNamespaceFactory)) {
            $reflectionNamespaceFactory = new ReflectionNamespaceFactory(ReflectionFileFactory::getInstance());
            $reflectionNamespaceFactory->registerLoader($this->getClassLoader());
            $this->reflectionNamespaceFactory = $reflectionNamespaceFactory;
        }

        return $this->reflectionNamespaceFactory;
    }

    public function setReflectionNamespaceFactory(ReflectionNamespaceFactoryInterface $reflectionNamespaceFactory): self
    {
        $this->reflectionNamespaceFactory = $reflectionNamespaceFactory;

        return $this;
    }

    public function getComponentScanner(): ComponentScannerInterface
    {
        if (!isset($this->componentScanner)) {
            $this->componentScanner = new ComponentScanner($this, $this->getReflectionNamespaceFactory());
        }

        return $this->componentScanner;
    }

    public function setComponentScanner(ComponentScannerInterface $componentScanner): self
    {
        $this->componentScanner = $componentScanner;

        return $this;
    }

    public function componentScan(array $namespaces): ContainerBuilderInterface
    {
        foreach ($namespaces as $namespace) {
            $this->scanNamespaces[$namespace] = true;
        }

        return $this;
    }

    public function componentScanExclude(string $namespace): ContainerBuilderInterface
    {
        $this->getComponentScanner()->exclude($namespace);

        return $this;
    }

    private function createAutowiring(): Autowiring
    {
        if ($this->useAttribute) {
            $autowiring = new AttributeBasedAutowiring();
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
            $configurations = array_values($this->configurations);
            $priorities = $this->configurationPriorities;
            foreach ($configurations as $i => $configuration) {
                if (!isset($priorities[get_class($configuration)])) {
                    $priorities[get_class($configuration)] = $i;
                }
            }
            usort($configurations, static function (object $conf1, object $conf2) use ($priorities): int {
                return $priorities[get_class($conf1)] - $priorities[get_class($conf2)];
            });
            $this->configurations = $configurations;
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

        $autowiring = $this->createAutowiring();
        if (!empty($this->conditionDefinitions)) {
            $this->conditionalDefinitionSource = new ConditionDefinitionSource($this->conditionDefinitions, $autowiring);
            $sources[] = $this->conditionalDefinitionSource;
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
                throw new RuntimeException('APCu is not enabled, PHP-DI cannot use it as a cache');
            }
            // Wrap the source with the cache decorator
            $source = new SourceCache($source, $this->sourceCacheNamespace);
        }

        return $source;
    }

    private function addDefinition(string $name, mixed $value): void
    {
        if ($value instanceof DefinitionHelper) {
            $value = $value->getDefinition($name);
        } elseif ($value instanceof Closure) {
            $value = new FactoryDefinition($name, $value);
        }
        if (!$value instanceof Definition) {
            $value = new ValueDefinition($value);
        }
        $value->setName($name);
        $this->mutableDefinitionSource->addDefinition($value);
    }
}
