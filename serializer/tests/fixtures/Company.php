<?php
namespace kuiper\serializer\fixtures;

use kuiper\serializer\annotation\SerializeName;
use kuiper\serializer\annotation\SerializeIgnore;

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
     * @var array
     */
    public $employers;
}
