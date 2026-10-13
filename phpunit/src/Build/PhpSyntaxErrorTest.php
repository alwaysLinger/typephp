<?php

namespace TypePhp\Tests\Build;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypePhp\CompilerTest;
use TypePhp\Exception\TestError;

final class PhpSyntaxErrorTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/typephp-syntax-' . bin2hex(random_bytes(8));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($this->directory);
    }

    public static function preparationPaths(): array
    {
        return [
            'direct file' => ['direct'],
            ...self::projectPreparationPaths(),
        ];
    }

    public static function projectPreparationPaths(): array
    {
        return [
            'project' => ['project'],
            'nano' => ['nano'],
        ];
    }

    #[DataProvider('preparationPaths')]
    public function testParserErrorIsFatalAndIncludesSourceLocation(string $path): void
    {
        $file = $this->directory . '/broken.php';
        file_put_contents($file, "<?php\nfunction broken(): void {\n    \$value = ;\n}\n");
        $compiler = $this->compiler();

        $this->expectException(TestError::class);
        $this->expectExceptionMessageMatches('/Syntax error.*' . preg_quote($file . ':3', '/') . '/');
        $this->prepare($compiler, $path, [$file]);
    }

    #[DataProvider('projectPreparationPaths')]
    public function testSyntaxErrorsDuringPreparationCannotDropAFile(string $path): void
    {
        $file = $this->directory . '/invalid-constant.php';
        file_put_contents($file, '<?php class InvalidConstant { public const VALUE = strlen("x"); }');
        $compiler = $this->compiler();

        $this->expectException(TestError::class);
        $this->expectExceptionMessageMatches('/' . preg_quote($file, '/') . '.*Constant expression contains invalid operations/');
        $this->prepare($compiler, $path, [$file]);
    }

    public function testChangedSourceCannotReuseAValidAstToHideSyntaxErrors(): void
    {
        $file = $this->directory . '/cached.php';
        file_put_contents($file, '<?php function cached(): int { return 1; }');
        $this->compiler()->prepareFile($file);
        $mtime = filemtime($file);
        file_put_contents($file, "<?php\nfunction cached(): int { return ; + }\n");
        touch($file, $mtime);

        $this->expectException(TestError::class);
        $this->expectExceptionMessage($file . ':2');
        $this->compiler()->prepareFile($file);
    }

    private function compiler(): CompilerTest
    {
        $compiler = CompilerTest::create(dirname(__DIR__, 3));
        $compiler->setTargetName('syntax_error');
        (new \ReflectionMethod($compiler, 'setBuildDir'))->invoke($compiler, $this->directory . '/build');
        return $compiler;
    }

    private function prepare(CompilerTest $compiler, string $path, array $files): void
    {
        if ($path === 'direct') {
            $compiler->prepareFile($files[0]);
        } elseif ($path === 'nano') {
            $compiler->prepareNanoSources($files, 'syntax_error', $this->directory . '/build', false);
        } else {
            (new \ReflectionMethod($compiler, 'preprocessProjectFiles'))->invoke($compiler, $files, 'syntax-error-test');
        }
    }
}
