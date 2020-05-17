<?php

declare(strict_types=1);

namespace kuiper\web\session;

class SessionFlash implements FlashInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @var array
     */
    private $messages;

    public function __construct(SessionInterface $session, $key = '_flashMessages')
    {
        $this->session = $session;
        $this->sessionKey = $key;
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
        if (isset($type)) {
            return !empty($messages);
        }

        return !empty($messages);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages(): array
    {
        if (null === $this->messages) {
            $this->messages = $this->session->get($this->sessionKey) ?: [];
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
