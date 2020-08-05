# Web 框架

kuiper web 基于 slim 框架，添加注解支持和会话支持。

```php
<?php

$app = \kuiper\web\SlimAppFactory::create($container);
$app->run();
```

## 配置项

| 配置名                                   | 说明                                                                         |
|------------------------------------------|------------------------------------------------------------------------------|
| application.web.middleware               | 中间件，可使用数字下标，数字越小，优先执行                                   |
| application.web.context-url              | URL 前缀                                                                     |
| application.web.error.display-error      | 页面是否显示详细错误信息，默认 false                                         |
| application.web.error.log-error          | 是否记录错误到日志，默认 false                                               |
| application.web.error.include-stacktrace | 日志记录时是否记录堆栈信息，可选值 never, always, on_trace_param，默认 never |
| application.web.view.path                | 模板路径地址                                                                 |
| application.web.session.auto_start       | 是否自动开启 session，默认 true                                              |
| application.web.session.prefix           | 缓存 key 前缀，如果多个项目使用相同缓存配置需要设置                          |
| application.web.session.lifetime         |                                                                              |
| application.web.session.cookie_name      |                                                                              |
| application.web.session.cookie_lifetime  |                                                                              |
| application.web.login.url                | 登录跳转地址                                                                 |
| application.web.login.redirect-param     | 登录后回调地址记录参数                                                       |

> include-stacktrace 值为 on_trace_param 时，当 url 中使用 trace 参数时记录错误堆栈到日志文件
