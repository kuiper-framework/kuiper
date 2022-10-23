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

namespace kuiper\reflection;

use Composer\Autoload\ClassLoader;

class ReflectionNamespaceFactory implements ReflectionNamespaceFactoryInterface
{
    private static ?ReflectionNamespaceFactory $INSTANCE;

    /**
     * @var ReflectionNamespace[]
     */
    private array $namespaces = [];

    /**
     * @var array
     */
    private array $namespaceDirs = [];

    /**
     * @var string[]
     */
    private array $extensions = ['php'];

    public static function createInstance(ReflectionFileFactoryInterface $reflectionFileFactory): void
    {
        self::$INSTANCE = new self($reflectionFileFactory);
    }

    public static function getInstance(): ReflectionNamespaceFactoryInterface
    {
        if (!isset(self::$INSTANCE)) {
            self::createInstance(ReflectionFileFactory::getInstance());
        }

        return self::$INSTANCE;
    }

    public function __construct(private readonly ReflectionFileFactoryInterface $reflectionFileFactory)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $namespace): ReflectionNamespaceInterface
    {
        $namespace = $this->normalizeNamespace($namespace);
        if (isset($this->namespaces[$namespace])) {
            return $this->namespaces[$namespace];
        }

        $dirs = [];
        foreach ($this->namespaceDirs as $prefix => $prefixDirs) {
            if (empty($prefix) || str_starts_with($namespace, $prefix)) {
                foreach (array_keys($prefixDirs) as $dir) {
                    $dir .= '/'.str_replace('\\', '/', substr($namespace, strlen($prefix)));
                    $dirs[] = $dir;
                }
            }
        }

        $namespaceTrimmed = trim($namespace, ReflectionNamespaceInterface::NAMESPACE_SEPARATOR);

        return $this->namespaces[$namespace]
            = new ReflectionNamespace($namespaceTrimmed, $dirs, $this->extensions, $this->reflectionFileFactory);
    }

    public function clearCache(string $namespace = null): void
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
     * @return static
     */
    public function register(string $namespace, string $dir): static
    {
        $namespace = $this->normalizeNamespace($namespace);
        $this->namespaceDirs[$namespace][$dir] = true;

        return $this;
    }

    /**
     * Registers composer class loader.
     * Only psr4 namespace support.
     */
    public function registerLoader(ClassLoader $loader): static
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
     */
    public function addExtension(string $ext): static
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
     */
    public function setExtensions(array $extensions): static
    {
        $this->extensions = array_unique($extensions);

        return $this;
    }

    private function normalizeNamespace(string $namespace): string
    {
        return trim($namespace, ReflectionNamespaceInterface::NAMESPACE_SEPARATOR)
            .ReflectionNamespaceInterface::NAMESPACE_SEPARATOR;
    }
}
