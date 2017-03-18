<?php

namespace kuiper\boot;

interface ProviderInterface
{
    /**
     * @param Application $app
     */
    public function setApplication(Application $app);

    /**
     * Initialize provider.
     */
    public function initialize();

    /**
     * @return Module
     */
    public function getModule();

    /**
     * @param Module
     */
    public function setModule(Module $module);

    /**
     * Registers services.
     */
    public function register();

    /**
     * Bootstraps.
     */
    public function boot();
}
