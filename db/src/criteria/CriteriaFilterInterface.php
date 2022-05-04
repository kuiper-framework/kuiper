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

namespace kuiper\db\criteria;

interface CriteriaFilterInterface
{
    /**
     * @param CriteriaClauseInterface $clause
     * @return CriteriaClauseInterface
     */
    public function filter(CriteriaClauseInterface $clause): CriteriaClauseInterface;
}
