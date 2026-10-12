--TEST--
Rejected object property assignments preserve the previous value and evaluate the RHS once
--FILE--
<?php
class PreservedPropertyExpected
{
    public function value(): string
    {
        return 'preserved';
    }
}

class PreservedPropertyOther
{
}

class PreservedPropertyHolder
{
    public PreservedPropertyExpected $value;
}

function assignPreservedProperty(object $value, PreservedPropertyHolder $holder): object
{
    return $holder->value = $value;
}

function nextPreservedPropertyObject(): object
{
    echo "RHS evaluated\n";
    return new PreservedPropertyOther();
}

function assignReturnedPropertyObject(PreservedPropertyHolder $holder): void
{
    $holder->value = nextPreservedPropertyObject();
}

function main(): void
{
    $holder = new PreservedPropertyHolder();
    $original = new PreservedPropertyExpected();
    var_dump(assignPreservedProperty($original, $holder) === $original);

    try {
        assignPreservedProperty(new PreservedPropertyOther(), $holder);
        echo "invalid assignment accepted\n";
    } catch (TypeError $error) {
        echo "assignment rejected\n";
    }
    var_dump($holder->value === $original);
    echo $holder->value->value(), "\n";

    try {
        assignReturnedPropertyObject($holder);
        echo "invalid returned object accepted\n";
    } catch (TypeError $error) {
        echo "returned object rejected\n";
    }
    var_dump($holder->value === $original);
    echo $holder->value->value(), "\n";
}
?>
--EXPECT--
bool(true)
assignment rejected
bool(true)
preserved
RHS evaluated
returned object rejected
bool(true)
preserved
