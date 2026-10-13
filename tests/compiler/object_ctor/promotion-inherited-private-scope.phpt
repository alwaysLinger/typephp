--TEST--
Constructor promotion writes inherited private promoted properties into the declared slot
--FILE--
<?php
class ParentCls
{
    public function __construct(private string $name = "default") {}
}

class ChildCls extends ParentCls {}

function main(): void
{
    $child = new ChildCls("hello");
    var_dump($child);

    $direct = new ParentCls("direct");
    var_dump($direct);
}
?>
--EXPECT--
object(ChildCls)#1 (1) {
  ["name":"ParentCls":private]=>
  string(5) "hello"
}
object(ParentCls)#2 (1) {
  ["name":"ParentCls":private]=>
  string(6) "direct"
}
