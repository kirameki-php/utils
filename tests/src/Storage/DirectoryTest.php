<?php declare(strict_types=1);

namespace Tests\Kirameki\Storage;

use Kirameki\Storage\Directory;
use Kirameki\Storage\File;
use Kirameki\Storage\FileType;
use Kirameki\Storage\Storable;
use Kirameki\Storage\Symlink;
use function dump;
use function mkdir;
use function symlink;
use function touch;

final class DirectoryTest extends TestCase
{
//    public function test_tt(): void
//    {
//    }

    public function test_scan_empty_directory(): void
    {
        $directory = new Directory($this->testDir);
        $files = $directory->scan();

        $this->assertCount(0, $files);
    }

    public function test_scan_with_files_only(): void
    {
        touch($this->testDir . '/f1.txt');
        touch($this->testDir . '/f2.php');
        touch($this->testDir . '/f3.json');

        $directory = new Directory($this->testDir);
        $files = $directory->scan();

        $this->assertCount(3, $files);

        foreach ($files as $file) {
            $this->assertInstanceOf(File::class, $file);
        }
        $this->assertSame(
            ['f1.txt', 'f2.php', 'f3.json'],
            $files->map(fn(Storable $s) => $s->basename())->sortAsc()->toArray(),
        );
    }

    public function test_scan_with_subdirectories(): void
    {
        // Create test files and subdirectories
        touch($this->testDir . '/f1.txt');
        mkdir($this->testDir . '/d1');
        touch($this->testDir . '/d1/nested_file.txt');
        mkdir($this->testDir . '/d2');

        $directory = new Directory($this->testDir);
        $files = $directory->scan();

        $this->assertCount(3, $files); // 1 file + 2 directories
        $this->assertCount(1, $files->filter(fn($s) => $s instanceof File));
        $this->assertCount(2, $files->filter(fn($s) => $s instanceof Directory));

        $this->assertSame(
            ['d1', 'd2', 'f1.txt'],
            $files->map(fn(Storable $s) => $s->basename())->sortAsc()->toArray(),
        );
    }

    public function test_scan_with_symlinks_follow_true(): void
    {
        // Create a regular file and a symlink to it
        $originalFile = $this->testDir . '/original.txt';
        touch($originalFile);
        symlink($originalFile, $this->testDir . '/symlink_file.txt');

        // Create a regular directory and a symlink to it
        $originalDir = $this->testDir . '/original_dir';
        mkdir($originalDir);
        touch($originalDir . '/nested_file.txt');
        symlink($originalDir, $this->testDir . '/symlink_dir');

        $directory = new Directory($this->testDir);
        $files = $directory->scan();

        $this->assertCount(4, $files); // original.txt + symlink_file.txt + original_dir + symlink_dir
        $this->assertCount(3, $files->filter(fn($s) => $s instanceof File)); // both treated as files
        $this->assertCount(1, $files->filter(fn($s) => $s instanceof Directory)); // both treated as directories
        $this->assertCount(0, $files->filter(fn($s) => $s instanceof Symlink)); // no symlinks when following

        $this->assertSame([
            'original.txt',
            'original_dir',
            'symlink_dir',
            'symlink_file.txt',
        ], $files->map(fn(Storable $s) => $s->basename())->sortAsc()->toArray());
    }

    public function test_scan_with_symlinks_follow_false(): void
    {
        // Create a regular file and a symlink to it
        $originalFile = $this->testDir . '/original.txt';
        touch($originalFile);
        symlink($originalFile, $this->testDir . '/symlink_file.txt');

        // Create a regular directory and a symlink to it
        $originalDir = $this->testDir . '/original_dir';
        mkdir($originalDir);
        touch($originalDir . '/nested_file.txt');
        symlink($originalDir, $this->testDir . '/symlink_dir');

        $directory = new Directory($this->testDir);
        $files = $directory->scan(false);

        $this->assertCount(4, $files); // original.txt + symlink_file.txt + original_dir + symlink_dir
        $this->assertCount(1, $files->filter(fn($s) => $s instanceof File)); // only original.txt
        $this->assertCount(1, $files->filter(fn($s) => $s instanceof Directory)); // only original_dir
        $this->assertCount(2, $files->filter(fn($s) => $s instanceof Symlink)); // both symlinks

        $this->assertSame([
            'original.txt',
            'original_dir',
            'symlink_dir',
            'symlink_file.txt',
        ], $files->map(fn(Storable $s) => $s->basename())->sortAsc()->toArray());
    }

