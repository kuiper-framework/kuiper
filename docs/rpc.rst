==============================
RPC 服务框架
==============================

kuiper 提供一个简单的 rpc 服务框架。通过一个计算器的例子来学习 rpc 框架的基本概念。

RPC server
==============================

计算器服务的接口定义如下

.. literalinclude:: examples/rpc-server/CalculatorInterface.php
   :language: php


并且接口实现如下

.. literalinclude:: examples/rpc-server/Calculator.php
   :language: php


那么可以通过 rpc-server 组件使用 `json-rpc <http://www.jsonrpc.org/specification>`_ 规范实现简单的 web 服务。

首先安装 kuiper/rpc-server 组件 ::

    composer require kuiper/rpc-server

服务实现如下

.. literalinclude:: examples/rpc-server/server.php
   :language: php

启动服务 ::

    php -S 0:9527 server.php

使用 jsonrpc 规范发起请求 ::

    $ curl -d '{"method":"CalculatorInterface.add","params":[1,2],"id":1}' 0:9527
    {"id":1,"jsonrpc":"1.0","result":3}

可以看出服务已经调用成功。
服务器主要涉及两个对象： `$resolver` 和 `$server` 。resolver 负责方法解析与调用，server 负责请求处理与输出响应。
在 server 中通过 JsonRpc 这个 middleware 实现协议的解析。

RPC client
==============================

可以通过 rpc-client 组件简化 rpc 调用过程。首先安装 kuiper/rpc-client 组件 ::

    composer require kuiper/rpc-client

对于使用 http 协议的 rpc 调用还依赖 guzzlehttp 库 ::

    composer require guzzlehttp/guzzle

rpc-client 通过 `Proxy-Manager <http://ocramius.github.io/ProxyManager/>`_ 将 rpc 调用变成类似于本地函数调用。

.. literalinclude:: examples/rpc-client/client.php
   :language: php

