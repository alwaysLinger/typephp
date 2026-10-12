<?php
/**
 * This file is part of TypePHP(AOT).
 *
 * @link     https://www.swoole.com/aot/
 * @contact  service@swoole.com
 */

namespace TypePhp\Tests\Build;

use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use PHPUnit\Framework\TestCase;
use TypePhp\Backend\CompilerBackend;
use TypePhp\Build\LinkOptions;
use TypePhp\Build\NativeBuilder;

/**
 * @internal
 * @coversNothing
 */
#[RequiresOperatingSystemFamily('Linux')]
final class NativeBuilderLinkTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/typephp-link-output-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
    }

    public function testLinkCreatesMissingOutputParentDirectory(): void
    {
        $target = $this->directory . '/artifacts/app';

        $backend = $this->createMock(CompilerBackend::class);
        $backend->method('buildLinkCommand')
            ->willReturnCallback(static fn(array $objects, string $linkTarget): string => 'touch ' . escapeshellarg($linkTarget));

        $result = (new NativeBuilder($backend))->link(['app.o'], $target, new LinkOptions([]));

        self::assertSame(0, $result['status']);
        self::assertTrue($result['generated']);
        self::assertDirectoryExists(dirname($target));
        self::assertFileExists($target);
    }

    public function testLinkReportsUnwritableOutputDirectoryInsteadOfBareLinkerError(): void
    {
        // A regular file where a parent directory is expected makes mkdir
        // fail regardless of process privileges (running as root ignores
        // read-only directory modes).
        $parentFile = $this->directory . '/artifacts';
        mkdir($this->directory, 0777, true);
        file_put_contents($parentFile, 'not a directory');
        $target = $parentFile . '/app';

        $backend = $this->createMock(CompilerBackend::class);
        $backend->method('buildLinkCommand')
            ->willReturnCallback(static fn(array $objects, string $linkTarget): string => 'touch ' . escapeshellarg($linkTarget));

        // ensureDirectory() does not suppress the mkdir() warning (same as the
        // other ensureDirectory implementations); swallow it here and assert
        // on the exception instead.
        set_error_handler(static function (int $errno, string $errstr): bool {return true;});
        try {
            (new NativeBuilder($backend))->link(['app.o'], $target, new LinkOptions([]));
            self::fail('Expected the link to fail with a directory error');
        } catch (\RuntimeException $exception) {
            self::assertStringStartsWith('Cannot create output directory: ', $exception->getMessage());
        } finally {
            restore_error_handler();
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($directory);
    }
}
