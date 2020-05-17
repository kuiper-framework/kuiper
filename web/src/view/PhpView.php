<?php

declare(strict_types=1);

namespace kuiper\web\view;

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

    public function __construct(string $baseDir, string $extension = '.php')
    {
        $this->baseDir = rtrim($baseDir, '/');
        $this->extension = $extension;
    }

    public function render(string $name, array $context = []): string
    {
        extract($context, EXTR_SKIP);
        ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->baseDir.'/'.$name.$this->extension;

        return ob_get_clean();
    }
}
