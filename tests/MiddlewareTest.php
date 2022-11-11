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
    public function testConstruct(): void
    {
        $logger = self::createMock(QueryLogger::class);
        $logger->expects(self::once())
            ->method('startQuery')
            ->willReturnCallback(var_dump(...));
        $middleware = new Middleware($logger);

        $c = $this->createDbal($middleware);
        // var_dump($c);
        // $c->query('SELECT 1');

        $s = $c->prepare('SELECT * FROM users WHERE id = :id');
        $s->bindValue('id', 'abcdef');

        $r = $s->executeQuery();
    }

    public function createDbal(Middleware $middleware): Connection
    {
        $connectionParams = [
            'url' => 'sqlite:///:memory:',
        ];
        $config = new Configuration();
        $config->setMiddlewares([$middleware]);
        $conn = DriverManager::getConnection($connectionParams, $config);

        $pdo = $conn->getWrappedConnection()->getNativeConnection();
        assert($pdo instanceof PDO);
        $pdo->exec('CREATE TABLE users (id string PRIMARY KEY);');

        return $conn;
    }
}
