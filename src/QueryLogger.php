<?php

declare(strict_types=1);

namespace Firehed\DbalLogger;

use Doctrine\DBAL\ParameterType;
use Throwable;

/**
 * @api
 */
interface QueryLogger
{
    /**
     * Logs a SQL statement somewhere.
     *
     * @param array<int|string, mixed>|null $params Statement parameters
     * @param ParameterType[] $types
     */
    public function startQuery(string $sql, ?array $params = null, ?array $types = null): void;

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * If the query failed with an exception of any kind, it will be provided.
     */
    public function stopQuery(?Throwable $exception = null): void;

    public function connect(): void;

    public function disconnect(): void;
}
