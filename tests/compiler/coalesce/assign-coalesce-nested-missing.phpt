--TEST--
Nested ??= preserves keys, lazy RHS and fresh containers across missing levels and loops
--FILE--
<?php
function nestedKey(string $key): string
{
    echo 'key:', $key, "\n";
    return $key;
}

function nestedDefault(): int
{
    echo "rhs\n";
    return 10;
}

function fillNested(array $input): array
{
    $input['a']['b']['c'] ??= 9;
    return $input;
}

function main(): void
{
    foreach ([[], ['a' => []], ['a' => ['b' => null]], ['a' => ['b' => []]]] as $input) {
        echo json_encode(fillNested($input)), "\n";
    }
    echo json_encode(fillNested(['a' => ['b' => ['c' => 'keep']]])), "\n";

    $four = [];
    $four['a']['b']['c']['d'] ??= 9;
    $integer[1][2] ??= 'x';
    echo json_encode([$four, $integer]), "\n";

    for ($i = 0; $i < 3; $i++) {
        $value = ($loop[nestedKey('outer')][nestedKey('inner')] ??= nestedDefault() + 1);
        echo json_encode([$value, $loop]), "\n";
        if ($i === 1) {
            unset($loop['outer']);
        }
    }

    // The same generated probe first selects an object, then sees an absent
    // outer key. It must create a fresh array instead of writing to the old object.
    $bag = new ArrayObject(['b' => 'keep']);
    $reused = ['a' => $bag];
    for ($i = 0; $i < 2; $i++) {
        $value = ($reused['a']['b'] ??= 12);
        echo json_encode([$value, $reused, $bag->getArrayCopy()]), "\n";
        $reused = [];
    }

    // Each level has its own reused selected-value temporary.
    $deepBag = new ArrayObject(['c' => 'keep']);
    $deep = ['a' => ['b' => $deepBag]];
    for ($i = 0; $i < 2; $i++) {
        $value = ($deep['a']['b']['c'] ??= 13);
        echo json_encode([$value, $deep, $deepBag->getArrayCopy()]), "\n";
        $deep = [];
    }
}
?>
--EXPECT--
{"a":{"b":{"c":9}}}
{"a":{"b":{"c":9}}}
{"a":{"b":{"c":9}}}
{"a":{"b":{"c":9}}}
{"a":{"b":{"c":"keep"}}}
[{"a":{"b":{"c":{"d":9}}}},{"1":{"2":"x"}}]
key:outer
key:inner
rhs
[11,{"outer":{"inner":11}}]
key:outer
key:inner
[11,{"outer":{"inner":11}}]
key:outer
key:inner
rhs
[11,{"outer":{"inner":11}}]
["keep",{"a":{"b":"keep"}},{"b":"keep"}]
[12,{"a":{"b":12}},{"b":"keep"}]
["keep",{"a":{"b":{"c":"keep"}}},{"c":"keep"}]
[13,{"a":{"b":{"c":13}}},{"c":"keep"}]
