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

namespace kuiper\reflection\filter;

use kuiper\reflection\type\CompositeType;
use kuiper\reflection\TypeFilterInterface;

class CompositeTypeFilter implements TypeFilterInterface
{
    /**
     * @var CompositeType
     */
    private $type;

    /**
     * CompositeTypeFilter constructor.
     */
    public function __construct(CompositeType $type)
    {
        $this->type = $type;
    }

    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     */
    public function isValid($value): bool
    {
        foreach ($this->type->getTypes() as $type) {
            if ($type->isValid($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitizes input value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function sanitize($value)
    {
        return $value;
    }
}
