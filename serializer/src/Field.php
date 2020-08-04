<?php

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
     * @var string
     */
    private $serializeName;

    /**
     * @var bool
     */
    private $isPublic = false;

    /**
     * @var string
     */
    private $getter;

    /**
     * @var string
     */
    private $setter;

    /**
     * @var ReflectionTypeInterface
     */
    private $type;

    /**
     * @var callable
     */
    private $getFunction;

    /**
     * @var callable
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
        return $this->serializeName ?: $this->name;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    /**
     * @return string
     */
    public function getGetter()
    {
        return $this->getter;
    }

    /**
     * @return string
     */
    public function getSetter()
    {
        return $this->setter;
    }

    public function getType(): ReflectionTypeInterface
    {
        return $this->type;
    }

    /**
     * @return $this
     */
    public function setSerializeName(string $serializeName)
    {
        $this->serializeName = $serializeName;

        return $this;
    }

    /**
     * @return $this
     */
    public function setIsPublic(bool $isPublic)
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * @return $this
     */
    public function setGetter(string $getter)
    {
        $this->getter = $getter;

        return $this;
    }

    /**
     * @return $this
     */
    public function setSetter(string $setter)
    {
        $this->setter = $setter;

        return $this;
    }

    /**
     * @return $this
     */
    public function setType(ReflectionTypeInterface $type)
    {
        $this->type = $type;

        return $this;
    }

    public function getValue($object)
    {
        if (!$this->getFunction) {
            if ($this->isPublic) {
                $this->getFunction = function ($object) {
                    return $object->{$this->name};
                };
            } elseif ($this->getter) {
                $this->getFunction = function ($object) {
                    return $object->{$this->getter}();
                };
            } else {
                $property = new \ReflectionProperty($this->className, $this->name);
                $property->setAccessible(true);
                $this->getFunction = function ($object) use ($property) {
                    return $property->getValue($object);
                };
            }
        }

        return call_user_func($this->getFunction, $object);
    }

    public function setValue($object, $value)
    {
        if (!$this->setFunction) {
            if ($this->isPublic) {
                $this->setFunction = function ($object, $value) {
                    $object->{$this->name} = $value;
                };
            } elseif ($this->setter) {
                $this->setFunction = function ($object, $value) {
                    $object->{$this->setter}($value);
                };
            } else {
                $property = new \ReflectionProperty($this->className, $this->name);
                $property->setAccessible(true);
                $this->setFunction = function ($object, $value) use ($property) {
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
            $this->$key = $val;
        }
    }
}
