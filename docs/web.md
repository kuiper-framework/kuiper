# Web 框架

kuiper web 基于 slim 框架，添加注解支持和会话支持。

```php
<?php

$app = \kuiper\web\SlimAppFactory::create($container);
$app->run();
```