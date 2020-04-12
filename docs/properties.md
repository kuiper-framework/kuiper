# Properties 对象

`\kuiper\helper\Properties` 类允许多维数组通过 `.` 连接的 key 访问数据。

`Properties` 对象通过 `fromArray` 方法构造：

```php
<?php
use kuiper\helper\Properties;

$config = Properties::fromArray([
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

$config = Properties::fromArray([
    'redis-cluster' => [
        ['host' => 'server1'],
        ['host' => 'server2']
    ]
]);
echo $config->get('redis-cluster[0].host');   // 'server1'
```

修改数据只能通过