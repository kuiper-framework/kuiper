namespace kuiper\tars\fixtures;

#[\kuiper\tars\attribute\TarsServant("demo.app.HelloObj")]
class HelloService34248ea1aac865c1b1d31385bf4a633e implements HelloService
{
    private $rpcExecutorFactory = null;

    public function __construct(\kuiper\rpc\client\RpcExecutorFactoryInterface $rpcExecutorFactory)
    {
        $this->rpcExecutorFactory = $rpcExecutorFactory;
    }

    /**
     * @inheritdoc
     */
    public function hello(string $name) : string
    {
        list ($ret) = $this->rpcExecutorFactory->createExecutor($this, __FUNCTION__, [$name])->execute();
        return $ret;
    }

    public function getRpcExecutorFactory() : \kuiper\rpc\client\RpcExecutorFactoryInterface
    {
        return $this->rpcExecutorFactory;
    }
}
