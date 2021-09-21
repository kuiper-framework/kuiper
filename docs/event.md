# Event Dispatcher

Kuiper Event 是基于 PSR-14 接口，使用 `symfony/event-dispatcher` 实现。

## 安装

```bash
composer require kuiper/event:^0.6
```

在 `src/config.php` 中配置：

```php
<?php

return [
    'application' => [
        'listeners' => [
            MyFooEventListener::class
        ]
    ]
];
```

或者使用 `@\kuiper\event\annotation\EventListener` 注解，在命名空间扫描目录中的事件监听器会自动添加到 Event Dispatcher 中。
例如：

```php
<?php

namespace app\listeners;

use kuiper\event\annotation\EventListener;
use kuiper\event\EventListenerInterface;

/**
 * @EventListener
 */
class MyFooEventListener implements EventListenerInterface {
}
```

事件监听器可以实现 `\kuiper\event\EventListenerInterface` 或者 `\kuiper\event\EventSubscriberInterface` 接口，
前者只能监听一种事件，后者可监听多种事件。
