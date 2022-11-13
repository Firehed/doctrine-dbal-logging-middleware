<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use Psr\Log\LoggerInterface;

/**
 * @api
 */
final class Middleware implements MiddlewareInterface
{
    private DbalLogger $logger;

    public function __construct(QueryLogger $logger)
    {
        if (!$logger instanceof DbalLogger) {
            $logger = new SqlLoggerBridge($logger);
        }
        $this->logger = $logger;
    }

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new Driver($driver, $this->logger);
    }
}
