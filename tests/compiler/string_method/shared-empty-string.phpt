--TEST--
Shared empty strings preserve values, defaults, array keys and independent writes
--FILE--
<?php

const SHARED_EMPTY_STRING = '';

final class SharedEmptyStringValues
{
    public const VALUE = '';
    public string $value = '';
    public static string $shared = '';
}

function sharedEmptyDefault(string $value = ''): string
{
    return $value;
}

function appendToEmptyString(string &$value): void
{
    $value .= 'ref';
}

function main(): void
{
    $first = '';
    $second = '';
    $copy = $first;
    $first .= 'first';
    appendToEmptyString($second);
    var_dump($first, $second, $copy, '');

    $values = ['' => ''];
    $arrayCopy = $values;
    $values[''] .= 'array';
    var_dump($values[''], $arrayCopy[''], array_key_exists('', $values));

    $left = new SharedEmptyStringValues();
    $right = new SharedEmptyStringValues();
    $left->value .= 'property';
    SharedEmptyStringValues::$shared .= 'static';
    var_dump($left->value, $right->value, SharedEmptyStringValues::$shared);
    var_dump(SHARED_EMPTY_STRING, SharedEmptyStringValues::VALUE, sharedEmptyDefault());

    $callback = fn(): string => '';
    var_dump($callback(), __NAMESPACE__, implode('', ['', 'x', '']), explode(',', ''));
    var_dump(strlen("\0"), "\0" === '', strlen(''), strlen($copy));
}
?>
--EXPECT--
string(5) "first"
string(3) "ref"
string(0) ""
string(0) ""
string(5) "array"
string(0) ""
bool(true)
string(8) "property"
string(0) ""
string(6) "static"
string(0) ""
string(0) ""
string(0) ""
string(0) ""
string(0) ""
string(1) "x"
array(1) {
  [0]=>
  string(0) ""
}
int(1)
bool(false)
int(0)
int(0)
