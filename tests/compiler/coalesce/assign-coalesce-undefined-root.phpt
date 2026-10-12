--TEST--
??= auto-vivifies an undefined root variable for array dimension targets
--FILE--
<?php
function vivify(): void
{
    // The root variable does not exist yet; ??= must create it.
    $a['x'] ??= 1;
    var_dump($a);
}

function vivifyInLoop(): array
{
    // Repeated ??= on the same undefined root: first call vivifies,
    // subsequent calls keep the existing value.
    $b['k'] ??= 'first';
    $b['k'] ??= 'second';
    return $b;
}

function vivifyIntKey(): void
{
    $c[3] ??= 'three';
    var_dump($c);
}

function main(): void
{
    vivify();

    $b = vivifyInLoop();
    var_dump($b);

    vivifyIntKey();

    // A defined root with a missing key keeps working (no regression).
    $d = [];
    $d['x'] ??= 1;
    var_dump($d);

    // A defined root holding null is treated as absent by ??=.
    $e = null;
    $e['x'] ??= 1;
    var_dump($e);

    // Scalar ??= on an undefined variable keeps working (no regression).
    $f ??= 1;
    var_dump($f);

    // After vivification the variable is a regular mixed local: it can be
    // reassigned to another array without a type conflict.
    $g['x'] ??= 1;
    $g = ['y' => 2];
    var_dump($g);
}
?>
--EXPECT--
array(1) {
  ["x"]=>
  int(1)
}
array(1) {
  ["k"]=>
  string(5) "first"
}
array(1) {
  [3]=>
  string(5) "three"
}
array(1) {
  ["x"]=>
  int(1)
}
array(1) {
  ["x"]=>
  int(1)
}
int(1)
array(1) {
  ["y"]=>
  int(2)
}
