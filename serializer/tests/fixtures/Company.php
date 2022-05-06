<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\serializer\fixtures;

use kuiper\serializer\attribute\SerializeIgnore;
use kuiper\serializer\attribute\SerializeName;

class Company
{
    #[SerializeName("org_name")]
    public $name;

    #[SerializeName("org_address")]
    public $address;

    #[SerializeIgnore]
    public $employers;
}
