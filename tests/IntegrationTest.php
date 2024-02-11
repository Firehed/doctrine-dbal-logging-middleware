<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\ParameterType;
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

        $conn->executeQuery('SELECT 1');

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

        $conn->executeQuery('SELECT 1');

        $logger->expects(self::once())
            ->method('disconnect');

        $conn->close();
    }

    /**
     * @dataProvider loggers
     * @param MockObject&QueryLogger $logger
     */
    public function testBindValueByPosition(QueryLogger $logger): void
    {
        $conn = $this->createDbal($logger);
        $this->insertRow($conn, 'a');

        $logger->expects(self::once())
            ->method('startQuery')
            ->with('SELECT * FROM users WHERE id = ?', [1 => 'a'], [1 => ParameterType::STRING]);

        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bindValue(1, 'a');
        $results = $stmt->executeQuery();
        self::assertCount(1, $results->fetchAllAssociative());
    }

    /**
     * @dataProvider loggers
     * @param MockObject&QueryLogger $logger
     */
    public function testBindValueByName(QueryLogger $logger): void
    {
        $conn = $this->createDbal($logger);
        $this->insertRow($conn, 'a');

        $logger->expects(self::once())
            ->method('startQuery')
            ->with('SELECT * FROM users WHERE id = :id', ['id' => 'a'], ['id' => ParameterType::STRING]);

        $stmt = $conn->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->bindValue('id', 'a');
        $results = $stmt->executeQuery();
        self::assertCount(1, $results->fetchAllAssociative());
    }

    /**
     * @dataProvider loggers
     * @param MockObject&QueryLogger $logger
     */
    public function testExecAndQuery(QueryLogger $logger): void
    {
        $conn = $this->createDbal($logger);
        $rowCount = $conn->executeStatement("INSERT INTO users (id) VALUES ('a')");
        $rowCount = $conn->executeStatement("INSERT INTO users (id) VALUES ('b')");
        $rowCount = $conn->executeStatement("INSERT INTO users (id) VALUES ('c')");
        self::assertSame(1, $rowCount);

        $rows = $conn->executeQuery('SELECT * FROM users')->fetchAllAssociative();
        self::assertCount(3, $rows);
    }

     /**
     * @dataProvider loggers
     * @param MockObject&QueryLogger $logger
     */
    public function testCommit(QueryLogger $logger): void
    {
        $logger->expects(self::exactly(3))
            ->method('startQuery')
            ->withConsecutive(
                ['START TRANSACTION', null, null],
                ['INSERT INTO users (id) VALUES (:id)', ['id' => 'abc'], ['id' => ParameterType::STRING]],
                ['COMMIT', null, null],
            );
        $conn = $this->createDbal($logger);
        $conn->beginTransaction();
        $stmt = $conn->prepare('INSERT INTO users (id) VALUES (:id)');
        $stmt->bindValue('id', 'abc');
        self::assertSame(1, $stmt->executeStatement());
        $conn->commit();
    }

    /**
     * @dataProvider loggers
     * @param MockObject&QueryLogger $logger
     */
    public function testRollback(QueryLogger $logger): void
    {
        $logger->expects(self::exactly(3))
            ->method('startQuery')
            ->withConsecutive(
                ['START TRANSACTION', null, null],
                ['INSERT INTO users (id) VALUES (:id)', ['id' => 'abc'], ['id' => ParameterType::STRING]],
                ['ROLLBACK', null, null],
            );
        $conn = $this->createDbal($logger);
        $conn->beginTransaction();
        $stmt = $conn->prepare('INSERT INTO users (id) VALUES (:id)');
        $stmt->bindValue('id', 'abc');
        self::assertSame(1, $stmt->executeStatement());
        $conn->rollBack();
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

    private function createDbal(QueryLogger $logger): Connection
    {
        $connectionParams = [
            'url' => 'sqlite:///:memory:',
            'driver' => 'pdo_sqlite',
        ];
        $config = new Configuration();
        $config->setMiddlewares([new Middleware($logger)]);
        $conn = DriverManager::getConnection($connectionParams, $config);

        $pdo = $conn->getNativeConnection();
        assert($pdo instanceof PDO);
        $pdo->exec('CREATE TABLE users (id string PRIMARY KEY);');

        return $conn;
    }

    private function insertRow(Connection $conn, string $id): void
    {
        $conn->executeStatement("INSERT INTO users (id) VALUES (:id)", ['id' => $id]);
    }
}
