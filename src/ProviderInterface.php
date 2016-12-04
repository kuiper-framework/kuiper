<?php
namespace kuiper\boot;

interface ProviderInterface
{
    /**
     * Constructs provider
     * 
     * @param Application $app
     */
    public function __construct(Application $app);

    /**
     * Registers services
     */
    public function register();

    /**
     * Bootstraps
     */
    public function boot();
}