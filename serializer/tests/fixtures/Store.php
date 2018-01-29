<?php

namespace kuiper\serializer\fixtures;

/**
 * Class Store
 * Test isXX, hasXX method.
 */
class Store
{
    /**
     * @var bool
     */
    private $open;

    /**
     * @var bool
     */
    private $admin;

    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * @param bool $open
     *
     * @return Store
     */
    public function setOpen(bool $open): Store
    {
        $this->open = $open;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasAdmin(): bool
    {
        return $this->admin;
    }

    /**
     * @param bool $admin
     *
     * @return Store
     */
    public function setAdmin(bool $admin): Store
    {
        $this->admin = $admin;

        return $this;
    }
}
