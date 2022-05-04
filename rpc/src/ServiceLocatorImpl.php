<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\rpc;

class ServiceLocator
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
    private $version;

    /**
     * ServiceLocator constructor.
     *
     * @param string $name
     * @param string $namespace
     * @param string $version
     */
    public function __construct(string $name, string $namespace = 'default', string $version = '1.0')
    {
        $this->name = $name;
        $this->namespace = $namespace;
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    public function __toString()
    {
        return "{$this->namespace}/{$this->name}:{$this->version}";
    }

    public static function fromString(string $str): self
    {
        $nsStop = strpos($str, '/');
        $versionStop = strrpos($str, ':');
        if (false === $nsStop) {
            $namespace = 'default';
            $nsStop = -1;
        } else {
            $namespace = substr($str, 0, $nsStop);
        }
        if (false === $versionStop) {
            $version = '1.0';
            $versionStop = strlen($str);
        } else {
            $version = substr($str, $versionStop + 1);
        }

        return new self(substr($str, $nsStop + 1, $versionStop - $nsStop - 1), $namespace, $version);
    }

    public function equals(ServiceLocator $other): bool
    {
        return $this->name === $other->name
            && $this->namespace === $other->namespace
            && $this->version === $other->version;
    }
}
