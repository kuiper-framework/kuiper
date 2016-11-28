<?php
namespace kuiper\boot;

use Composer\Autoload\ClassLoader;
use kuiper\di\ContainerBuilder;
use kuiper\helper\DotArray;

class Application
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
     * @var ContainerBuilder
     */
    private $containerBuilder;

    public function loadConfig($configPath)
    {
        $config = [];
        foreach (glob($configPath . '/*.php') as $file) {
            $prefix = basename($file, '.php');
            $config[$prefix] = require($file);
        }
        return $this->setSettings($config);
    }
    
    public function setSettings(array $config)
    {
        $this->settings = new DotArray($config);
        return $this;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function setLoader(ClassLoader $loader)
    {
        $this->loader = $loader;
        return $this;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    public function setContainerBuilder(ContainerBuilder $builder)
    {
        $this->containerBuilder = $builder;
        return $this;
    }

    public function getContainerBuilder()
    {
        if ($this->containerBuilder === null) {
            $this->setContainerBuilder(new ContainerBuilder());
        }
        return $this->containerBuilder;
    }

    public function getServices()
    {
        return $this->getContainerBuilder();
    }

    public function addProvider(ProviderInterface $provider)
    {
        $this->providers[] = $provider;
        return $this;
    }

    public function get($id)
    {
        return $this->getContainer()->get($id);
    }

    public function getContainer()
    {
        if ($this->container === null) {
            $this->container = $this->buildContainer();
        }
        return $this->container;
    }

    private function buildContainer()
    {
        $providers = $this->settings['app.providers'];
        if ($providers) {
            foreach ($providers as $provider) {
                $this->addProvider(new $provider($this));
            }
        }
        foreach ($this->providers as $provider) {
            $provider->register();
        }
        return $this->getContainerBuilder()->build();
    }
}
