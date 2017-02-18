<?php

namespace kuiper\di;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use kuiper\di\definition\AliasDefinition;
use kuiper\di\definition\ArrayDefinition;
use kuiper\di\definition\DefinitionInterface;
use kuiper\di\definition\EnvDefinition;
use kuiper\di\definition\FactoryDefinition;
use kuiper\di\definition\ObjectDefinition;
use kuiper\di\definition\StringDefinition;
use kuiper\di\definition\ValueDefinition;
use kuiper\di\event\DefinitionEvent;
use kuiper\di\event\Events;
use kuiper\di\event\ResolveEvent;
use kuiper\di\exception\DefinitionException;
use kuiper\di\exception\DependencyException;
use kuiper\di\exception\NotFoundException;
use kuiper\di\resolver\ArrayResolver;
use kuiper\di\resolver\EnvResolver;
use kuiper\di\resolver\FactoryResolver;
use kuiper\di\resolver\ObjectResolver;
use kuiper\di\resolver\ResolvableInterface;
use kuiper\di\resolver\ResolverInterface;
use kuiper\di\resolver\StringResolver;
use kuiper\di\source\MutableSourceInterface;
use kuiper\di\source\SourceInterface;
use LogicException;
use ProxyManager\Proxy\VirtualProxyInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Container implements ContainerInterface, ResolverInterface
{
    /**
     * @var SourceInterface
     */
    private $source;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $resolvers = [];

    /**
     * @var array
     */
    private $singletonEntries = [];

    /**
     * @var array
     */
    private $requestEntries = [];

    /**
     * @var bool
     */
    private $isResolvingShared;

    /**
     * @var array
     */
    private $resolvedValues = [];

    private static $DEFINITION_RESOLVERS = [
        EnvDefinition::class => [__CLASS__, 'createEnvResolver'],
        StringDefinition::class => [__CLASS__, 'createStringResolver'],
        ArrayDefinition::class => [__CLASS__, 'createArrayResolver'],
        FactoryDefinition::class => [__CLASS__, 'createFactoryResolver'],
        ObjectDefinition::class => [__CLASS__, 'createObjectResolver'],
    ];

    public function __construct(MutableSourceInterface $source, ProxyFactory $proxyFactory, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->source = $source;
        $this->proxyFactory = $proxyFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        $name = $this->normalize($name);
        if ($this->isResolved($name)) {
            return $this->getResolved($name);
        }
        $this->isResolvingShared = true;
        $this->resolvedValues = [];

        return $this->resolve($this, $this->getDefinition($name));
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $definition)
    {
        $name = $this->normalize($name);
        if (!$definition instanceof DefinitionInterface) {
            $definition = new ValueDefinition($definition);
        }
        unset($this->singletonEntries[$name]);
        unset($this->requestEntries[$name]);
        $this->source->set($name, $definition);
    }

    /**
     * {@inheritdoc}
     */
    public function make($name, $parameters = [])
    {
        $name = $this->normalize($name);
        $this->isResolvingShared = false;
        $this->resolvedValues = [];

        return $this->resolve($this, $this->getDefinition($name), $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        $name = $this->normalize($name);

        return $this->isResolved($name) || $this->source->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function startRequest()
    {
        foreach ($this->requestEntries as $name => $entry) {
            list($value, $initializer) = $entry;
            $value->setProxyInitializer($initializer);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function endRequest()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ContainerInterface $container, DefinitionEntry $entry, $parameters = [])
    {
        $name = $entry->getName();
        if ($this->isResolvingShared && $this->isResolved($name)) {
            return $this->getResolved($name);
        }
        if (array_key_exists($name, $this->resolvedValues)) {
            return $this->resolvedValues[$name];
        }
        if (isset($this->resolving[$name])) {
            throw new DependencyException("Circular dependency detected while trying to resolve entry '$name': " . json_encode($this->resolving));
        }
        $this->resolving[$name] = true;

        if ($this->eventDispatcher) {
            $event = new ResolveEvent($container, $entry, $parameters);
            $this->eventDispatcher->dispatch(Events::BEFORE_RESOLVE, $event);
            $value = $event->getValue();
        }
        if (!isset($value)) {
            $definition = $entry->getDefinition();
            if ($definition instanceof ValueDefinition) {
                $value = $definition->getValue();
            } elseif ($definition instanceof AliasDefinition) {
                $aliasEntry = $this->getDefinition($definition->getAlias());
                $value = $this->resolve($container, $aliasEntry, $parameters);
            } else {
                $resolver = $this->getResolver($definition);
                if ($resolver === null) {
                    throw new DefinitionException('Cannot resolve definition '.get_class($definition));
                }
                $value = $resolver->resolve($container, $entry, $parameters);
            }
            if ($value instanceof DeferredObject) {
                $deferred = $value;
                $value = $deferred->getInstance();
            }
            if ($this->eventDispatcher) {
                $event->setValue($value);
                $this->eventDispatcher->dispatch(Events::AFTER_RESOLVE, $event);
            }
        }
        if ($this->isResolvingShared) {
            $scope = $definition->getScope();
            if ($scope === Scope::SINGLETON) {
                $this->singletonEntries[$name] = $value;
            } elseif ($scope === Scope::REQUEST) {
                if (!$value instanceof VirtualProxyInterface) {
                    throw new LogicException("Request entry '{$name}' is not object or factory");
                }
                $this->requestEntries[$name] = [$value, $value->getProxyInitializer()];
            }
        }
        $this->resolvedValues[$name] = $value;
        unset($this->resolving[$name]);
        if (isset($deferred)) {
            $deferred->initialize();
        }

        return $value;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getProxyFactory()
    {
        return $this->proxyFactory;
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

    /**
     * Removes '\' at the beginning.
     */
    protected function normalize($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('The name parameter must be of type string');
        }

        return ltrim($name, '\\');
    }

    protected function getDefinition($name)
    {
        if ($this->eventDispatcher) {
            $event = new DefinitionEvent($name);
            $this->eventDispatcher->dispatch(Events::BEFORE_GET_DEFINITION, $event);
            if ($definition = $event->getDefinition()) {
                return $definition;
            }
        }
        $definition = $this->source->get($name);
        if ($definition === null) {
            throw new NotFoundException("Cannot resolve entry '$name'");
        }
        if ($this->eventDispatcher) {
            $event->setDefinition($definition);
            $this->eventDispatcher->dispatch(Events::AFTER_GET_DEFINITION, $event);
        }

        return $definition;
    }

    protected function isResolved($name)
    {
        return array_key_exists($name, $this->singletonEntries)
            || array_key_exists($name, $this->requestEntries);
    }

    protected function getResolved($name)
    {
        if (array_key_exists($name, $this->singletonEntries)) {
            return $this->singletonEntries[$name];
        } elseif (array_key_exists($name, $this->requestEntries)) {
            return $this->requestEntries[$name][0];
        }
    }

    public function getResolver($definition)
    {
        foreach (self::$DEFINITION_RESOLVERS as $definitionType => $resolverFactory) {
            if (is_a($definition, $definitionType, true)) {
                if (!isset($this->resolvers[$definitionType])) {
                    $this->resolvers[$definitionType] = call_user_func($resolverFactory, $this);
                }

                return $this->resolvers[$definitionType];
            }
        }
        if ($definition instanceof ResolvableInterface) {
            return $definition->getResolver($this);
        }
        throw new DefinitionException("Cannot found resolver");
    }

    public function setResolver($definitionType, ResolverInterface $resolver)
    {
        $this->resolvers[$definitionType] = $resolver;

        return $this;
    }

    public static function createEnvResolver(Container $container)
    {
        return new EnvResolver();
    }

    public static function createStringResolver(Container $container)
    {
        return new StringResolver();
    }

    public static function createArrayResolver(Container $container)
    {
        return new ArrayResolver($container);
    }

    public static function createFactoryResolver(Container $container)
    {
        return new FactoryResolver($container, $container->proxyFactory);
    }

    public static function createObjectResolver(Container $container)
    {
        return (new ObjectResolver($container, $container->proxyFactory))
            ->setAwarables([
                'setLogger' => [LoggerAwareInterface::class, LoggerInterface::class],
                'setContainer' => [ContainerAwareInterface::class, ContainerInterface::class]
            ]);
    }
}
