# 2.0.0

This adds support for DBAL 4, and drops support for DBAL 3.

While the internal APIs have some significant changes to account for this, the user-facing API is _mostly_ unchanged.
There is one probably-small adjustment to account for:

`QueryLogger::startQuery()`'s third parameter, `$types` now takes an array of `\Doctrine\DBAL\ParameterType` enums instead of the previous `int` values.
If your logger does not look at `$types`, you almost certainly don't have to do anything.

Since the enum-based version in DBAL 4 is name-compatible with the constants in DBAL 3 (e.g. `ParameterType::STRING`), it's possible you don't need to make any changes to your implementation.
Depending on _how_ you're using `$types`, you may need to adjust your implementation or output format.
