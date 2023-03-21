# Logging

During application development, we often need to write log records in different places in different ways.
The `kuiper/logger` package can create different logger objects by class name or specified name, and configure the log behavior through configuration items.

## Installation

```bash
composer require kuiper/logger:^0.8
```

## Usage

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

In the configuration, `loggers` are used to create logger objects, and the configuration method is described below. `loggers` must contain a configuration named root. 

`level` is used to set the log level, either by namespace or by specific class.

`logger` is used for the logger object used by the class. key is the namespace or specific class name, and value is the key in `loggers`.

When calling the `kuiper\logger\LoggerFactory::create($className)` method, first find the logger configured in `logger` by class name and namespace.
Use the logger object if present, otherwise use the logger object corresponding to root.

Then look up the log level configured by `level` by class name and namespace, and if there is a log level configured, wrap a proxy log object with `kuiperloggerLogger`. Only the log level in `level` will only take effect if it is higher than the agent log object level, for example, the log level set by the agent log object is `info`, then configuring `debug` in `level` will not take effect.

Logger object configuration options include:
- name logger The name of the object
- level log level, you can use the value in `Psr\Log\LogLevel`
- The console value is of type bool, and the value of true adds output to Stderr's handler,
- The file value is of type string, add the handler output to the file 
- handlers Create handlers by parameters
- processors Create a processor by parameter

Handlers and formatters can be configured in the handlers configuration. Handlers, formatters, and processors are all created using the same rules, using handlers as an example.
The configuration can be a string that uses a string value to get an object from the container.
It can also be an array, in which class sets the class name and constructor sets the constructor parameter. For example, the following are the correct configurations:

```php
[
   'handlers' => [
      [
          'handler' => MonologHandlerErrorLogHandler::class
      ],
      [
          'handler' => [
              'class' => MonologHandlerStreamHandler::class,
              'constructor' => [
                  'stream' => 'default.log',
              ]
          ],
          'formatter' => [
              'class' => MonologFormatterLineFormatter::class,
              'constructor' => [
                  'allowInlineLineBreaks' => true,
              ],
          ],
      ]
   ]
]
```

## LoggerConfiguration

When creating a project container using [DI](di.md), you can use `kuiper\logger\LoggerConfiguration` for log configuration.
Configure `application.logging.path` to set the log directory, with all logs in the log file default.log and only ERROR level logs in error.log.

Configuration item description:

| Configuration Item | Environment variables | Description |
|-------------------------------------|------------------------|-------------|
| logging.path                        | LOGGING_PATH           | Log Directory |
| logging.loggers.root.console        | LOGGING_CONSOLE        | Whether to output logs to standard output |
| logging.loggers.root.level          | LOGGING_LEVEL          | Log Filtering Level |
| logging.loggers.root.log_file       | LOGGING_LOG_FILE       | Log file name |
| logging.loggers.root.error_log_file | LOGGING_ERROR_LOG_FILE | Error log file name | 

Next: [Reflection](reflection.md)
