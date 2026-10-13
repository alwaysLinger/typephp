--TEST--
Variable method names use runtime reference signatures and preserve value snapshots
--FILE--
<?php
class DynamicArguments
{
    public int $value = 1;
    public readonly int $constant;

    public function __construct()
    {
        $this->constant = 7;
    }

    public function bump(&$first, &...$rest): void
    {
        $first += 10;
        foreach ($rest as &$value) {
            $value += 10;
        }
    }

    public function values($first, $second): void
    {
        var_dump($first, $second);
        $first = 100;
        $second = 200;
    }

    public function mixedArgs($first, &$second): void
    {
        var_dump($first, $second);
        $second += 10;
    }

    public function fail(&$value): void
    {
        $value += 3;
        throw new RuntimeException('failed');
    }

    private function privateBump(&$value): void
    {
        $value += 4;
    }

    public function privateCall(): void
    {
        $a = 1;
        $method = 'privateBump';
        $this->$method($a);
        var_dump($a);
    }

    public function __call(string $name, array $args): void
    {
        $args[0] = 999;
        var_dump($name, $args[1]);
    }
}

function change(&$value): int
{
    $value += 1;
    return $value;
}

function main(): void
{
    $object = new DynamicArguments();
    $method = 'bump';
    $a = 1;
    $object->$method($a, $a);
    var_dump($a);
    $a = 1;
    $object->$method(first: $a, extra: $a);
    var_dump($a);
    $args = [1, 2];
    $copy = $args;
    $object->$method(...$args);
    var_dump($args[0], $args[1], $copy[0], $copy[1]);
    $namedArgs = ['extra' => 2, 'first' => 1];
    $object->$method(...$namedArgs);
    var_dump($namedArgs['first'], $namedArgs['extra']);
    $a = 1;
    for ($i = 0; $i < 2; $i++) {
        $object->$method($a, $a);
    }
    var_dump($a);
    $object->$method($object->value, $object->value);
    var_dump($object->value);
    $array = [1];
    $object->$method($array[0], $array[0]);
    var_dump($array[0]);
    $a = 1;
    $object?->$method($a, $a);
    var_dump($a);
    $nullable = null;
    $nullable?->$method($a, change($a));
    var_dump($a);

    $method = 'values';
    $a = 1;
    $object->$method($a, change($a));
    var_dump($a);
    $object->$method($object->constant, $object->constant);
    $method = 'mixedArgs';
    $a = 1;
    $object->$method($a, $a);
    var_dump($a);
    $a = 1;
    $object->$method(change($a), $a);
    var_dump($a);
    $a = 1;
    $object->$method(second: $a, first: $a);
    var_dump($a);
    $method = 'missing';
    $a = 1;
    $object->$method($a, $a);
    var_dump($a);
    $method = 'fail';
    $a = 1;
    try {
        $object->$method($a);
    } catch (RuntimeException $exception) {
        echo $exception->getMessage(), "\n";
    }
    var_dump($a);
    $object->privateCall();
}
?>
--EXPECT--
int(21)
int(21)
int(11)
int(12)
int(1)
int(2)
int(11)
int(12)
int(41)
int(21)
int(21)
int(21)
int(21)
int(1)
int(2)
int(2)
int(7)
int(7)
int(1)
int(1)
int(11)
int(2)
int(2)
int(12)
int(1)
int(1)
int(11)
string(7) "missing"
int(1)
int(1)
failed
int(4)
int(5)
