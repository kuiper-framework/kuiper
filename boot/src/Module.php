<?php

namespace kuiper\boot;

class Module
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var ProviderInterface
     */
    private $provider;

    /**
     * @var Module
     */
    private static $DUMMY_MODULE;

    public function __construct($name, $basePath, $namespace = null)
    {
        $this->name = $name;
        $this->basePath = $basePath;
        if (is_string($namespace)) {
            $this->namespace = rtrim($namespace, '\\');
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public static function dummy()
    {
        if (self::$DUMMY_MODULE === null) {
            self::$DUMMY_MODULE = new self(null, null);
        }

        return self::$DUMMY_MODULE;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function setProvider(ProviderInterface $provider)
    {
        $this->provider = $provider;

        return $this;
    }
}
