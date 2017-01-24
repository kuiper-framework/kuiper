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
     * @inheritDoc
     */
    public function error($message)
    {
        $this->message('error', $message);
    }

    /**
     * @inheritDoc
     */
    public function notice($message)
    {
        $this->message('notice', $message);
    }

    /**
     * @inheritDoc
     */
    public function success($message)
    {
        $this->message('success', $message);
    }

    /**
     * Shows a HTML warning message
     */
    public function warning($message)
    {
        $this->message('warning', $message);
    }

    /**
     * Outputs a message
     */
    public function message($type, $message)
    {
        $messages = $this->getMessages();
        $messages[$type][] = $message;
        $this->setMessages($messages);
    }

    public function has($type = null)
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

    protected function setMessages(array $messages)
    {
        $this->messages = $messages;
        $this->session->set($this->sessionKey, $messages);
    }
}
