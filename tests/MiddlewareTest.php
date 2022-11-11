<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PDO;

/**
 * @covers Firehed\DbalLogger\Middleware
 */
class MiddlewareTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructWithQueryLogger(): void
    {
        $logger = self::createMock(QueryLogger::class);

        $conn = $this->createDbal($logger);

        $logger->expects(self::once())
            ->method('startQuery')
            ->with('SELECT 1', null, null);
        $logger->expects(self::once())
            ->method('stopQuery');

        $conn->query('SELECT 1');
    }

    public function testConstructWithDbalLogger(): void
    {
        $logger = self::createMock(DbalLogger::class);
        $logger->expects(self::once())
            ->method('connect');

        $conn = $this->createDbal($logger);

        $logger->expects(self::once())
            ->method('startQuery')
            ->with('SELECT 1', null, null);
        $logger->expects(self::once())
            ->method('stopQuery');

        $conn->query('SELECT 1');

        $logger->expects(self::once())
            ->method('disconnect');

        $conn->close();
    }

    public function createDbal(QueryLogger $logger): Connection
    {
        $connectionParams = [
            'url' => 'sqlite:///:memory:',
        ];
        $config = new Configuration();
        $config->setMiddlewares([new Middleware($logger)]);
        $conn = DriverManager::getConnection($connectionParams, $config);

        $pdo = $conn->getWrappedConnection()->getNativeConnection();
        assert($pdo instanceof PDO);
        $pdo->exec('CREATE TABLE users (id string PRIMARY KEY);');

        return $conn;
    }
}
