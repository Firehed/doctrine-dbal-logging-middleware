<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Throwable;

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

    public function startQuery(string $sql, ?array $params = null, ?array $types = null): void
    {
        $this->logger->startQuery($sql, $params, $types);
    }

    public function stopQuery(?Throwable $exception = null): void
    {
        $this->logger->stopQuery($exception);
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
