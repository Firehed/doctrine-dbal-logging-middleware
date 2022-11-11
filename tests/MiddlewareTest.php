<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

/**
 * @covers Middleware
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
        $c->query('SELECT 1');
    }

    public function createDbal(Middleware $middleware)
    {
        $connectionParams = [
            // 'url' => 'mysql://user:secret@localhost/mydb',
            'url' => 'sqlite:///:memory:',
        ];
        $config = new \Doctrine\DBAL\Configuration();
        $config->setMiddlewares([$middleware]);
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

        return $conn;
    }
}
