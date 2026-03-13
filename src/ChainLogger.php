<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Throwable;

/**
 * Runs all of the specified loggers, FIFO.
 */
class ChainLogger implements DbalLogger
{
    /**
     * @param QueryLogger[] $loggers
     */
    public function __construct(private array $loggers)
    {
    }

    public function connect(): void
    {
        foreach ($this->loggers as $logger) {
            if ($logger instanceof DbalLogger) {
                $logger->connect();
            }
        }
    }

    public function disconnect(): void
    {
        foreach ($this->loggers as $logger) {
            if ($logger instanceof DbalLogger) {
                $logger->disconnect();
            }
        }
    }

    public function startQuery(string $sql, ?array $params = null, ?array $types = null): void
    {
        foreach ($this->loggers as $logger) {
            $logger->startQuery($sql, $params, $types);
        }
    }

    public function stopQuery(?Throwable $exception = null): void
    {
        foreach ($this->loggers as $logger) {
            $logger->stopQuery($exception);
        }
    }
}
