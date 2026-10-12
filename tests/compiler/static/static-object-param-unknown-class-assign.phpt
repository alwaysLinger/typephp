--TEST--
Unknown object classes are checked on static property writes and rejected writes preserve the old value
--FILE--
<?php
class StaticObjectExpected
{
}

class StaticObjectChild extends StaticObjectExpected
{
}

class StaticObjectOther
{
}

class StaticObjectHolder
{
    public static StaticObjectExpected $value;
    public static ?StaticObjectExpected $nullable;
    public static object $generic;
}

function assignStaticObject(object $value): void
{
    StaticObjectHolder::$value = $value;
}

function assignNullableStaticObject(?object $value): void
{
    StaticObjectHolder::$nullable = $value;
}

function assignGenericStaticObject(object $value): void
{
    StaticObjectHolder::$generic = $value;
}

function main(): void
{
    $expected = new StaticObjectExpected();
    assignStaticObject($expected);
    var_dump(StaticObjectHolder::$value === $expected);

    $child = new StaticObjectChild();
    assignStaticObject($child);
    var_dump(StaticObjectHolder::$value === $child);

    $other = new StaticObjectOther();
    try {
        assignStaticObject($other);
        echo "invalid static assignment accepted\n";
    } catch (TypeError $error) {
        echo "static assignment rejected\n";
    }
    var_dump(StaticObjectHolder::$value === $child);

    assignNullableStaticObject(null);
    var_dump(StaticObjectHolder::$nullable);
    assignNullableStaticObject($child);
    var_dump(StaticObjectHolder::$nullable === $child);
    try {
        assignNullableStaticObject($other);
        echo "invalid nullable static assignment accepted\n";
    } catch (TypeError $error) {
        echo "nullable static assignment rejected\n";
    }
    var_dump(StaticObjectHolder::$nullable === $child);

    assignGenericStaticObject($other);
    var_dump(StaticObjectHolder::$generic === $other);
}
?>
--EXPECT--
bool(true)
bool(true)
static assignment rejected
bool(true)
NULL
bool(true)
nullable static assignment rejected
bool(true)
bool(true)
