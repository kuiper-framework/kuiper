<?php
namespace kuiper\helper\fixtures;

class User
{
    public $name;

    private $age;

    private $gender;

    private $isFemale;

    public function __construct($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function setAge($age)
    {
        $this->age = $age;
        return $this;
    }

    public function isFemale()
    {
        return $this->isFemale;
    }
}
