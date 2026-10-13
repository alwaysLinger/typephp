--TEST--
Array writes and ??= declare undefined roots and preserve existing containers
--FILE--
<?php
function main(): void
{
    $ordinary['x'] = 1;
    $ordinary[] = 2;
    echo json_encode($ordinary), "\n";

    $append[] = 3;
    $nested['x']['y'] = 4;
    $nested['x'][] = 5;
    echo json_encode([$append, $nested]), "\n";

    $first = ($a['x'] ??= 1);
    $again = ($a['x'] ??= 99);
    $a[2] ??= 2;
    $a[] = 3;
    echo json_encode([$first, $again, $a]), "\n";

    $value = ($deep['x']['y']['z'] ??= 4);
    $deep['x']['y']['z'] ??= 99;
    echo json_encode([$value, $deep]), "\n";

    $nullable = null;
    $nullable['x'] ??= 5;
    $existing = ['x' => null];
    $existing['x'] ??= 6;
    $object = new ArrayObject();
    $object['x'] ??= 7;
    $object['x'] ??= 99;
    echo json_encode([$nullable, $existing, $object->getArrayCopy()]), "\n";

    $root = [];
    $alias = &$root;
    $alias['x'] ??= 8;
    echo json_encode([$root, $alias]), "\n";

    $GLOBALS['array_write_root']['x'] ??= 9;
    $GLOBALS['array_write_root']['nested']['y'] = 10;
    $GLOBALS['array_write_root']['nested']['y'] ??= 99;
    echo json_encode($GLOBALS['array_write_root']), "\n";
}
?>
--EXPECT--
{"x":1,"0":2}
[[3],{"x":{"y":4,"0":5}}]
[1,1,{"x":1,"2":2,"3":3}]
[4,{"x":{"y":{"z":4}}}]
[{"x":5},{"x":6},{"x":7}]
[{"x":8},{"x":8}]
{"x":9,"nested":{"y":10}}
