--TEST--
Nested trait constructor promotion preserves private and protected slots across inheritance
--FILE--
<?php

trait PromotionFields
{
    public function __construct(
        private string $name = 'default',
        protected int $score = 7,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }
}

trait NestedPromotionFields
{
    use PromotionFields;
}

class TraitPromotionBase
{
    use NestedPromotionFields;
}

class TraitPromotionChild extends TraitPromotionBase
{
    public function getScore(): int
    {
        return $this->score;
    }

    public function __set(string $name, mixed $value): void
    {
        throw new RuntimeException('Unexpected magic property write');
    }
}

class TraitPromotionLeaf extends TraitPromotionChild {}

function main(): void
{
    $property = new ReflectionProperty(TraitPromotionBase::class, 'name');
    $base = new TraitPromotionBase('base', 10);
    var_dump($base->getName(), $property->getValue($base));

    $child = new TraitPromotionChild('child', 20);
    var_dump($child->getName(), $child->getScore(), $property->getValue($child));
    var_dump(count((array) $child), get_object_vars($child));

    $leaf = new TraitPromotionLeaf();
    var_dump($leaf->getName(), $leaf->getScore(), $property->getValue($leaf));
    var_dump(count((array) $leaf), get_object_vars($leaf));
}
?>
--EXPECT--
string(4) "base"
string(4) "base"
string(5) "child"
int(20)
string(5) "child"
int(2)
array(0) {
}
string(7) "default"
int(7)
string(7) "default"
int(2)
array(0) {
}
