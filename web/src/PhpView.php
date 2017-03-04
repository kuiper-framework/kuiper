<?php

namespace kuiper\web;

class PhpView implements ViewInterface
{
    /**
     * @var string
     */
    private $baseDir;

    /**
     * @var string
     */
    private $extension;

    public function __construct($baseDir, $ext = '.php')
    {
        $this->baseDir = rtrim($baseDir, '/');
        $this->extension = $ext;
    }

    public function render($name, array $context = [])
    {
        extract($context);
        ob_start();
        include $this->baseDir.'/'.$name.$this->extension;

        return ob_get_clean();
    }
}
