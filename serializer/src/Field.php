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

final class Field implements \Serializable
{
    private ?ReflectionTypeInterface $type = null;

    private ?string $serializeName = null;

    private bool $public = false;

    private ?string $getter = null;

    private ?string $setter = null;

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
    public function __construct(
        private readonly string $className,
        private readonly string $name)
    {
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

    public function getGetter(): ?string
    {
        return $this->getter;
    }

    public function getSetter(): ?string
    {
        return $this->setter;
    }

    public function getType(): ?ReflectionTypeInterface
    {
        return $this->type;
    }

    public function setType(ReflectionTypeInterface $type): void
    {
        $this->type = $type;
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
                $this->setFunction = static function ($object, $value) use ($property): void {
                    $property->setValue($object, $value);
                };
            }
        }
        call_user_func($this->setFunction, $object, $value);
    }

    public function serialize()
    {
        return null;
    }

    public function unserialize(string $data): void
    {
    }

    public function __serialize(): array
    {
        $data = get_object_vars($this);
        unset($data['getFunction'], $data['setFunction']);
        return $data;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            /** @phpstan-ignore-next-line */
            $this->$key = $value;
        }
    }
}
