<?php

namespace kuiper\serializer\fixtures;

use kuiper\helper\JsonSerializeTrait;

class User implements \JsonSerializable
{
    use JsonSerializeTrait;

    /**
     * @var string
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $birthday;

    /**
     * @var Gender
     */
    private $gender;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param \DateTime $birthday
     *
     * @return $this
     */
    public function setBirthday(\DateTime $birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * @return Gender
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param Gender $gender
     *
     * @return $this
     */
    public function setGender(Gender $gender)
    {
        $this->gender = $gender;

        return $this;
    }
}
