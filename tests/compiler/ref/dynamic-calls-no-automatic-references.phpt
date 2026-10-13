--TEST--
Unknown signatures do not automatically turn value arguments into references
--FILE--
<?php
class ManualReferenceTarget
{
    public function increment(&$value): void
    {
        $value++;
    }

    public static function staticIncrement(&$value): void
    {
        $value++;
    }
}

function manual_increment(&$value): void
{
    $value++;
}

function invoke_value(callable $callback): void
{
    $value = 1;
    $callback($value);
    var_dump($value);
}

function main(): void
{
    set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
        if (str_contains($message, 'must be passed by reference')) {
            echo "explicit reference required\n";
            return true;
        }
        return false;
    });
    $object = new ManualReferenceTarget();
    $method = 'increment';
    $value = 1;
    $object->$method($value);
    var_dump($value);
    $object?->$method($value);
    var_dump($value);
    invoke_value('manual_increment');
    $closure = function (&$value): void { $value++; };
    invoke_value($closure);
    $class = ManualReferenceTarget::class;
    $method = 'staticIncrement';
    $class::$method($value);
    var_dump($value);
    restore_error_handler();
}
?>
--EXPECT--
explicit reference required
int(1)
explicit reference required
int(1)
explicit reference required
int(1)
explicit reference required
int(1)
explicit reference required
int(1)
