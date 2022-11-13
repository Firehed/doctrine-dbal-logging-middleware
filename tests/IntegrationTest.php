<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group integration
 *
 * @covers Firehed\DbalLogger\Connection
 * @covers Firehed\DbalLogger\Driver
 * @covers Firehed\DbalLogger\Middleware
 * @covers Firehed\DbalLogger\SqlLoggerBridge
 * @covers Firehed\DbalLogger\Statement
 */
class IntegrationTest extends \PHPUnit\Framework\TestCase
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

        $conn->close();
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

    /**
     * @dataProvider loggers
     */
    public function testCommit(MockObject&QueryLogger $logger): void
    {
        $logger->expects(self::exactly(3))
            ->method('startQuery')
            ->withConsecutive(
                ['START TRANSACTION', null, null],
                ['INSERT INTO users (id) VALUES (:id)', ['id' => 'abc'], ['id' => 2]],
                ['COMMIT', null, null],
            );
        $conn = $this->createDbal($logger);
        self::assertTrue($conn->beginTransaction());
        $stmt = $conn->prepare('INSERT INTO users (id) VALUES (:id)');
        $stmt->bindValue('id', 'abc');
        self::assertSame(1, $stmt->executeStatement());
        self::assertTrue($conn->commit());
    }

    /**
     * @dataProvider loggers
     */
    public function testRollback(MockObject&QueryLogger $logger): void
    {
        $logger->expects(self::exactly(3))
            ->method('startQuery')
            ->withConsecutive(
                ['START TRANSACTION', null, null],
                ['INSERT INTO users (id) VALUES (:id)', ['id' => 'abc'], ['id' => 2]],
                ['ROLLBACK', null, null],
            );
        $conn = $this->createDbal($logger);
        self::assertTrue($conn->beginTransaction());
        $stmt = $conn->prepare('INSERT INTO users (id) VALUES (:id)');
        $stmt->bindValue('id', 'abc');
        self::assertSame(1, $stmt->executeStatement());
        self::assertTrue($conn->rollback());
    }

    /**
     * @return array{MockObject}[]
     */
    public function loggers(): array
    {
        return [
            'QueryLogger' => [self::createMock(QueryLogger::class)],
            'DbalLogger' => [self::createMock(DbalLogger::class)],
        ];
    }
}
