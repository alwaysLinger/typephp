--TEST--
nested ??= keeps every dimension key and evaluates it exactly once
--FILE--
<?php
function k(string $s): string
{
    echo $s, "\n";
    return strtolower($s);
}

function store(array $in): array
{
    // The bug: when the outer dimension is absent, the inner key temporary
    // was never assigned and the store degraded to an append.
    $in['a']['b'] ??= 9;
    return $in;
}

function main(): void
{
    // 1. two levels, outer absent (the reported silent key loss)
    $g = [];
    $g['a']['b'] ??= 9;
    var_dump($g);

    // 2. two levels, through a helper (outer absent)
    var_dump(store([]));

    // 3. two levels, outer present, inner absent
    $g2 = ['a' => []];
    $g2['a']['b'] ??= 9;
    var_dump($g2);

    // 4. two levels, both present: no overwrite
    $g3 = ['a' => ['b' => 'keep']];
    $g3['a']['b'] ??= 9;
    var_dump($g3);

    // 5. three levels, all absent
    $g4 = [];
    $g4['a']['b']['c'] ??= 9;
    var_dump($g4);

    // 6. three levels, middle absent
    $g5 = ['a' => []];
    $g5['a']['b']['c'] ??= 9;
    var_dump($g5);

    // 7. four levels, all absent
    $g6 = [];
    $g6['a']['b']['c']['d'] ??= 9;
    var_dump($g6);

    // 8. side-effecting keys, two levels, outer absent: each key evaluated
    //    exactly once, in source order
    $g7 = [];
    $g7[k('K1')][k('K2')] ??= 9;
    var_dump($g7);

    // 9. side-effecting keys, two levels, outer present, inner absent
    $g8 = ['k1' => []];
    $g8[k('K1')][k('K2')] ??= 9;
    var_dump($g8);

    // 10. side-effecting keys, three levels, all absent
    $g9 = [];
    $g9[k('K1')][k('K2')][k('K3')] ??= 9;
    var_dump($g9);

    // 11. side-effecting keys, both present: keys still evaluated once,
    //     existing value untouched
    $g10 = ['k1' => ['k2' => 'keep']];
    $g10[k('K1')][k('K2')] ??= 9;
    var_dump($g10);

    // 12. integer keys nested
    $g11 = [];
    $g11[1][2] ??= 'x';
    var_dump($g11);
}
?>
--EXPECT--
array(1) {
  ["a"]=>
  array(1) {
    ["b"]=>
    int(9)
  }
}
array(1) {
  ["a"]=>
  array(1) {
    ["b"]=>
    int(9)
  }
}
array(1) {
  ["a"]=>
  array(1) {
    ["b"]=>
    int(9)
  }
}
array(1) {
  ["a"]=>
  array(1) {
    ["b"]=>
    string(4) "keep"
  }
}
array(1) {
  ["a"]=>
  array(1) {
    ["b"]=>
    array(1) {
      ["c"]=>
      int(9)
    }
  }
}
array(1) {
  ["a"]=>
  array(1) {
    ["b"]=>
    array(1) {
      ["c"]=>
      int(9)
    }
  }
}
array(1) {
  ["a"]=>
  array(1) {
    ["b"]=>
    array(1) {
      ["c"]=>
      array(1) {
        ["d"]=>
        int(9)
      }
    }
  }
}
K1
K2
array(1) {
  ["k1"]=>
  array(1) {
    ["k2"]=>
    int(9)
  }
}
K1
K2
array(1) {
  ["k1"]=>
  array(1) {
    ["k2"]=>
    int(9)
  }
}
K1
K2
K3
array(1) {
  ["k1"]=>
  array(1) {
    ["k2"]=>
    array(1) {
      ["k3"]=>
      int(9)
    }
  }
}
K1
K2
array(1) {
  ["k1"]=>
  array(1) {
    ["k2"]=>
    string(4) "keep"
  }
}
array(1) {
  [1]=>
  array(1) {
    [2]=>
    string(1) "x"
  }
}
