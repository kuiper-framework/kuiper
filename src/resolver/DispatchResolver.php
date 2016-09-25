<?php
namespace kuiper\di\resolver;

use Interop\Container\ContainerInterface;
use kuiper\di\DefinitionEntry;
use kuiper\di\definition\ValueDefinition;
use kuiper\di\definition\AliasDefinition;
use kuiper\di\definition\EnvDefinition;
use kuiper\di\definition\StringDefinition;
use kuiper\di\definition\ArrayDefinition;
use kuiper\di\definition\FactoryDefinition;
use kuiper\di\definition\ObjectDefinition;
use kuiper\di\ProxyFactory;
use kuiper\di\DeferredObject;
use kuiper\di\Container;
use kuiper\di\Scope;
use kuiper\di\exception\DefinitionException;
use kuiper\di\exception\DependencyException;
use kuiper\di\exception\NotFoundException;
use kuiper\di\source\SourceInterface;

class DispatchResolver implements ResolverInterface
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
     * @var Container
     */
    private $container;

    /**
     * @var EnvResolver
     */
    private $envResolver;

    /**
     * @var StringResolver
     */
    private $stringResolver;

    /**
     * @var ArrayResolver
     */
    private $arrayResolver;

    /**
     * @var FactoryResolver
     */
    private $factoryResolver;

    /**
     * @var ObjectResolver
     */
    private $objectResolver;

    /**
     * @var array
     */
    private $resolving;

    /**
     * @var array
     */
    private $values = [];
    
    public function __construct(SourceInterface $source, ProxyFactory $proxyFactory, Container $container)
    {
        $this->source = $source;
        $this->proxyFactory = $proxyFactory;
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function resolve(ContainerInterface $container, DefinitionEntry $entry, $parameters = [])
    {
        $name = $entry->getName();
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }
        if (isset($this->resolving[$name])) {
            throw new DependencyException("Circular dependency detected while trying to resolve entry '$name'");
        }
        $this->resolving[$name] = true;

        $definition = $entry->getDefinition();
        if ($definition instanceof ValueDefinition) {
            $value = $definition->getValue();
        } elseif ($definition instanceof AliasDefinition) {
            $aliasEntry = $this->source->get($definition->getAlias());
            if ($aliasEntry === null) {
                throw new NotFoundException(sprintf("Cannot resolve entry '%s'", $definition->getAlias()));
            }
            $aliasDefinition = $aliasEntry->getDefinition();
            if ($aliasDefinition->getScope() !== Scope::PROTOTYPE) {
                $value = $this->container->getShared($aliasEntry->getName());
            } else {
                $value = $this->resolve($container, $aliasEntry, $parameters);
            } 
        } elseif ($definition instanceof EnvDefinition) {
            $resolver = $this->getEnvResolver();
        } elseif ($definition instanceof StringDefinition) {
            $resolver = $this->getStringResolver();
        } elseif ($definition instanceof ArrayDefinition) {
            $resolver = $this->getArrayResolver();
        } elseif ($definition instanceof FactoryDefinition) {
            $resolver = $this->getFactoryResolver();
        } elseif ($definition instanceof ObjectDefinition) {
            $resolver = $this->getObjectResolver();
        } elseif ($definition instanceof ResolvableInterface) {
            $resolver = $definition->getResolver($this);
        } else {
            throw new DefinitionException("Cannot resolve definition " . get_class($definition));
        }
        if (isset($resolver)) {
            $value = $resolver->resolve($container, $entry, $parameters);
        }
        if ($value instanceof DeferredObject) {
            $deferred = $value;
            $value = $deferred->getInstance();
        }
        $this->values[$name] = $value;

        unset($this->resolving[$name]);
        if (isset($deferred)) {
            $deferred->initialize();
        }
        return $value;
    }

    public function reset()
    {
        $this->values = [];
        return $this;
    }

    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }

    protected function getEnvResolver()
    {
        if ($this->envResolver === null) {
            $this->envResolver = new EnvResolver();
        }
        return $this->envResolver;
    }

    protected function getStringResolver()
    {
        if ($this->stringResolver === null) {
            $this->stringResolver = new StringResolver();
        }
        return $this->stringResolver;
    }

    protected function getArrayResolver()
    {
        if ($this->arrayResolver === null) {
            $this->arrayResolver = new ArrayResolver($this);
        }
        return $this->arrayResolver;
    }

    public function getFactoryResolver()
    {
        if ($this->factoryResolver === null) {
            $this->factoryResolver = new FactoryResolver($this, $this->proxyFactory);
        }
        return $this->factoryResolver;
    }

    public function getObjectResolver()
    {
        if ($this->objectResolver === null) {
            $this->objectResolver = new ObjectResolver($this, $this->proxyFactory);
        }
        return $this->objectResolver;
    }
}
