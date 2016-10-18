<?php
namespace kuiper\rpc\server\request;

use kuiper\rpc\server\exception\MalformedJsonException;
use InvalidArgumentException;

class JsonRequest
{
    const METHOD_REGEX = '/^[a-z][a-z0-9\\\\_.]*$/i';

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var string
     */
    protected $version = '1.0';

    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        if (!in_array($this->version, ['1.0', '2.0'])) {
            throw new InvalidArgumentException("Json RPC version '{$this->version}' is invalid");
        }
        if ($this->method === null || !preg_match(self::METHOD_REGEX, $this->method)) {
            throw new InvalidArgumentException("Method '{$this->method} is invalid'");
        }
        if (!is_array($this->params)) {
            throw new InvalidArgumentException("Parameters is invalid");
        }
        return $this;
    }

    public function loadJson($json)
    {
        $options = json_decode($json, true);
        if ($options === false) {
            throw new MalformedJsonException("Json parse failed: " . json_last_error_msg());
        }
        return $this->setOptions($options);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function toJson()
    {
        $jsonArray = [
            'method' => $this->getMethod()
        ];

        if (null !== ($id = $this->getId())) {
            $jsonArray['id'] = $id;
        }

        $params = $this->getParams();
        if (! empty($params)) {
            $jsonArray['params'] = $params;
        }

        if ('2.0' == $this->getVersion()) {
            $jsonArray['jsonrpc'] = '2.0';
        }

        return json_encode($jsonArray);
    }
}