    public function test_scan_does_not_recurse(): void
    {
        // Create nested structure
        touch($this->testDir . '/root_file.txt');
        mkdir($this->testDir . '/subdir');
        touch($this->testDir . '/subdir/nested_file.txt');
        mkdir($this->testDir . '/subdir/nested_dir');
        touch($this->testDir . '/subdir/nested_dir/deep_file.txt');

        $directory = new Directory($this->testDir);
        $files = $directory->scan();

        // Should only return direct children (not recursive)
        $this->assertCount(2, $files); // root_file.txt + subdir

        $this->assertSame(
            ['root_file.txt', 'subdir'],
            $files->map(fn(Storable $s) => $s->basename())->sortAsc()->toArray(),
        );
    }

    public function test_scanRecursively_empty_directory(): void
    {
        $directory = new Directory($this->testDir);
        $files = $directory->scanRecursively();

        $this->assertCount(0, $files);
    }

    public function test_scanRecursively_with_nested_structure(): void
    {
        touch($this->testDir . '/root_file.txt');
        mkdir($this->testDir . '/dir1');
        touch($this->testDir . '/dir1/file1.txt');
        mkdir($this->testDir . '/dir1/subdir1');
        touch($this->testDir . '/dir1/subdir1/nested_file.txt');
        mkdir($this->testDir . '/dir2');
        touch($this->testDir . '/dir2/file2.txt');

        $files = new Directory($this->testDir)->scanRecursively();

        $this->assertSame([
            'file1.txt',
            'file2.txt',
            'nested_file.txt',
            'root_file.txt',
        ], $files->map(fn(Storable $s) => $s->basename())->sortAsc()->toArray());

        $this->assertCount(4, $files);
        $this->assertCount(4, $files->filter(fn($s) => $s instanceof File));
        $this->assertCount(0, $files->filter(fn($s) => $s instanceof Directory));
    }

    public function test_scanRecursively_with_symlinks_follow_true(): void
    {
        mkdir($this->testDir . '/dir1');
        touch($this->testDir . '/dir1/file1.txt');
        mkdir($this->testDir . '/dir2');
        touch($this->testDir . '/dir2/file2.txt');
        symlink($this->testDir . '/dir1/file1.txt', $this->testDir . '/symlink_file.txt');
        symlink($this->testDir . '/dir2', $this->testDir . '/symlink_dir');

        $files = new Directory($this->testDir)->scanRecursively();

        $this->assertCount(4, $files);
        $this->assertCount(0, $files->filter(fn($s) => $s instanceof Symlink));
        $this->assertCount(4, $files->filter(fn($s) => $s instanceof File));
        $this->assertCount(0, $files->filter(fn($s) => $s instanceof Directory));
    }

    public function test_scanRecursively_with_symlinks_follow_false(): void
    {
        mkdir($this->testDir . '/dir1');
        touch($this->testDir . '/dir1/file1.txt');
        mkdir($this->testDir . '/dir2');
        touch($this->testDir . '/dir2/file2.txt');
        symlink($this->testDir . '/dir1/file1.txt', $this->testDir . '/symlink_file.txt');
        symlink($this->testDir . '/dir2', $this->testDir . '/symlink_dir');

        $files = new Directory($this->testDir)->scanRecursively(false);

        $this->assertCount(4, $files);
        $this->assertCount(1, $files->filter(fn($s) => $s instanceof Symlink));
        $this->assertCount(3, $files->filter(fn($s) => $s instanceof File));
        $this->assertCount(0, $files->filter(fn($s) => $s instanceof Directory));
    }

    public function test_scanRecursively_deep_nesting(): void
    {
        $currentPath = $this->testDir;
        for ($i = 1; $i <= 3; $i++) {
            $currentPath .= "/level{$i}";
            mkdir($currentPath);
            touch($currentPath . "/file{$i}.txt");
        }

        $directory = new Directory($this->testDir);
        $files = $directory->scanRecursively();

        $this->assertCount(3, $files);

        $this->assertSame([
            'file1.txt',
            'file2.txt',
            'file3.txt',
        ], $files->map(fn(Storable $s) => $s->basename())->sortAsc()->toArray());
    }

