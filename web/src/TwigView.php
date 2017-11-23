<?php

namespace kuiper\web;

use kuiper\web\exception\ViewException;

class TwigView implements ViewInterface
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return $this->twig;
    }

    public function render($name, array $context = [])
    {
        try {
            return $this->twig->render($name, $context);
        } catch (\Twig_Error $exception) {
            throw new ViewException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
