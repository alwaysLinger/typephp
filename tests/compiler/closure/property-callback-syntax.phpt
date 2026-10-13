--TEST--
Property callbacks and methods with the same name use distinct PHP call syntax
--FILE--
<?php
class PropertyCallbackHolder
{
    public Closure $onTap;
    public int $calls = 0;

    public function __construct()
    {
        $this->onTap = function (string $value): string {
            $this->calls++;
            return "property:" . $value;
        };
    }

    public function callMethod(): string
    {
        return $this->onTap("direct");
    }

    public function callProperty(): string
    {
        return ($this->onTap)("parentheses");
    }

    public function callLocal(): string
    {
        $callback = $this->onTap;
        return $callback("local");
    }
}

class PropertyCallbackWithMethod extends PropertyCallbackHolder
{
    public function onTap(string $value): string
    {
        return "method:" . $value;
    }
}

class PropertyCallbackWithMagic extends PropertyCallbackHolder
{
    public function __call(string $name, array $arguments): string
    {
        return "magic:" . $name . ":" . $arguments[0];
    }
}

function main()
{
    $holder = new PropertyCallbackHolder();
    try {
        $holder->callMethod();
    } catch (Error $error) {
        echo get_class($error), "\n";
    }
    echo $holder->callProperty(), "\n";
    echo $holder->callLocal(), "\n";
    echo ($holder->onTap)("external"), "\n";
    echo "calls:", $holder->calls, "\n";

    $both = new PropertyCallbackWithMethod();
    echo $both->callMethod(), "\n";
    echo $both->callProperty(), "\n";
    echo "calls:", $both->calls, "\n";

    $magic = new PropertyCallbackWithMagic();
    echo $magic->callMethod(), "\n";
    echo $magic->callProperty(), "\n";
    echo "calls:", $magic->calls, "\n";
}
?>
--EXPECT--
Error
property:parentheses
property:local
property:external
calls:3
method:direct
property:parentheses
calls:1
magic:onTap:direct
property:parentheses
calls:1
