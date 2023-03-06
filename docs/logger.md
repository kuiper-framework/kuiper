# Logging

在应用开发过程中，我们经常需要在不同的地方使用不同的方式写入日志记录。
`kuiper/logger` 包可以按类名或指定名称创建不同的 Logger 对象，并通过配置项配置日志行为。

## 安装

```bash
composer require kuiper/logger:^0.8
```

## 使用方法

```php
<?php

use kuiper\logger\LoggerFactory;

$loggerFactory = new LoggerFactory($container, [
   'loggers' => [
      'root' => [ 'console' => true ]
   ],
   'level' => [
      'foo' => 'error'
   ],
   'logger' => [
   ]
]);
$logger = $loggerFactory->create(MyClass::class);
$logger->info("test");
```

配置中 `loggers` 用于创建 Logger 对象，配置方式见下文。`loggers` 中必须包含名为 root 的配置。 

`level` 用于设置日志等级，可以按命名空间设置，也可以设置具体某个类。

`logger` 用于类使用的 Logger 对象。key 是命名空间或者具体类名，value 是 `loggers` 中的 key。

调用 `\kuiper\logger\LoggerFactory::create($className)` 方法时，首先按类名和命名空间查找到 `logger` 中配置的 Logger。
如果存在则使用该 Logger 对象，否则使用 root 对应的 Logger 对象。

然后按类名和命名空间查找 `level` 配置的日志等级，如果有日志等级配置，则使用 `\kuiper\logger\Logger` 包装一个代理日志对象。只有 `level` 中的日志等级只有比代理日志对象等级高才生效，比如代理日志对象设置的日志等级是 `info` ，那么在 `level` 中配置成 `debug` 是不会生效的。

Logger 对象配置选项包括：
- name Logger 对象名字
- level 日志等级，可以使用 `\Psr\Log\LogLevel` 中的值
- console 值为 bool 类型，值为真时添加输出到 stderr 的 handler，
- file 值为 string 类型，添加输出到文件的 handler 
- handlers 按参数创建 handler
- processors 按参数创建 processor

handlers 配置中可以配置 handler 和 formatter。handler, formatter, processor 都使用相同规则创建，以handler 为例。
配置可以是一个字符串，使用字符串值从容器中获取对象。
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

## LoggerConfiguration

当使用 [DI](di.md) 创建项目容器，可以使用 `\kuiper\logger\LoggerConfiguration` 进行日志配置。配置 `application.logging.path` 用于设置日志目录，日志文件 default.log 中包含所有日志，error.log 中只包含 ERROR 级别的日志。

配置项说明：

| 配置项                                 | 环境变量                   | 说明          |
|-------------------------------------|------------------------|-------------|
| logging.path                        | LOGGING_PATH           | 日志目录        |
| logging.loggers.root.console        | LOGGING_CONSOLE        | 是否输出日志到标准输出 |
| logging.loggers.root.level          | LOGGING_LEVEL          | 日志过滤等级      |
| logging.loggers.root.log_file       | LOGGING_LOG_FILE       | 日志文件名       |
| logging.loggers.root.error_log_file | LOGGING_ERROR_LOG_FILE | 错误日志文件名     |

下一节：[Reflection](reflection.md)
