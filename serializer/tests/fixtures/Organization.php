<?php
namespace kuiper\serializer\fixtures;

class Organization
{
    private $name;

    /**
     * @var array<Member>
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
     * @param $members array<Member>
     * @return static
     */
    public function setMembers(array $members)
    {
        $this->members = $members;
        return $this;
    }

    /**
     * @return array<Member>
     */
    public function getMembers()
    {
        return $this->members;
    }
}
