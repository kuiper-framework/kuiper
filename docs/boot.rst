==============================
应用脚手架
==============================

项目中大量的服务配置是相同的， `kuiper/boot` 模块使得这些配置可以重用。

kuiper 使用 `Laravel <https://laravel.com/>`_ 的 `服务提供者 <https://laravel.com/docs/5.4/providers>`_ 概念，由服务提供者完成容器注册、事件绑定、设置中间件等。

使用 composer 安装以下模块 ::

    composer require kuiper/boot
    composer require kuiper/annotations
    composer require twig/twig
    composer require monolog/monolog
    composer require symfony/console

入口文件
=================================

应用初始化时需要创建 `kuiper\boot\Application` 对象，入口文件示例如下

.. literalinclude:: examples/boot/index.php
   :language: php

通过调用 `loadConfig($dir)` 加载配置文件，加载的配置按文件名分组存储在 `$app->settings`

配置文件
==============================



Web 应用
==============================

