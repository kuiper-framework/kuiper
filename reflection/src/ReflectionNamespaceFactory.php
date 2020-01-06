<?php

namespace kuiper\reflection;

use Composer\Autoload\ClassLoader;

class ReflectionNamespaceFactory implements ReflectionNamespaceFactoryInterface
{
    /**
     * @var ReflectionNamespaceFactory
     */
    private static $INSTANCE;

    /**
     * @var ReflectionFileFactoryInterface
     */
    private $reflectionFileFactory;

    /**
     * @var ReflectionNamespace[]
     */
    private $namespaces = [];

    /**
     * @var array
     */
    private $namespaceDirs = [];

    /**
     * @var string[]
     */
    private $extensions = ['php'];

    /**
     * {@inheritdoc}
     */
    public static function createInstance(ReflectionFileFactoryInterface $reflectionFileFactory = null)
    {
        if (!isset(self::$INSTANCE)) {
            self::$INSTANCE = new self($reflectionFileFactory);
        }

        return self::$INSTANCE;
    }

    private function __construct(ReflectionFileFactoryInterface $factory = null)
    {
        $this->reflectionFileFactory = $factory ?: ReflectionFileFactory::createInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function create($namespace): ReflectionNamespaceInterface
    {
        $namespace = $this->normalizeNamespace($namespace);
        if (isset($this->namespaces[$namespace])) {
            return $this->namespaces[$namespace];
        }

        $dirs = [];
        foreach ($this->namespaceDirs as $prefix => $prefixDirs) {
            if (empty($prefix) || 0 === strpos($namespace, $prefix)) {
                foreach (array_keys($prefixDirs) as $dir) {
                    $dir .= '/' . str_replace('\\', '/', substr($namespace, strlen($prefix)));
                    $dirs[] = $dir;
                }
            }
        }

        return $this->namespaces[$namespace]
            = new ReflectionNamespace($namespace, $dirs, $this->extensions, $this->reflectionFileFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache($namespace = null): void
    {
        if (isset($namespace)) {
            unset($this->namespaces[$this->normalizeNamespace($namespace)]);
        } else {
            $this->namespaces = [];
        }
    }

    /**
     * Registers directory for the namespace.
     *
     * @param string $namespace
     * @param string $dir
     *
     * @return self
     */
    public function register($namespace, $dir): self
    {
        $namespace = $this->normalizeNamespace($namespace);
        $this->namespaceDirs[$namespace][$dir] = true;

        return $this;
    }

    /**
     * Registers composer class loader.
     * Only psr4 namespace support.
     *
     * @param ClassLoader $loader
     *
     * @return self
     */
    public function registerLoader(ClassLoader $loader): self
    {
        foreach ($loader->getPrefixesPsr4() as $namespace => $dirs) {
            foreach ($dirs as $dir) {
                $this->register($namespace, $dir);
            }
        }

        return $this;
    }

    /**
     * Adds new php code file extension.
     *
     * @param string $ext
     *
     * @return self
     */
    public function addExtension(string $ext): self
    {
        if (!in_array($ext, $this->extensions, true)) {
            $this->extensions[] = $ext;
        }

        return $this;
    }

    /**
     * Sets php code file extension list.
     *
     * @param string[] $extensions
     *
     * @return self
     */
    public function setExtensions(array $extensions): self
    {
        $this->extensions = array_unique($extensions);

        return $this;
    }

    private function normalizeNamespace(string $namespace): string
    {
        return trim($namespace, ReflectionNamespaceInterface::NAMESPACE_SEPARATOR) . ReflectionNamespaceInterface::NAMESPACE_SEPARATOR;
    }
}
