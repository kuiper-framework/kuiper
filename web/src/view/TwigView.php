<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\web\view;

use kuiper\helper\Text;
use kuiper\web\exception\ViewException;
use Twig\Environment;
use Twig\Error\Error;

class TwigView implements ViewInterface
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var string|null
     */
    private $extension;

    public function __construct(Environment $twig, ?string $extension = '.html')
    {
        $this->twig = $twig;
        $this->extension = $extension;
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function render(string $name, array $context = []): string
    {
        try {
            if (null !== $this->extension
                && !Text::endsWith($name, $this->extension)
                && '' === pathinfo($name, PATHINFO_EXTENSION)) {
                $name .= $this->extension;
            }

            return $this->twig->render($name, $context);
        } catch (Error $exception) {
            throw new ViewException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
