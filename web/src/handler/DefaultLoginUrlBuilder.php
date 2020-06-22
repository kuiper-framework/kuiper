<?php

declare(strict_types=1);

namespace kuiper\web\handler;

use Psr\Http\Message\ServerRequestInterface;

class DefaultLoginUrlBuilder implements LoginUrlBuilderInterface
{
    /**
     * @var string
     */
    private $loginUrl;
    /**
     * @var string|null
     */
    private $redirectParam;

    public function __construct(string $loginUrl = '/login', ?string $redirectParam = 'redirect')
    {
        $this->loginUrl = $loginUrl;
        $this->redirectParam = $redirectParam;
    }

    public function build(ServerRequestInterface $request): string
    {
        if (isset($this->redirectParam)) {
            $redirectUrl = (string) $request->getUri();

            return $this->loginUrl
                .(false === strpos($this->loginUrl, '?') ? '?' : '&')
                .$this->redirectParam.'='.urlencode($redirectUrl);
        } else {
            return $this->loginUrl;
        }
    }
}
