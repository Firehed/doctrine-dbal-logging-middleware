<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Psr\Log\LoggerInterface;

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
    public function connect(array $params)
    {
        $this->logger->connect();

        return new Connection(
            parent::connect($params),
            $this->logger,
        );
    }

    /**
     * @param array<string,mixed> $params Connection parameters
     *
     * @return array<string,mixed>
     */
    private function maskPassword(array $params): array
    {
        if (isset($params['password'])) {
            $params['password'] = '<redacted>';
        }

        if (isset($params['url'])) {
            $params['url'] = '<redacted>';
        }

        return $params;
    }
}
