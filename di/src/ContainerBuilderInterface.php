<?php

namespace kuiper\di;

use kuiper\annotations\ReaderInterface;
use kuiper\di\source\SourceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface ContainerBuilderInterface
{
    /**
     * @return ContainerInterface
     */
    public function build();

    /**
     * @param array $definitions
     * @param bool  $mergeDeeply
     *
     * @return self
     */
    public function addDefinitions(array $definitions, $mergeDeeply = false);

    /**
     * @param SourceInterface $source
     *
     * @return self
     */
    public function addSource(SourceInterface $source);

    /**
     * Enable or disable the use of annotations to guess injections.
     *
     * Disabled by default.
     *
     * @param bool $annotations
     *
     * @return self
     */
    public function useAnnotations($annotations = true);

    /**
     * Enable or disable the use of autowiring to guess injections.
     *
     * Enabled by default.
     *
     * @param bool $autowiring
     *
     * @return self
     */
    public function useAutowiring($autowiring = true);

    /**
     * @param ReaderInterface $annotationReader
     *
     * @return static
     */
    public function setAnnotationReader(ReaderInterface $annotationReader);

    /**
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return self
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher);

    /**
     * @param ProxyFactory $proxyFactory
     *
     * @return self
     */
    public function setProxyFactory(ProxyFactory $proxyFactory);
}
