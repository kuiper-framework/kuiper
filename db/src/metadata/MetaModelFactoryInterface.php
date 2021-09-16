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

namespace kuiper\db\metadata;

use kuiper\db\exception\MetaModelException;

interface MetaModelFactoryInterface
{
    /**
     * Creates the table metadata.
     *
     * @throws MetaModelException if column not valid
     */
    public function create(string $entityClass): MetaModelInterface;

    /**
     * Creates the table metadata by repository.
     */
    public function createFromRepository(string $repositoryClass): MetaModelInterface;
}
