--TEST--
Devirtualize: instanceof narrows receiver for native method call
--FILE--
<?php

// positive: narrowing to a class without subclass overrides grants the direct call
class Animal { public function speak(): string { return "speak(Animal)"; } }
class Dog extends Animal { public function speak(): string { return "speak(Dog)"; } }
class Cat extends Animal { public function speak(): string { return "speak(Cat)"; } }

function describe(Animal $a): string {
    if ($a instanceof Dog) {
        return $a->speak();
    }
    return "unknown";
}

// negative: the narrowed class has an overriding subclass, so the receiver
// may be that subclass and the dynamic dispatch must stay
class Animal2 { public function speak(): string { return "speak(Animal2)"; } }
class Dog2 extends Animal2 { public function speak(): string { return "speak(Dog2)"; } }
class Poodle2 extends Dog2 { public function speak(): string { return "speak(Poodle2)"; } }
class Cat2 extends Animal2 { public function speak(): string { return "speak(Cat2)"; } }

function describeWithOverride(Animal2 $a): string {
    if ($a instanceof Dog2) {
        return $a->speak();
    }
    return "unknown";
}

// negative: a rebinding inside the arm invalidates the narrowing
class Animal3 { public function speak(): string { return "speak(Animal3)"; } }
class Dog3 extends Animal3 { public function speak(): string { return "speak(Dog3)"; } }
class Cat3 extends Animal3 { public function speak(): string { return "speak(Cat3)"; } }

function describeWithReassignment(Animal3 $a): string {
    if ($a instanceof Dog3) {
        $a = new Cat3();
        return $a->speak();
    }
    return "unknown";
}

// negative: a narrowing whose target is not statically assignable to the
// declared receiver class must not change resolution
class Animal4 { public function speak(): string { return "speak(Animal4)"; } }
class Dog4 extends Animal4 { public function speak(): string { return "speak(Dog4)"; } }
class Cat4 extends Animal4 { public function speak(): string { return "speak(Cat4)"; } }
class Plane { public function speak(): string { return "speak(Plane)"; } }

function describeStaticallyUnrelated(Animal4 $a): string {
    if ($a instanceof Plane) {
        return $a->speak();
    }
    return "unknown";
}

function main() {
    var_dump(describe(new Dog()));
    var_dump(describe(new Cat()));
    var_dump(describeWithOverride(new Dog2()));
    var_dump(describeWithOverride(new Poodle2()));
    var_dump(describeWithReassignment(new Dog3()));
    var_dump(describeStaticallyUnrelated(new Dog4()));
}

?>
--EXPECT--
string(10) "speak(Dog)"
string(7) "unknown"
string(11) "speak(Dog2)"
string(14) "speak(Poodle2)"
string(11) "speak(Cat3)"
string(7) "unknown"
