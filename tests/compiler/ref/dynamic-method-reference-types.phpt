--TEST--
Dynamic method reference bridges preserve typed roots, aliases and argument order
--FILE--
<?php
class TypedDynamicReferences
{
    public function strings(&$first, &$second): void
    {
        $first .= 'a';
        $second .= 'b';
    }

    public function arrays(&$first, &$second): void
    {
        $first[] = 2;
        $second[] = 3;
    }

    public function floats(&$first, &$second): void
    {
        $first += 0.5;
        $second += 0.25;
    }

    public function bools(&$first, &$second): void
    {
        $first = !$first;
        $second = !$second;
    }

    public function ints(&$first, &$second): void
    {
        $first += 1;
        $second += 2;
    }
}

function main(): void
{
    $object = new TypedDynamicReferences();
    $method = 'strings';
    $s = 'x';
    $object->$method($s, $s);
    var_dump($s);
    $method = 'arrays';
    $a = [1];
    $object->$method($a, $a);
    var_dump($a);
    $method = 'floats';
    $f = 1.0;
    $object->$method($f, $f);
    var_dump($f);
    $method = 'bools';
    $b = true;
    $object->$method($b, $b);
    var_dump($b);
    $method = 'ints';
    $i = 1;
    $alias = &$i;
    $object->$method($i, $alias);
    var_dump($i, $alias);
}
?>
--EXPECT--
string(3) "xab"
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
float(1.75)
bool(true)
int(4)
int(4)
