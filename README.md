# DBAL Logger
A replacement for the the former Doctrine DBAL SQLLogger

## Why?
Doctrine\DBAL\Logging\SQLLogger was deprecated.
The bundled Middleware-based replacement is _similar_, but with a few critical differences:

- It's tied directly to a PSR-3 logger
- The log format (and levels) is part of the middleware; it cannot be customized
- There is no event for queries completing; this makes it impossible to use the logger for application telemetry.

## How this is different from the original

The port of the original SQLLogger did not have native return types, instead favoring docblocks.
This adds an explicit return type to the interface.

The `SAVEPOINT` queries either will show up in their underlying connection-specific syntax or possibly not at all.
I'm not sure how to test this!
(doctrine/dbal/src/Connection.php and thereabouts)

The names are, of course, also different.

## How to use this
If you have an implemenation of the DBAL SQLLogger interface (which is probably the case if you're here), you'll need to make the following changes:

- Have it implement `Firehed\DbalLogger\QueryLogger` instead of `Doctrine\DBAL\Logging\SQLLogger`
- Wrap it in Middlware: `$middleware = new Firehed\DbalLogger\Middleware($yourQueryLogger);`
- Adjust your DBAL/Doctrine setup code to use the Middleware instead of directly using the Logger:
```diff
-$config->setSQLLogger($yourSQLLogger);
+$config->setMiddlewares([$middleware]);
```

That should do it!
