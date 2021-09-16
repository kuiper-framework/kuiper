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

namespace kuiper\serializer;

use kuiper\reflection\ReflectionTypeInterface;

class Field implements \Serializable
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $serializeName;

    /**
     * @var bool
     */
    private $public = false;

    /**
     * @var string|null
     */
    private $getter;

    /**
     * @var string|null
     */
    private $setter;

    /**
     * @var ReflectionTypeInterface|null
     */
    private $type;

    /**
     * @var callable|null
     */
    private $getFunction;

    /**
     * @var callable|null
     */
    private $setFunction;

    /**
     * Field constructor.
     */
    public function __construct(string $className, string $name)
    {
        $this->className = $className;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSerializeName(): string
    {
        return $this->serializeName ?? $this->name;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * @return string
     */
    public function getGetter(): ?string
    {
        return $this->getter;
    }

    /**
     * @return string
     */
    public function getSetter(): ?string
    {
        return $this->setter;
    }

    public function getType(): ReflectionTypeInterface
    {
        return $this->type;
    }

    public function setSerializeName(string $serializeName): void
    {
        $this->serializeName = $serializeName;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    public function setGetter(string $getter): void
    {
        $this->getter = $getter;
    }

    public function setSetter(string $setter): void
    {
        $this->setter = $setter;
    }

    public function setType(ReflectionTypeInterface $type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getValue(object $object)
    {
        if (null === $this->getFunction) {
            if ($this->public) {
                $this->getFunction = function ($object) {
                    /* @phpstan-ignore-next-line */
                    return $object->{$this->name};
                };
            } elseif (null !== $this->getter) {
                $this->getFunction = function ($object) {
                    /* @phpstan-ignore-next-line */
                    return $object->{$this->getter}();
                };
            } else {
                $property = new \ReflectionProperty($this->className, $this->name);
                $property->setAccessible(true);
                $this->getFunction = static function ($object) use ($property) {
                    return $property->getValue($object);
                };
            }
        }

        return call_user_func($this->getFunction, $object);
    }

    /**
     * @param mixed $value
     *
     * @throws \ReflectionException
     */
    public function setValue(object $object, $value): void
    {
        if (null === $this->setFunction) {
            if ($this->public) {
                $this->setFunction = function ($object, $value): void {
                    /* @phpstan-ignore-next-line */
                    $object->{$this->name} = $value;
                };
            } elseif (null !== $this->setter) {
                $this->setFunction = function ($object, $value): void {
                    /* @phpstan-ignore-next-line */
                    $object->{$this->setter}($value);
                };
            } else {
                $property = new \ReflectionProperty($this->className, $this->name);
                $property->setAccessible(true);
                $this->setFunction = static function ($object, $value) use ($property): void {
                    $property->setValue($object, $value);
                };
            }
        }
        call_user_func($this->setFunction, $object, $value);
    }

    /**
     * String representation of object.
     *
     * @see http://php.net/manual/en/serializable.serialize.php
     *
     * @return string the string representation of the object or null
     *
     * @since 5.1.0
     */
    public function serialize()
    {
        $vars = get_object_vars($this);
        unset($vars['getFunction']);
        unset($vars['setFunction']);

        return serialize($vars);
    }

    /**
     * Constructs the object.
     *
     * @see http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized <p>
     *                           The string representation of the object.
     *                           </p>
     *
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $key => $val) {
            /* @phpstan-ignore-next-line */
            $this->$key = $val;
        }
    }
}
