<?php

declare(strict_types=1);

namespace kuiper\serializer\fixtures;

use kuiper\serializer\annotation\SerializeIgnore;
use kuiper\serializer\annotation\SerializeName;

class Company
{
    /**
     * @SerializeName("org_name")
     */
    public $name;

    /**
     * @SerializeName("org_address")
     */
    public $address;

    /**
     * @SerializeIgnore
     *
     * @var array
     */
    public $employers;
}
