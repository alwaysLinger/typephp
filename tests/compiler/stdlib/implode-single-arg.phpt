--TEST--
implode() and join() single-argument form: arrays join with an empty separator, non-arrays throw TypeError
--FILE--
<?php
class InvalidPayload
{
}

function joinList(array $items): string
{
    return implode($items);
}

function implodeValue(mixed $value): string
{
    return implode($value);
}

function joinValue(mixed $value): string
{
    return join($value);
}

function provideArray(): array
{
    return ['x', 'y'];
}

function expectTypeError(callable $fn): void
{
    try {
        $fn();
        echo "no error\n";
    } catch (Throwable $error) {
        echo $error::class, "\n";
    }
}

function main(): void
{
    // Valid arrays take the optimized single-argument path.
    var_dump(implode(['a', 'b', 'c']));
    var_dump(implode([]));
    var_dump(joinList(['x', 'y']));
    var_dump(implode([1, 2.5, true, 's']));
    var_dump(implode(['k1' => 'v1', 'k2' => 'v2']));
    // join() is a documented alias of implode() and must support the same form
    var_dump(join(['p', 'q']));
    // two-argument form unchanged
    var_dump(implode('-', ['a', 'b']));

    // Non-array values must reach Zend's parameter validation instead of
    // being silently coerced: direct arguments...
    expectTypeError(fn(): string => implode(42));
    expectTypeError(fn(): string => implode('ab'));
    expectTypeError(fn(): string => implode(true));
    expectTypeError(fn(): string => implode(null));
    expectTypeError(fn(): string => implode(new InvalidPayload()));

    // ...and values passed through mixed/var, which erase the static type.
    expectTypeError(fn(): string => implodeValue(42));
    expectTypeError(fn(): string => implodeValue('ab'));
    expectTypeError(fn(): string => implodeValue(true));
    expectTypeError(fn(): string => implodeValue(null));
    expectTypeError(fn(): string => implodeValue(new InvalidPayload()));

    expectTypeError(fn(): string => join(42));
    expectTypeError(fn(): string => join('ab'));
    expectTypeError(fn(): string => join(true));
    expectTypeError(fn(): string => join(null));
    expectTypeError(fn(): string => join(new InvalidPayload()));

    expectTypeError(fn(): string => joinValue(42));
    expectTypeError(fn(): string => joinValue('ab'));
    expectTypeError(fn(): string => joinValue(true));
    expectTypeError(fn(): string => joinValue(null));
    expectTypeError(fn(): string => joinValue(new InvalidPayload()));

    // Valid arrays passed through a dynamic value keep working: the
    // fallback reaches Zend, which accepts the runtime array.
    echo implode(provideArray()), "\n";
    echo joinValue(provideArray()), "\n";
    echo implodeValue(provideArray()), "\n";
    echo implode(std::any(['a', 'b'])), "\n";
}
?>
--EXPECT--
string(3) "abc"
string(0) ""
string(2) "xy"
string(6) "12.51s"
string(4) "v1v2"
string(2) "pq"
string(3) "a-b"
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
TypeError
xy
xy
xy
ab
