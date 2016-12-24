<?php
namespace kuiper\di;

use InvalidArgumentException;
use kuiper\di\exception\NotFoundException;
use kuiper\di\exception\DependencyException;
use kuiper\di\source\SourceInterface;
use kuiper\di\source\MutableSourceInterface;
use kuiper\di\resolver\DispatchResolver;
use kuiper\di\source\CachedSource;
use kuiper\di\definition\DefinitionInterface;
use kuiper\di\definition\ValueDefinition;
use kuiper\di\Scope;
use ProxyManager\Proxy\VirtualProxyInterface;
use LogicException;

class Container implements ContainerInterface
{
    /**
     * @var SourceInterface
     */
    private $source;
    
    /**
     * @var DispatchResolver
     */
    private $resolver;

    /**
     * @var array
     */
    private $singletonEntries = [];

    /**
     * @var array
     */
    private $requestEntries = [];

    /**
     * @var array
     */
    private $initializers = [];

    public function __construct(SourceInterface $source, ProxyFactory $proxyFactory)
    {
        $this->source = $source;
        $this->resolver = new DispatchResolver($source, $proxyFactory, $this);
    }

    /**
     * Removes '\' at the beginning
     */
    protected function normalize($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException("The name parameter must be of type string");
        }
        return ltrim($name, '\\');
    }

    protected function resolve($name, $reset, $parameters = [])
    {
        $definition = $this->source->get($name);
        if ($definition === null) {
            throw new NotFoundException("Cannot resolve entry '$name'");
        }
        if ($reset) {
            $value = $this->resolver->reset()
                   ->resolve($this, $definition, $parameters);
            $this->resolver->reset();
        } else {
            $value = $this->resolver->resolve($this, $definition, $parameters);
        }
        return [$definition, $value];
    }

    /**
     * @inheritDoc
     */
    public function get($name)
    {
        return $this->resolveShared($name);
    }

    /**
     * @inheritDoc
     */
    public function getShared($name)
    {
        return $this->resolveShared($name, false);
    }

    /**
     * @inheritDoc
     */
    public function set($name, $value)
    {
        if ($this->source instanceof MutableSourceInterface) {
            if (!$value instanceof DefinitionInterface) {
                $value = new ValueDefinition($value);
            }
            unset($this->singletonEntries[$name]);
            $this->source->set($name, $value);
        } elseif ($this->source instanceof CachedSource) {
            throw new LogicException("Cannot change di definition when cache enabled");
        } else {
            throw new LogicException("Cannot change di definition");
        }
    }

    /**
     * @inheritDoc
     */
    public function make($name, $parameters = [])
    {
        $name = $this->normalize($name);
        list ($definition, $value) = $this->resolve($name, true, $parameters);
        if ($value instanceof DeferredObject) {
            $value->initialize();
            $value = $value->getInstance();
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function has($name)
    {
        $name = $this->normalize($name);
        return array_key_exists($name, $this->singletonEntries)
            || array_key_exists($name, $this->requestEntries)
            || $this->source->has($name);
    }

    /**
     * @inheritDoc
     */
    public function hasShared($name)
    {
        $name = $this->normalize($name);
        return array_key_exists($name, $this->singletonEntries)
            || array_key_exists($name, $this->requestEntries);
    }

    /**
     * @inheritDoc
     */
    public function startRequest()
    {
        foreach ($this->requestEntries as $name => $entry) {
            $entry->setProxyInitializer($this->initializers[$name]);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function endRequest()
    {
        return $this;
    }

    private function resolveShared($name, $resetResolver = true)
    {
        $name = $this->normalize($name);
        if (array_key_exists($name, $this->singletonEntries)) {
            return $this->singletonEntries[$name];
        } elseif (array_key_exists($name, $this->requestEntries)) {
            return $this->requestEntries[$name];
        }
        list ($definition, $value) = $this->resolve($name, $resetResolver);
        $scope = $definition->getScope();
        if ($scope === Scope::SINGLETON) {
            $this->singletonEntries[$name] = $value;
        } elseif ($scope === Scope::REQUEST) {
            $this->requestEntries[$name] = $value;
            if (!$value instanceof VirtualProxyInterface) {
                throw new LogicException("Request entry '{$name}' is not object or factory");
            }
            $this->initializers[$name] = $value->getProxyInitializer();
        }
        return $value;
    }
}
