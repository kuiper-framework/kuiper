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

namespace kuiper\web\session;

class SessionFlash implements FlashInterface
{
    private ?array $messages = null;

    public function __construct(
        private readonly SessionInterface $session,
        private readonly string $sessionKey = '_flashMessages')
    {
    }

    /**
     * {@inheritdoc}
     */
    public function error(string $message): void
    {
        $this->message('error', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function notice(string $message): void
    {
        $this->message('notice', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function success(string $message): void
    {
        $this->message('success', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string $message): void
    {
        $this->message('warning', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function message(string $type, string $message): void
    {
        $messages = $this->getMessages();
        $messages[$type][] = $message;
        $this->setMessages($messages);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $type = null): bool
    {
        $messages = $this->getMessages();
        return !empty($messages);

    }

    /**
     * {@inheritdoc}
     */
    public function getMessages(): array
    {
        if (null === $this->messages) {
            $this->messages = $this->session->get($this->sessionKey) ?? [];
            $this->session->remove($this->sessionKey);
        }

        return $this->messages;
    }

    /**
     * {@inheritdoc}
     */
    public function clearAll(): void
    {
        $this->messages = null;
    }

    protected function setMessages(array $messages): void
    {
        $this->messages = $messages;
        $this->session->set($this->sessionKey, $messages);
    }
}
