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

use DI\Definition\Definition;
use DI\Definition\Exception\InvalidDefinition;
use DI\Definition\FactoryDefinition;
use DI\Definition\Helper\DefinitionHelper;
use DI\Definition\InstanceDefinition;
use DI\Definition\ObjectDefinition;
use DI\Definition\Resolver\DefinitionResolver;
use DI\Definition\Resolver\ResolverDispatcher;
use DI\Definition\Source\DefinitionArray;
use DI\Definition\Source\MutableDefinitionSource;
use DI\Definition\Source\ReflectionBasedAutowiring;
use DI\Definition\Source\SourceChain;
use DI\Definition\ValueDefinition;
use DI\DependencyException;
use DI\FactoryInterface;
use DI\Invoker\DefinitionParameterResolver;
use DI\NotFoundException;
use DI\Proxy\ProxyFactory;
use InvalidArgumentException;
use Invoker\Invoker;
use Invoker\InvokerInterface;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\NumericArrayResolver;
use Invoker\ParameterResolver\ResolverChain;
use kuiper\swoole\coroutine\Coroutine;
use Psr\Container\ContainerInterface;

/**
 * Dependency Injection Container.
 *
 * copy from \DI\Container
 */
class Container implements ContainerInterface, FactoryInterface, InvokerInterface
{
    /**
     * Map of entries that are already resolved.
     */
    protected array $resolvedEntries = [];

    private MutableDefinitionSource $definitionSource;

    private DefinitionResolver $definitionResolver;

    /**
     * Map of definitions that are already fetched (local cache).
     */
    private array $fetchedDefinitions = [];

    private ?InvokerInterface $invoker = null;

    /**
     * Container that wraps this container. If none, points to $this.
     */
    protected readonly ContainerInterface $delegateContainer;

    protected readonly ProxyFactory $proxyFactory;

    /**
     * Use `$container = new Container()` if you want a container with the default configuration.
     *
     * If you want to customize the container's behavior, you are discouraged to create and pass the
     * dependencies yourself, the ContainerBuilder class is here to help you instead.
     *
     * @see ContainerBuilder
     */
    public function __construct(
        MutableDefinitionSource $definitionSource = null,
        ProxyFactory $proxyFactory = null,
        ContainerInterface $wrapperContainer = null
    ) {
        $this->delegateContainer = $wrapperContainer ?? $this;
        $this->definitionSource = $definitionSource ?? $this->createDefaultDefinitionSource();
        $this->proxyFactory = $proxyFactory ?? new ProxyFactory(null);
        $this->definitionResolver = new ResolverDispatcher($this->delegateContainer, $this->proxyFactory);

        // Auto-register the container
        $this->resolvedEntries = [
            static::class => $this,
            ContainerInterface::class => $this->delegateContainer,
            FactoryInterface::class => $this,
            InvokerInterface::class => $this,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $id)
    {
        // If the entry is already resolved we return it
        if (isset($this->resolvedEntries[$id]) || array_key_exists($id, $this->resolvedEntries)) {
            return $this->resolvedEntries[$id];
        }

        $definition = $this->getDefinition($id);
        if (null === $definition) {
            throw new NotFoundException("No entry or class found for '{$id}'");
        }

        $value = $this->resolveDefinition($definition);

        $this->resolvedEntries[$id] = $value;

        return $value;
    }

    /**
     * @param string $name
     *
     * @return Definition|null
     *
     * @throws InvalidDefinition
     */
    protected function getDefinition(string $name): ?Definition
    {
        // Local cache that avoids fetching the same definition twice
        if (!array_key_exists($name, $this->fetchedDefinitions)) {
            $this->fetchedDefinitions[$name] = $this->definitionSource->getDefinition($name);
        }

        return $this->fetchedDefinitions[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function make(string $name, array $parameters = []): mixed
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException(sprintf('The name parameter must be of type string, %s given', get_debug_type($name)));
        }

        $definition = $this->getDefinition($name);
        if (null === $definition) {
            // If the entry is already resolved we return it
            if (array_key_exists($name, $this->resolvedEntries)) {
                return $this->resolvedEntries[$name];
            }

            throw new NotFoundException("No entry or class found for '$name'");
        }

        return $this->resolveDefinition($definition, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->resolvedEntries)) {
            return true;
        }

        $definition = $this->getDefinition($id);
        if (null === $definition) {
            return false;
        }

        return $this->definitionResolver->isResolvable($definition);
    }

    /**
     * Inject all dependencies on an existing instance.
     *
     * @template T
     *
     * @param T $instance Object to perform injection upon
     *
     * @return T $instance Returns the same instance
     *
     * @throws DependencyException Error while injecting dependencies
     * @throws InvalidDefinition
     */
    public function injectOn(object $instance): object
    {
        if (!$instance) {
            return $instance;
        }

        $className = get_class($instance);

        // If the class is anonymous, don't cache its definition
        // Checking for anonymous classes is cleaner via Reflection, but also slower
        $objectDefinition = str_contains($className, '@anonymous')
            ? $this->definitionSource->getDefinition($className)
            : $this->getDefinition($className);

        if (!$objectDefinition instanceof ObjectDefinition) {
            return $instance;
        }

        $definition = new InstanceDefinition($instance, $objectDefinition);

        $this->definitionResolver->resolve($definition);

        return $instance;
    }

