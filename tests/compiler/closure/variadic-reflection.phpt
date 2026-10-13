--TEST--
Closure metadata preserves reflection and named variadic arguments
--FILE--
<?php
function describeClosure(Closure $callback): void
{
    $reflection = new ReflectionFunction($callback);
    echo $reflection->getNumberOfParameters(), ":",
        $reflection->getNumberOfRequiredParameters(), ":",
        $reflection->isVariadic() ? "variadic" : "fixed", "\n";
    foreach ($reflection->getParameters() as $parameter) {
        echo $parameter->getPosition(), ":", $parameter->getName(), ":",
            $parameter->isOptional() ? "optional" : "required", ":",
            $parameter->isVariadic() ? "variadic" : "fixed", "\n";
    }
}

function main()
{
    describeClosure(function (): void {});
    describeClosure(function ($first, $second = 42): void {});
    describeClosure(function (...$rest): void {});
    describeClosure(function ($first, ...$rest): void {});
    describeClosure(function ($first, $optional = 42, ...$rest): void {});
    $onlyVariadic = fn(...$items): array => $items;
    describeClosure($onlyVariadic);
    echo json_encode($onlyVariadic(items: 5, extra: 6)), "\n";

    $collect = function ($first = "default", ...$rest): array {
        return [$first, $rest];
    };
    describeClosure($collect);
    echo json_encode($collect()), "\n";
    echo json_encode($collect("positional", 1, 2)), "\n";
    echo json_encode($collect(first: "named", rest: 3, extra: 4)), "\n";
}
?>
--EXPECT--
0:0:fixed
2:1:fixed
0:first:required:fixed
1:second:optional:fixed
1:0:variadic
0:rest:optional:variadic
2:1:variadic
0:first:required:fixed
1:rest:optional:variadic
3:1:variadic
0:first:required:fixed
1:optional:optional:fixed
2:rest:optional:variadic
1:0:variadic
0:items:optional:variadic
{"items":5,"extra":6}
2:0:variadic
0:first:optional:fixed
1:rest:optional:variadic
["default",[]]
["positional",[1,2]]
["named",{"rest":3,"extra":4}]
