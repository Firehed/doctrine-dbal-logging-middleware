# DBAL Logger

A customizable query logger for Doctrine DBAL.

This can be used in a number of ways:

- Debug logging
- Telemetry
- Capturing SQL queries for audit trails
- And more! Be creative!

[![Test](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/test.yml/badge.svg)](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/test.yml)
[![Lint](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/lint.yml/badge.svg)](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/lint.yml)
[![Static analysis](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/static-analysis.yml)
[![codecov](https://codecov.io/gh/Firehed/doctrine-dbal-logging-middleware/branch/main/graph/badge.svg?token=rcevTlCKj3)](https://codecov.io/gh/Firehed/doctrine-dbal-logging-middleware)

## Installation

```bash
composer require firehed/dbal-logger
```

## Usage

1. Implement `Firehed\DbalLogger\DbalLogger`
2. Wrap it in the middleware
3. Add the middleware to your DBAL configuration

```php
use Firehed\DbalLogger\DbalLogger;
use Firehed\DbalLogger\Middleware;

class MyLogger implements DbalLogger
{
    public function startQuery(string $sql, ?array $params = null, ?array $types = null): void
    {
        // Called before each query
        // Commonly: store $sql and start a timer
    }

    public function stopQuery(?Throwable $exception = null): void
    {
        // Called after each query completes (or fails)
        // Commonly: determine the query duration based on the start timer and log or send a telemetry event
    }

    public function connect(): void
    {
        // Called when a connection is established
    }

    public function disconnect(): void
    {
        // Called when a connection is closed
    }
}

$logger = new MyLogger();
$middleware = new Middleware($logger);

// Add to your Doctrine\DBAL\Configuration
$config = new \Doctrine\DBAL\Configuration();
$config->setMiddlewares([$middleware]);

$connection = \Doctrine\DBAL\DriverManager::getConnection($connectionDetails, $config);
```

> [!TIP]
> Use dependency injection to provide services to your DbalLogger implementation,
> such as a log writer (e.g. PSR-3) or telemetry system.

## Error Handling

The `stopQuery()` method receives the exception if a query fails, or `null` on success.
This enables query timing, failure tracking, and telemetry:

```php
public function stopQuery(?Throwable $exception = null): void
{
    $duration = hrtime(true) - $this->start;
    if ($exception !== null) {
        $this->logger->error('Query failed', [
            'sql' => $this->sql,
            'duration' => $duration,
            'exception' => $exception,
        ]);
    } else {
        $this->logger->debug('Query completed', [
            'sql' => $this->sql,
            'duration' => $duration,
        ]);
    }
}
```

## Multiple Loggers

Use `ChainLogger` to send events to multiple loggers:

```php
use Firehed\DbalLogger\ChainLogger;
use Firehed\DbalLogger\Middleware;

$chain = new ChainLogger([
    new QueryLogger(),
    new MetricsLogger(),
    new AuditTrailLogger(),
]);

$config = new \Doctrine\DBAL\Configuration();
$config->setMiddlewares([new Middleware($chain)]);
```

## Migrating from SQLLogger

If you're migrating from the deprecated `Doctrine\DBAL\Logging\SQLLogger`:

- Change `implements SQLLogger` to `implements DbalLogger`
- Add `connect(): void` and `disconnect(): void` methods (can be no-ops)
- The `stopQuery()` method now accepts `?Throwable $exception`
- Wrap your logger in the middleware and configure DBAL:

```diff
-$config->setSQLLogger($yourSQLLogger);
+$config->setMiddlewares([new Middleware($yourLogger)]);
```

## Why this library?

Doctrine's bundled middleware-based replacement for SQLLogger has limitations:
- It's tied directly to a PSR-3 logger
- The log format and levels cannot be customized
- There is no event for queries completing, making telemetry impossible

This library provides full control over logging behavior while using DBAL's middleware system.

## Misc

This project follows semantic versioning.

Please use Github for reporting any issues or making any feature requests.
