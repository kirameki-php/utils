<?php declare(strict_types=1);

namespace Tests\Kirameki\Storage;

use Kirameki\Storage\Directory;
use Kirameki\Storage\File;
use Kirameki\Storage\FileType;
use Kirameki\Storage\Symlink;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_link;
use function mkdir;
use function symlink;
use function touch;

final class SymlinkTest extends TestCase
{
    public function test_getTarget_file_symlink(): void
    {
        $targetFilePath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/symlink_to_file.txt';
        $content = 'Target file content';

        // Create target file
        file_put_contents($targetFilePath, $content);

        // Create symlink to file
        symlink($targetFilePath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $target = $symlink->getTarget();

        $this->assertInstanceOf(File::class, $target);
        $this->assertSame($targetFilePath, $target->pathname);
        $this->assertSame($target->type, FileType::File);
        $this->assertTrue($target->exists());
    }

    public function test_getTarget_directory_symlink(): void
    {
        $targetDirPath = $this->testDir . '/target_directory';
        $symlinkPath = $this->testDir . '/symlink_to_directory';

        // Create target directory with a file inside
        mkdir($targetDirPath);
        touch($targetDirPath . '/nested_file.txt');

        // Create symlink to directory
        symlink($targetDirPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $target = $symlink->getTarget();

        $this->assertInstanceOf(Directory::class, $target);
        $this->assertSame($targetDirPath, $target->pathname);
        $this->assertSame($target->type, FileType::Directory);
        $this->assertTrue($target->exists());
    }

    public function test_getTarget_relative_path_symlink(): void
    {
        $targetFileName = 'relative_target.txt';
        $targetFilePath = $this->testDir . '/' . $targetFileName;
        $symlinkPath = $this->testDir . '/relative_symlink.txt';
        $content = 'Relative target content';

        // Create target file
        file_put_contents($targetFilePath, $content);

        // Create symlink with relative path
        symlink($targetFileName, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $target = $symlink->getTarget();

        $this->assertInstanceOf(File::class, $target);
        $this->assertSame($targetFileName, $target->pathname);
    }

    public function test_getTarget_absolute_path_symlink(): void
    {
        $targetFilePath = $this->testDir . '/absolute_target.txt';
        $symlinkPath = $this->testDir . '/absolute_symlink.txt';
        $content = 'Absolute target content';

        // Create target file
        file_put_contents($targetFilePath, $content);

        // Create symlink with absolute path
        symlink($targetFilePath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $target = $symlink->getTarget();

        $this->assertInstanceOf(File::class, $target);
        $this->assertSame($targetFilePath, $target->pathname);
        $this->assertTrue($target->exists());
    }

    public function test_getTarget_nested_directory_symlink(): void
    {
        $targetDirPath = $this->testDir . '/level1/level2/target_dir';
        $symlinkPath = $this->testDir . '/nested_symlink';

        // Create nested target directory structure
        mkdir($targetDirPath, 0755, true);
        touch($targetDirPath . '/deep_file.txt');

        // Create symlink to nested directory
        symlink($targetDirPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $target = $symlink->getTarget();

        $this->assertInstanceOf(Directory::class, $target);
        $this->assertSame($targetDirPath, $target->pathname);
        $this->assertTrue($target->exists());
    }

    public function test_getTarget_broken_symlink(): void
    {
        $nonExistentPath = $this->testDir . '/nonexistent_target.txt';
        $symlinkPath = $this->testDir . '/broken_symlink.txt';

        // Create symlink to non-existent file
        symlink($nonExistentPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $target = $symlink->getTarget();

        // Should still return a File object even if target doesn't exist
        $this->assertInstanceOf(File::class, $target);
        $this->assertSame($nonExistentPath, $target->pathname);
        $this->assertFalse($target->exists());
    }

    public function test_getTarget_chained_symlinks(): void
    {
        $targetFilePath = $this->testDir . '/final_target.txt';
        $firstSymlinkPath = $this->testDir . '/first_symlink.txt';
        $secondSymlinkPath = $this->testDir . '/second_symlink.txt';
        $content = 'Chained symlink target';

        // Create target file
        file_put_contents($targetFilePath, $content);

        // Create chain: second_symlink -> first_symlink -> target_file
        symlink($targetFilePath, $firstSymlinkPath);
        symlink($firstSymlinkPath, $secondSymlinkPath);

        $symlink = new Symlink($secondSymlinkPath);
        $target = $symlink->getTarget();

        // Should return the immediate target (first symlink), not the final target
        $this->assertInstanceOf(File::class, $target);
        $this->assertSame($firstSymlinkPath, $target->pathname);
        $this->assertTrue($target->exists());
    }

    public function test_getTarget_throws_exception_for_invalid_symlink(): void
    {
        $invalidPath = $this->testDir . '/not_a_symlink.txt';

        // Create a regular file (not a symlink)
        touch($invalidPath);

        $symlink = new Symlink($invalidPath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unable to read link {$invalidPath}, error: Invalid argument");

        $symlink->getTarget();
    }

    public function test_delete_file_symlink(): void
    {
        $targetFilePath = $this->testDir . '/target_for_deletion.txt';
        $symlinkPath = $this->testDir . '/symlink_for_deletion.txt';
        $content = 'Target file that should remain after symlink deletion';

        // Create target file
        file_put_contents($targetFilePath, $content);

        // Create symlink
        symlink($targetFilePath, $symlinkPath);

        // Verify both exist
        $this->assertTrue(file_exists($targetFilePath));
        $this->assertTrue(is_link($symlinkPath));

        $symlink = new Symlink($symlinkPath);
        $symlink->delete();

        // Verify symlink is deleted but target remains
        $this->assertFalse(file_exists($symlinkPath));
        $this->assertFalse(is_link($symlinkPath));
        $this->assertTrue(file_exists($targetFilePath));
        $this->assertSame($content, file_get_contents($targetFilePath));
    }

    public function test_delete_directory_symlink(): void
    {
        $targetDirPath = $this->testDir . '/target_dir_for_deletion';
        $symlinkPath = $this->testDir . '/symlink_dir_for_deletion';

        // Create target directory with content
        mkdir($targetDirPath);
        touch($targetDirPath . '/important_file.txt');
        file_put_contents($targetDirPath . '/important_file.txt', 'Important content');

        // Create symlink to directory
        symlink($targetDirPath, $symlinkPath);

        // Verify both exist
        $this->assertTrue(is_dir($targetDirPath));
        $this->assertTrue(is_link($symlinkPath));

        $symlink = new Symlink($symlinkPath);
        $symlink->delete();

        // Verify symlink is deleted but target directory and its contents remain
        $this->assertFalse(file_exists($symlinkPath));
        $this->assertFalse(is_link($symlinkPath));
        $this->assertTrue(is_dir($targetDirPath));
        $this->assertTrue(file_exists($targetDirPath . '/important_file.txt'));
        $this->assertSame('Important content', file_get_contents($targetDirPath . '/important_file.txt'));
    }

    public function test_delete_broken_symlink(): void
    {
        $nonExistentPath = $this->testDir . '/nonexistent_target_for_deletion.txt';
        $symlinkPath = $this->testDir . '/broken_symlink_for_deletion.txt';

        // Create broken symlink
        symlink($nonExistentPath, $symlinkPath);

        // Verify symlink exists but target doesn't
        $this->assertTrue(is_link($symlinkPath));
        $this->assertFalse(file_exists($nonExistentPath));

        $symlink = new Symlink($symlinkPath);
        $symlink->delete();

        // Verify broken symlink is deleted
        $this->assertFalse(file_exists($symlinkPath));
        $this->assertFalse(is_link($symlinkPath));
    }

    public function test_delete_relative_path_symlink(): void
    {
        $targetFileName = 'relative_target_for_deletion.txt';
        $targetFilePath = $this->testDir . '/' . $targetFileName;
        $symlinkPath = $this->testDir . '/relative_symlink_for_deletion.txt';
        $content = 'Relative target content';

        // Create target file
        file_put_contents($targetFilePath, $content);

        // Create symlink with relative path
        symlink($targetFileName, $symlinkPath);

        // Verify both exist
        $this->assertTrue(file_exists($targetFilePath));
        $this->assertTrue(is_link($symlinkPath));

        $symlink = new Symlink($symlinkPath);
        $symlink->delete();

        // Verify symlink is deleted but target remains
        $this->assertFalse(file_exists($symlinkPath));
        $this->assertFalse(is_link($symlinkPath));
        $this->assertTrue(file_exists($targetFilePath));
        $this->assertSame($content, file_get_contents($targetFilePath));
    }

    public function test_delete_throws_exception_for_nonexistent_symlink(): void
    {
        $nonExistentSymlinkPath = $this->testDir . '/nonexistent_symlink.txt';

        $symlink = new Symlink($nonExistentSymlinkPath);

        $this->expectErrorMessage("unlink({$nonExistentSymlinkPath}): No such file or directory");

        $symlink->delete();
    }

    public function test_delete_throws_exception_for_regular_file(): void
    {
        $regularFilePath = $this->testDir . '/regular_file_not_symlink.txt';

        // Create a regular file (not a symlink)
        touch($regularFilePath);

        // Try to delete it as if it were a symlink
        $symlink = new Symlink($regularFilePath);
        $symlink->delete();

        // Should succeed in deleting the file even though it's not actually a symlink
        // because unlink() works on regular files too
        $this->assertFalse(file_exists($regularFilePath));
    }

    public function test_getTarget_and_delete_integration(): void
    {
        $targetFilePath = $this->testDir . '/integration_target.txt';
        $symlinkPath = $this->testDir . '/integration_symlink.txt';
        $content = 'Integration test content';

        // Create target file
        file_put_contents($targetFilePath, $content);

        // Create symlink
        symlink($targetFilePath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);

        // Test getTarget
        $target = $symlink->getTarget();
        $this->assertInstanceOf(File::class, $target);
        $this->assertSame($targetFilePath, $target->pathname);
        $this->assertSame($content, $target->read());

        // Test delete
        $symlink->delete();

        // Verify symlink is gone but target remains accessible
        $this->assertFalse(file_exists($symlinkPath));
        $this->assertTrue($target->exists());
        $this->assertSame($content, $target->read());
    }
}
