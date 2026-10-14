<?php


$root = dirname(__DIR__, 2);
$source = __DIR__ . '/benchmark.php';
$project = __DIR__ . '/project.yml';
$binary = __DIR__ . '/dynamic_call_instanceof' . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
$skipBuild = in_array('--skip-build', $argv, true);

if (!is_file($binary)) {
    $skipBuild = false;
}

function runInstanceofCommand(array $command, string $cwd, bool $capture): string
{
    $stdout = $capture ? ['pipe', 'w'] : STDOUT;
    $stderr = $capture ? ['pipe', 'w'] : STDERR;
    $process = proc_open(
        $command,
        [STDIN, $stdout, $stderr],
        $pipes,
        $cwd,
        null,
        ['bypass_shell' => true],
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Failed to start: ' . implode(' ', $command));
    }

    $output = '';
    $error = '';
    if ($capture) {
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
    }
    $status = proc_close($process);
    if ($status !== 0) {
        throw new RuntimeException(
            'Command failed (' . $status . '): ' . implode(' ', $command) . "\n" . $output . $error,
        );
    }
    return $output;
}

/** @return array<string, float> */
function parseInstanceofMetrics(string $output): array
{
    $metrics = [];
    foreach (explode("\n", trim($output)) as $line) {
        if (preg_match('/^([a-z_]+)_ns=([0-9.]+)$/', $line, $matches)) {
            $metrics[$matches[1]] = (float) $matches[2];
        }
    }
    return $metrics;
}

/** @return array<string, float> */
function parseInstanceofChecksums(string $output): array
{
    $checksums = [];
    foreach (explode("\n", trim($output)) as $line) {
        if (preg_match('/^checksum_([a-z_]+)=(-?[0-9]+)$/', $line, $matches)) {
            $checksums[$matches[1]] = (float) $matches[2];
        }
    }
    return $checksums;
}

$runs = 1;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--runs=')) {
        $runs = max(1, (int) substr($argument, strlen('--runs=')));
    }
}

$baselineTs = static fn (): string => trim(runInstanceofCommand([
    PHP_BINARY,
    '-n',
    '-r',
    'printf("%s;%d;%d;%d", PHP_VERSION, PHP_ZTS, PHP_DEBUG, PHP_INT_SIZE);',
], $root, true));
$baselineRuntime = $baselineTs();

if (!$skipBuild) {
    echo "Building TypePHP instanceof benchmark (-O3 + LTO)...\n";
    runInstanceofCommand([
        PHP_BINARY,
        '-n',
        $root . '/bin/tpc.php',
        $project,
        '-j',
        '8',
        '--no-color',
        '--no-progress',
    ], $root, false);
}
if (!is_file($binary)) {
    throw new RuntimeException('Benchmark binary does not exist: ' . $binary);
}

$environment = getenv();
if (PHP_OS_FAMILY !== 'Windows') {
    $phpxHome = getenv('PHPX_HOME') ?: dirname($root) . '/phpx';
    $loaderVariable = PHP_OS_FAMILY === 'Darwin' ? 'DYLD_LIBRARY_PATH' : 'LD_LIBRARY_PATH';
    $existing = $environment[$loaderVariable] ?? '';
    $environment[$loaderVariable] = $phpxHome . '/lib'
        . ($existing === '' ? '' : PATH_SEPARATOR . $existing);
}

$phpMetrics = null;
$phpChecksums = null;
$phpSamples = [];
for ($run = 1; $run <= $runs; $run++) {
    $output = runInstanceofCommand([
        PHP_BINARY,
        '-n',
        '-d',
        'opcache.enable_cli=0',
        '-d',
        'opcache.jit=0',
        '-r',
        'require ' . var_export($source, true) . '; main();',
    ], $root, true, $environment);
    $sample = parseInstanceofMetrics($output);
    $checksums = parseInstanceofChecksums($output);
    foreach ($sample as $case => $nanoseconds) {
        $phpSamples[$case][] = $nanoseconds;
    }
    $phpChecksums = $checksums;
}

$typephpSamples = [];
$typephpChecksums = null;
for ($run = 1; $run <= $runs; $run++) {
    $output = runInstanceofCommand([$binary], $root, true, $environment);
    $sample = parseInstanceofMetrics($output);
    foreach ($sample as $case => $nanoseconds) {
        $typephpSamples[$case][] = $nanoseconds;
    }
    $typephpChecksums = parseInstanceofChecksums($output);
}

$median = static function (array $values): float {
    sort($values, SORT_NUMERIC);
    $count = count($values);
    if ($count % 2 === 1) {
        return $values[intdiv($count, 2)];
    }
    return ($values[intdiv($count, 2) - 1] + $values[intdiv($count, 2)]) / 2;
};

echo "Runtime: {$baselineRuntime}\n";
echo "Metric                    PHP ns/op  TypePHP ns/op  TypePHP/PHP\n";
echo "--------------------------------------------------------------\n";
foreach ($phpSamples as $case => $_) {
    if (!isset($typephpSamples[$case])) {
        throw new RuntimeException("Missing benchmark metric: {$case}");
    }
    if (($phpChecksums[$case] ?? null) !== ($typephpChecksums[$case] ?? null)) {
        throw new RuntimeException("Checksum mismatch for benchmark case: {$case}");
    }
    $phpMedian = $median($phpSamples[$case]);
    $typephpMedian = $median($typephpSamples[$case]);
    printf(
        "%-25s %10.2f %14.2f %12.2fx\n",
        $case,
        $phpMedian,
        $typephpMedian,
        $typephpMedian / $phpMedian,
    );
}
