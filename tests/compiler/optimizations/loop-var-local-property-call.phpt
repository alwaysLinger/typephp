--TEST--
Loop optimization does not resolve locally assigned object properties before statement conversion
--FILE--
<?php
class LoopCallValue {
    public int $value = -2;
}

function main(): void {
    $object = new LoopCallValue();
    $found = array_search($object->value, [-1, -2, -3], true);
    $positive = abs($object->value);
    $length = strlen((string) $object->value);
    $sum = 0;
    for ($i = 0; $i < $length; ++$i) {
        $sum += $positive;
    }
    var_dump($found, $positive, $length, $sum);
}
?>
--EXPECT--
int(1)
int(2)
int(2)
int(4)
