# 日志配置

```php
[
    "logging" => [
        "loggers" => [
            "root" => [
                "name" => "LoggerName",
                "console" => true,
                "file" => "/path/to/file",
                "rotate" => true,
                "handlers" => [
                    [
                        "handler" => [
                            "class" => FooHandler::class,
                            "constructor" => [],
                        ],
                        "formatter" => [
                            "class" => \Monolog\Formatter\LineFormatter::class,
                            "constructor" => [
                                "format" => null,
                                "dateFormat" => null,
                                "allowInlineLineBreaks" => true,
                            ]
                        ],
                    ]
                ],
                "processors" => [
                    CoroutineIdProcessor::class
                ],
            ],
            "AccessLogger" => [
            ],
        ],
        "level" => [
            "root" => "info",
            "com.github" => "error"
        ],
        "config" => [
            RequestLog::class => "AccessLogger"
        ],
    ]
]
```
