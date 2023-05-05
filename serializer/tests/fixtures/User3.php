<?php

namespace kuiper\serializer\fixtures;

class User3
{

    public readonly string $id;

    public readonly \DateTimeInterface $birthday;

    public readonly Gender $gender;

    /**
     * @param string $id
     * @param \DateTimeInterface $birthday
     * @param Gender $gender
     */
    public function __construct(string $id, \DateTimeInterface $birthday, Gender $gender)
    {
        $this->id = $id;
        $this->birthday = $birthday;
        $this->gender = $gender;
    }
}