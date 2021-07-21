# RPC Client

JSON RPC 客户端：

```php
<?php

/**
 * @JsonRpcClient(protocol="http", url="https://rpc-server")
 */
interface HelloService
{
   public function hello(string $name): string;
}
```
```php
<?php

/**
 * @TarsClient("app.server.HelloObj")
 */
interface HelloService
{
   public function hello(string $name): string;
}
```
```php
<?php

/**
 * @HttpClient(url="")
 */
interface HelloService
{
   /**
    * @GetMapping()
    */
   public function hello(string $name): string;
}
```

接口将动态（或者预先）生成代理对象代码。

```php
<?php

class HelloServiceClient implements HelloService
{
   /**
    * @var RpcClient
    */
   private $client;

   public function hello(string $name): string
   {
       return $this->client->call($this, __METHOD__, $name);
   }
}
```

