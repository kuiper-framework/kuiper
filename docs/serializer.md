# Serializer

在应用中很多场景中需要进行对象序列化，例如RPC的参数和返回值解析，配置项转换为配置对象，缓存等。 
php 内置的 `serialize` 序列化结果对人是不友好的。一些开源库也提供序列化实现，例如
[Symfony Serializer](https://symfony.com/doc/current/components/serializer.html) ，和
[JMS Seriealizer](https://jmsyst.com/libs/serializer) 。kuiper 基于 reflection 库提供一个 简单但能满足绝大多数场景的序列化实现。

## 安装

```bash
composer require kuiper/serializer:^0.6
```

## 使用方法

首先我们需要创建出 `\kuiper\serializer\Serializer` 对象：

```php
use kuiper\annotations\AnnotationReader;
use kuiper\helper\Enum;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\serializer\normalizer\DateTimeNormalizer;
use kuiper\serializer\normalizer\EnumNormalizer;
use kuiper\serializer\Serializer;

$serializer = new Serializer(AnnotationReader::getInstance(), ReflectionDocBlockFactory::getInstance(), [
    \DateTimeInterface::class => new DateTimeNormalizer(),
    Enum::class => new EnumNormalizer() 
]); 
```

Serializer 可以读取方法或属性上的注解实现序列化的定制功能，目前支持的注解包括 :

- `@SerializerName` 修改序列化属性名称
- `@SerializerIgnore` 忽略该属性的序列化

`ReflectionDocBlockFactory` 可以通过文档注解识别属性类型，从而支持 php 内置类型无法表示的类型，例如对象数组。

第三个参数 `normalizers` 用于支持自定义对象类型的序列化。

序列化调用方法：

```php
$serializer->normalize($var);
```

如果 `$var` 是数组，则对数组元素递归调用 `normalize` 方法。
如果 `$var` 是对象，则使用对象序列化规则进行序列化。
其他情况不做处理，直接返回变量值。

Serializer 对象的序列化规则为，首先判断对象是否实现了 `JsonSerializable` 接口，如果是则直接调用 `jsonSerialize` 方法。
否则先判断是否对象类型是否匹配自定义对象类型（即是否为构造函数中 `normalizers` 数组 key 的子类），
如果是则调用自定义的对象类型序列化类进行序列化。如果均不满足，最后使用 `kuiper\serializer\normalizer\ObjectNormalizer` 
进行对象的序列化。

`kuiper\serializer\normalizer\ObjectNormalizer` 首先遍历所有的 Getter 函数(以 get 或 is 或 has 开头的函数)。
属性名称默认是去除 get,is,has 前缀后的部分首字母小写。例如 getFooBar 序列化属性名为 fooBar。
属性值通过递归调用 `Serializer` 序列化得到。

反序列化调用方法：

```php
$serializer->denormalize($data, $type);
```
反序列化时必须提供序列化结果的类型信息。类型信息可以是类型名，参考 `\kuiper\reflection\ReflectionType::parse` 说明。
也可以是 `\kuiper\reflection\ReflectionType` 对象。

反序列化过程会根据类型进行相应的处理，如果类型 `isClass()` 返回真值，使用对象的反序列化规则。
如果类型 `isArray()` 返回真值，对数组遍历递归调用反序列化。
如果类型 `isComposite()` 返回真值，目前只能支持简单类型（isScalar() 为真值）的反序列化。
如果类型 `isScalar()` 返回真值，调用 `\kuiper\reflection\ReflectionType::sanitize` 方法
转换成与类型匹配的值。

对象的反序列化规则为，首先判断类型是否匹配自定义对象类型，如果是则调用自定义对象类型序列化类进行反序列化。
如果不是则使用 `kuiper\serializer\normalizer\ObjectNormalizer` 进行对象的反序列化。

`kuiper\serializer\normalizer\ObjectNormalizer` 只能处理序列化结果为数组的情况。
首先使用 `\ReflectionClass::newInstanceWithoutConstructor` 创建对象实例。
对数组遍历，数组的 key 使用 `\kuiper\helper\Text::snakeCase` 归一化，例如数组 key 为 `fooBar` 或 `foo_bar` 
都将转换成 `fooBar`。
首先查找是否对应的 Setter 函数且函数有且仅有一个参数，例如是否有 `setFooBar($value)` 方法，如果有，则调用
该对象实例 Setter 函数。如果不存在 `Setter` 函数，则查找是否有对应的属性。如果有对应的属性，则使用反射
设置属性值。Setter 函数参数和属性值都是通过递归调用 Serializer denormalize 方法得到，参数中的类型值
通过 `ReflectionDocBlockFactory` 反射得到。

注意到 `ObjectNormalizer` 的 normalize 使用 `JsonSerializable::jsonSerialize` 序列化，而反序列化是没有对应
的函数，也就是说在实现 `JsonSerializable::jsonSerialize` 时必须满足反序列化规则，否则反序列化时可能会出现错误。


## SerializerConfiguration

当使用 [DI](di.md) 创建项目容器，可以使用 `\kuiper\serializer\SerializerConfiguration` 配置 Serializer。

下一节：[Server](swoole.md)
