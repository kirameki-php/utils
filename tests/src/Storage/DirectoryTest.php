<?php declare(strict_types=1);

namespace Tests\Kirameki\Storage;

use Kirameki\Storage\Directory;
use Kirameki\Storage\File;
use Kirameki\Storage\FileType;
use Kirameki\Storage\Storable;
use Kirameki\Storage\Symlink;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fileperms;
use function filesize;
use function is_dir;
use function is_link;
use function mkdir;
use function pack;
use function str_repeat;
use function symlink;
use function touch;
use const PHP_EOL;

final class DirectoryTest extends TestCase
{
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

    public function test_createFile_new_file(): void
    {
        $directory = new Directory($this->testDir);
        $content = 'Hello, World!';

        $file = $directory->createFile('test.txt', $content);

        $this->assertInstanceOf(File::class, $file);
        $this->assertTrue($file->exists());
        $this->assertSame('test.txt', $file->basename());
        $this->assertSame($this->testDir . '/test.txt', $file->pathname);
        $this->assertSame($content, file_get_contents($file->pathname));
    }

    public function test_createFile_empty_content(): void
    {
        $directory = new Directory($this->testDir);

        $file = $directory->createFile('empty.txt', '');

        $this->assertInstanceOf(File::class, $file);
        $this->assertTrue($file->exists());
        $this->assertSame('empty.txt', $file->basename());
        $this->assertSame('', file_get_contents($file->pathname));
    }

    public function test_createFile_overwrites_existing(): void
    {
        $directory = new Directory($this->testDir);
        $originalContent = 'Original content';
        $newContent = 'New content';

        // Create file first
        touch($this->testDir . '/existing.txt');
        file_put_contents($this->testDir . '/existing.txt', $originalContent);

        // Create file with same name (should overwrite)
        $file = $directory->createFile('existing.txt', $newContent);

        $this->assertInstanceOf(File::class, $file);
        $this->assertTrue($file->exists());
        $this->assertSame('existing.txt', $file->basename());
        $this->assertSame($newContent, file_get_contents($file->pathname));
        $this->assertNotSame($originalContent, file_get_contents($file->pathname));
    }

    public function test_createFile_with_subdirectory_path(): void
    {
        $directory = new Directory($this->testDir);
        $content = 'Content in subdirectory';

        // Create subdirectory first
        mkdir($this->testDir . '/subdir');

        $file = $directory->createFile('subdir/nested.txt', $content);

        $this->assertInstanceOf(File::class, $file);
        $this->assertTrue($file->exists());
        $this->assertSame('nested.txt', $file->basename());
        $this->assertSame($this->testDir . '/subdir/nested.txt', $file->pathname);
        $this->assertSame($content, file_get_contents($file->pathname));
    }

    public function test_createFile_special_characters(): void
    {
        $directory = new Directory($this->testDir);
        $fileName = 'test file_with-special.chars & symbols.txt';
        $content = "Content with special chars: àáâãäåæçèéêë\n\t!@#$%^&*()";

        $file = $directory->createFile($fileName, $content);

        $this->assertInstanceOf(File::class, $file);
        $this->assertTrue($file->exists());
        $this->assertSame($fileName, $file->basename());
        $this->assertSame($content, file_get_contents($file->pathname));
    }

    public function test_createFile_binary_content(): void
    {
        $directory = new Directory($this->testDir);
        // Create some binary data
        $binaryContent = pack('C*', 0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A); // PNG header

        $file = $directory->createFile('binary.dat', $binaryContent);

        $this->assertInstanceOf(File::class, $file);
        $this->assertTrue($file->exists());
        $this->assertSame('binary.dat', $file->basename());
        $this->assertSame($binaryContent, file_get_contents($file->pathname));
    }

    public function test_createFile_large_content(): void
    {
        $directory = new Directory($this->testDir);
        // Create large content (1MB)
        $largeContent = str_repeat('Large content line ' . PHP_EOL, 50000);

        $file = $directory->createFile('large.txt', $largeContent);

        $this->assertInstanceOf(File::class, $file);
        $this->assertTrue($file->exists());
        $this->assertSame('large.txt', $file->basename());
        $this->assertSame($largeContent, file_get_contents($file->pathname));
    }

