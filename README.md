# DBAL Logger
A replacement for the the former Doctrine DBAL SQLLogger

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

## Misc

This project follows semantic versioning.

Please use Github for reporting any issues or making any feature requests.
