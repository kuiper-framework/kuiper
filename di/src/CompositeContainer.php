<?php

namespace kuiper\di;

use kuiper\di\exception\NotFoundException;

class CompositeContainer implements ContainerInterface
{
    const VALUE = 0;
    const CHILDREN = 1;

    /**
     * @var ContainerInterface[]
     */
    private $containers;

    /**
     * @var array
     */
    private $namespaces;

    public function __construct(array $containers)
    {
        $this->containers = $containers;
        $this->buildNamespaces();
    }

    public function hasNamespace($namespace)
    {
        return isset($this->containers[$namespace]);
    }

    public function withNamespace($namespace)
    {
        if (!isset($this->containers[$namespace])) {
            throw new \InvalidArgumentException("Unknown namespace '$namespace'");
        }

        return $this->containers[$namespace];
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (strpos($id, '\\') !== false) {
            $container = $this->getContainer($id);
            if ($container) {
                return $container->get($id);
            }
        }
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }
        throw new NotFoundException("Cannot resolve entry '$id'");
    }

    /**
     * {@inheritdoc}
     */
    public function has($id, $onlyDefined = false)
    {
        foreach ($this->containers as $container) {
            if ($container->has($id, $onlyDefined)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function make($id, $parameters = [])
    {
        if (strpos($id, '\\') !== false) {
            $container = $this->getContainer($id);
            if ($container) {
                return $container->make($id, $parameters);
            }
        }
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container->make($id, $parameters);
            }
        }
        throw new NotFoundException("Cannot resolve entry '$id'");
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $definition)
    {
        current($this->containers)->set($name, $definition);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function startRequest()
    {
        foreach ($this->containers as $container) {
            $container->startRequest();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function endRequest()
    {
        foreach ($this->containers as $container) {
            $container->endRequest();
        }
    }

    private function buildNamespaces()
    {
        $namespaces = [];

        foreach (array_keys($this->containers) as $namespace) {
            $parts = explode('\\', $namespace);
            $last = array_pop($parts);
            unset($current);
            $current = &$namespaces;
            foreach ($parts as $part) {
                if (!isset($current[self::CHILDREN][$part])) {
                    $current[self::CHILDREN][$part] = [null, []];
                }
                $current = &$current[self::CHILDREN][$part];
            }
            if (isset($current[self::CHILDREN][$last])) {
                $current[self::CHILDREN][$last][self::VALUE] = $namespace;
            } else {
                $current[self::CHILDREN][$last] = [$namespace, []];
            }
        }
        $this->namespaces = $namespaces;
    }

    private function getContainer($id)
    {
        $parts = explode('\\', $id);
        $current = $this->namespaces;
        $namespaces = [];
        foreach ($parts as $part) {
            if (isset($current[self::VALUE])) {
                $namespaces[] = $current[self::VALUE];
            }
            if (!isset($current[self::CHILDREN][$part])) {
                break;
            }
            $current = $current[self::CHILDREN][$part];
        }
        foreach (array_reverse($namespaces) as $namespace) {
            if ($this->containers[$namespace]->has($id)) {
                return $this->containers[$namespace];
            }
        }
    }
}
