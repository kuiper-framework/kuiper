# Kuiper Utility Functions

## 安装 

```bash
composer require kuiper/helper:^0.6
```

## Enum

`\kuiper\helper\Enum` 和 [myclabs/php-enum](https://github.com/myclabs/php-enum) 功能相同，
都是提供类似 Java Enum 的功能。

声明一个 Enum 类：

```php
<?php

use kuiper\helper\Enum;

final class Action extends Enum
{
    private const VIEW = 'view';
    private const EDIT = 'edit';
}
```

使用方法：

```php
$action = Action::VIEW();
$action = Action::fromValue(Action::VIEW);
$action = Action::fromName('VIEW');
Action::hasValue($value);
Action::hasName($name);
```
遍历所有 Enum 的方法：

```php
Action::enums()
Action::values();
Action::names();
```

通过添加静态属性 `$PROPERTIES` 可以为 Enum 添加属性查询，例如：

```php
<?php
use kuiper\helper\Enum;

final class Action extends Enum
{
    private const VIEW = 'view';
    private const EDIT = 'edit';
    
    protected static $PROPERTIES = [
        'description' => [
            self::VIEW => '查看',
            self::EDIT => '编辑'
        ]
    ];
}

echo Action::VIEW()->description;
```

Enum 类实现 `JsonSerializable` 接口，可以 json 序列化为常量名字。例如：

```php
json_encode(Action::VIEW());   // "VIEW"
```

## Arrays

`kuiper\helper\Arrays` 提供常用的数组处理方法：

### Arrays::pull($arr, $field)
从二维索引数组或对象中(使用 Getter 方法)提取字段构成新数组。例如：

```php
<?php
use kuiper\helper\Arrays;

Arrays::pull([['id' => 1], ['id' => 2]], 'id'); // [1, 2]

class User {
    private $id;
    
    public function __construct($id) {
        $this->id = $id;
    }
    
    public function getId() {
        return $this->id;
    }
}

Arrays::pull([new User(1), new User(2)], 'id'); // [1, 2]
```

### Arrays::assoc($arr, $field)

从二维索引数组或对象中(使用 Getter 方法)使用指定的字段值构造索引数组。例如：

```php
Arrays::assoc([['id' => 1], ['id' => 2]], 'id'); // [1=>['id'=>1], 2 => ['id' => 2]]
Arrays::assoc([new User(1), new User(2)], 'id'); // [1=>new User(1), 2 => new User(2)]
```

### Arrays::groupBy($arr, $field)

从二维索引数组或对象中(使用 Getter 方法)使用指定的字段值进行聚合构造成索引数组。例如：


```php
Arrays::groupBy([['id' => 1], ['id' => 1], ['id' => 2]], 'id'); // [1=>[['id'=>1],['id' => 1]], 2 => [['id' => 2]]]
Arrays::groupBy([new User(1), new User(2), nre User(2)], 'id'); // [1=>[new User(1)], 2 => [new User(2), new User(2)]]
```

### Arrays::flatten(array $arr, int $depth = 1, bool $keepKeys = false)

将多维数组降维平铺，例如：

```php
Arrays::flatten([[1], [2, 3]]); // [1, 2, 3]
```

### Arrays::assign($object, $attributes)

使用对象的 setter 方法设置属性。

> 注意 `$attributes` 中的值必须和 `setter` 方法参数类型声明一致，否则在强类型时会产生类型错误。 

### Arrays::toArray($object)

使用对象的 getter 方法转换成数组。

## Text

`kuiper\helper\Text` 提供常用的字符串方法：

- `isEmpty(?string $str)` 当 `$str` 为 null 或空字符串时返回 true
- `isNotEmpty(?string $str)` 当 `$str` 不是 null 或空字符串时返回 true
- `startsWith(?string $haystack, string $needle, bool $ignoreCase = true)` 检查字符串是否以 `$needle` 开头
- `endsWith(string $haystack, string $needle, bool $ignoreCase = true)` 检查字符串是否以 `$needle` 结束
- `camelCase(string $str, string $delimiter = null)` 将字符串转换为驼峰形式，例如 `coco_bongo` 转换成 `CocoBongo`
- `snakeCase(string $str, string $delimiter = null)` 将驼峰形式转换为小写下划线形式，例如`CocoBongo`转换为 `coco_bongo`

## Properties

`\kuiper\helper\Properties` 类允许多维数组通过 `.` 连接的 key 访问数据。

`Properties` 对象通过 `create` 方法构造：

```php
<?php
use kuiper\helper\Properties;

$config = Properties::create([
     'redis' => [
         'host' => 'localhost'
     ]
]);
echo $config->get('redis.host');   // 'localhost'
```

除了通过 `.` key 访问，也可以通过数组或对象方式访问，上面的例子等同于：

```php
<?php
echo $config['redis']['host'];
echo $config->redis->host;
```

对于数组，可以通过 `[index]` 方式访问，例如：

```php
<?php
use kuiper\helper\Properties;

$config = Properties::create([
    'redis-cluster' => [
        ['host' => 'server1'],
        ['host' => 'server2']
    ]
]);
echo $config->get('redis-cluster[0].host');   // 'server1'
```


