<?php

namespace kuiper\boot;

use Composer\Autoload\ClassLoader;
use kuiper\annotations\ReaderInterface;
use kuiper\di\ContainerBuilderInterface;
use kuiper\di\ContainerInterface;
use kuiper\helper\DotArray;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface ApplicationInterface
{
    /**
     * @param string $configPath
     *
     * @return self
     */
    public function loadConfig($configPath);

    /**
     * @return DotArray
     */
    public function getSettings();

    /**
     * @param ClassLoader $loader
     *
     * @return self
     */
    public function setLoader(ClassLoader $loader);

    /**
     * @return ContainerBuilderInterface
     */
    public function getServices();

    /**
     * @return Module[]
     */
    public function getModules();

    /**
     * @param bool $useAnnotations
     *
     * @return self
     */
    public function useAnnotations($useAnnotations = true);

    /**
     * @param ReaderInterface $annotationReader
     */
    public function setAnnotationReader(ReaderInterface $annotationReader);

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher);

    /**
     * @param string $eventName
     * @param Event  $event
     */
    public function dispatch($eventName, Event $event = null);

    /**
     * @param ProviderInterface $provider
     *
     * @return self
     */
    public function addProvider(ProviderInterface $provider);

    /**
     * @return ContainerInterface
     */
    public function getContainer();

    /**
     * @return self
     */
    public function bootstrap();

    /**
     * @return mixed
     */
    public function get($id);
}
