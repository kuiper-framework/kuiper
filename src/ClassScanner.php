<?php
namespace kuiper\reflection;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Composer\Autoload\ClassLoader;

/**
 * Scan all classes in given namespace, for example
 *
 *     $loader = require('vendor/autoload.php');
 *     $scanner = new ClassScanner;
 *     $scanner->registerLoader($loader);
 *     foreach ($scanner->scan($namesapce) as $className) {
 *          echo "Found class $className in file " . $scanner->getFile(), "\n";
 *     }
 */
class ClassScanner
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $namespaces = [];

    /**
     * @var bool
     */
    private $usingPackageInfo = false;

    /**
     * @var bool
     */
    private $usingIncludePath = false;

    /**
     * @var array
     */
    private $extensions = ['php'];

    /**
     * @var string
     */
    private $currentFile;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var string
     */
    private $packageInfoClass = 'packageinfo';

    /**
     * Registers directory for the namespace 
     * 
     * @param string $namespace
     * @param string $dir
     * @return static
     */
    public function register($namespace, $dir)
    {
        $namespace = $this->normalizeNamespace($namespace);
        $this->namespaces[$namespace][$dir] = true;
        return $this;
    }

    /**
     * Registers composer class loader.
     * Note only psr4 namespace support
     * 
     * @param ClassLoader $loader
     * @return static
     */
    public function registerLoader(ClassLoader $loader)
    {
        foreach ($loader->getPrefixesPsr4() as $namespace => $dirs) {
            foreach ($dirs as $dir) {
                $this->register($namespace, $dir);
            }
        }
    }

    /**
     * @param bool $enable
     * @return static
     */
    public function enablePackageInfo($enable = true)
    {
        $this->usingPackageInfo = $enable;
        return $this;
    }

    /**
     * @param bool $enable
     * @return static
     */
    public function enableIncludePath($enable = true)
    {
        $this->usingIncludePath = $enable;
        return $this;
    }

    /**
     * @param string $packageInfoClass
     * @return static
     */
    public function setPackageInfoClass($packageInfoClass)
    {
        $this->packageInfoClass = $packageInfoClass;
        return $this;
    }

    /**
     * @return \Generator generate class name
     */
    public function scan($namespace)
    {
        $seen = [];
        $namespace = $this->normalizeNamespace($namespace);
        foreach ($this->namespaces as $prefix => $dirs) {
            if (empty($prefix) || strpos($namespace, $prefix) === 0) {
                foreach (array_keys($dirs) as $dir) {
                    $dir .= '/'.str_replace('\\', '/', substr($namespace, strlen($prefix)));
                    $seen[$dir] = true;
                    foreach ($this->scanPath($dir, $namespace) as $class) {
                        yield $class;
                    }
                }
            }
        }
        if ($this->usingPackageInfo) {
            foreach ($this->allNamespaces($namespace) as $ns) {
                $packageClassName = $ns. $this->packageInfoClass;
                if (class_exists($packageClassName)) {
                    try {
                        $class = new ReflectionClass($packageClassName);
                    } catch (ReflectionException $e) {
                        if ($this->logger) {
                            $this->logger->warning("cannot reflection package class '{$packageClassName}'");
                        }
                        continue;
                    }
                    $dir = dirname($class->getFilename());
                    $dir .= str_replace('\\', '/', substr($namespace, strlen($class->getNamespaceName())));
                    if (!isset($seen[$dir])) {
                        $seen[$dir] = true;
                        foreach ($this->scanPath($dir, $namespace) as $class) {
                            if ($this->getClassSimpleName($class) === $this->packageInfoClass) {
                                continue;
                            }
                            yield $class;
                        }
                    }
                    break;
                }
            }
        }
        if ($this->usingIncludePath) {
            $path = explode("\\", trim($namespace, "\\"));
            foreach (explode(PATH_SEPARATOR, get_include_path()) as $dir) {
                $dir = $dir.'/'.implode('/', $path);
                if (is_dir($dir) && !isset($seen[$dir])) {
                    foreach ($this->scanPath($dir, $namespace) as $class) {
                        yield $class;
                    }
                }
            }
        }
    }

    /**
     * Gets file name for current class
     *
     * @return string
     */
    public function getFile()
    {
        return $this->currentFile;
    }

    protected function allNamespaces($namespace)
    {
        $parts = explode("\\", trim($namespace, "\\"));
        while (!empty($parts)) {
            yield implode("\\", $parts) . "\\";
            array_pop($parts);
        }
    }

    protected function normalizeNamespace($namespace)
    {
        return trim($namespace, "\\") . "\\";
    }

    protected function getClassSimpleName($className)
    {
        $parts = explode("\\", $className);
        return end($parts);
    }

    protected function filter($file, $fileInfo)
    {
        return true;
    }

    protected function scanPath($path, $namespace)
    {
        $found = false;
        foreach ($this->cache as $dir => $classes) {
            if (strpos($path, $dir) === 0) {
                // $path is inside $dir
                foreach ($classes as $class => $file) {
                    if (strpos($class, $namespace) === 0) {
                        $this->currentFile = $file;
                        yield $class;
                    }
                }
                $found = true;
                break;
            }
        }
        if (!$found && is_dir($path)) {
            if ($this->logger) {
                $this->logger->debug("[ClassScanner] scan directory '$path' for namespace $namespace");
            }
            $classes = [];
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($it as $file => $fileInfo) {
                $name = $fileInfo->getFilename();
                if ($name == '.' || $name == '..') {
                    continue;
                }
                if (!in_array($fileInfo->getExtension(), $this->extensions)) {
                    continue;
                }
                if (!$this->filter($file, $fileInfo)) {
                    continue;
                }
                $parser = new ReflectionFile($file);
                foreach ($parser->getClasses() as $class) {
                    $classes[$class] = $file;
                    if (strpos($class, $namespace) === 0) {
                        $this->currentFile = $file;
                        yield $class;
                    }
                }
            }
            $this->cache[$path] = $classes;
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
