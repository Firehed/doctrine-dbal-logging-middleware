<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\ParameterType;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Connection::class)]
#[CoversClass(Driver::class)]
#[CoversClass(Middleware::class)]
#[CoversClass(SqlLoggerBridge::class)]
#[CoversClass(Statement::class)]
#[Group('integration')]
class IntegrationTest extends TestCase
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

    #[DataProvider('loggers')]
    public function testBindValueByPosition(QueryLogger&MockObject $logger): void
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

    #[DataProvider('loggers')]
    public function testBindValueByName(QueryLogger&MockObject $logger): void
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

    #[DataProvider('loggers')]
    public function testExecAndQuery(QueryLogger&MockObject $logger): void
    {
        $conn = $this->createDbal($logger);
        $rowCount = $conn->executeStatement("INSERT INTO users (id) VALUES ('a')");
        $rowCount = $conn->executeStatement("INSERT INTO users (id) VALUES ('b')");
        $rowCount = $conn->executeStatement("INSERT INTO users (id) VALUES ('c')");
        self::assertSame(1, $rowCount);

        $rows = $conn->executeQuery('SELECT * FROM users')->fetchAllAssociative();
        self::assertCount(3, $rows);
    }

    #[DataProvider('loggers')]
    public function testCommit(QueryLogger&MockObject $logger): void
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

    #[DataProvider('loggers')]
    public function testRollback(QueryLogger&MockObject $logger): void
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
