<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Psr\Log\LoggerInterface;

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
        // $this->logger->debug('Executing query: {sql}', ['sql' => $sql]);

        return parent::query($sql);
    }

    public function exec(string $sql): int
    {
        // $this->logger->debug('Executing statement: {sql}', ['sql' => $sql]);

        return parent::exec($sql);
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        $this->logger->startQuery('START TRANSACTION');
        try {
            return parent::beginTransaction();
        } finally {
            $this->logger->stopQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        $this->logger->startQuery('COMMIT');
        try {
            return parent::commit();
        } finally {
            $this->logger->stopQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        $this->logger->startQuery('ROLLBACK');
        try {
            return parent::rollBack();
        } finally {
            $this->logger->stopQuery();
        }
    }
}
