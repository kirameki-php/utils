<?php declare(strict_types=1);

namespace Tests\Kirameki\File;

use Kirameki\File\FileSystemType;
use Kirameki\Testing\TestCase;
use function dechex;
use function decoct;
use function dump;
use function stat;

final class FileSystemTest extends TestCase
{
    public function test_instantiate(): void
    {
        dump(0o0644);
        dump(0100644 & 0007777);
        dump(FileSystemType::File->value === (0100644 &- 0000777));
        dump(stat(__FILE__));
    }
}