    public function test_createFile_multiline_content(): void
    {
        $directory = new Directory($this->testDir);
        $content = "Line 1\nLine 2\r\nLine 3\n\nLine 5 with spaces   \n\tTabbed line";

        $file = $directory->createFile('multiline.txt', $content);

        $this->assertInstanceOf(File::class, $file);
        $this->assertTrue($file->exists());
        $this->assertSame($content, file_get_contents($file->pathname));
    }

    public function test_createFile_throws_exception_on_write_failure(): void
    {
        $this->expectErrorMessage('file_put_contents(' . $this->testDir . '/nonexistent/file.txt): Failed to open stream: No such file or directory');

        $directory = new Directory($this->testDir);
        $directory->createFile('nonexistent/file.txt', 'content');
    }

    public function test_delete_empty_directory(): void
    {
        $directory = new Directory($this->testDir);

        $this->assertTrue($directory->exists());

        $directory->delete();

        $this->assertFalse($directory->exists());
        $this->assertFalse(is_dir($this->testDir));
    }

    public function test_delete_directory_with_files(): void
    {
        touch($this->testDir . '/file1.txt');
        touch($this->testDir . '/file2.php');
        file_put_contents($this->testDir . '/file3.json', '{"test": "content"}');

        $directory = new Directory($this->testDir);

        $this->assertTrue(file_exists($this->testDir . '/file1.txt'));
        $this->assertTrue(file_exists($this->testDir . '/file2.php'));
        $this->assertTrue(file_exists($this->testDir . '/file3.json'));

        $directory->delete();

        $this->assertFalse($directory->exists());
        $this->assertFalse(file_exists($this->testDir . '/file1.txt'));
        $this->assertFalse(file_exists($this->testDir . '/file2.php'));
        $this->assertFalse(file_exists($this->testDir . '/file3.json'));
    }

    public function test_delete_directory_with_subdirectories(): void
    {
        mkdir($this->testDir . '/subdir1');
        mkdir($this->testDir . '/subdir2');
        mkdir($this->testDir . '/subdir1/nested');

        touch($this->testDir . '/root_file.txt');
        touch($this->testDir . '/subdir1/file1.txt');
        touch($this->testDir . '/subdir2/file2.txt');
        touch($this->testDir . '/subdir1/nested/deep_file.txt');

        $directory = new Directory($this->testDir);

        $this->assertTrue(is_dir($this->testDir . '/subdir1'));
        $this->assertTrue(is_dir($this->testDir . '/subdir2'));
        $this->assertTrue(is_dir($this->testDir . '/subdir1/nested'));
        $this->assertTrue(file_exists($this->testDir . '/root_file.txt'));
        $this->assertTrue(file_exists($this->testDir . '/subdir1/file1.txt'));
        $this->assertTrue(file_exists($this->testDir . '/subdir2/file2.txt'));
        $this->assertTrue(file_exists($this->testDir . '/subdir1/nested/deep_file.txt'));

        $directory->delete();

        $this->assertFalse($directory->exists());
        $this->assertFalse(is_dir($this->testDir . '/subdir1'));
        $this->assertFalse(is_dir($this->testDir . '/subdir2'));
        $this->assertFalse(is_dir($this->testDir . '/subdir1/nested'));
        $this->assertFalse(file_exists($this->testDir . '/root_file.txt'));
        $this->assertFalse(file_exists($this->testDir . '/subdir1/file1.txt'));
        $this->assertFalse(file_exists($this->testDir . '/subdir2/file2.txt'));
        $this->assertFalse(file_exists($this->testDir . '/subdir1/nested/deep_file.txt'));
    }

