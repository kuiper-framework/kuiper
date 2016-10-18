<?php
namespace kuiper\rpc\server\response;

use kuiper\serializer\JsonSerializerInterface as SerializerInterface;

class JsonResponse
{
    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    /**
     * Response error
     *
     * @var null|Error
     */
    private $error;

    /**
     * Request ID
     *
     * @var string
     */
    private $id;

    /**
     * Result
     *
     * @var mixed
     */
    private $result;

    /**
     * JSON-RPC version
     *
     * @var null|string
     */
    private $version;

    /**
     * Constructs response
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Set result.
     *
     * @param  mixed $value
     * @return self
     */
    public function setResult($value)
    {
        $this->result = $value;
        return $this;
    }

    /**
     * Get result.
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set result error
     *
     * RPC error, if response results in fault.
     *
     * @param  mixed $error
     * @return self
     */
    public function setError(Error $error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * Get response error
     *
     * @return null|Error
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Is the response an error?
     *
     * @return bool
     */
    public function isError()
    {
        return isset($this->error);
    }

    /**
     * Set request ID
     *
     * @param  mixed $name
     * @return self
     */
    public function setId($name)
    {
        $this->id = $name;
        return $this;
    }

    /**
     * Get request ID.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set JSON-RPC version.
     *
     * @param  string $version
     * @return self
     */
    public function setVersion($version)
    {
        $version = (string) $version;
        if ('2.0' == $version) {
            $this->version = '2.0';
            return $this;
        }

        $this->version = null;
        return $this;
    }

    /**
     * Retrieve JSON-RPC version
     *
     * @return null|string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Cast to JSON
     *
     * @return string
     */
    public function toJson()
    {
        $response = ['id' => $this->getId()];

        if ($this->isError()) {
            $response['error'] = $this->getError()->toArray();
        } else {
            $response['result'] = $this->getResult();
        }

        if (null !== ($version = $this->getVersion())) {
            $response['jsonrpc'] = $version;
        }

        return $this->serializer->toJson($response);
    }

    /**
     * Cast to string (JSON).
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
