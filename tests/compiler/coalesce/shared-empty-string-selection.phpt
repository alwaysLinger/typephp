--TEST--
Coalesce and ternary selections preserve shared empty strings, types and lazy evaluation
--FILE--
<?php

const EMPTY_SELECTION_TEXT = '';

final class EmptyStringSelection
{
    public mixed $value = null;
    public string $text = '';
    public static array $values = ['present' => 'static'];
}

function selectionValue(mixed $value): mixed
{
    echo "left\n";
    return $value;
}

function main(): void
{
    $values = ['present' => 'value', 'null' => null, 'empty' => '', 'zero' => 0, 'false' => false];
    var_dump($values['present'] ?? '');
    var_dump($values['missing'] ?? '');
    var_dump($values['null'] ?? '');
    var_dump($values['empty'] ?? '');
    var_dump($values['zero'] ?? '');
    var_dump($values['false'] ?? '');
    var_dump($values['present'] ?: '', $values['empty'] ?: '', $values['false'] ?: '');

    $object = new EmptyStringSelection();
    var_dump($object->value ?? '', $object->text ?? '');
    var_dump(EmptyStringSelection::$values['present'] ?? '', EmptyStringSelection::$values['missing'] ?? '');

    $dynamic = null;
    var_dump($dynamic ?? '', $dynamic ?: '');
    var_dump(selectionValue('call') ?? '', selectionValue(null) ?? '');
    var_dump(selectionValue('call') ?: '', selectionValue(false) ?: '');
    var_dump($values['missing'] ?? $values['null'] ?? '');

    $text = 'text';
    $empty = '';
    var_dump($text ?: '', $empty ?: '', true ? $text : '', false ? '' : $text);
    var_dump(true ? EMPTY_SELECTION_TEXT : '', false ? '' : EMPTY_SELECTION_TEXT);

    $selected = $values['missing'] ?? '';
    $selected .= 'changed';
    var_dump($selected, '', $values['empty']);
    var_dump($values['present'] ?? selectionValue('unused'));
    var_dump($values['missing'] ?? selectionValue('fallback'));
}
?>
--EXPECT--
string(5) "value"
string(0) ""
string(0) ""
string(0) ""
int(0)
bool(false)
string(5) "value"
string(0) ""
string(0) ""
string(0) ""
string(0) ""
string(6) "static"
string(0) ""
string(0) ""
string(0) ""
left
left
string(4) "call"
string(0) ""
left
left
string(4) "call"
string(0) ""
string(0) ""
string(4) "text"
string(0) ""
string(4) "text"
string(4) "text"
string(0) ""
string(0) ""
string(7) "changed"
string(0) ""
string(0) ""
string(5) "value"
left
string(8) "fallback"
