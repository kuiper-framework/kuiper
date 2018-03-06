<?php

namespace kuiper\web\session;

class FlashSession implements FlashInterface
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
    public function error(string $message)
    {
        $this->message('error', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function notice(string $message)
    {
        $this->message('notice', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function success(string $message)
    {
        $this->message('success', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string $message)
    {
        $this->message('warning', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function message(string $type, string $message)
    {
        $messages = $this->getMessages();
        $messages[$type][] = $message;
        $this->setMessages($messages);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $type = null)
    {
        $messages = $this->getMessages();
        if (isset($type)) {
            return !empty($messages);
        }

        return !empty($messages);
    }

    public function getMessages()
    {
        if ($this->messages === null) {
            $this->messages = $this->session->get($this->sessionKey);
            $this->session->remove($this->sessionKey);
        }

        return $this->messages;
    }

    public function reset()
    {
        $this->messages = null;

        return $this;
    }

    protected function setMessages(array $messages)
    {
        $this->messages = $messages;
        $this->session->set($this->sessionKey, $messages);
    }
}
