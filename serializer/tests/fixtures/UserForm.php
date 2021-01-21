<?php

declare(strict_types=1);

namespace kuiper\serializer\fixtures;

use kuiper\serializer\fixtures\query\FormTrait;

class UserForm
{
    use FormTrait;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Company
     */
    private $company;
}
