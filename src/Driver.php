<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class Driver extends AbstractDriverMiddleware
{
    private DbalLogger $logger;

    /** @internal This driver can be only instantiated by its middleware. */
    public function __construct(DriverInterface $driver, DbalLogger $logger)
    {
        parent::__construct($driver);

        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function connect(array $params): DriverConnection
    {
        $this->logger->connect();

        return new Connection(
            parent::connect($params),
            $this->logger,
        );
    }
}
