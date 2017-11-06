<?php

namespace kuiper\reflection;

use kuiper\reflection\exception\SyntaxErrorException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
     * @var string[]
     */
    private $classes;

    public function __construct($namespace, array $dirs, array $extensions, ReflectionFileFactoryInterface $reflectionFileFactory)
    {
        $this->namespace = $namespace;
        $this->dirs = $dirs;
        $this->extensions = $extensions;
        $this->reflectionFileFactory = $reflectionFileFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return rtrim($this->namespace, '\\');
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses()
    {
        if (isset($this->classes)) {
            return $this->classes;
        }
        $classes = [];
        $seen = [];
        foreach ($this->dirs as $dir) {
            $dir = realpath($dir);
            if ($dir === false) {
                continue;
            }
            if (isset($seen[$dir])) {
                continue;
            }
            $seen[$dir] = true;
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($it as $file => $fileInfo) {
                $name = $fileInfo->getFilename();
                if ($name[0] == '.') {
                    continue;
                }
                if (!in_array($fileInfo->getExtension(), $this->extensions)) {
                    continue;
                }
                $reflectionFile = $this->reflectionFileFactory->create($file);
                try {
                    foreach ($reflectionFile->getClasses() as $class) {
                        if (strpos($class, $this->namespace) === 0) {
                            $classes[] = $class;
                        }
                    }
                } catch (SyntaxErrorException $e) {
                    trigger_error($e->getMessage());
                }
            }
        }

        return $this->classes = array_unique($classes);
    }
}