    public function test_createSubDirectory_new_directory(): void
    {
        $directory = new Directory($this->testDir);
        $created = false;

        $subDir = $directory->createSubDirectory('new_subdir', 0744, $created);

        $this->assertTrue($created);
        $this->assertInstanceOf(Directory::class, $subDir);
        $this->assertTrue($subDir->exists());
        $this->assertSame('new_subdir', $subDir->basename());
        $this->assertSame($this->testDir . '/new_subdir', $subDir->pathname);
        $this->assertSame($subDir->permissions, 0744);
    }

    public function test_createSubDirectory_existing_directory(): void
    {
        mkdir($this->testDir . '/existing_dir');

        $directory = new Directory($this->testDir);
        $created = false;

        $subDir = $directory->createSubDirectory('existing_dir', 0744, $created);

        $this->assertFalse($created);
        $this->assertInstanceOf(Directory::class, $subDir);
        $this->assertTrue($subDir->exists());
        $this->assertSame('existing_dir', $subDir->basename());
        $this->assertSame($this->testDir . '/existing_dir', $subDir->pathname);
        $this->assertSame($subDir->type, FileType::Directory);
        $this->assertSame($subDir->permissions, 0755, 'Permissions should not change');
    }

    public function test_createSubDirectory_nested_path(): void
    {
        $directory = new Directory($this->testDir);
        $created = false;
        $subDir = $directory->createSubDirectory('level1/level2/level3', 0755, $created);

        $this->assertTrue($created);
        $this->assertInstanceOf(Directory::class, $subDir);
        $this->assertTrue($subDir->exists());
        $this->assertSame('level3', $subDir->basename());
        $this->assertSame($this->testDir . '/level1/level2/level3', $subDir->pathname);

        $this->assertTrue(is_dir($this->testDir . '/level1'));
        $this->assertTrue(is_dir($this->testDir . '/level1/level2'));
        $this->assertSame(0755, fileperms($this->testDir . '/level1') & 0777);
        $this->assertSame(0755, fileperms($this->testDir . '/level1/level2') & 0777);
        $this->assertSame(0755, fileperms($subDir->pathname) & 0777);
    }

    public function test_createSubDirectory_with_permissions(): void
    {
        $directory = new Directory($this->testDir);
        $created = false;

        $subDir = $directory->createSubDirectory('perm_test', 0744, $created);
        $this->assertTrue($created);
        $this->assertInstanceOf(Directory::class, $subDir);

        // Note: On some systems, permissions might be modified by umask
        // So we check that the directory was created successfully
        $this->assertTrue($subDir->exists());
        $this->assertSame('perm_test', $subDir->basename());
    }

    public function test_createSubDirectory_without_created_parameter(): void
    {
        $directory = new Directory($this->testDir);
        $subDir = $directory->createSubDirectory('no_created_param', 0755);

        $this->assertInstanceOf(Directory::class, $subDir);
        $this->assertTrue($subDir->exists());
        $this->assertSame('no_created_param', $subDir->basename());
    }

    public function test_createSubDirectory_special_characters(): void
    {
        $directory = new Directory($this->testDir);
        $created = false;

        $dirName = 'test dir_with-special.chars';
        $subDir = $directory->createSubDirectory($dirName, 0744, $created);

        $this->assertTrue($created);
        $this->assertInstanceOf(Directory::class, $subDir);
        $this->assertTrue($subDir->exists());
        $this->assertSame($dirName, $subDir->basename());
        $this->assertSame(0744, fileperms($subDir->pathname) & 0777);
    }

    public function test_createSubDirectory_clearstatcache_called(): void
    {
        mkdir($this->testDir . '/existing_for_cache_test');

        $directory = new Directory($this->testDir);
        $created = false;

        // This should call clearstatcache internally when directory already exists
        $subDir = $directory->createSubDirectory('existing_for_cache_test', 0755, $created);

        $this->assertFalse($created);
        $this->assertInstanceOf(Directory::class, $subDir);
        $this->assertTrue($subDir->exists());
    }
}
