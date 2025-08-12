<?php declare(strict_types=1);

namespace Tests\Kirameki\Storage;

use Kirameki\Storage\Directory;
use Kirameki\Storage\File;
use Kirameki\Storage\Symlink;
use Kirameki\Storage\FileType;
use Kirameki\Time\Instant;
use function dirname;
use function fileatime;
use function filectime;
use function filegroup;
use function filemtime;
use function fileowner;
use function filesize;
use function mkdir;
use function strlen;
use function symlink;
use function touch;

final class StorableTest extends TestCase
{
    public function test_directory_property_for_file_in_root(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $directory = $file->directory;

        $this->assertInstanceOf(Directory::class, $directory);
        $this->assertSame($this->testDir, $directory->pathname);
        $this->assertTrue($directory->exists());
    }

    public function test_directory_property_for_file_in_nested_path(): void
    {
        $nestedDir = $this->testDir . '/level1/level2/level3';
        mkdir($nestedDir, 0755, true);

        $filePath = $nestedDir . '/nested_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $directory = $file->directory;

        $this->assertInstanceOf(Directory::class, $directory);
        $this->assertSame($nestedDir, $directory->pathname);
        $this->assertTrue($directory->exists());
    }

    public function test_directory_property_for_directory_itself(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath);

        $directory = new Directory($dirPath);
        $parentDirectory = $directory->directory;

