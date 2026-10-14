# Dynamic call versus instanceof-guarded call benchmark

This benchmark isolates a guarded dynamic dispatch: `function
instanceofNarrowedCall(NarrowAnimal $speaker)` receives a typed `Animal`,
returns `$speaker->bark()` inside `if ($speaker instanceof NarrowDog)`, and
falls back to `$speaker->speak()` otherwise. The measured loop keeps taking
the guarded branch, so the benchmark purely exercises the dispatch cost of the
call inside the `instanceof` arm; the fallback branch exists for PHP
correctness, not for the measurement.

TypePHP's method-call cache is monomorphic and still pays per-call guard
bookkeeping; the e-SSA pi narrow lets the compiler direct-call
`NarrowDog::bark()` inside the arm. The binary is built by `project.yml` with
the same `-O3 + LTO` protocol as `benchmark/dynamic-call/`; medians are taken
across `--runs=N` executions of both the baseline PHP process and the
compiled binary.

## Usage

```bash
php run.php --runs=3        # rebuild the AOT binary, run 3+ sampling rounds
php run.php --skip-build    # reuse the existing dynamic_call_instanceof binary
```

`PHP_BIN`/`TPC_PHP_BIN` must share one PHP ABI, as in `benchmark/dynamic-call/`.
