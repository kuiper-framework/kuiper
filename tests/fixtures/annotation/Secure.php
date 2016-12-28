<?php
namespace kuiper\annotations\fixtures\annotation;

/** @Annotation */
class Secure
{
    private $roles;

    public function __construct(array $values)
    {
        if (is_string($values['value'])) {
            $values['value'] = [$values['value']];
        }

        $this->roles = $values['value'];
    }
}
