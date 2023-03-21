# Reflection

Kuiper Reflection adds some missing features in PHP reflection.

## Installation

```bash
composer require kuiper/reflection:^0.8
```

## ReflectionFile

`ReflectionFile` extracts the class names, namespaces, and import symbols (i.e. classes, functions, constants introduced via use) defined in the file.

The `ReflectionFile` object is created with the `ReflectionFileFactory`:

```php
use kuiper\reflection\ReflectionFileFactory;

$reflectionFile = ReflectionFileFactory::getInstance()->create('/path/to/file.php');
var_export($reflectionFile->getClasses()); 
```

By reading the import symbols, we can know the full class name in the file via `FqcnResolver`:

```php
$resolver = new \kuiper\reflection\FqcnResolver($reflectionFile);
$fullClassName = $resolver->resolve('Foo', $namespace);
```

The `$namespace` in the parameter is the namespace where the symbol `Foo` is located, which generally needs to be obtained by parsing the context (such as the class name in the file). If there is one and only one namespace in the file,
it can be obtained by `$reflectionFile->getNamespaces()[0]`.

## ReflectionNamespace

`ReflectionNamespace` gets all the defined classes in the namespace. This is done by scanning all files in the corresponding directory of the namespace and then getting all class names through `ReflectionFile`.

`ReflectionNamespace` can be created from `ReflectionNamespaceFactory`:

```php
<?php
use kuiper\reflection\ReflectionNamespaceFactory;

$factory = ReflectionNamespaceFactory::getInstance();
$factory->register('myapp', '/path/to/src/my/app');
$reflectionNamespace = $factory->create('myapp');
var_export($reflectionNamespace->getClasses());
```

The corresponding rules for namespaces and directory paths are registered with 'register($namespace, $path)', or by setting the Composer ClassLoader object using PSR-4 rule:

```php
$factory = ReflectionNamespaceFactory::getInstance();
$classLoader = require 'vendor/autoload.php';
 
$factory->registerLoader($classLoader);
var_export($factory->create('myapp')->getClasses());
```

> [DI ComponentScan](di.md#ComponentScan) is implemented using ReflectionNamespace.

## ReflectionType

`ReflectionType` provides type introspection and value validation, and supports Union types and array types.

Create the `ReflectionType` method:

```php
$reflectionType = ReflectionType::parse('?string');   StringType
$reflectionType = ReflectionType::parse('Foo');       ClassType
$reflectionType = ReflectionType::parse('int[]');     ArrayType
$reflectionType = ReflectionType::parse('float|int');  CompositeType
```

Type of introspection:

| Method | Type |
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

Type value validation features:

```php
$reflectionType->isValid($value);
$reflectionType->sanitize($value);
```
`isValid` is used to determine if the value matches the type. `sanitize` is used to type a value.

## ReflectionDocBlock

`ReflectionDocBlock` can get property types or method parameter and return value types through annotations on properties and methods.

How to use:

```php
<?php
use kuiper\reflection\ReflectionDocBlockFactory;
$factory = ReflectionDocBlockFactory::getInstance();

$reflectPropertyDocBlock = $factory->createPropertyDocBlock(new ReflectionProperty($class, $propertyName));
$reflectPropertyDocBlock->getType();

$reflectMethodDocBlock = $factory->createMethodDocBlock(new ReflectionMethod($class, $methodName));
$reflectMethodDocBlock->getParameterTypes();
$reflectMethodDocBlock->getReturnType();
```

Next: [Serializer](serializer.md)
