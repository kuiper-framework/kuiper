<?php

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
