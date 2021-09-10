<?php

declare(strict_types=1);

namespace kuiper\resilience\retry\exception;

use kuiper\resilience\core\ResilienceException;

class MaxRetriesExceededException extends \Exception implements ResilienceException
{
}
