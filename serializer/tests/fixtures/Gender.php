<?php

declare(strict_types=1);

namespace kuiper\serializer\fixtures;

use kuiper\helper\Enum;

class Gender extends Enum
{
    public const MALE = 'male';
    public const FEMALE = 'female';
}
