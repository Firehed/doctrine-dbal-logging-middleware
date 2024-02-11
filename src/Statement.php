<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;

use function array_slice;
use function func_get_args;
use function func_num_args;

/**
 * @internal
 */
final class Statement extends AbstractStatementMiddleware
{
    private DbalLogger $logger;
    private string $sql;

    /** @var mixed[] */
    private array $params = [];

    /** @var ParameterType[] */
    private array $types = [];

    /** @internal This statement can be only instantiated by its connection. */
    public function __construct(StatementInterface $statement, DbalLogger $logger, string $sql)
    {
        parent::__construct($statement);

        $this->logger = $logger;
        $this->sql    = $sql;
    }

    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        $this->params[$param] = $value;
        $this->types[$param]  = $type;

        parent::bindValue($param, $value, $type);
    }

    public function execute(): ResultInterface
    {
        $this->logger->startQuery($this->sql, $this->params, $this->types);
        try {
            return parent::execute();
        } finally {
            $this->logger->stopQuery();
        }
    }
}
