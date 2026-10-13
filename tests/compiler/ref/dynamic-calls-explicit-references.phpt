--TEST--
Dynamic calls accept explicit std::ref across methods, closures, functions and static calls
--FILE--
<?php
class ExplicitReferenceTarget
{
    public int $value = 1;

    public function bump(&$first, &$second): void
    {
        $first++;
        $second += 2;
    }

    public static function increment(&$value): void
    {
        $value++;
    }

    private function privateIncrement(&$value): void
    {
        $value++;
    }

    public function privateCall(): void
    {
        $method = 'privateIncrement';
        $value = 1;
        $this->$method(std::ref($value));
        var_dump($value);
    }
}

function explicit_increment(&$value): void
{
    $value++;
}

function invoke_explicit(callable $callback): void
{
    $value = 1;
    $callback(std::ref($value));
    var_dump($value);
}

function main(): void
{
    $object = new ExplicitReferenceTarget();
    $method = 'bump';
    $value = 1;
    $object->$method(std::ref($value), std::ref($value));
    var_dump($value);
    $object->$method(second: std::ref($value), first: std::ref($value));
    var_dump($value);
    $object->$method(std::ref($object->value), std::ref($object->value));
    var_dump($object->value);
    $array = [1];
    $object->$method(std::ref($array[0]), std::ref($array[0]));
    var_dump($array[0]);
    $object?->$method(std::ref($value), std::ref($value));
    var_dump($value);
    $nullable = null;
    $nullable?->$method(std::ref($value), std::ref($value));
    var_dump($value);
    $object->privateCall();
    invoke_explicit('explicit_increment');
    $closure = function (&$value): void { $value++; };
    invoke_explicit($closure);
    $class = ExplicitReferenceTarget::class;
    $method = 'increment';
    $class::$method(std::ref($value));
    var_dump($value);
    ExplicitReferenceTarget::$method(std::ref($value));
    var_dump($value);
}
?>
--EXPECT--
int(4)
int(7)
int(4)
int(4)
int(10)
int(10)
int(2)
int(2)
int(2)
int(11)
int(12)
