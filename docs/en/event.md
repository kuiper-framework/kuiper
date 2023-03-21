# Event Dispatcher

Kuiper Event is based on the PSR-14 interface and is implemented using `symfony/event-dispatcher`.

## Installation

```bash
composer require kuiper/event:^0.8
```

Configure in `src/config.php`:

```php
<?php

return [nnnnnnnn
    'application' => [
        'listeners' => [
            MyFooEventListener::class
        ]
    ]n
];
```

Alternatively, use the `kuiper\event\attribute\EventListener`
annotation and event listeners in the namespace scan directory are automatically added to the Event Dispatcher.
For example:

```php
<?php

namespace app\listeners;

use kuiper\event\attribute\EventListener;
use kuiper\event\EventListenerInterface;

#[EventListener]
class MyFooEventListener implements EventListenerInterface {
}
```

Event listeners can implement the `kuiper\event\EventListenerInterface` or `kuiper\event\EventSubscriberInterface` interface,
The former can only listen for one type of event, while the latter can listen for multiple events.
