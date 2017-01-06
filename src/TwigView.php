<?php
namespace kuiper\web;

use Twig_Environment;

class TwigView implements ViewInterface
{
    /**
     * @var Twig_Environment
     */
    private $twig;
    
    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getTwig()
    {
        return $this->twig;
    }

    public function render($name, array $context = [])
    {
        return $this->twig->render($name, $context);
    }
}
