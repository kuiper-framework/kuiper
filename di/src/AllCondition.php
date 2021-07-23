<?php

declare(strict_types=1);

namespace kuiper\di;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Conditional;
use Psr\Container\ContainerInterface;

class AllCondition implements Conditional
{
    /**
     * @var Conditional[]
     */
    private $conditions;

    /**
     * AllCondition constructor.
     *
     * @param Conditional ...$conditions
     */
    public function __construct(...$conditions)
    {
        foreach ($conditions as $condition) {
            $this->addCondition($condition);
        }
    }

    public function addCondition(Conditional $condition): void
    {
        $this->conditions[] = $condition;
    }

    public function match(ContainerInterface $container): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->match($container)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \ReflectionMethod|\ReflectionClass|mixed $reflector
     */
    public static function create(AnnotationReaderInterface $annotationReader, $reflector): ?AllCondition
    {
        $conditions = [];
        if ($reflector instanceof \ReflectionClass) {
            $annotations = $annotationReader->getClassAnnotations($reflector);
        } elseif ($reflector instanceof \ReflectionMethod) {
            $annotations = $annotationReader->getMethodAnnotations($reflector);
        } else {
            throw new \InvalidArgumentException('invalid reflector '.get_class($reflector));
        }
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Conditional) {
                $conditions[] = $annotation;
            }
        }

        return empty($conditions) ? null : new AllCondition(...$conditions);
    }
}
