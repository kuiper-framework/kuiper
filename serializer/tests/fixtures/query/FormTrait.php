<?php

declare(strict_types=1);

namespace kuiper\serializer\fixtures\query;

use kuiper\serializer\fixtures\User;

trait FormTrait
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var Param
     */
    private $param;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getParam(): Param
    {
        return $this->param;
    }

    public function setParam(Param $param): void
    {
        $this->param = $param;
    }
}
