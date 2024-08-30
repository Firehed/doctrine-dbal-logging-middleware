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

The basic `QueryLogger` API remains the same: `startQuery()` and `stopQuery()`.

## How this is different from the original

`Doctrine\DBAL\Logging\SQLLogger` is now `Firehed\DbalLogger\QueryLogger` (the API remains the same).

Setup for DBAL/ORM is different; that's inherent to the deprecation that prompted the creation of this library.

The port of the original SQLLogger did not have native return types, instead favoring docblocks.
This adds an explicit return type to the interface.

There's a new `DbalLogger` interface which your logger can also implement, creating hooks for `connect()` and `disconnect()` events.
This is optional, and if you want a low-effort conversion, it's fine to stick with the basic `QueryLogger` interface.

The `SAVEPOINT` queries either will show up in their underlying connection-specific syntax or possibly not at all.
I'm not sure how to test this!
(doctrine/dbal/src/Connection.php and thereabouts)

## How to use this
If you have an implemenation of the DBAL SQLLogger interface (which is probably the case if you're here), you'll need to make the following changes:

- Have it implement `Firehed\DbalLogger\QueryLogger` instead of `Doctrine\DBAL\Logging\SQLLogger`
- Wrap it in Middleware: `$middleware = new Firehed\DbalLogger\Middleware($yourQueryLogger);`
- Adjust your DBAL/Doctrine setup code to use the Middleware instead of directly using the Logger:
```diff
-$config->setSQLLogger($yourSQLLogger);
+$config->setMiddlewares([$middleware]);
```

If you _don't_ have a SQLLogger implementation you're looking to migrate, you'll want create one!

1) Implement `Firehed\Dbal\QueryLogger` or `Firehed\DbalLogger\DbalLogger`
2) Wrap it in a middleware: `$middleware = new \Fireheed\DbalLogger\Middleware($instanceOfYourClass);`
3) Add it to the DBAL/Doctrine config, per above.

That should do it!

## I need to log to multiple backends!

No problem - there's a built in `ChainLogger` that accepts an array of `QueryLogger`/`DbalLogger` instances.
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
