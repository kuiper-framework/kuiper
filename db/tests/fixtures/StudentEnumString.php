<?php

namespace kuiper\db\fixtures;

use kuiper\db\attribute\Column;
use kuiper\db\attribute\Enumerated;
use kuiper\db\attribute\Id;

class StudentEnumString
{
    #[Id]
    private ?int $id = null;

    #[Enumerated("STRING")]
    private ?GenderEnum $gender = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return GenderEnum|null
     */
    public function getGender(): ?GenderEnum
    {
        return $this->gender;
    }

    /**
     * @param GenderEnum|null $gender
     */
    public function setGender(?GenderEnum $gender): void
    {
        $this->gender = $gender;
    }
}