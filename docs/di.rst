==============================
容器
==============================

通过容器可轻松管理运行时类和配置的依赖关系，简化类的创建过程。

容器组件参考 `php-di <http://php-di.org/doc/getting-started.html>`_ 接口实现，通过帮助函数、反射、注释等技术简化配置。容器的基本概念参考 php-di 文档，下面只介绍 kuiper/di 的使用方式。

安装
==============================

composer 安装命令如下 ::

    composer require kuiper/di

使用容器，必须先创建 `ContainerBuilder` 对象

.. code-block:: php

   <?php
   use kuiper\di\ContainerBuilder;

   $builder = new ContainerBuilder();
   $container = $builder->build();

`ContainerBuilder` 提供配置方法，在 build 方法调用前使用，可修改容器行为，例如启用注释

.. code-block:: php

   <?php
   $builder->useAnnotations(true);

配置
==============================

依赖注入的对象可通过 addDefinitions 方法定义

.. code-block:: php

   <?php
   $builder->addDefinitions([
       // 对象定义
   ]);

组件提供多个帮助函数简化声明 

.. code-block:: php

   <?php
   use kuiper\di;
   use Psr\Log\LoggerInterface;
   use Monolog\Logger;
   
   $builder->addDefinitions([
       LoggerInterface::class => di\object(Logger::class)
   ]);

