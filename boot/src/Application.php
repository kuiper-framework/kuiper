<?php

namespace kuiper\boot;

use Composer\Autoload\ClassLoader;
use kuiper\annotations\AnnotationReader;
use kuiper\annotations\ReaderInterface;
use kuiper\di\CompositeContainerBuilder;
use kuiper\di\ContainerBuilderInterface;
use kuiper\di\source\DotArraySource;
use kuiper\helper\DotArray;
use kuiper\reflection\ReflectionNamespaceFactory;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Application implements ApplicationInterface
{
    /**
     * @var DotArray
     */
    private $settings;

    /**
     * @var ClassLoader
     */
    private $loader;

    /**
     * @var \kuiper\di\Container
     */
    private $container;

    /**
     * @var ContainerBuilderInterface
     */
    private $containerBuilder;

    /**
     * @var Provider[]
     */
    private $providers = [];

    /**
     * @var Module[]
     */
    private $modules = [];

    /**
     * @var bool
     */
    private $bootstrap = false;

    /**
     * @var bool
     */
    private $useAnnotations = false;

    /**
     * @var ReaderInterface
     */
    private $annotationReader;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string[]
     */
    private $configPaths;

    /**
     * {@inheritdoc}
     */
    public function loadConfig($configPath)
    {
        if (!isset($this->configPaths[$configPath])) {
            $this->getSettings()->merge($this->readConfig($configPath));
            $this->configPaths[$configPath] = true;
        }

        return $this;
    }

    public function readConfig($configPath)
    {
        $config = [];
        foreach (glob($configPath.'/*.php') as $file) {
            $prefix = basename($file, '.php');
            /* @noinspection PhpIncludeInspection */
            $config[$prefix] = require $file;
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings()
    {
        if ($this->settings === null) {
            $this->settings = new DotArray();
        }

        return $this->settings;
    }

    /**
     * {@inheritdoc}
     */
    public function setLoader(ClassLoader $loader)
    {
        $this->loader = $loader;
        ReflectionNamespaceFactory::createInstance()
            ->registerLoader($loader);

        return $this;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    public function setContainerBuilder(ContainerBuilderInterface $builder)
    {
        $this->containerBuilder = $builder;
        $this->containerBuilder->addDefinitions([
            ApplicationInterface::class => $this,
        ]);
        $this->containerBuilder->setEventDispatcher($this->getEventDispatcher());

        return $this;
    }

    /**
     * @return ContainerBuilderInterface
     */
    public function getContainerBuilder()
    {
        if ($this->containerBuilder === null) {
            $this->setContainerBuilder(new CompositeContainerBuilder());
        }

        return $this->containerBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getServices()
    {
        return $this->getContainerBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * {@inheritdoc}
     */
    public function useAnnotations($annotations = true)
    {
        $this->useAnnotations = $annotations;
        $this->getContainerBuilder()->useAnnotations($annotations);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addProvider(ProviderInterface $provider)
    {
        $this->providers[] = $provider;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return $this->getContainer()->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        if (!$this->bootstrap) {
            throw new \RuntimeException('Application does not bootstrap');
        }

        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function bootstrap()
    {
        if (!$this->bootstrap) {
            $this->registerProviders();
            $this->container = $this->getContainerBuilder()->build();
            $this->bootstrap = true;

            foreach ($this->providers as $provider) {
                $provider->boot();
            }
        }

        return $this;
    }

    public function getAnnotationReader()
    {
        if ($this->annotationReader === null) {
            $this->setAnnotationReader(new AnnotationReader());
        }

        return $this->annotationReader;
    }

    public function setAnnotationReader(ReaderInterface $annotationReader)
    {
        $this->annotationReader = $annotationReader;
        $this->getContainerBuilder()->setAnnotationReader($annotationReader);

        return $this;
    }

    public function getEventDispatcher()
    {
        if ($this->eventDispatcher === null) {
            $this->setEventDispatcher(new EventDispatcher());
        }

        return $this->eventDispatcher;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function dispatch($eventName, Event $event = null)
    {
        $this->getEventDispatcher()->dispatch($eventName, $event);

        return $this;
    }

    protected function expandSettings($settings)
    {
        $re = '#\{([^\{\}]+)\}#';
        foreach ($settings as $key => $value) {
            if (is_string($value) && preg_match($re, $value)) {
                do {
                    $value = preg_replace_callback($re, function (array $matches) use ($settings) {
                        $name = $matches[1];
                        if (!isset($settings[$name])) {
                            throw new \RuntimeException("Unknown config entry: '$name'");
                        }

                        return $settings[$name];
                    }, $value);
                } while (preg_match($re, $value));

                $settings[$key] = $value;
            }
        }
    }

    protected function registerProviders()
    {
        $settings = $this->getSettings();
        $providers = $settings['app.providers'];
        if ($providers && is_array($providers)) {
            foreach ($providers as $provider) {
                if (is_subclass_of($provider, ProviderInterface::class)) {
                    $this->addProvider(new $provider());
                } else {
                    throw new \UnexpectedValueException("$provider is not instanceof ".ProviderInterface::class);
                }
            }
        }
        foreach ($this->providers as $provider) {
            $provider->setApplication($this);
            $this->registerModule($provider);
        }
        $this->expandSettings($settings);
        $this->getContainerBuilder()->addSource(new DotArraySource($settings));
        foreach ($this->providers as $provider) {
            $provider->register();
        }
    }

    /**
     * @param ProviderInterface $provider
     */
    protected function registerModule(ProviderInterface $provider)
    {
        $module = $provider->getModule();
        if ($module === Module::dummy()) {
            if ($this->useAnnotations) {
                $module = $this->createModuleFromAnnotation($provider);
            }
        }
        if (!$module || !$module->getName()) {
            return;
        }

        if (isset($this->modules[$module->getName()])) {
            throw new \RuntimeException(sprintf(
                "Conflict module name '%s' for %s, previous provider is %s",
                $module->getName(), get_class($provider), get_class($this->modules[$module->getName()]->getProvider())
            ));
        }
        if ($module->getBasePath()) {
            $this->loadModuleConfig($module);
            if (!isset($this->settings[$module->getName().'.base_path'])) {
                $this->settings[$module->getName().'.base_path'] = $module->getBasePath();
            }
        }
        $namespace = $module->getNamespace();
        $containerBuilder = $this->getServices();
        if ($namespace && $containerBuilder instanceof CompositeContainerBuilder) {
            $containerBuilder->withNamespace($namespace)->addDefinitions([
                Module::class => $module,
            ]);
        }
        $this->modules[$module->getName()] = $module;
    }

    /**
     * 根据 Module 注解和 composer.json 文件信息构造 Module 对象
     *
     * @param ProviderInterface $provider
     *
     * @return Module
     */
    protected function createModuleFromAnnotation(ProviderInterface $provider)
    {
        $class = new \ReflectionClass($provider);
        $annotation = $this->getAnnotationReader()->getClassAnnotation($class, annotation\Module::class);
        if (!$annotation) {
            return null;
        }
        try {
            $info = $this->readComposerInfo(dirname($class->getFilename()));
        } catch (\RuntimeException $e) {
            if (empty($annotation->name)) {
                throw new \RuntimeException('Cannot find composer.json for '.get_class($provider));
            }
        }
        foreach (get_object_vars($annotation) as $key => $val) {
            if (empty($annotation->$key) && !empty($info[$key])) {
                $annotation->$key = $info[$key];
            }
        }
        $module = new Module($annotation->name, $annotation->basePath, $annotation->namespace);
        $provider->setModule($module);

        return $module;
    }

    /**
     * 从 composer.json 文件中读取模块信息:
     *  - basePath composer.json 文件所在目录
     *  - name composer package 名字全替换为 _ 格式，如 foo/bar-baz 模块名为 foo_bar_baz
     *  - namespace autoload 中 psr-4 第一个名字空间.
     *
     * @param string $path
     *
     * @return array
     */
    protected function readComposerInfo($path)
    {
        if (!file_exists($path.'/composer.json')) {
            $parent = dirname($path);
            if ($parent == $path) {
                throw new \RuntimeException('Cannot find composer.json');
            }

            return $this->readComposerInfo(dirname($path));
        }
        $data = json_decode(file_get_contents($path.'/composer.json'), true);
        $info = ['basePath' => $path];
        if (isset($data['name'])) {
            $info['name'] = str_replace(['/', '-'], '_', $data['name']);
        }
        if (isset($data['autoload']['psr-4'])) {
            $namespaces = array_keys($data['autoload']['psr-4']);
            if (!empty($namespaces)) {
                $info['namespace'] = $namespaces[0];
            }
        }

        return $info;
    }

    protected function loadModuleConfig(Module $module)
    {
        $configDir = $module->getBasePath().'/config';
        if (!is_dir($configDir)) {
            return;
        }
        $this->loadConfig($configDir);
    }
}
