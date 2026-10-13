--TEST--
Runtime reference selection keeps fixed objects, resources and containers legal as values
--FILE--
<?php
class DynamicFixedStorage
{
    public function inspect($value): void
    {
        if (is_array($value)) {
            echo 'array:', count($value), "\n";
        } elseif (is_resource($value)) {
            echo "resource\n";
        } else {
            echo get_debug_type($value), "\n";
        }
    }

    public function clear(&$value): void
    {
        $value = null;
    }
}

function main(): void
{
    $object = new DynamicFixedStorage();
    $value = new stdClass();
    $resource = fopen('php://memory', 'w+');
    $fixed = std::array(Type::Int, 2);
    $checked = std::list(Type::Int);
    $checked[] = 1;
    $method = 'inspect';
    $object->$method($value);
    $object->$method($resource);
    $object->$method($fixed);
    $object->$method($fixed[0]);
    $object->$method($checked);
    $object->$method($checked[0]);

    $method = 'clear';
    try {
        $object->$method($value);
    } catch (Error $error) {
        echo "object reference rejected\n";
    }
    try {
        $object->$method($checked);
    } catch (Error $error) {
        echo "checked reference rejected\n";
    }
    $any = std::any($value);
    $object->$method($any);
    var_dump($any);
    fclose($resource);
}
?>
--EXPECT--
stdClass
resource
array:2
int
array:1
int
object reference rejected
checked reference rejected
NULL
