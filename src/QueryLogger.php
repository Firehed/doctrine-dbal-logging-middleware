<?php

namespace Firehed\DbalLogger;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Throwable;

/**
 * @api
 */
interface QueryLogger
{
    /**
     * Logs a SQL statement somewhere.
     *
     * @param string $sql SQL statement
     * @param array<int|string, mixed>|null $params Statement parameters
     * @param ParameterType[] $types
     *
     * @return void
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null);

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * If the query failed with an exception of any kind, it will be provided.
     *
     * @return void
     */
    public function stopQuery(?Throwable $exception = null);
}
