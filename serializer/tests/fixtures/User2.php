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

namespace kuiper\serializer\fixtures;

class User2
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $birthday;

    /**
     * @var EnumGender
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
     * @return $this
     */
    public function setBirthday(\DateTime $birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * @return EnumGender
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @return $this
     */
    public function setGender(EnumGender $gender)
    {
        $this->gender = $gender;

        return $this;
    }
}