    /**
     * Call the given function using the given parameters.
     *
     * Missing parameters will be resolved from the container.
     *
     * @param callable $callable   function to call
     * @param array    $parameters Parameters to use. Can be indexed by the parameter names
     *                             or not indexed (same order as the parameters).
     *                             The array can also contain DI definitions, e.g. DI\get().
     *
     * @return mixed result of the function
     * @throws \Exception
     */
    public function call($callable, array $parameters = []): mixed
    {
        return $this->getInvoker()->call($callable, $parameters);
    }

    /**
     * Define an object or a value in the container.
     *
     * @param string $name  Entry name
     * @param mixed  $value Value, use definition helpers to define objects
     */
    public function set(string $name, mixed $value): void
    {
        if ($value instanceof DefinitionHelper) {
            $value = $value->getDefinition($name);
        } elseif ($value instanceof \Closure) {
            $value = new FactoryDefinition($name, $value);
        }

        if ($value instanceof ValueDefinition) {
            $this->resolvedEntries[$name] = $value->getValue();
        } elseif ($value instanceof Definition) {
            $value->setName($name);
            $this->setDefinition($name, $value);
        } else {
            $this->resolvedEntries[$name] = $value;
        }
    }

    /**
     * Get defined container entries.
     *
     * @return string[]
     */
    public function getKnownEntryNames(): array
    {
        $entries = array_unique(array_merge(
            array_keys($this->definitionSource->getDefinitions()),
            array_keys($this->resolvedEntries)
        ));
        sort($entries);

        return $entries;
    }

    /**
     * Get entry debug information.
     *
     * @param string $name Entry name
     *
     * @throws InvalidDefinition
     * @throws NotFoundException
     */
    public function debugEntry(string $name): string
    {
        $definition = $this->definitionSource->getDefinition($name);
        if ($definition instanceof Definition) {
            return (string) $definition;
        }

        if (array_key_exists($name, $this->resolvedEntries)) {
            return $this->getEntryType($this->resolvedEntries[$name]);
        }

        throw new NotFoundException("No entry or class found for '$name'");
    }

    /**
     * Get formatted entry type.
     */
    private function getEntryType(mixed $entry): string
    {
        if (is_object($entry)) {
            return sprintf("Object (\n    class = %s\n)", get_class($entry));
        }

        if (is_array($entry)) {
            return preg_replace(['/^array \(/', '/\)$/'], ['[', ']'], var_export($entry, true));
        }

        if (is_string($entry)) {
            return sprintf('Value (\'%s\')', $entry);
        }

        if (is_bool($entry)) {
            return sprintf('Value (%s)', true === $entry ? 'true' : 'false');
        }

        return sprintf('Value (%s)', is_scalar($entry) ? $entry : ucfirst(gettype($entry)));
    }

    /**
     * @param Definition $definition
     * @param array      $parameters
     *
     * @return mixed
     *
     * @throws DependencyException
     * @throws InvalidDefinition
     */
    protected function resolveDefinition(Definition $definition, array $parameters = []): mixed
    {
        $entryName = $definition->getName();

        // Check if we are already getting this entry -> circular dependency
        if ($this->isResolving($entryName)) {
            throw new DependencyException("Circular dependency detected while trying to resolve entry '$entryName'");
        }
        $this->setResolving($entryName);

        // Resolve the definition
        try {
            $value = $this->definitionResolver->resolve($definition, $parameters);
        } finally {
            $this->setResolving($entryName, false);
        }

        return $value;
    }

    protected function isResolving(string $name): bool
    {
        return Coroutine::getContext()->offsetExists('containerEntityResolving'.$name);
    }

    protected function setResolving(string $name, bool $resolving = true): void
    {
        if ($resolving) {
            Coroutine::getContext()['containerEntityResolving'.$name] = true;
        } else {
            Coroutine::getContext()->offsetUnset('containerEntityResolving'.$name);
        }
    }

    protected function setDefinition(string $name, Definition $definition): void
    {
        // Clear existing entry if it exists
        if (array_key_exists($name, $this->resolvedEntries)) {
            unset($this->resolvedEntries[$name]);
        }
        $this->fetchedDefinitions = []; // Completely clear this local cache

        $this->definitionSource->addDefinition($definition);
    }

    private function getInvoker(): InvokerInterface
    {
        if (null === $this->invoker) {
            $parameterResolver = new ResolverChain([
                new DefinitionParameterResolver($this->definitionResolver),
                new NumericArrayResolver(),
                new AssociativeArrayResolver(),
                new DefaultValueResolver(),
                new TypeHintContainerResolver($this->delegateContainer),
            ]);

            $this->invoker = new Invoker($parameterResolver, $this);
        }

        return $this->invoker;
    }

    private function createDefaultDefinitionSource(): SourceChain
    {
        $source = new SourceChain([new ReflectionBasedAutowiring()]);
        $source->setMutableDefinitionSource(new DefinitionArray([], new ReflectionBasedAutowiring()));

        return $source;
    }
}
