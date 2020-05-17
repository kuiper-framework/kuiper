<?php

declare(strict_types=1);

namespace kuiper\web\view;

interface ViewInterface
{
    /**
     * Renders a template.
     *
     * @param string $name    The template name
     * @param array  $context An array of parameters to pass to the template
     *
     * @return string The rendered template
     */
    public function render(string $name, array $context = []): string;
}
