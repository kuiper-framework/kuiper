=================================
Web 框架
=================================

kuiper 提供一个类似 slim 的微服务框架。

简单应用
==============================

首先使用 composer 安装以下组件 ::

    composer require kuiper/web
    composer require kuiper/di
    composer require zendframework/zend-diactoros

创建入口文件 index.php 如下：

.. literalinclude:: examples/web/index.php
   :language: php

启动 php built-in web 服务 ::

   php -S 0:8080 -t public

使用浏览器打开 http://localhost:8080 页面，应该可以看到输出内容。

路由定义
==============================

`$app` 对象类似 `Slim <http://www.slimframework.com/docs/>`_ 中的 `Slim\App` 。
路由使用 `Fast-Route <https://github.com/nikic/FastRoute>`_ 实现，与 slim 的路由规则
类似，基本的路由由一个 URI 模式和一个回调函数构成：

.. code-block:: php

   <?php
   $app->get('/hello/{name}', function ($request, $response, $args) {
       $response->getBody()->write("Hello " . $args['name']);
   });

回调函数前两个参数为请求对象和响应对象，使用 `PSR 7 <http://www.php-fig.org/psr/psr-7/>`_ 的实现。最后一个参数为 URI 模式中匹配的参数，参考 FastRoute 文档。

除了使用回调函数，还可以使用 `Class:method` 形式定义路由

.. code-block:: php

   <?php
   $app->get('/hello/{name}', 'IndexController:hello');
   
当 `Class` 实现 `kuiper\web\ControllerInterface` 时，函数参数将按 URI 模式匹配的顺序传入：

.. literalinclude:: examples/web/IndexController.php
   :language: php

controller 如果使用名字空间，可以使用 group 函数简化

.. code-block:: php

   <?php
   $app->group(['namespace' => 'app\\controllers'], function($app) {
        $app->get('/hello/{name}', 'IndexController:hello');
   });

