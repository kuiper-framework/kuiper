<?php

namespace kuiper\boot;

/**
 * @property \kuiper\di\ContainerBuilderInterface services
 * @property \ArrayAccess settings
 */
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
            return $this->app->getServices();
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
