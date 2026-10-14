<?php


const DYNAMIC_CALL_INSTANCEOF_ITERATIONS = 500_000;

class NarrowAnimal
{
    public function speak(): string
    {
        return 'animal';
    }
}

final class NarrowDog extends NarrowAnimal
{
    public function speak(): string
    {
        return 'dog';
    }

    public function bark(): int
    {
        return 7;
    }
}

/**
 * The instanceof guard is the hot narrowable dispatch shape: the compiler
 * learns the receiver's runtime class from the guard, yet the guarded call is
 * currently produced as a runtime-cached method call.
 */
function instanceofNarrowedCall(string $mode, NarrowAnimal $speaker): string
{
    if ($speaker instanceof NarrowDog) {
        return $mode . $speaker->bark();
    }
    return $speaker->speak();
}

function matchedValue(int $rounds, string $mode, NarrowAnimal $receiver): int
{
    $result = $rounds;
    for ($i = 0; $i < $rounds; $i++) {
        if (instanceofNarrowedCall($mode, $receiver) === $mode) {
            $result = $result + 1;
        } else {
            $result = $result - 1;
        }
    }

    return $result;
}

function benchInstanceofNarrowedCall(int $rounds): int
{
    $receiver = new NarrowDog();

    // Warm the guarded path before measuring.
    matchedValue(16, 'warm', $receiver);

    $start = hrtime(true);
    $result = matchedValue($rounds, 'hot', $receiver);
    $elapsed = hrtime(true) - $start;

    printf("instanceof_narrowed_call_ns=%.2f\n", $elapsed / $rounds);

    return $result;
}

function main(): void
{
    $rounds = DYNAMIC_CALL_INSTANCEOF_ITERATIONS;
    $checksumInstanceofNarrowedCall = benchInstanceofNarrowedCall($rounds);

    printf("checksum_instanceof_narrowed_call=%d\n", $checksumInstanceofNarrowedCall);
}
