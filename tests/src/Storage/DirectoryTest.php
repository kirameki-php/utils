<?php declare(strict_types=1);

namespace Tests\Kirameki\Storage;

use Kirameki\Storage\Directory;
use Kirameki\Storage\File;
use Kirameki\Storage\Storable;
use Kirameki\Storage\Symlink;
use function mkdir;
use function symlink;
use function touch;

final class DirectoryTest extends TestCase
{
    public function test_getFiles_empty_directory(): void
    {
        $directory = new Directory($this->testDir);
        $files = $directory->getFiles();

        $this->assertCount(0, $files);
    }

    public function test_getFiles_with_files_only(): void
    {
        touch($this->testDir . '/file1.txt');
        touch($this->testDir . '/file2.php');
        touch($this->testDir . '/file3.json');

        $directory = new Directory($this->testDir);
        $files = $directory->getFiles();

        $this->assertCount(3, $files);

        foreach ($files as $file) {
            $this->assertInstanceOf(File::class, $file);
        }
        $this->assertSame(
            ['file1.txt', 'file2.php', 'file3.json'],
            $files->map(fn(Storable $s) => $s->basename())->toArray(),
        );
    }

    public function test_getFiles_with_subdirectories(): void
    {
        // Create test files and subdirectories
        touch($this->testDir . '/file1.txt');
        mkdir($this->testDir . '/dir1');
        mkdir($this->testDir . '/dir2');
        touch($this->testDir . '/dir1/nested_file.txt');

        $directory = new Directory($this->testDir);
        $files = $directory->getFiles();

        $this->assertCount(3, $files); // 1 file + 2 directories
        $this->assertCount(1, $files->filter(fn($s) => $s instanceof File));
        $this->assertCount(2, $files->filter(fn($s) => $s instanceof Directory));

        $this->assertSame(
            ['file1.txt', 'dir1', 'dir2'],
            $files->map(fn(Storable $s) => $s->basename())->toArray(),
        );
    }

    public function test_getFiles_with_symlinks_follow_true(): void
    {
        // Create a regular file and a symlink to it
        $originalFile = $this->testDir . '/original.txt';
        touch($originalFile);
        symlink($originalFile, $this->testDir . '/symlink.txt');

        $directory = new Directory($this->testDir);
        $files = $directory->getFiles(true);

        $this->assertCount(2, $files);
        $this->assertCount(2, $files->filter(fn($s) => $s instanceof File));
    }

    public function test_getFiles_with_symlinks_follow_false(): void
    {
        // Create a regular file and a symlink to it
        $originalFile = $this->testDir . '/original.txt';
        touch($originalFile);

        $symlinkPath = $this->testDir . '/symlink.txt';
        symlink($originalFile, $symlinkPath);

        $directory = new Directory($this->testDir);
        $files = $directory->getFiles(false);

        $this->assertCount(2, $files);
        $this->assertCount(2, $files->filter(fn($s) => $s instanceof File));
        $this->assertCount(1, $files->filter(fn($s) => $s instanceof Symlink));
    }

    public function test_getFiles_does_not_recurse(): void
    {
        // Create nested structure
        touch($this->testDir . '/root_file.txt');
        mkdir($this->testDir . '/subdir');
        touch($this->testDir . '/subdir/nested_file.txt');
        mkdir($this->testDir . '/subdir/nested_dir');
        touch($this->testDir . '/subdir/nested_dir/deep_file.txt');

        $directory = new Directory($this->testDir);
        $files = $directory->getFiles();

        // Should only return direct children (not recursive)
        $this->assertCount(2, $files); // root_file.txt + subdir

        $this->assertSame(
            ['root_file.txt', 'subdir'],
            $files->map(fn(Storable $s) => $s->basename())->toArray(),
        );
    }
}
