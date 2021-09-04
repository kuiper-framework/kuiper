# 日志配置

`\kuiper\logger\LoggerFactory` 实现按类名创建 Logger 对象，设置日志等级。

使用方法：
```php
<?php

use kuiper\logger\LoggerFactory;

$loggerFactory = new LoggerFactory([
   'loggers' => [
      'root' => [ 'console' => true ]
   ],
   'level' => [
      'foo' => 'error'
   ],
   'logger' => [
      MyClass::class => '',
   ]
]);
$logger = $loggerFactory->create(MyClass::class);
$logger->info("test");
```

配置中 `loggers` 用于配置 Logger 对象，必须包含 root 配置，配置方式见下文；`level` 用于设置日志等级，可以按命名空间设置，也可以设置具体某个类；
`logger` 用于设置日志类，key 可以是命名空间或者具体类名，value 是 `loggers` 中的 key。

调用 `\kuiper\logger\LoggerFactory::create($className)` 方法时，如果按类名和命名空间查找到 `logger` 中配置的 Logger，则使用该 Logger 对象，
否则使用 root 对应的 Logger 对象；然后按类名和命名空间查找 `level` 配置的日志等级，如果有日志等级配置，则使用 `\kuiper\logger\Logger` 包装
一个代理日志对象。也就是说 `level` 中的日志等级只有比原日志对象等级高才生效，比如日志对象设置的日志等级是 `info` ，那么在 `level` 中配置成 `debug` 
是不会生效的。

Logger 对象配置选项包括：
- name Logger 对象名字
- level 日志等级，可以使用 `\Psr\Log\LogLevel` 中的值
- console 值为 bool 类型，值为真时添加输出到 stderr 的 handler，
- file 值为 string 类型，添加输出到文件的 handler 
- handlers 按参数创建 handler
- processors 按参数创建 processor

handlers 配置中可以配置 handler 和 formatter。handler, formatter, processor 都使用相同规则创建，以handler 为例。
配置 可以为一个字符串，使用字符串值从容器中获取对象。
也可以是数组，数组中 class 设置类名， constructor 设置构造函数参数。例如以下都是正确的配置：

```php
[
   'handlers' => [
      [
          'handler' => \Monolog\Handler\ErrorLogHandler::class
      ],
      [
          'handler' => [
              'class' => \Monolog\Handler\StreamHandler::class,
              'constructor' => [
                  'stream' => 'default.log',
              ]
          ],
          'formatter' => [
              'class' => \Monolog\Formatter\LineFormatter::class,
              'constructor' => [
                  'allowInlineLineBreaks' => true,
              ],
          ],
      ]
   ]
]
```
