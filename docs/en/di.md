# DI

Kuiper DI (Dependency Injection) is based on [php-di](http://php-di.org/),
with Configuration class declaration definitions, namespace scanning, conditional annotations and other features added.

## Installation

```bash
composer require kuiper/di:^0.8
```

## ContainerBuilder 

Container is created using `kuiper\di\ContainerBuilder`, for example:

```php
<?php
use kuiper\di\ContainerBuilder;

$builder = new ContainerBuilder();
// configure the container 
$container = $builder->build();
```

`ContainerBuilder` is compatible with [php-di](http://php-di.org/doc/container-configuration.html).
Unlike php-di's ContainerBuilder, kuiper ContainerBuilder has annotations enabled by default.

## Configuration class

The configuration of containers is recommended using the Configuration class. In the Configuration class, 
All the method with attribute `kuiper\di\attribute\Bean` is registered
to the container as [factory](https://php-di.org/doc/php-definitions.html#factories). For example:

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

Noted that the name defined in the container uses the return type of the method by default.
If the method does not have a return type or needs to specify a name,
it can be set by `#[Bean('beanName')]`, for example:

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

In php-di, factory resolves parameters by parameter type.
If the parameter has no class type or the name defined in the container
is not a class name, it should be annotated with attribute
`DI\Attribute\Inject`. Refer to [php-di documentation](https://php-di.org/doc/attributes.html#inject) for how to use attribute.

If you want to create a definition using the methods provided by php-di,
you can declare the definition by implementing the `kuiper\di\DefinitionConfiguration` interface, for example:

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

The array definition in the `getDefinitions()` method is the same as [php-di](https://php-di.org/doc/php-definitions.html#syntax).

## Component scan

The container supports scanning all classes under certain namespace
and identifying all classes that annotated with attribute that implements `kuiper\di\attribute\ComponentInterface`.

Namespace scanning is based on composer PSR-4 rules, using Composer ClassLoader to find the path
mapping to the namespace, and then recursively scan files in the directory.
To use this feature, you must first register the Composer ClassLoader to ContainerBuilder:

```php
<?php
$loader = require __DIR__ . '/vendor/autoload.php';

$builder->setClassLoader($loader);
$builder->componentScan(["app\\service"]);
$container = $builder->build();
```

If a class annotated with attribute `kuiper\di\attribute\ComponentScan`,
the namespace list configured by the annotation will continue to be scanned.

Commonly used annotations include:

- `\kuiper\di\attribute\Configuration`
- `\kuiper\di\attribute\Component`
- `\kuiper\di\attribute\Controller`
- `\kuiper\di\attribute\Service`

The `#[Configuration]` annotation identifies that the class is a Configuration class
and is automatically added to the container definition.

The three attribute `#[Component]`, `#[Controller]`, `#[Service]` are used to
add the class marked to the container.
By default, all interface names implemented by the current class are registered.
If the attribute specifies a name, the name is used as the container definition name.
For example:

```php
<?php

name app\service;

use kuiper\di\attribute\Service;

#[Service]
class UserServiceImpl implement UserService
{
}
```

## Conditional attribute

When developing a public library or an open source component,
we want the application to be automatically configured based on user configuration or packages.
you can use conditional attribute to define the conditions for taking effect.
Currently supported conditional annotations include:

- `kuiper\di\attribute\ConditionalOnClass` takes effect when the specified class exists
- `kuiper\di\attribute\ConditionalOnMissingClass` takes effect only when the specified class does not exist
- `kuiper\di\attribute\ConditionalOnBean` takes effect when the definition of the name specified in the container exists
- `kuiper\di\attribute\ConditionalOnMissingBean` takes effect only when the definition of the specified name in the container does not exist
- `kuiper\di\attribute\ConditionalOnProperty` determines whether it takes effect based on the value of the configuration item
- `kuiper\di\attribute\AllConditions` takes effect when all subcondition annotations are true
- `kuiper\di\attribute\AnyCondition` takes effect when any of the subcondition annotations are true
- `kuiper\di\attribute\NoneCondition` takes effect when all subcondition annotations are false
- `kuiper\di\attribute\Conditional` Determines whether it is effective based on the class that customizes the Condition interface 

Using the ConditionalOnProperty attribute requires registering a `kuiper\helper\PropertyResolverInterface`
object to the container first, for example:

```php
<?php
use kuiper\helper\PropertyResolverInterface;
use kuiper\helper\Property;

$builder->addDefinitions([
    PropertyResolverInterface::class => Properties::create([
    ])
]);
```

## Awarable interface

Sometimes we need to register some common components like objects, such as loggers. We can do this by implementing the LoggerAwareInterface.
LoggerAwareTrait lets the container automatically inject the logger object. For example:

```php
<?php

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class FooService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function foo()
    {
        // The logger attribute is automatically injected by the container
        $this->logger->info("write info log");
    }
}
```

To achieve this, we need to call the `ContainerBuilder::addAwareInjection` method to declare that we need to register logger objects:

```php
<?php
use kuiper\di\AwareInjection;
use Psr\Log\LoggerAwareInterface;

$builder->addAwareInjection(AwareInjection::create(LoggerAwareInterface::class));
```

After adding this declaration, all objects that implement the `LoggerAwareInterface` will be set by the container by calling the `setLogger` method
An object defined by `Psr\Logger\LoggerInterface` in the container.

## Configuration items

By adding the `PropertiesDefinitionSource` definition, you can read the configuration directly from the container.

```php
<?php

use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\Properties;

$properties = Properties::create([
    `redis` => [
        `host` => `localhost`
    ]
]);
$builder->addDefinitions(new PropertiesDefinitionSource($properties));
$container = $builder->build();
$container->get(`redis.host`);
```

When `$container->get("some.not.exist.key")` is called, an exception will be throw when the key does not exist.
The `PropertiesDefinitionSource` constructor can set the configuration prefix when the configuration key and prefix match,
if the configuration item is not present, null is returned without throwing an exception.
The default configuration prefix is `application.`

## Define the priority

When `ContainerBuilder::addDefinitions(['foo' => $definition])` is called,
the definition will be replaced if foo already exists
The later definition will take precedence over the previous definition.
The configuration of containers in a real project will be more complex,
and we need to clearly define priorities to ensure that the object definitions
used in the project are the definitions needed.

A namespace scan occurs when `ContainerBuilder::build()` is called,
and during the scan, when the `#[Component]` annotation is scanned,
`ContainerBuilder::addDefinitions()` will be called to add the definition.
If it's a `#[Configuration]` annotation,
`ContainerBuilder::addConfiguration()` is called to add the definition.
After the scan is complete, all Configuration objects are extracted from
the corresponding definitions, calling `ContainerBuilder::addDefinitions()`
added to the container configuration. So `ComponentBuilder::componentScan()`
will override `ContainterBuilder::addConfiguration()`.

## Configure the container from project

The configuration of the container can be done by using the `Configuration` class and ComponentScan.
We can put all the `Configuration` class and scanning namespace to the configuration file.
The container is built by calling the `ContainterBuilder::create($projectPath)` method.
The configuration file will be look up `extra.kuiper.config-file` from `$projectPath/composer.json`.
The configuration file format is as follows:

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

Use [kuiper/component-installer](https://packagist.org/packages/kuiper/component-installer) to generate the configuration file automatically.

## Bootstrapping

If `Configuration` class implements the interface `kuiper\di\Bootstrap`,
ContainerBuilder will call the `boot($container)` method.

Next: [Logging](logger.md)
