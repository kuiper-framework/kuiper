<?php

namespace kuiper\helper\fixtures;

enum EnumGender: string
{
    case MALE = 'm';

    case FEMALE = 'f';

    public function description(): string
    {
        return match ($this) {
            self::MALE => '男',
            self::FEMALE => '女',
        };
    }
}
