<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\ParameterType;
use PDO;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(Connection::class)]
#[CoversClass(Driver::class)]
#[CoversClass(Middleware::class)]
#[CoversClass(Statement::class)]
#[Group('integration')]
class IntegrationTest extends TestCase
{
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
            ->method('stopQuery')
            ->with(null);

        $conn->executeQuery('SELECT 1');

        $logger->expects(self::once())
            ->method('disconnect');

        $conn->close();
    }

    /**
     * @param class-string<DbalLogger> $loggerClass
     */
    #[DataProvider('loggers')]
    public function testBindValueByPosition(string $loggerClass): void
    {
        $logger = $this->createMock($loggerClass);
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
     * @param class-string<DbalLogger> $loggerClass
     */
    #[DataProvider('loggers')]
    public function testBindValueByName(string $loggerClass): void
    {
        $logger = $this->createMock($loggerClass);
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
     * @param class-string<DbalLogger> $loggerClass
     */
    #[DataProvider('loggers')]
    public function testExecAndQuery(string $loggerClass): void
    {
        $logger = self::createStub($loggerClass);
        $conn = $this->createDbal($logger);
        $rowCount = $conn->executeStatement("INSERT INTO users (id) VALUES ('a')");
        $rowCount = $conn->executeStatement("INSERT INTO users (id) VALUES ('b')");
        $rowCount = $conn->executeStatement("INSERT INTO users (id) VALUES ('c')");
        self::assertSame(1, $rowCount);

        $rows = $conn->executeQuery('SELECT * FROM users')->fetchAllAssociative();
        self::assertCount(3, $rows);
    }

    /**
     * @param class-string<DbalLogger> $loggerClass
     */
    #[DataProvider('loggers')]
    public function testCommit(string $loggerClass): void
    {
        $logger = $this->createMock($loggerClass);
        $callIndex = 0;
        $expectedCalls = [
            ['START TRANSACTION', null, null],
            ['INSERT INTO users (id) VALUES (:id)', ['id' => 'abc'], ['id' => ParameterType::STRING]],
            ['COMMIT', null, null],
        ];
        $logger->expects(self::exactly(3))
            ->method('startQuery')
            ->willReturnCallback(function ($sql, $params, $types) use (&$callIndex, $expectedCalls) {
                self::assertSame($expectedCalls[$callIndex][0], $sql);
                self::assertSame($expectedCalls[$callIndex][1], $params);
                self::assertSame($expectedCalls[$callIndex][2], $types);
                $callIndex++;
            });
        $conn = $this->createDbal($logger);
        $conn->beginTransaction();
        $stmt = $conn->prepare('INSERT INTO users (id) VALUES (:id)');
        $stmt->bindValue('id', 'abc');
        self::assertSame(1, $stmt->executeStatement());
        $conn->commit();
    }

    /**
     * @param class-string<DbalLogger> $loggerClass
     */
    #[DataProvider('loggers')]
    public function testRollback(string $loggerClass): void
    {
        $logger = $this->createMock($loggerClass);
        $callIndex = 0;
        $expectedCalls = [
            ['START TRANSACTION', null, null],
            ['INSERT INTO users (id) VALUES (:id)', ['id' => 'abc'], ['id' => ParameterType::STRING]],
            ['ROLLBACK', null, null],
        ];
        $logger->expects(self::exactly(3))
            ->method('startQuery')
            ->willReturnCallback(function ($sql, $params, $types) use (&$callIndex, $expectedCalls) {
                self::assertSame($expectedCalls[$callIndex][0], $sql);
                self::assertSame($expectedCalls[$callIndex][1], $params);
                self::assertSame($expectedCalls[$callIndex][2], $types);
                $callIndex++;
            });
        $conn = $this->createDbal($logger);
        $conn->beginTransaction();
        $stmt = $conn->prepare('INSERT INTO users (id) VALUES (:id)');
        $stmt->bindValue('id', 'abc');
        self::assertSame(1, $stmt->executeStatement());
        $conn->rollBack();
    }

    /**
     * @param class-string<DbalLogger> $loggerClass
     */
    #[DataProvider('loggers')]
    public function testStopQueryReceivesExceptionOnQueryFailure(string $loggerClass): void
    {
        $logger = $this->createMock($loggerClass);
        $conn = $this->createDbal($logger);

        $logger->expects(self::once())
            ->method('startQuery')
            ->with('SELECT * FROM nonexistent_table');
        $logger->expects(self::once())
            ->method('stopQuery')
            ->with(self::isInstanceOf(Throwable::class));

        $this->expectException(Throwable::class);
        $conn->executeQuery('SELECT * FROM nonexistent_table');
    }

    /**
     * @param class-string<DbalLogger> $loggerClass
     */
    #[DataProvider('loggers')]
    public function testStopQueryReceivesExceptionOnExecFailure(string $loggerClass): void
    {
        $logger = $this->createMock($loggerClass);
        $conn = $this->createDbal($logger);

        $logger->expects(self::once())
            ->method('startQuery')
            ->with('INSERT INTO nonexistent_table (id) VALUES (1)');
        $logger->expects(self::once())
            ->method('stopQuery')
            ->with(self::isInstanceOf(Throwable::class));

        $this->expectException(Throwable::class);
        $conn->executeStatement('INSERT INTO nonexistent_table (id) VALUES (1)');
    }

    /**
     * @param class-string<DbalLogger> $loggerClass
     */
    #[DataProvider('loggers')]
    public function testStopQueryReceivesExceptionOnPreparedStatementFailure(string $loggerClass): void
    {
        $logger = $this->createMock($loggerClass);
        $conn = $this->createDbal($logger);
        $this->insertRow($conn, 'a');

        $logger->expects(self::once())
            ->method('startQuery');
        $logger->expects(self::once())
            ->method('stopQuery')
            ->with(self::isInstanceOf(Throwable::class));

        $this->expectException(Throwable::class);
        $stmt = $conn->prepare('INSERT INTO users (id) VALUES (:id)');
        $stmt->bindValue('id', 'a');
        $stmt->executeStatement();
    }

    /**
     * @param class-string<DbalLogger> $loggerClass
     */
    #[DataProvider('loggers')]
    public function testStopQueryReceivesExceptionOnBeginTransactionFailure(string $loggerClass): void
    {
        $logger = $this->createMock($loggerClass);
        $conn = $this->createDbal($logger);

        // Get native connection and start transaction directly to bypass Doctrine's tracking
        $pdo = $conn->getNativeConnection();
        assert($pdo instanceof PDO);
        $pdo->beginTransaction();

        $logger->expects(self::once())
            ->method('startQuery')
            ->with('START TRANSACTION');
        $logger->expects(self::once())
            ->method('stopQuery')
            ->with(self::isInstanceOf(Throwable::class));

        $this->expectException(Throwable::class);
        $conn->beginTransaction();
    }

    /**
     * @param class-string<DbalLogger> $loggerClass
     */
    #[DataProvider('loggers')]
    public function testStopQueryReceivesExceptionOnCommitFailure(string $loggerClass): void
    {
        $logger = $this->createMock($loggerClass);
        $conn = $this->createDbal($logger);

        // Start transaction through DBAL, then roll it back via PDO
        // so DBAL's commit will fail
        $conn->beginTransaction();
        $pdo = $conn->getNativeConnection();
        assert($pdo instanceof PDO);
        $pdo->rollBack();

        $logger->expects(self::once())
            ->method('startQuery')
            ->with('COMMIT');
        $logger->expects(self::once())
            ->method('stopQuery')
            ->with(self::isInstanceOf(Throwable::class));

        $this->expectException(Throwable::class);
        $conn->commit();
    }

    /**
     * @param class-string<DbalLogger> $loggerClass
     */
    #[DataProvider('loggers')]
    public function testStopQueryReceivesExceptionOnRollbackFailure(string $loggerClass): void
    {
        $logger = $this->createMock($loggerClass);
        $conn = $this->createDbal($logger);

        // Start transaction through DBAL, then commit it via PDO
        // so DBAL's rollback will fail
        $conn->beginTransaction();
        $pdo = $conn->getNativeConnection();
        assert($pdo instanceof PDO);
        $pdo->commit();

        $logger->expects(self::once())
            ->method('startQuery')
            ->with('ROLLBACK');
        $logger->expects(self::once())
            ->method('stopQuery')
            ->with(self::isInstanceOf(Throwable::class));

        $this->expectException(Throwable::class);
        $conn->rollBack();
    }

    /**
     * @return array{class-string<DbalLogger>}[]
     */
    public static function loggers(): array
    {
        return [
            'DbalLogger' => [DbalLogger::class],
        ];
    }

    private function createDbal(DbalLogger $logger): DBALConnection
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

    private function insertRow(DBALConnection $conn, string $id): void
    {
        $conn->executeStatement("INSERT INTO users (id) VALUES (:id)", ['id' => $id]);
    }
}
