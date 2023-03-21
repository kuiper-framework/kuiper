# DI

Kuiper DI(Dependency Injection)依赖注入是基于 [php-di](http://php-di.org/) 实现，增加 Configuration 类声明定义、命名空间扫描、条件注解等特性。

## 安装

```bash
composer require kuiper/di:^0.8
```

## ContainerBuilder 

容器对象使用 `\kuiper\di\ContainerBuilder` 创建，例如：

```php
<?php
use kuiper\di\ContainerBuilder;

$builder = new ContainerBuilder();
// configure the container 
$container = $builder->build();
```

`ContainerBuilder` 兼容 [php-di](http://php-di.org/doc/container-configuration.html) 文档中的相关说明。
与 php-di 的 ContainerBuilder 不同的是，Kuiper ContainerBuilder 默认开启了注解。


## Configuration 

容器的配置推荐使用使用 `Configuration` 类完成。在 `Configuration` 类中，所有添加了 `\kuiper\di\attribute\Bean` 
注解的方法将以 [factory](https://php-di.org/doc/php-definitions.html#factories) 的方式注册到容器中。例如：

```php
<?php
use kuiper\di\attribute\Bean;

class MyConfiguration
{
    #[Bean]
    public function userRegistrationService(UserRepository $userRepository): UserRegistrationService
    {
        return new UserRegistrationService($userRepository);
    }
}

$builer->addConfiguration(new MyConfiguration());
```

需要注意的是容器中定义名字默认使用函数的返回类型。如果函数无返回类型或需要指定定义名字可以通过 `#[Bean("beanName")]` 方式设置，例如：

```php
<?php
use kuiper\di\attribute\Bean;

class Configuration
{
    #[Bean("userRegistrationService")]
    public function userRegistrationService(UserRepository $userRepository): UserRegistrationService
    {
        return new UserRegistrationService($userRepository);
    }
}
```

在 php-di 中，factory 通过参数类型解析参数。如果参数不是一个 class 类型或者容器中定义名不是 class 类型，则需要使用
`\DI\Attribute\Inject` 注解来设置参数。注解的使用方式参考 [php-di 文档](https://php-di.org/doc/attributes.html#inject) 。

如果需要使用 php-di 提供的方法来创建定义，可以通过实现 `\kuiper\di\DefinitionConfiguration` 接口进行定义声明，例如：

```php
<?php

use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

class MyConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            // place your definitions here
        ];
    }
}
```

`getDefinitions()` 方法中数组定义和 [php-di](https://php-di.org/doc/php-definitions.html#syntax) 相同。

## ComponentScan

容器支持按命名空间扫描命名空间下所有类，识别出所有实现 `\kuiper\di\attribute\ComponentInterface` 接口的注解。

命名空间扫描是基于 composer PSR-4 规则，通过 Composer ClassLoader 根据命名空间查找到命名空间对应的目录，然后递归扫描
目录中的文件。使用这个特性必须先向 `ContainerBuilder` 中注册 Composer ClassLoader:

```php
<?php
$loader = require __DIR__ . '/vendor/autoload.php';

$builder->setClassLoader($loader);
$builder->componentScan(["app\\service"]);
$container = $builder->build();
```

扫描过程中如果类使用 `\kuiper\di\attribute\ComponentScan` 注解，注解配置的命名空间列表将被继续扫描。

常用的注解包括：

- `\kuiper\di\attribute\Configuration`
- `\kuiper\di\attribute\Component`
- `\kuiper\di\attribute\Controller`
- `\kuiper\di\attribute\Service`

`#[Configuration]` 注解用于标识该类是一个 Configuration 类，将自动添加到容器定义中。

`#[Component]`, `#[Controller]`, `#[Service]` 三种注解用于将当前注解标记的类添加到容器定义中。
默认将当前类实现的所有接口名都注册到容器中。如果注解指定名称，则使用注解中指定的名称作为容器定义名字。
例如：

```php
<?php

name app\service;

use kuiper\di\attribute\Service;

#[Service]
class UserServiceImpl implement UserService
{
}
```

获取 `UserService` 对象 `$container->get(\app\service\UserService::class)` 。 

## Conditional 注解

当开发一个公共库或者一个开源组件时，我们希望应用可以根据用户配置或者用户引入的包自动进行配置。
在 Kuiper DI 中可以使用条件注解设置定义生效的条件。目前支持的条件注解包括：

- `\kuiper\di\attribute\ConditionalOnClass` 当指定的类存在时生效
- `\kuiper\di\attribute\ConditionalOnMissingClass` 当指定类不存在才生效
- `\kuiper\di\attribute\ConditionalOnBean` 当容器中指定的名字的定义存在时生效
- `\kuiper\di\attribute\ConditionalOnMissingBean` 当容器中指定名字的定义不存在才生效
- `\kuiper\di\attribute\ConditionalOnProperty` 根据配置项值判断是否生效
- `\kuiper\di\attribute\AllConditions` 所有子条件注解都为真时生效
- `\kuiper\di\attribute\AnyCondition` 任意一个子条件注解为真时生效
- `\kuiper\di\attribute\NoneCondition` 所有子条件注解都为假时生效
- `\kuiper\di\attribute\Conditional` 根据自定义实现 Condition 接口的类判断是否生效 

使用 `ConditionalOnProperty` 注解需要先在容器中注册一个 `\kuiper\helper\PropertyResolverInterface` 对象，例如：

```php
<?php
use kuiper\helper\PropertyResolverInterface;
use kuiper\helper\Property;

$builder->addDefinitions([
    PropertyResolverInterface::class => Properties::create([
    ])
]);
```

## Aware 类型接口

有时候我们需要像对象中注册一些通用组件，例如 logger。我们可以通过实现 `LoggerAwareInterface` 配合
`LoggerAwareTrait` 让容器自动注入 logger 对象。例如：

```php
<?php

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class FooService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function foo()
    {
        // logger 属性将自动由容器注入
        $this->logger->info("write info log");
    }
}
```

为了实现这个目的，我们需要调用 `ContainerBuilder::addAwareInjection` 方法声明需要统一注册 logger 对象：

```php
<?php
use kuiper\di\AwareInjection;
use Psr\Log\LoggerAwareInterface;

$builder->addAwareInjection(AwareInjection::create(LoggerAwareInterface::class));
```

添加这个声明后，所有实现 `LoggerAwareInterface` 的对象都将由容器统一调用 `setLogger` 方法设置
容器中 `Psr\Logger\LoggerInterface` 定义的对象。

## 配置项

通过添加 `PropertiesDefinitionSource` 定义可以从容器中直接读取配置。

```php
<?php

use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\Properties;

$properties = Properties::create([
    'redis' => [
        'host' => 'localhost'
    ]
]);
$builder->addDefinitions(new PropertiesDefinitionSource($properties));
$container = $builder->build();
$container->get('redis.host');
```

当调用 `$container->get("some.not.exist.key")` ，当配置项不存在时，将抛出异常。
`PropertiesDefinitionSource` 构造函数中可设置配置前缀，当配置key和前缀匹配时，如果配置项
不存在时，只返回 null ，而不抛出异常。默认配置前缀是 `application.` 。

## 定义优先级

当调用 `ContainerBuilder::addDefinitions(['foo' => $definition])`，如果 foo 已经存在，会替换
已经存在的定义，所以后面的定义会优先于之前的定义。
实际项目中容器的配置会比较复杂，我们需要明确定义的优先级，确保项目中使用的对象定义是需要的定义。

当调用 `ContainerBuilder::build()` 时，才发生命名空间扫描，扫描过程中，当扫描到 `@Component` 注解，
会调用 `ContainerBuilder::addDefinitions()` 添加定义；如果是 `@Configuration` 注解，
会调用 `ContainerBuilder::addConfiguration()` 添加定义。
在扫描结束之后，才会将所有 Configuration 对象提取相应的定义，调用 `ContainerBuilder::addDefinitions()`
添加到容器配置中。所以对于通过 `ContainterBuilder::addConfiguration()` 添加的定义和 
`ComponentBuilder::componentScan()` 扫描命名空间的定义，是扫描命名空间的定义优先，在顺序上是后面的覆盖前面。

## 根据项目配置容器

通过使用 `Configuration` 类和 ComponentScan 可以完成容器的配置。我们可以把所有 `Configuration` 类名和需要扫描
的命名空间写到配置文件中，通过调用 `ContainterBuilder::create($projectPath)` 方法完成容器配置。在这个方法中会通过
`require "$path/vendor/autoload.php"` 配置 Composer ClassLoader，在 `$projectPath/composer.json` 查找
`extra.kuiper.config-file` 配置项，如果存在就加载此配置文件；否则使用默认配置文件 `config/container.php` 。
这个配置文件格式如下：

```php
<?php

return [
    'component_scan' => [
    // namespace to scan
    ],
    'configuration' => [
    // configuration classes
    ]
];
```

使用 [kuiper/component-installer](https://packagist.org/packages/kuiper/component-installer) 可以自动生成
这个配置文件。

## 初始化

`Configuration` 类可以实现接口 `\kuiper\di\Bootstrap` 来进行初始化操作。ContainerBuilder 在完成容器对象创建后会调用实现了 Boostrap 接口的 configuration 类的 `boot($container)` 方法。 

下一节: [Logging](logger.md)
