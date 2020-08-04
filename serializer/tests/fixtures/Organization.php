<?php

declare(strict_types=1);

namespace kuiper\serializer\fixtures;

class Organization
{
    private $name;

    /**
     * @var Member[]
     */
    private $members;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $members Member[]
     *
     * @return static
     */
    public function setMembers(array $members)
    {
        $this->members = $members;

        return $this;
    }

    /**
     * @return Member[]
     */
    public function getMembers()
    {
        return $this->members;
    }
}
