# Reflection

Reflection 是增加了一些 PHP 反射中缺失的功能。

## ReflectionFile

ReflectionFile 可以提取文件中定义的类名，命名空间，以及文件中导入符号（即通过 use 引入的
类、函数、常量）。

ReflectionFile 通过 ReflectionFileFactory 创建：

```php
<?php

use kuiper\reflection\ReflectionFileFactory;

$reflectionFile = ReflectionFileFactory::getInstance()->create('/path/to/file.php');
var_export($reflectionFile->getClasses()); 
```

通过文件导入符号表，我们就可以知道文件中一个符号对应的类名。可以通过 `FqcnResolver` 查询：

```php
<?php

$resolver = new \kuiper\reflection\FqcnResolver($reflectionFile);
$fullClassName = $resolver->resolve('Foo', $namespace);
```

参数中 `$namespace` 是符号 `Foo` 所在的命名空间，一般需要通过解析上下文获取（比如文件中类名）。如果文件中有且仅有一个命名空间，
可以通过 `$reflectionFile->getNamespaces()[0]` 获取。

## ReflectionNamespace

ReflectionNamespace 可以获取命名空间中所有定义的类。这是通过扫描命名空间对应目录中所有文件，然后通过 `ReflectionFile` 获取
文件中定义的类名实现。

ReflectionNamespace 可以通过 ReflectionNamespaceFactory 创建：

```php
<?php

$factory = ReflectionNamespaceFactory::getInstance();
$factory->register('my\\app', '/path/to/src/my/app');
$reflectionNamespace = $factory->create('my\\app');
var_export($reflectionNamespace->getClasses());
```

命名空间与目录路径的对应规则通过 `register($namespace, $path)` 注册，也可以
基于 composer PSR-4 规则，通过设置 Composer ClassLoader 对象实现根据命名空间查找到命名空间对应的目录：

```php

$classLoader = require 'vendor/autoload.php';
 
ReflectionNamespaceFactory::getInstance()->registerLoader($classLoader);
var_export($factory->create('my\\app')->getClasses());
```

## ReflectionType

ReflectionType 提供类型自省能力和值验证的功能，并支持 Union 类型和数组类型支持。

创建 ReflectionType 方法：

```php
<?php

$reflectionType = ReflectionType::parse('?string');  // StringType
$reflectionType = ReflectionType::parse('Foo');      // ClassType
$reflectionType = ReflectionType::parse('int[]');    // ArrayType
$reflectionType = ReflectionType::parse('float|int'); // CompositeType
```

类型反省能力：

| 方法        | 类型                                                                     |
|-------------|--------------------------------------------------------------------------|
| isPrimitive | bool, int, float, array, callable, mixed, object, number, resource, void |
| isScalar    | bool, float, int, string                                                 |
| isArray     | array, int[]                                                             |
| isNull      | null                                                                     |
| isResource  | resource                                                                 |
| isClass     | Foo                                                                      |
| isObject    | object                                                                   |
| isComposite |                                                                          |
| isUnknown   | mixed, array                                                             |
| isPseudo    | mixed, number, void                                                      |

类型值验证功能：

```php
<?php
$reflectionType->isValid($value);
$reflectionType->sanitize($value);
```
`isValid` 用于判断值是否和类型一致。`sanitize` 用于对值做类型转换。

## ReflectionDocBlock

ReflectionDocBlock 可以通过属性和方法上的注解获取属性类型或方法参数和返回值类型。

使用方法：

```php

$reflectPropertyDocBlock = ReflectionDocBlockFactory::getInstance()
    ->createPropertyDocBlock(new ReflectionProperty($class, $propertyName));
$reflectPropertyDocBlock->getType();

$reflectMethodDocBlock = ReflectionDocBlockFactory::getInstance()
    ->createMethodDocBlock(new ReflectionMethod($class, $methodName));
$reflectMethodDocBlock->getParameterTypes();
$reflectMethodDocBlock->getReturnType();
```
