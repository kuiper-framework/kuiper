<?php

declare(strict_types=1);

namespace kuiper\web\view;

use kuiper\web\exception\ViewException;
use Twig\Environment;
use Twig\Error\Error;

class TwigView implements ViewInterface
{
    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function render(string $name, array $context = []): string
    {
        try {
            return $this->twig->render($name, $context);
        } catch (Error $exception) {
            throw new ViewException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
