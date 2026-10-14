--TEST--
Inherited asymmetric promoted properties initialize in parent scope and retain set visibility
--FILE--
<?php

class AsymmetricPromotionBase
{
    public function __construct(
        public private(set) string $name = 'default',
        public protected(set) int $score = 7,
    ) {}
}

class AsymmetricPromotionChild extends AsymmetricPromotionBase
{
    public function incrementScore(): void
    {
        $this->score += 1;
    }
}

class AsymmetricPromotionLeaf extends AsymmetricPromotionChild {}

function rejectExternalPromotionWrites(mixed $object): void
{
    try {
        $object->name = 'external';
    } catch (Error $error) {
        echo "private(set) blocked\n";
    }
    try {
        $object->score = 100;
    } catch (Error $error) {
        echo "protected(set) blocked\n";
    }
    var_dump($object->name, $object->score);
}

function main(): void
{
    $base = new AsymmetricPromotionBase('base', 10);
    var_dump($base->name, $base->score);

    $child = new AsymmetricPromotionChild('child', 20);
    $child->incrementScore();
    rejectExternalPromotionWrites($child);

    $leaf = new AsymmetricPromotionLeaf();
    $leaf->incrementScore();
    rejectExternalPromotionWrites($leaf);
    var_dump(count((array) $leaf));
}
?>
--EXPECT--
string(4) "base"
int(10)
private(set) blocked
protected(set) blocked
string(5) "child"
int(21)
private(set) blocked
protected(set) blocked
string(7) "default"
int(8)
int(2)
