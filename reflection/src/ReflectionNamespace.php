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

use kuiper\reflection\exception\ReflectionException;

class ReflectionNamespace implements ReflectionNamespaceInterface
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string[]
     */
    private $dirs;

    /**
     * @var ReflectionFileFactoryInterface
     */
    private $reflectionFileFactory;

    /**
     * @var string[]
     */
    private $extensions;

    /**
     * @var string[]|null
     */
    private $classes;

    public function __construct(string $namespace, array $dirs, array $extensions, ReflectionFileFactoryInterface $reflectionFileFactory)
    {
        $this->namespace = trim($namespace, self::NAMESPACE_SEPARATOR);
        $this->dirs = $dirs;
        $this->extensions = $extensions;
        $this->reflectionFileFactory = $reflectionFileFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses(): array
    {
        if (isset($this->classes)) {
            return $this->classes;
        }
        $classes = [];
        $seen = [];
        foreach ($this->dirs as $dir) {
            $dir = realpath($dir);
            if (false === $dir) {
                continue;
            }
            if (isset($seen[$dir])) {
                continue;
            }
            $seen[$dir] = true;
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($it as $file => $fileInfo) {
                $name = $fileInfo->getFilename();
                if ('.' === $name[0]) {
                    continue;
                }
                if (!in_array($fileInfo->getExtension(), $this->extensions, true)) {
                    continue;
                }
                $reflectionFile = $this->reflectionFileFactory->create($file);
                try {
                    foreach ($reflectionFile->getClasses() as $class) {
                        if (0 === strpos($class, $this->namespace)) {
                            $classes[] = $class;
                        }
                    }
                } catch (ReflectionException $e) {
                    throw new ReflectionException("fail to parse file '$file'".$e->getMessage(), 0, $e);
                }
            }
        }

        return $this->classes = array_unique($classes);
    }
}
