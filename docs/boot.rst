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

配置文件
==============================

入口文件中通过调用 `loadConfig($dir)` 加载配置文件，加载的配置按文件名分组存储在 `$app->settings` ，例如 `app.php` 中配置项如下

.. literalinclude:: examples/boot/config/app.php
   :language: php

可以通过 `$app->settings['app.base_path']` 获取配置项的值。

Providers
==============================

在加载配置后， `bootstrap()` 通过配置项 `app.providers` 加载所有的 Providers。
provider 必须实现 `kuiper\boot\ProviderInterface` 接口, 其中有两个重要的函数 

.. code-block:: php

   <?php

   namespace kuiper\boot;
   
   interface ProviderInterface
   {
       /**
        * Registers services.
        */
       public function register();
   
       /**
        * Bootstraps.
        */
       public function boot();
   }
   
`register` 函数用于向容器注入配置， `boot` 函数在容器构造完成后运行。
provider 的实现可继承 `kuiper\boot\Provider` 类，提供三个属性：

* app 即初始化时创建的 `kuiper\boot\Application` 对象
* settings 加载的配置
* services `kuiper\di\ContainerBuilderInterface` 对象