    public function test_delete_directory_with_symlinks(): void
    {
        touch($this->testDir . '/original_file.txt');
        mkdir($this->testDir . '/original_dir');
        touch($this->testDir . '/original_dir/nested_file.txt');

        symlink($this->testDir . '/original_file.txt', $this->testDir . '/symlink_file.txt');
        symlink($this->testDir . '/original_dir', $this->testDir . '/symlink_dir');

        $directory = new Directory($this->testDir);

        $this->assertTrue(is_link($this->testDir . '/symlink_file.txt'));
        $this->assertTrue(is_link($this->testDir . '/symlink_dir'));

        $directory->delete();

        $this->assertFalse($directory->exists());
        $this->assertFalse(file_exists($this->testDir . '/symlink_file.txt'));
        $this->assertFalse(file_exists($this->testDir . '/symlink_dir'));
        $this->assertFalse(file_exists($this->testDir . '/original_file.txt'));
        $this->assertFalse(file_exists($this->testDir . '/original_dir'));
    }

    public function test_delete_deeply_nested_structure(): void
    {
        // Create deeply nested structure (5 levels)
        $currentPath = $this->testDir;
        for ($i = 1; $i <= 5; $i++) {
            $currentPath .= "/level{$i}";
            mkdir($currentPath);
            touch($currentPath . "/file{$i}.txt");

            // Add some branches
            if ($i <= 3) {
                mkdir($currentPath . "/branch{$i}");
                touch($currentPath . "/branch{$i}/branch_file{$i}.txt");
            }
        }

        $directory = new Directory($this->testDir);

        // Verify deep structure exists
        $this->assertTrue(is_dir($this->testDir . '/level1/level2/level3/level4/level5'));
        $this->assertTrue(file_exists($this->testDir . '/level1/level2/level3/level4/level5/file5.txt'));
        $this->assertTrue(file_exists($this->testDir . '/level1/branch1/branch_file1.txt'));

        $directory->delete();

        // Verify complete deletion
        $this->assertFalse($directory->exists());
        $this->assertFalse(is_dir($this->testDir . '/level1'));
    }

    public function test_delete_directory_with_special_filenames(): void
    {
        // Create files with special characters in names
        touch($this->testDir . '/file with spaces.txt');
        touch($this->testDir . '/file-with_special.chars');
        touch($this->testDir . '/файл.txt'); // Cyrillic characters
        mkdir($this->testDir . '/dir with spaces');
        touch($this->testDir . '/dir with spaces/nested file.txt');

        $directory = new Directory($this->testDir);

        // Verify files exist
        $this->assertTrue(file_exists($this->testDir . '/file with spaces.txt'));
        $this->assertTrue(file_exists($this->testDir . '/file-with_special.chars'));
        $this->assertTrue(file_exists($this->testDir . '/файл.txt'));
        $this->assertTrue(is_dir($this->testDir . '/dir with spaces'));

        $directory->delete();

        // Verify all deleted
        $this->assertFalse($directory->exists());
        $this->assertFalse(file_exists($this->testDir . '/file with spaces.txt'));
        $this->assertFalse(file_exists($this->testDir . '/file-with_special.chars'));
        $this->assertFalse(file_exists($this->testDir . '/файл.txt'));
        $this->assertFalse(is_dir($this->testDir . '/dir with spaces'));
    }

    public function test_delete_directory_with_large_files(): void
    {
        $largeContent = str_repeat('Large file content line ' . PHP_EOL, 10000);
        file_put_contents($this->testDir . '/large_file.txt', $largeContent);

        $binaryContent = str_repeat(pack('C*', 0x00, 0xFF, 0xAB, 0xCD), 1000);
        file_put_contents($this->testDir . '/binary_file.dat', $binaryContent);

        $directory = new Directory($this->testDir);

        $this->assertTrue(file_exists($this->testDir . '/large_file.txt'));
        $this->assertTrue(file_exists($this->testDir . '/binary_file.dat'));
        $this->assertGreaterThan(100000, filesize($this->testDir . '/large_file.txt'));

        $directory->delete();

        $this->assertFalse($directory->exists());
        $this->assertFalse(file_exists($this->testDir . '/large_file.txt'));
        $this->assertFalse(file_exists($this->testDir . '/binary_file.dat'));
    }
}
