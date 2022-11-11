<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

/**
 * @internal
 */
class SqlLoggerBridge implements DbalLogger
{
    private QueryLogger $logger;

    public function __construct(QueryLogger $logger)
    {
        $this->logger = $logger;
    }

    public function startQuery($sql, ?array $params = null, ?array $types = null): void
    {
        $this->logger->startQuery($sql, $params, $types);
    }

    public function stopQuery(): void
    {
        $this->logger->stopQuery();
    }

    public function connect(): void
    {
        // no-op
    }

    public function disconnect(): void
    {
        // no-op
    }
}
