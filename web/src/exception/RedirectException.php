<?php

declare(strict_types=1);

namespace kuiper\web\exception;

class RedirectException extends \RuntimeException
{
    /**
     * @var string
     */
    private $url;

    public function __construct(string $url, int $code = 302)
    {
        if ($code > 310 || $code < 300) {
            throw new \InvalidArgumentException("Invalid redirect http code $code");
        }
        parent::__construct("redirect to $url", $code);
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
