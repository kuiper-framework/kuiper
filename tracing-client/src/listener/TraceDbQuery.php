<?php

declare(strict_types=1);

namespace kuiper\tracing\listener;

use kuiper\db\ConnectionInterface;
use kuiper\db\event\StatementQueriedEvent;
use kuiper\db\Statement;
use kuiper\event\EventListenerInterface;
use kuiper\tracing\Tracer;
use OpenTracing\NoopSpan;

use const OpenTracing\Tags\DATABASE_STATEMENT;
use const OpenTracing\Tags\DATABASE_TYPE;
use const OpenTracing\Tags\ERROR;

use PDO;
use Webmozart\Assert\Assert;

class TraceDbQuery implements EventListenerInterface
{
    public function __invoke($event): void
    {
        $span = Tracer::get()->getActiveSpan();
        if (null === $span || ($span instanceof NoopSpan)) {
            return;
        }
        Assert::isInstanceOf($event, StatementQueriedEvent::class);
        /** @var StatementQueriedEvent $event */
        /** @var Statement $stmt */
        $stmt = $event->getStatement();

        $sql = preg_replace('/\s+/', ' ', $stmt->getStatement());
        $params = [];
        foreach ($stmt->getBindValues() as $name => $value) {
            $params[':'.$name] = is_string($value) ? "'".addslashes($value)."'" : $value;
        }

        $dbType = null;
        $stmt->withConnection(function (ConnectionInterface $conn) use (&$dbType) {
            $dbType = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        });
        $scope = Tracer::get()->startActiveSpan('query on '.$dbType, [
            'start_time' => (int) ($stmt->getStartTime() * 1000 * 1000),
        ]);
        $span = $scope->getSpan();
        $span->setTag(DATABASE_STATEMENT, strtr($sql, $params));
        $span->setTag(DATABASE_TYPE, $dbType);
        if (null !== $event->getException()) {
            $span->setTag(ERROR, $event->getException()->getMessage());
        }
        $scope->close();
    }

    public function getSubscribedEvent(): string
    {
        return StatementQueriedEvent::class;
    }
}
