# Kuiper Utility Functions

## Installation 

```bash
composer require kuiper/helper:^0.8
```

## Arrays

`kuiper\helper\Arrays` provides common array utility methods.

### Arrays::pull($arr, $field)
Extracting fields from a two-dimensional indexed array or object (using the Getter method) forms a new array. For example:

```php
<?php
use kuiper\helper\Arrays;

Arrays::pull([['id' => 1], ['id' => 2]], 'id');  // [1, 2]

class User {
    private $id;
    
    public function __construct($id) {
        $this->id = $id;
    }
    
    public function getId() {
        return $this->id;
    }
}

Arrays::pull([new User(1), new User(2)], 'id');  // [1, 2]
```

### Arrays::assoc($arr, $field)

Constructs an indexed array from a two-dimensional indexed array or object (using the Getter method) using the specified field values. For example:

```php
Arrays::assoc([['id' => 1], ['id' => 2]], 'id');  // [1=>['id'=>1], 2 => ['id' => 2]]
Arrays::assoc([new User(1), new User(2)], 'id');  // [1=>new User(1), 2 => new User(2)]
```

### Arrays::groupBy($arr, $field)

Aggregates from a two-dimensional indexed array or object (using the Getter method) using the specified field values to construct an indexed array. For example:

```php
Arrays::groupBy([['id' => 1], ['id' => 1], ['id' => 2]], 'id');  // [1=>[['id'=>1],['id' => 1]], 2 => [['id' => 2]]]
Arrays::groupBy([new User(1), new User(2), nre User(2)], 'id');  // [1=>[new User(1)], 2 => [new User(2), new User(2)]]
```

### Arrays::flatten(array $arr, int $depth = 1, bool $keepKeys = false)

Tiles the multidimensional array in dimensionality, for example:

```php
Arrays::flatten([[1], [2, 3]]);  // [1, 2, 3]
```

### Arrays::assign($object, $attributes)

Use the object's setter method to set the property.

> Note that the value in '$attributes' must match the 'setter' method parameter type declaration, otherwise a type error will occur when strongly typed. 

### Arrays::toArray($object)

Use the object's getter method to convert to an array.

## Text


`kuiper\helper\Text` provides common string methods:

- `isEmpty(?string $str)` returns true when `$str` is null or an empty string
- `isNotEmpty(?string $str)` returns true when `$str` is not a null or empty string
- `camelCase(string $str, string $delimiter = null)` converts the string to camel form, e.g. `coco_bongo` to `CocoBongo`
- `snakeCase(string $str, string $delimiter = null)` converts camel form to lowercase underscore, e.g. `CocoBongo` to `coco_bongo`

## Properties

The `kuiper\helper\Properties` class allows multidimensional arrays to access data through `.` concatenated keys.

The `Properties` object is constructed using the `create` method:

```php
<?php
use kuiper\helper\Properties;

$config = Properties::create([
     'redis' => [
         'host' => 'localhost'
     ]
]);
echo $config->get('redis.host');    'localhost'
```

In addition to access via '.' key, it can also be accessed via arrays or objects, and the above example is equivalent to:

```php
<?php
echo $config['redis']['host'];
echo $config->redis->host;
```

For arrays, they can be accessed in the way '[index]', for example:

```php
<?php
use kuiper\helper\Properties;

$config = Properties::create([
    'redis-cluster' => [
        ['host' => 'server1'],
        ['host' => 'server2']
    ]
]);
echo $config->get('redis-cluster[0].host');    'server1'
```

