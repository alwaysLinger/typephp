--TEST--
Logical operators finish left operand reference writes before evaluating the right
--FILE--
<?php
function flag(&$value, bool $result): bool
{
    $value += 1;
    return $result;
}

function bump(&$value): bool
{
    $value += 20;
    return true;
}

function sharedBump(&$first, &...$rest): bool
{
    $first += 10;
    foreach ($rest as &$value) {
        $value += 10;
    }
    return true;
}

function main(): void
{
    $a = 1;
    var_dump(flag($a, true) && bump($a), $a);
    $a = 1;
    var_dump(flag($a, false) || bump($a), $a);
    $a = 1;
    var_dump((flag($a, true) and sharedBump($a, $a)), $a);
    $a = 1;
    var_dump((flag($a, false) or sharedBump($a, $a)), $a);
    $a = 1;
    var_dump(flag($a, false) && bump($a), $a);
    $a = 1;
    var_dump(flag($a, true) || bump($a), $a);
    $a = 1;
    var_dump(flag($a, true) && (flag($a, false) || bump($a)), $a);
    $a = 1;
    var_dump($a++ && $a === 2, $a);
    $a = 0;
    var_dump($a++ || $a === 1, $a);
}
?>
--EXPECT--
bool(true)
int(22)
bool(true)
int(22)
bool(true)
int(22)
bool(true)
int(22)
bool(false)
int(2)
bool(true)
int(2)
bool(true)
int(23)
bool(true)
int(2)
bool(true)
int(1)
