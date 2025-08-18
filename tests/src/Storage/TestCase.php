<?php declare(strict_types=1);

namespace Tests\Kirameki\Storage;

use FilesystemIterator;
use Kirameki\Testing\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function mkdir;
use function rmdir;
use function uniqid;
use function unlink;

class TestCase extends BaseTestCase
{
    protected string $testDir;

    #[Before]
    protected function createTestDir(): void
    {
        $this->testDir = '/tmp/phpunit_storage_' . $this->name() . '_' . uniqid();
        mkdir($this->testDir);
    }

    #[After]
    protected function removeTestDir(): void
    {
        if (!is_dir($this->testDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->testDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                unlink($file->getPathname());
                continue;
            }
            if ($file->isFile()) {
                unlink($file->getPathname());
                continue;
            }
            rmdir($file->getPathname());
        }
        rmdir($this->testDir);
    }
}
