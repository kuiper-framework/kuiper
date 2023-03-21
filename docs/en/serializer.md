# Serializer

Object serialization is required in many scenarios, such as RPC parameter and return value parsing, conversion of configuration items to configuration objects, and caching. 
PHP's built-in 'serialize' serialization results are unfriendly to people. Some open-source libraries also provide serialization implementations, for example
[Symfony Serializer](https://symfony.com/doc/current/components/serializer.html), and
[JMS Seriealizer](https://jmsyst.com/libs/serializer) 。 Kuiper provides a simple serialization implementation based on the reflection library, 
but can meet the needs of most scenarios.

## Installation

```bash
composer require kuiper/serializer:^0.8
```

## Usage

First we need to create the `kuiper\serializer\Serializer` object:

```php
<?php

use kuiper\annotations\AnnotationReader;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\serializer\normalizer\DateTimeNormalizer;
use kuiper\serializer\normalizer\PhpEnumNormalizer;
use kuiper\serializer\Serializer;

$serializer = new Serializer(ReflectionDocBlockFactory::getInstance(), [
    DateTimeInterface::class => new DateTimeNormalizer(),
    \UnitEnum::class => new PhpEnumNormalizer() 
]); 
```

Serializer can read annotations on methods or properties to implement custom features for serialization, and currently supported annotations include:

- `SerializerName` modifies the serialized property name
- `SerializerIgnore` ignores serialization of the property

`ReflectionDocBlockFactory` can identify property types through document annotations, thus supporting types that cannot be represented by php built-in types, such as object arrays.

The third parameter, `normalizers`, is used to support serialization of custom object types.

Serialization call method:

```php
$serializer->normalize($var);
```

If `$var` is an array, the `normalize` method is called recursively on the array elements.
If `$var` is an object, it is serialized using object serialization rules.
In other cases, the variable value is returned directly.

Serializer objects do serialization by check whether the object implements the `JsonSerializable` interface or not.
If the object does implements `JsonSerializable`, call the `jsonSerialize` method directly.
Otherwise, check whether the object type matches the custom object type (whether it is a subclass of the `normalizers` array key in the constructor),
If so, call the custom object type serialization class for serialization.
If none of them are satisfied, use `kuiper\serializer\normalizer\ObjectNormalizer` to serialize the object.

`kuiper\serializer\normalizer\ObjectNormalizer` first iterates through all Getter functions (functions that start with get or is or has).
By default, the property name is lowercase be removing `get`, `is` or `has`. For example, the `getFooBar` property is named `fooBar`.
Property values are serialized by recursively calling `Serializer`.

Deserialize the call method:

```php
$serializer->denormalize($data, $type);
```
Type information for the serialized result must be provided when deserializing.
Type information can be a type name or a `kuiper\reflection\ReflectionType` object(refer to `kuiper\reflection\ReflectionType::parse` description).

The deserialization process processes accordingly the type, and if the type `isClass()` returns a true value, the deserialization rules for the object are used.
If type `isArray()` returns a true value, deserialization is called on array traversal recursion.
If the type `isComposite()` returns a true value, deserialization of only simple types (isScalar() is true) is currently supported.
If the type `isScalar()` returns a true value, call the `kuiper\reflection\ReflectionType::sanitize` method
Converts to a value that matches the type.

The deserialization rule of an object is to first determine whether the type matches the custom object type, and if so,
call the custom object type serialization class for deserialization.
If not, use `kuiper\serializer\normalizer\ObjectNormalizer` for deserialization of the object.

`kuiper\serializer\normalizer\ObjectNormalizer` can only handle serialized results as arrays.
First create an object instance using `ReflectionClass::newInstanceWithoutConstructor`.
Traversing the array, the array's key is normalized using `kuiperhelperText::snakeCase`, e.g. the array key is `fooBar` or `foo_bar` 
will be converted to `fooBar`.
First look for the corresponding Setter function and the function has and only one parameter,
such as whether there is a `setFooBar($value)` method, and if so, called
the object instance is the Setter function. If the `Setter` function does not exist,
look for a corresponding property. If there is a corresponding property, reflection is used
to set the property value.
The Setter function parameters and property values are obtained by recursively calling the Serializer denormalize method.

Note that the normalize of `ObjectNormalizer` is serialized using `JsonSerializable::jsonSerialize`, while deserialization has no correspondence
, that is, the deserialization rules must be satisfied when implementing `JsonSerializable::jsonSerialize`, otherwise an error may occur when deserializing.

## SerializerConfiguration

When creating a project container using [DI](di.md), you can configure Serializer with `kuiper\serializer\SerializerConfiguration`.

Next: [Server](swoole.md)
