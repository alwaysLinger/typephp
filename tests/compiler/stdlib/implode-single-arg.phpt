--TEST--
implode() single-argument form joins with an empty separator
--FILE--
<?php
function joinList(array $items): string
{
    return implode($items);
}

function main(): void
{
    var_dump(implode(['a', 'b', 'c']));
    var_dump(implode([]));
    var_dump(joinList(['x', 'y']));
    var_dump(implode([1, 2.5, true, 's']));
    var_dump(implode(['k1' => 'v1', 'k2' => 'v2']));
    // join() is a documented alias of implode() and must support the same form
    var_dump(join(['p', 'q']));
    // two-argument form unchanged
    var_dump(implode('-', ['a', 'b']));
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
