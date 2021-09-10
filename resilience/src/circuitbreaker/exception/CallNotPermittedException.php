<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker\exception;

use kuiper\resilience\core\ResilienceException;

class CallNotPermittedException extends \Exception implements ResilienceException
{
}
