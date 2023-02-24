<?php

namespace kuiper\db\fixtures;

use kuiper\db\attribute\Column;
use kuiper\db\attribute\Enumerated;
use kuiper\db\attribute\Id;

class Student
{
    #[Id]
    private ?int $id = null;

    private ?Gender $gender = null;
}