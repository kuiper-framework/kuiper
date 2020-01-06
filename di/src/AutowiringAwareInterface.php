<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Source\Autowiring;

interface AutowiringAwareInterface
{
    public function setAutowiring(Autowiring $autowiring): void;
}
