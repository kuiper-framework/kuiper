<?php

namespace kuiper\db\fixtures;

use kuiper\helper\Enum;

class GenderEnum extends Enum
{
    public const MALE = 0;
    public const FEMALE = 1;
    public const UNKNOWN = 2;
}