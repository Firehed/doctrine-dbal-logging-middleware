<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class Connection extends AbstractConnectionMiddleware
{
    private DbalLogger $logger;

    /** @internal This connection can be only instantiated by its driver. */
    public function __construct(ConnectionInterface $connection, DbalLogger $logger)
    {
        parent::__construct($connection);

        $this->logger = $logger;
    }

    public function __destruct()
    {
        $this->logger->disconnect();
    }

    public function prepare(string $sql): DriverStatement
    {
        return new Statement(
            parent::prepare($sql),
            $this->logger,
            $sql,
        );
    }

    public function query(string $sql): Result
    {
        $this->logger->startQuery($sql);
        try {
            return parent::query($sql);
        } finally {
            $this->logger->stopQuery();
        }
    }

    public function exec(string $sql): int|string
    {
        $this->logger->startQuery($sql);
        try {
            return parent::exec($sql);
        } finally {
            $this->logger->stopQuery();
        }
    }

    public function beginTransaction(): void
    {
        $this->logger->startQuery('START TRANSACTION');
        try {
            parent::beginTransaction();
        } finally {
            $this->logger->stopQuery();
        }
    }

    public function commit(): void
    {
        $this->logger->startQuery('COMMIT');
        try {
            parent::commit();
        } finally {
            $this->logger->stopQuery();
        }
    }

    public function rollBack(): void
    {
        $this->logger->startQuery('ROLLBACK');
        try {
            parent::rollBack();
        } finally {
            $this->logger->stopQuery();
        }
    }
}
