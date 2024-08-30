<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Firehed\DbalLogger\ChainLogger
 */
class ChainLoggerTest extends TestCase
{
    private DbalLogger&MockObject $l1;
    private DbalLogger&MockObject $l2;
    private QueryLogger&MockObject $l3;
    private ChainLogger $logger;

    public function setUp(): void
    {
        $this->l1 = self::createMock(DbalLogger::class);
        $this->l2 = self::createMock(DbalLogger::class);
        $this->l3 = self::createMock(QueryLogger::class);
        $this->logger = new ChainLogger([$this->l1, $this->l2, $this->l3]);
    }

    public function testConnectDelegates(): void
    {
        $this->l1->expects(self::once())->method('connect');
        $this->l2->expects(self::once())->method('connect');
        // No l3
        $this->logger->connect();
    }

    public function testDisconnectDelegates(): void
    {
        $this->l1->expects(self::once())->method('disconnect');
        $this->l2->expects(self::once())->method('disconnect');
        // No l3
        $this->logger->disconnect();
    }

    public function testStartQueryDelegates(): void
    {
        $sql = 'SELECT * FROM foos WHERE id = ? AND bar IN (?, ?)';
        $params = [1, 'a', 'b'];
        $types = [ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING];
        $this->l1->expects(self::once())->method('startQuery')->with($sql, $params, $types);
        $this->l2->expects(self::once())->method('startQuery')->with($sql, $params, $types);
        $this->l3->expects(self::once())->method('startQuery')->with($sql, $params, $types);
        $this->logger->startQuery($sql, $params, $types);
    }

    public function testStopQueryDelegates(): void
    {
        $this->l1->expects(self::once())->method('stopQuery');
        $this->l2->expects(self::once())->method('stopQuery');
        $this->l3->expects(self::once())->method('stopQuery');
        $this->logger->stopQuery();
    }
}
