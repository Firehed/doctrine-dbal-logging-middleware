# 3.0.0

## PHP 8.2 required

PHP 8.1 is no longer supported.
PHP 8.2 or later is required.
Note: As of release, PHP 8.2 is _already_ EOL, but this library doesn't require newer features.

## Interface changes

The logger interface has several breaking changes:

### Interface consolidation

`QueryLogger` and `DbalLogger` have been merged into a single `DbalLogger` interface.

If you previously implemented `QueryLogger`, you'll need to:
1. Change `implements QueryLogger` to `implements DbalLogger`
2. Add `connect(): void` and `disconnect(): void` methods (can be empty no-ops)

### Native types added

The interface methods now have native type declarations:
- `startQuery(string $sql, ...)` - `$sql` parameter is now typed
- `startQuery(...): void` - return type added
- `stopQuery(...): void` - return type added

### Exception parameter added to `stopQuery()`

`stopQuery()` now accepts a nullable `?Throwable $exception` parameter.
If a query fails, the exception is passed; on success, `null` is passed.
Update your implementation signature to accept this parameter (you may ignore it).

## Middleware constructor

The `Middleware` constructor now requires a `DbalLogger` instance.
Previously it accepted `QueryLogger` and automatically wrapped it; this is no longer supported.

# 2.0.0

This adds support for DBAL 4, and drops support for DBAL 3.

While the internal APIs have some significant changes to account for this, the user-facing API is _mostly_ unchanged.
There is one probably-small adjustment to account for:

`QueryLogger::startQuery()`'s third parameter, `$types` now takes an array of `\Doctrine\DBAL\ParameterType` enums instead of the previous `int` values.
If your logger does not look at `$types`, you almost certainly don't have to do anything.

Since the enum-based version in DBAL 4 is name-compatible with the constants in DBAL 3 (e.g. `ParameterType::STRING`), it's possible you don't need to make any changes to your implementation.
Depending on _how_ you're using `$types`, you may need to adjust your implementation or output format.