        $this->assertInstanceOf(Directory::class, $parentDirectory);
        $this->assertSame($this->testDir, $parentDirectory->pathname);
        $this->assertTrue($parentDirectory->exists());
        $this->assertNotSame($directory, $parentDirectory);
    }

    public function test_directory_property_for_nested_directory(): void
    {
        $parentPath = $this->testDir . '/parent';
        $childPath = $parentPath . '/child';
        mkdir($childPath, 0755, true);

        $childDirectory = new Directory($childPath);
        $parentDirectory = $childDirectory->directory;

        $this->assertInstanceOf(Directory::class, $parentDirectory);
        $this->assertSame($parentPath, $parentDirectory->pathname);
        $this->assertTrue($parentDirectory->exists());
    }

    public function test_directory_property_cached(): void
    {
        $filePath = $this->testDir . '/cache_test.txt';
        touch($filePath);

        $file = new File($filePath);
        $directory1 = $file->directory;
        $directory2 = $file->directory;

        $this->assertSame($directory1, $directory2);
    }

    public function test_directory_property_for_nonexistent_file(): void
    {
        $nonExistentPath = $this->testDir . '/nonexistent/file.txt';

        $file = new File($nonExistentPath);
        $directory = $file->directory;

        $this->assertInstanceOf(Directory::class, $directory);
        $this->assertSame(dirname($nonExistentPath), $directory->pathname);
        $this->assertFalse($directory->exists());
    }

    public function test_type_property_for_file(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        touch($filePath);

        $type = new File($filePath)->type;

        $this->assertSame(FileType::File, $type);
    }

    public function test_type_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath);

        $type = new Directory($dirPath)->type;

        $this->assertSame(FileType::Directory, $type);
    }

    public function test_type_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target.txt';
        $symlinkPath = $this->testDir . '/symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $type = new Symlink($symlinkPath)->type;

        $this->assertSame(FileType::Link, $type);
    }

    public function test_type_property_cached(): void
    {
        $filePath = $this->testDir . '/type_cache_test.txt';
        touch($filePath);

        $file = new File($filePath);

        $this->assertSame($file->type, $file->type);
    }

    public function test_filename_property_for_simple_file(): void
    {
        $filePath = $this->testDir . '/simple_file.txt';
        touch($filePath);

        $this->assertSame('simple_file.txt', new File($filePath)->filename);
    }

    public function test_filename_property_for_file_without_extension(): void
    {
        $filePath = $this->testDir . '/noextension';
        touch($filePath);

        $this->assertSame('noextension', new File($filePath)->filename);
    }

    public function test_filename_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath);

        $this->assertSame('test_directory', new Directory($dirPath)->filename);
    }

    public function test_filename_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/my_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $this->assertSame('my_symlink.txt', new Symlink($symlinkPath)->filename);
    }

    public function test_filename_property_with_dots(): void
    {
        $filePath = $this->testDir . '/file.with.multiple.dots.txt';
        touch($filePath);

        $file = new File($filePath);
        $filename = $file->filename;

        $this->assertSame('file.with.multiple.dots.txt', $filename);
    }

    public function test_filename_property_hidden_file(): void
    {
        $filePath = $this->testDir . '/.hidden_file';
        touch($filePath);

        $file = new File($filePath);
        $filename = $file->filename;

        $this->assertSame('.hidden_file', $filename);
    }

    public function test_filename_property_nested_path(): void
    {
        $nestedDir = $this->testDir . '/level1/level2';
        mkdir($nestedDir, 0755, true);

        $filePath = $nestedDir . '/nested_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $filename = $file->filename;

        // Should only return the filename, not the full path
        $this->assertSame('nested_file.txt', $filename);
    }

    public function test_filename_property_for_current_directory(): void
    {
        $dirPath = $this->testDir . '/.';

        $directory = new Directory($dirPath);
        $filename = $directory->filename;

        $this->assertSame('.', $filename);
    }

    public function test_filename_property_for_parent_directory(): void
    {
        $dirPath = $this->testDir . '/..';

        $directory = new Directory($dirPath);
        $filename = $directory->filename;

        $this->assertSame('..', $filename);
    }

    public function test_type_and_filename_integration(): void
    {
        $filePath = $this->testDir . '/integration_test.php';
        $dirPath = $this->testDir . '/integration_dir';
        $symlinkPath = $this->testDir . '/integration_symlink.txt';

        touch($filePath);
        mkdir($dirPath);
        symlink($filePath, $symlinkPath);

        $file = new File($filePath);
        $directory = new Directory($dirPath);
        $symlink = new Symlink($symlinkPath);

        // Test type consistency
        $this->assertSame(FileType::File, $file->type);
        $this->assertSame(FileType::Directory, $directory->type);
        $this->assertSame(FileType::Link, $symlink->type);

        // Test filename consistency
        $this->assertSame('integration_test.php', $file->filename);
        $this->assertSame('integration_dir', $directory->filename);
        $this->assertSame('integration_symlink.txt', $symlink->filename);
    }

    public function test_type_property_persistence(): void
    {
        $filePath = $this->testDir . '/persistence_test.txt';
        touch($filePath);

        $file = new File($filePath);

        // Get type multiple times and ensure it's consistent
        $type1 = $file->type;
        $type2 = $file->type;

        $this->assertSame(FileType::File, $type1);
        $this->assertSame(FileType::File, $type2);
        $this->assertSame($type1, $type2);
    }

    public function test_filename_property_consistency(): void
    {
        $filePath = $this->testDir . '/consistency_test.txt';
        touch($filePath);

        $file = new File($filePath);

        // Get filename multiple times and ensure it's consistent
        $filename1 = $file->filename;
        $filename2 = $file->filename;
        $filename3 = $file->filename;

        $this->assertSame('consistency_test.txt', $filename1);
        $this->assertSame('consistency_test.txt', $filename2);
        $this->assertSame('consistency_test.txt', $filename3);
        $this->assertSame($filename1, $filename2);
        $this->assertSame($filename2, $filename3);
    }

    public function test_name_property_for_simple_file(): void
    {
        $filePath = $this->testDir . '/simple_file.txt';
        touch($filePath);

        $this->assertSame('simple_file', new File($filePath)->name);
    }

    public function test_name_property_for_file_without_extension(): void
    {
        $filePath = $this->testDir . '/noextension';
        touch($filePath);

        $this->assertSame('noextension', new File($filePath)->name);
    }

    public function test_name_property_with_multiple_dots(): void
    {
        $filePath = $this->testDir . '/file.with.multiple.dots.txt';
        touch($filePath);

        $this->assertSame('file.with.multiple.dots', new File($filePath)->name);
    }

    public function test_name_property_for_hidden_file_with_extension(): void
    {
        $filePath = $this->testDir . '/.hidden_file.txt';
        touch($filePath);

        $this->assertSame('.hidden_file', new File($filePath)->name);
    }

    public function test_name_property_for_hidden_file_without_extension(): void
    {
        $filePath = $this->testDir . '/.hidden';
        touch($filePath);

        $this->assertSame('.hidden', new File($filePath)->name);
    }

    public function test_name_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath);

        $this->assertSame('test_directory', new Directory($dirPath)->name);
    }

    public function test_name_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/my_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $this->assertSame('my_symlink', new Symlink($symlinkPath)->name);
    }

    public function test_name_vs_filename_difference(): void
    {
        $filePath = $this->testDir . '/comparison_test.php';
        touch($filePath);

        $file = new File($filePath);

        // filename includes extension, name excludes it
        $this->assertSame('comparison_test.php', $file->filename);
        $this->assertSame('comparison_test', $file->name);
        $this->assertNotSame($file->filename, $file->name);
    }

    public function test_name_vs_filename_for_no_extension(): void
    {
        $filePath = $this->testDir . '/no_extension_file';
        touch($filePath);

        $file = new File($filePath);

        $this->assertSame('no_extension_file', $file->filename);
        $this->assertSame('no_extension_file', $file->name);
        $this->assertSame($file->filename, $file->name);
    }

    public function test_name_property_with_compound_extensions(): void
    {
        $filePath = $this->testDir . '/archive.tar.gz';
        touch($filePath);

        $this->assertSame('archive.tar', new File($filePath)->name);
    }

    public function test_filename_property_consistency_across_storable_types(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        $dirPath = $this->testDir . '/test_dir';
        $symlinkPath = $this->testDir . '/test_symlink.txt';

        touch($filePath);
        mkdir($dirPath);
        symlink($filePath, $symlinkPath);

        $this->assertSame('test_file.txt', new File($filePath)->filename);
        $this->assertSame('test_dir', new Directory($dirPath)->filename);
        $this->assertSame('test_symlink.txt', new Symlink($symlinkPath)->filename);
    }

    public function test_filename_property_from_nested_paths(): void
    {
        $nestedPath = $this->testDir . '/deep/nested/path';
        mkdir($nestedPath, 0755, true);

        $filePath = $nestedPath . '/deeply_nested_file.txt';
        touch($filePath);

        $this->assertSame('deeply_nested_file.txt', new File($filePath)->filename);
    }

    public function test_filename_properties_cached(): void
    {
        $filePath = $this->testDir . '/cache_test.txt';
        touch($filePath);

        $file = new File($filePath);

        $filename1 = $file->filename;

        $this->assertSame($filename1, $file->filename);
        $this->assertSame('cache_test.txt', $filename1);
    }

    public function test_name_property_ending_with_multiple_dots(): void
    {
        $dotEndingPath = $this->testDir . '/filename..txt';
        touch($dotEndingPath);
        $dotEndingFile = new File($dotEndingPath);
        $this->assertSame('filename.', $dotEndingFile->name);
    }

    public function test_extension_property_for_simple_file(): void
    {
        $filePath = $this->testDir . '/simple_file.txt';
        touch($filePath);

        $this->assertSame('txt', new File($filePath)->extension);
    }

    public function test_extension_property_for_file_without_extension(): void
    {
        $filePath = $this->testDir . '/noextension';
        touch($filePath);

        $this->assertSame('', new File($filePath)->extension);
    }

    public function test_extension_property_for_compound_extensions(): void
    {
        $filePath = $this->testDir . '/archive.tar.gz';
        touch($filePath);

        $this->assertSame('gz', new File($filePath)->extension);
    }

    public function test_extension_property_for_hidden_file_without_extension(): void
    {
        $filePath = $this->testDir . '/.hidden';
        touch($filePath);

        $this->assertSame('hidden', new File($filePath)->extension);
    }

    public function test_extension_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath);

        $this->assertSame('', new Directory($dirPath)->extension);
    }

    public function test_extension_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/my_symlink.php';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $this->assertSame('php', new Symlink($symlinkPath)->extension);
    }

    public function test_extension_property_for_file_ending_with_dot(): void
    {
        $filePath = $this->testDir . '/filename.';
        touch($filePath);

        $this->assertSame('', new File($filePath)->extension);
    }

    public function test_extension_property_consistency(): void
    {
        $filePath = $this->testDir . '/consistency_test.txt';
        touch($filePath);
        $file = new File($filePath);

        $this->assertSame($file->extension, $file->extension);
    }

    public function test_extension_property_for_multiple_consecutive_dots(): void
    {
        $filePath = $this->testDir . '/file...txt';
        touch($filePath);

        $this->assertSame('txt', new File($filePath)->extension);
    }

    public function test_permissions_property_for_file(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        touch($filePath);
        chmod($filePath, 0611);

        $file = new File($filePath);
        $permissions = $file->permissions;

        $this->assertSame(0611, $permissions);
    }

    public function test_permissions_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath, 0744);

        $directory = new Directory($dirPath);
        $permissions = $directory->permissions;

        $this->assertSame(0744, $permissions);
    }

    public function test_permissions_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/test_symlink.txt';

        touch($targetPath);
        chmod($targetPath, 0600);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $permissions = $symlink->permissions;

        $this->assertGreaterThanOrEqual(0, $permissions);
        $this->assertLessThanOrEqual(0777, $permissions);
    }

    public function test_permissions_property_different_values(): void
    {
        $readOnlyPath = $this->testDir . '/readonly.txt';
        $executablePath = $this->testDir . '/executable.sh';
        $restrictedPath = $this->testDir . '/restricted.txt';

        touch($readOnlyPath);
        touch($executablePath);
        touch($restrictedPath);
        chmod($readOnlyPath, 0444);   // Read-only
        chmod($executablePath, 0755); // Executable
        chmod($restrictedPath, 0600); // Owner read/write only

        $this->assertSame(0444, new File($readOnlyPath)->permissions);
        $this->assertSame(0755, new File($executablePath)->permissions);
        $this->assertSame(0600, new File($restrictedPath)->permissions);
    }

    public function test_uid_property_for_file(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        touch($filePath);

        $uid = new File($filePath)->uid;

        $this->assertGreaterThanOrEqual(0, $uid);
        $this->assertSame(fileowner($filePath), $uid);
    }

    public function test_uid_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath);

        $uid = new Directory($dirPath)->uid;

        $this->assertGreaterThanOrEqual(0, $uid);
        $this->assertSame(fileowner($dirPath), $uid);
    }

    public function test_uid_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/test_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $uid = new Symlink($symlinkPath)->uid;

        $this->assertGreaterThanOrEqual(0, $uid);
        $this->assertSame(fileowner($symlinkPath), $uid);
    }

    public function test_gid_property_for_file(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        touch($filePath);

        $gid = new File($filePath)->gid;

        $this->assertGreaterThanOrEqual(0, $gid);
        $this->assertSame(filegroup($filePath), $gid);
    }

    public function test_gid_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath);

        $gid = new Directory($dirPath)->gid;

        $this->assertGreaterThanOrEqual(0, $gid);
        $this->assertSame(filegroup($dirPath), $gid);
    }

    public function test_gid_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/test_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $gid = new Symlink($symlinkPath)->gid;

        $this->assertGreaterThanOrEqual(0, $gid);
        $this->assertSame(filegroup($symlinkPath), $gid);
    }

    public function test_bytes_property_for_empty_file(): void
    {
        $filePath = $this->testDir . '/empty_file.txt';
        touch($filePath);

        $bytes = new File($filePath)->bytes;

        $this->assertSame(0, $bytes);
    }

    public function test_bytes_property_for_file_with_content(): void
    {
        $filePath = $this->testDir . '/content_file.txt';
        $content = 'Hello, World!';
        file_put_contents($filePath, $content);

        $bytes = new File($filePath)->bytes;

        $expectedSize = strlen($content);
        $this->assertSame($expectedSize, $bytes);
        $this->assertSame(13, $bytes);
    }

    public function test_bytes_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath);

        $bytes = new Directory($dirPath)->bytes;

        $this->assertGreaterThanOrEqual(0, $bytes);
        $this->assertSame(filesize($dirPath), $bytes);
    }

    public function test_bytes_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $content = 'Target file content';
        file_put_contents($targetPath, $content);

        $symlinkPath = $this->testDir . '/test_symlink.txt';
        symlink($targetPath, $symlinkPath);

        $bytes = new Symlink($symlinkPath)->bytes;

        $this->assertGreaterThan(0, $bytes);
        $this->assertSame(filesize($symlinkPath), $bytes);
    }

    public function test_bytes_property_updates_after_content_change(): void
    {
        $filePath = $this->testDir . '/dynamic_size_test.txt';

        // Create file with initial content
        $initialContent = 'Initial';
        file_put_contents($filePath, $initialContent);

        $initialBytes = new File($filePath)->bytes;
        $this->assertSame(strlen($initialContent), $initialBytes);

        // Update file content
        $newContent = 'New content that is much longer';
        file_put_contents($filePath, $newContent);

        // Create new File instance to get updated size
        $newBytes = new File($filePath)->bytes;
        $this->assertSame(strlen($newContent), $newBytes);
        $this->assertNotSame($initialBytes, $newBytes);
    }

    public function test_atime_property_for_file(): void
    {
        $filePath = $this->testDir . '/atime_test.txt';
        $content = 'Test content for access time';
        file_put_contents($filePath, $content);

        $atime = new File($filePath)->atime;

        $this->assertInstanceOf(Instant::class, $atime);
        $this->assertSame(fileatime($filePath), $atime->toInt());
    }

    public function test_mtime_property_for_file(): void
    {
        $filePath = $this->testDir . '/mtime_test.txt';
        $content = 'Test content for modification time';
        file_put_contents($filePath, $content);

        $mtime = new File($filePath)->mtime;

        $this->assertInstanceOf(Instant::class, $mtime);
        $this->assertSame(filemtime($filePath), $mtime->toInt());
    }

    public function test_ctime_property_for_file(): void
    {
        $filePath = $this->testDir . '/ctime_test.txt';
        $content = 'Test content for change time';
        file_put_contents($filePath, $content);

        $file = new File($filePath);
        $ctime = $file->ctime;

        $this->assertInstanceOf(Instant::class, $ctime);

        // Should match filesystem change time
        $this->assertSame(filectime($filePath), $ctime->toInt());
    }

    public function test_atime_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/atime_test_dir';
        mkdir($dirPath);

        $directory = new Directory($dirPath);
        $atime = $directory->atime;

        $this->assertInstanceOf(Instant::class, $atime);

        // Should match filesystem access time
        $expectedAtime = fileatime($dirPath);
        $this->assertSame($expectedAtime, $atime->toInt());
    }

    public function test_mtime_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/mtime_test_dir';
        mkdir($dirPath);

        $mtime = new Directory($dirPath)->mtime;

        $this->assertInstanceOf(Instant::class, $mtime);
        $this->assertSame(filemtime($dirPath), $mtime->toInt());
    }

    public function test_ctime_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/ctime_test_dir';
        mkdir($dirPath);

        $ctime = new Directory($dirPath)->ctime;

        $this->assertInstanceOf(Instant::class, $ctime);
        $this->assertSame(filectime($dirPath), $ctime->toInt());
    }

    public function test_atime_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/atime_target.txt';
        $symlinkPath = $this->testDir . '/atime_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $atime = new Symlink($symlinkPath)->atime;

        $this->assertInstanceOf(Instant::class, $atime);
        $this->assertSame(fileatime($symlinkPath), $atime->toInt());
    }

    public function test_mtime_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/mtime_target.txt';
        $symlinkPath = $this->testDir . '/mtime_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $mtime = new Symlink($symlinkPath)->mtime;

        $this->assertInstanceOf(Instant::class, $mtime);
        $this->assertSame(filemtime($symlinkPath), $mtime->toInt());
    }

    public function test_ctime_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/ctime_target.txt';
        $symlinkPath = $this->testDir . '/ctime_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $ctime = new Symlink($symlinkPath)->ctime;

        $this->assertInstanceOf(Instant::class, $ctime);
        $this->assertSame(filectime($symlinkPath), $ctime->toInt());
    }

    public function test_time_properties_not_cached(): void
    {
        $filePath = $this->testDir . '/time_cache_test.txt';
        file_put_contents($filePath, 'Cache test content');

        $file = new File($filePath);

        $this->assertNotSame($file->atime, $file->atime);
        $this->assertNotSame($file->mtime, $file->mtime);
        $this->assertNotSame($file->ctime, $file->ctime);
    }

    public function test_pathname_property_for_file(): void
    {
        $filePath = $this->testDir . '/pathname_test.txt';
        touch($filePath);

        $file = new File($filePath);
        $pathname = $file->pathname;

        $this->assertSame($filePath, $pathname);
    }

    public function test_pathname_property_for_directory(): void
    {
        $dirPath = $this->testDir . '/pathname_test_dir';
        mkdir($dirPath);

        $directory = new Directory($dirPath);
        $pathname = $directory->pathname;

        $this->assertSame($dirPath, $pathname);
    }

    public function test_pathname_property_for_symlink(): void
    {
        $targetPath = $this->testDir . '/pathname_target.txt';
        $symlinkPath = $this->testDir . '/pathname_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $pathname = new Symlink($symlinkPath)->pathname;

        $this->assertSame($symlinkPath, $pathname);
        $this->assertNotSame($targetPath, $pathname);
    }

    public function test_pathname_property_absolute_path(): void
    {
        $absolutePath = $this->testDir . '/absolute_path_test.txt';
        touch($absolutePath);

        $pathname = new File($absolutePath)->pathname;

        $this->assertSame($absolutePath, $pathname);
        $this->assertTrue(str_starts_with($pathname, '/'));
    }

    public function test_pathname_property_relative_path(): void
    {
        $relativePath = 'relative_test.txt';

        // Create file in current working directory
        $originalCwd = getcwd();
        chdir($this->testDir);
        touch($relativePath);

        try {
            $pathname = new File($relativePath)->pathname;

            $this->assertSame($relativePath, $pathname);
            $this->assertFalse(str_starts_with($pathname, '/'));
        } finally {
            unlink($relativePath);
        }
    }
}
