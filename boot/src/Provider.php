<?php

namespace kuiper\boot;

abstract class Provider implements ProviderInterface
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Module
     */
    protected $module;

    /**
     * {@inheritdoc}
     */
    public function setApplication(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
    }

    public function getModule()
    {
        if ($this->module === null) {
            $this->setModule($this->createModule());
        }

        return $this->module;
    }

    /**
     * {@inheritdoc}
     */
    public function setModule(Module $module)
    {
        $this->module = $module;
        $this->module->setProvider($this);

        return $this;
    }

    protected function createModule()
    {
        return Module::dummy();
    }

    public function __get($name)
    {
        if ($name === 'settings') {
            return $this->app->getSettings();
        } elseif ($name === 'services') {
            $services = $this->app->getServices();
            $namespace = $this->getModule()->getNamespace();
            if ($namespace) {
                return $services->withNamespace($namespace);
            } else {
                return $services;
            }
        } else {
            throw new \LogicException("Property '$name' is undefined");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
    }
}
