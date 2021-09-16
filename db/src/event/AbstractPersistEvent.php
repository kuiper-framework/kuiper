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

namespace kuiper\db\event;

use kuiper\db\CrudRepositoryInterface;
use kuiper\event\StoppableEventTrait;
use Psr\EventDispatcher\StoppableEventInterface;

abstract class AbstractPersistEvent implements StoppableEventInterface
{
    use StoppableEventTrait;

    /**
     * @var CrudRepositoryInterface
     */
    private $repository;

    /**
     * @var object
     */
    private $entity;

    /**
     * AbstractPersistEvent constructor.
     */
    public function __construct(CrudRepositoryInterface $repository, object $entity)
    {
        $this->repository = $repository;
        $this->entity = $entity;
    }

    public function getRepository(): CrudRepositoryInterface
    {
        return $this->repository;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }
}
