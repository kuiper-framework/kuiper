<?php

declare(strict_types=1);

namespace kuiper\di;

use kuiper\annotations\AnnotationReaderInterface;
use Psr\Container\ContainerInterface;

class AllCondition implements Condition
{
    /**
     * @var Condition[]
     */
    private $conditions;

    /**
     * AllCondition constructor.
     *
     * @param Condition ...$conditions
     */
    public function __construct(...$conditions)
    {
        foreach ($conditions as $condition) {
            $this->addCondition($condition);
        }
    }

    public function addCondition(Condition $condition): void
    {
        $this->conditions[] = $condition;
    }

    public function matches(ContainerInterface $container): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->matches($container)) {
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
            if ($annotation instanceof Condition) {
                $conditions[] = $annotation;
            }
        }

        return empty($conditions) ? null : new AllCondition(...$conditions);
    }
}
