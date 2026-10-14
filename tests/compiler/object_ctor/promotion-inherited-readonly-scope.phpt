--TEST--
Inherited readonly promotion initializes the declared private slot and rejects reinitialization
--FILE--
<?php

class ReadonlyPromotionBase
{
    public function __construct(private readonly string $token = 'default') {}

    public function getToken(): string
    {
        return $this->token;
    }
}

class ReadonlyPromotionChild extends ReadonlyPromotionBase {}

class ReadonlyPromotionLeaf extends ReadonlyPromotionChild
{
    public function __construct(string $token = 'default')
    {
        parent::__construct($token);
    }
}

function checkReadonlyPromotion(ReadonlyPromotionBase $object): void
{
    $property = new ReflectionProperty(ReadonlyPromotionBase::class, 'token');
    var_dump($object->getToken(), $property->getValue($object), $property->isInitialized($object));
    var_dump(count((array) $object), get_object_vars($object));

    try {
        $object->__construct('replacement');
    } catch (Error $error) {
        echo "readonly blocked\n";
    }
    var_dump($object->getToken());
}

function main(): void
{
    checkReadonlyPromotion(new ReadonlyPromotionBase('base'));
    checkReadonlyPromotion(new ReadonlyPromotionChild('child'));
    checkReadonlyPromotion(new ReadonlyPromotionLeaf());
}
?>
--EXPECT--
string(4) "base"
string(4) "base"
bool(true)
int(1)
array(0) {
}
readonly blocked
string(4) "base"
string(5) "child"
string(5) "child"
bool(true)
int(1)
array(0) {
}
readonly blocked
string(5) "child"
string(7) "default"
string(7) "default"
bool(true)
int(1)
array(0) {
}
readonly blocked
string(7) "default"
