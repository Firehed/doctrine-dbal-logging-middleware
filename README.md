# DBAL Logger
A replacement for the the former Doctrine DBAL SQLLogger

[![Test](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/test.yml/badge.svg)](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/test.yml)
[![Lint](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/lint.yml/badge.svg)](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/lint.yml)
[![Static analysis](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/Firehed/doctrine-dbal-logging-middleware/actions/workflows/static-analysis.yml)
[![codecov](https://codecov.io/gh/Firehed/doctrine-dbal-logging-middleware/branch/main/graph/badge.svg?token=rcevTlCKj3)](https://codecov.io/gh/Firehed/doctrine-dbal-logging-middleware)

## Why?
Doctrine\DBAL\Logging\SQLLogger was deprecated.
The bundled Middleware-based replacement is _similar_, but with a few critical differences:

- It's tied directly to a PSR-3 logger
- The log format (and levels) is part of the middleware; it cannot be customized
- There is no event for queries completing; this makes it impossible to use the logger for application telemetry.

## How this is similar to the original

The basic API remains the same: `startQuery()` and `stopQuery()`.

## Error Handling

The `stopQuery()` method accepts an optional `?\Throwable $exception` parameter.
If the query failed with an exception, it will be passed to `stopQuery()`.
On success, `null` is passed.

This allows your logger to record query failures for debugging or metrics purposes:

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

## How this is different from the original

`Doctrine\DBAL\Logging\SQLLogger` is now `Firehed\DbalLogger\DbalLogger`.

Setup for DBAL/ORM is different; that's inherent to the deprecation that prompted the creation of this library.

The port of the original SQLLogger did not have native return types, instead favoring docblocks.
This adds an explicit return type to the interface.

The `DbalLogger` interface also includes `connect()` and `disconnect()` hooks.
If you don't need these, implement them as no-ops.

The `SAVEPOINT` queries either will show up in their underlying connection-specific syntax or possibly not at all.
I'm not sure how to test this!
(doctrine/dbal/src/Connection.php and thereabouts)

You can now find out about query failres (see Error Handling, above)

## How to use this
If you have an implemenation of the DBAL SQLLogger interface (which is probably the case if you're here), you'll need to make the following changes:

- Have it implement `Firehed\DbalLogger\DbalLogger` instead of `Doctrine\DBAL\Logging\SQLLogger`
- Add `connect(): void` and `disconnect(): void` methods (can be no-ops)
- Wrap it in Middleware: `$middleware = new Firehed\DbalLogger\Middleware($yourLogger);`
- Adjust your DBAL/Doctrine setup code to use the Middleware instead of directly using the Logger:
```diff
-$config->setSQLLogger($yourSQLLogger);
+$config->setMiddlewares([$middleware]);
```

If you _don't_ have a SQLLogger implementation you're looking to migrate, you'll want create one!

1) Implement `Firehed\DbalLogger\DbalLogger`
2) Wrap it in a middleware: `$middleware = new \Firehed\DbalLogger\Middleware($instanceOfYourClass);`
3) Add it to the DBAL/Doctrine config, per above.

That should do it!

## I need to log to multiple backends!

No problem - there's a built in `ChainLogger` that accepts an array of `DbalLogger` instances.
When configured, it will relay all of the logging events it receives to each of the loggers.

```php
$logger1 = new MyLogger();
$logger2 = new MyOtherLogger(); // Maybe metrics?
$chain = new ChainLogger([$logger1, $logger2]);
$config->setMiddlewares([new LoggerMiddleware($chain)])
```

## Misc

This project follows semantic versioning.

Please use Github for reporting any issues or making any feature requests.
