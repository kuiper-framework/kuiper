<?php

declare(strict_types=1);

namespace kuiper\annotations;

use Doctrine\Common\Annotations\AnnotationException;
use kuiper\annotations\fixtures\Value;

trait AnnotationTrait
{
    /**
     * AnnotationTrait constructor.
     */
    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            foreach ((array) $values['value'] as $value) {
                if ($value instanceof Value) {
                    $this->{$value->property} = $value->value;
                }
            }
            unset($values['value']);
        }
        foreach ($values as $property => $value) {
            if (!property_exists($this, $property)) {
                if ('value' !== $property) {
                    throw AnnotationException::creationError(sprintf('The annotation @%s does not have a property named "%s".', get_class($this), $property));
                }
            }

            $this->{$property} = $value;
        }
    }
}
