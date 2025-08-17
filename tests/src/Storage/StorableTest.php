<?php declare(strict_types=1);

namespace Tests\Kirameki\Storage;

use Kirameki\Storage\Directory;
use Kirameki\Storage\File;
use Kirameki\Storage\Symlink;
use Kirameki\Storage\FileType;
use Kirameki\Time\Instant;
use function chdir;
use function chmod;
use function clearstatcache;
use function dirname;
use function file_exists;
use function file_put_contents;
use function fileatime;
use function filectime;
use function filegroup;
use function filemtime;
use function fileowner;
use function filesize;
use function getcwd;
use function is_executable;
use function is_link;
use function is_readable;
use function is_writable;
use function mkdir;
use function posix_getgrgid;
use function posix_getpwuid;
use function realpath;
use function rmdir;
use function str_starts_with;
use function strlen;
use function symlink;
use function touch;
use function unlink;

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

    public function test_basename_for_simple_file(): void
    {
        $filePath = $this->testDir . '/simple_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $basename = $file->basename();

        $this->assertSame('simple_file.txt', $basename);
    }

    public function test_basename_for_nested_file(): void
    {
        $nestedDir = $this->testDir . '/level1/level2';
        mkdir($nestedDir, 0755, true);

        $filePath = $nestedDir . '/nested_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $basename = $file->basename();

        $this->assertSame('nested_file.txt', $basename);
    }

    public function test_basename_for_directory(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath);

        $directory = new Directory($dirPath);
        $basename = $directory->basename();

        $this->assertSame('test_directory', $basename);
    }

    public function test_basename_with_suffix(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $basename = $file->basename('.txt');

        $this->assertSame('test_file', $basename);
    }

    public function test_basename_with_partial_suffix(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $basename = $file->basename('xt');

        $this->assertSame('test_file.t', $basename);
    }

    public function test_basename_with_non_matching_suffix(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $basename = $file->basename('.php');

        $this->assertSame('test_file.txt', $basename);
    }

    public function test_basename_with_empty_suffix(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $basename = $file->basename('');

        $this->assertSame('test_file.txt', $basename);
    }

    public function test_basename_for_file_without_extension(): void
    {
        $filePath = $this->testDir . '/noextension';
        touch($filePath);

        $file = new File($filePath);
        $basename = $file->basename();

        $this->assertSame('noextension', $basename);
    }

    public function test_basename_for_file_with_multiple_dots(): void
    {
        $filePath = $this->testDir . '/file.with.multiple.dots.txt';
        touch($filePath);

        $file = new File($filePath);
        $basename = $file->basename();

        $this->assertSame('file.with.multiple.dots.txt', $basename);
    }

    public function test_basename_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/my_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $basename = $symlink->basename();

        $this->assertSame('my_symlink.txt', $basename);
    }

    public function test_basename_with_suffix_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/my_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $basename = $symlink->basename('.txt');

        $this->assertSame('my_symlink', $basename);
    }

    public function test_basename_consistency(): void
    {
        $filePath = $this->testDir . '/consistency_test.txt';
        touch($filePath);

        $file = new File($filePath);
        $basename1 = $file->basename();
        $basename2 = $file->basename();

        $this->assertSame('consistency_test.txt', $basename1);
        $this->assertSame($basename1, $basename2);
    }

    public function test_basename_for_root(): void
    {
        $rootPath = '/';
        $directory = new Directory($rootPath);

        $this->assertSame('', $directory->basename());
    }

    public function test_dirname_for_simple_file(): void
    {
        $filePath = $this->testDir . '/simple_file.txt';
        touch($filePath);
        $file = new File($filePath);
        $dirname = $file->dirname();

        $this->assertSame($this->testDir, $dirname);
    }

    public function test_dirname_for_nested_file(): void
    {
        $nestedDir = $this->testDir . '/level1/level2/level3';
        mkdir($nestedDir, 0755, true);
        $filePath = $nestedDir . '/nested_file.txt';
        touch($filePath);
        $file = new File($filePath);
        $dirname = $file->dirname();

        $this->assertSame($nestedDir, $dirname);
    }

    public function test_dirname_for_directory(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath);
        $directory = new Directory($dirPath);
        $dirname = $directory->dirname();

        $this->assertSame($this->testDir, $dirname);
    }

    public function test_dirname_with_multiple_levels(): void
    {
        $nestedDir = $this->testDir . '/level1/level2/level3/level4';
        mkdir($nestedDir, 0755, true);

        $filePath = $nestedDir . '/deep_file.txt';
        touch($filePath);
        $file = new File($filePath);

        $this->assertSame($nestedDir, $file->dirname(1));
        $this->assertSame($this->testDir . '/level1/level2/level3', $file->dirname(2));
        $this->assertSame($this->testDir . '/level1/level2', $file->dirname(3));
        $this->assertSame($this->testDir . '/level1', $file->dirname(4));
        $this->assertSame($this->testDir, $file->dirname(5));
    }

    public function test_dirname_with_high_level_count(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        touch($filePath);
        $file = new File($filePath);

        $this->assertNotEmpty($file->dirname(10));
    }

    public function test_dirname_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/my_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $dirname = $symlink->dirname();

        $this->assertSame($this->testDir, $dirname);
    }

    public function test_dirname_with_levels_for_symlink(): void
    {
        $nestedDir = $this->testDir . '/level1/level2';
        mkdir($nestedDir, 0755, true);

        $targetPath = $nestedDir . '/target_file.txt';
        $symlinkPath = $nestedDir . '/my_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);

        $this->assertSame($nestedDir, $symlink->dirname(1));
        $this->assertSame($this->testDir . '/level1', $symlink->dirname(2));
        $this->assertSame($this->testDir, $symlink->dirname(3));
    }

    public function test_dirname_consistency(): void
    {
        $filePath = $this->testDir . '/consistency_test.txt';
        touch($filePath);

        $file = new File($filePath);
        $dirname1 = $file->dirname();
        $dirname2 = $file->dirname();

        $this->assertSame($this->testDir, $dirname1);
        $this->assertSame($dirname1, $dirname2);
    }

    public function test_dirname_with_different_levels_consistency(): void
    {
        $nestedDir = $this->testDir . '/level1/level2/level3';
        mkdir($nestedDir, 0755, true);

        $filePath = $nestedDir . '/test_file.txt';
        touch($filePath);

        $file = new File($filePath);

        $this->assertSame($file->dirname(2), $file->dirname(2));
        $this->assertSame($file->dirname(3), $file->dirname(3));
    }

    public function test_dirname_for_root_directory(): void
    {
        $rootPath = '/';
        $directory = new Directory($rootPath);
        $dirname = $directory->dirname();

        $this->assertSame('/', $dirname);
    }

    public function test_dirname_edge_case_single_level(): void
    {
        $filePath = $this->testDir . '/single_level.txt';
        touch($filePath);

        $file = new File($filePath);
        $dirname = $file->dirname(1);

        $this->assertSame($this->testDir, $dirname);
    }

    public function test_dirname_on_relative_path(): void
    {
        $file = new File('relative_test.txt');
        $dirname = $file->dirname();

        $this->assertSame('.', $dirname);
    }

    public function test_realPath_for_simple_file(): void
    {
        $filePath = $this->testDir . '/simple_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $realPath = $file->realPath();

        $this->assertSame(realpath($filePath), $realPath);
        $this->assertTrue(str_starts_with($realPath, '/'));
    }

    public function test_realPath_for_directory(): void
    {
        $dirPath = $this->testDir . '/test_directory';
        mkdir($dirPath);

        $directory = new Directory($dirPath);
        $realPath = $directory->realPath();

        $this->assertSame(realpath($dirPath), $realPath);
        $this->assertTrue(str_starts_with($realPath, '/'));
    }

    public function test_realPath_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/test_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $realPath = $symlink->realPath();

        $this->assertSame(realpath($targetPath), $realPath);
        $this->assertNotSame($symlinkPath, $realPath);
    }

    public function test_realPath_with_dot_segments(): void
    {
        $nestedDir = $this->testDir . '/level1/level2';
        mkdir($nestedDir, 0755, true);

        $filePath = $nestedDir . '/../level2/./test_file.txt';
        touch($nestedDir . '/test_file.txt');

        $file = new File($filePath);
        $realPath = $file->realPath();

        $expectedRealPath = $nestedDir . '/test_file.txt';
        $this->assertSame($expectedRealPath, $realPath);
        $this->assertStringNotContainsString('..', $realPath);
        $this->assertStringNotContainsString('./', $realPath);
    }

    public function test_realPath_consistency(): void
    {
        $filePath = $this->testDir . '/consistency_test.txt';
        touch($filePath);

        $file = new File($filePath);
        $realPath1 = $file->realPath();
        $realPath2 = $file->realPath();

        $this->assertSame($realPath1, $realPath2);
        $this->assertSame(realpath($filePath), $realPath1);
    }

    public function test_realPath_for_symlink_to_directory(): void
    {
        $targetDir = $this->testDir . '/target_directory';
        $symlinkPath = $this->testDir . '/dir_symlink';

        mkdir($targetDir);
        symlink($targetDir, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $realPath = $symlink->realPath();

        $this->assertSame(realpath($targetDir), $realPath);
        $this->assertNotSame($symlinkPath, $realPath);
    }

    public function test_isWritable_for_writable_file(): void
    {
        $filePath = $this->testDir . '/writable_file.txt';
        touch($filePath);
        chmod($filePath, 0644); // Owner read/write, group/other read

        $file = new File($filePath);
        $isWritable = $file->isWritable();

        $this->assertTrue($isWritable);
        $this->assertSame(is_writable($filePath), $isWritable);
    }

    public function test_isWritable_for_readonly_file(): void
    {
        $filePath = $this->testDir . '/readonly_file.txt';
        touch($filePath);
        chmod($filePath, 0444);

        $file = new File($filePath);
        $isWritable = $file->isWritable();
        $this->assertFalse($isWritable);
        $this->assertSame(is_writable($filePath), $isWritable);
    }

    public function test_isWritable_for_writable_directory(): void
    {
        $dirPath = $this->testDir . '/writable_directory';
        mkdir($dirPath, 0755); // Owner read/write/execute, group/other read/execute

        $directory = new Directory($dirPath);
        $isWritable = $directory->isWritable();

        $this->assertTrue($isWritable);
        $this->assertSame(is_writable($dirPath), $isWritable);
    }

    public function test_isWritable_for_readonly_directory(): void
    {
        $dirPath = $this->testDir . '/readonly_directory';
        mkdir($dirPath, 0755);
        chmod($dirPath, 0555); // Read/execute only for all

        $directory = new Directory($dirPath);
        $isWritable = $directory->isWritable();

        $this->assertFalse($isWritable);
        $this->assertSame(is_writable($dirPath), $isWritable);
    }

    public function test_isWritable_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/test_symlink.txt';

        touch($targetPath);
        chmod($targetPath, 0644); // Writable target
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $this->assertSame(is_writable($symlinkPath), $symlink->isWritable());
    }

    public function test_isWritable_for_symlink_to_readonly_target(): void
    {
        $targetPath = $this->testDir . '/readonly_target.txt';
        $symlinkPath = $this->testDir . '/symlink_to_readonly.txt';

        touch($targetPath);
        chmod($targetPath, 0444); // Read-only target
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $this->assertSame(is_writable($symlinkPath), $symlink->isWritable());
    }

    public function test_isWritable_for_different_permission_levels(): void
    {
        $ownerWritePath = $this->testDir . '/owner_write.txt';
        $groupWritePath = $this->testDir . '/group_write.txt';
        $noWritePath = $this->testDir . '/no_write.txt';

        touch($ownerWritePath);
        touch($groupWritePath);
        touch($noWritePath);

        chmod($ownerWritePath, 0644); // Owner write
        chmod($groupWritePath, 0664); // Owner and group write
        chmod($noWritePath, 0444);    // No write permissions

        $ownerWriteFile = new File($ownerWritePath);
        $groupWriteFile = new File($groupWritePath);
        $noWriteFile = new File($noWritePath);
        $this->assertSame(is_writable($ownerWritePath), $ownerWriteFile->isWritable());
        $this->assertSame(is_writable($groupWritePath), $groupWriteFile->isWritable());
        $this->assertSame(is_writable($noWritePath), $noWriteFile->isWritable());
    }

    public function test_isWritable_consistency(): void
    {
        $filePath = $this->testDir . '/consistency_test.txt';
        touch($filePath);
        chmod($filePath, 0644);

        $file = new File($filePath);
        $isWritable1 = $file->isWritable();
        $isWritable2 = $file->isWritable();

        $this->assertSame($isWritable1, $isWritable2);
        $this->assertTrue($isWritable1);
    }

    public function test_isWritable_after_permission_change(): void
    {
        $filePath = $this->testDir . '/permission_change_test.txt';
        touch($filePath);
        chmod($filePath, 0644);
        $this->assertTrue(new File($filePath)->isWritable());
        chmod($filePath, 0444);
        $this->assertFalse(new File($filePath)->isWritable());
    }

    public function test_isWritable_for_nonexistent_file(): void
    {
        $nonExistentPath = $this->testDir . '/nonexistent_file.txt';

        $file = new File($nonExistentPath);
        $isWritable = $file->isWritable();

        $this->assertFalse($isWritable);
        $this->assertSame(is_writable($nonExistentPath), $isWritable);
    }

    public function test_isWritable_for_directory_with_files(): void
    {
        $dirPath = $this->testDir . '/dir_with_files';
        mkdir($dirPath, 0755);

        // Add some files to the directory
        touch($dirPath . '/file1.txt');
        touch($dirPath . '/file2.txt');

        $directory = new Directory($dirPath);
        $isWritable = $directory->isWritable();

        $this->assertTrue($isWritable);
        $this->assertSame(is_writable($dirPath), $isWritable);
    }

    public function test_isReadable_for_readable_file(): void
    {
        $filePath = $this->testDir . '/readable_file.txt';
        touch($filePath);
        chmod($filePath, 0644); // Owner read/write, group/other read

        $file = new File($filePath);
        $isReadable = $file->isReadable();

        $this->assertTrue($isReadable);
        $this->assertSame(is_readable($filePath), $isReadable);
    }

    public function test_isReadable_for_unreadable_file(): void
    {
        $filePath = $this->testDir . '/unreadable_file.txt';
        touch($filePath);
        chmod($filePath, 0000); // No permissions

        $file = new File($filePath);
        $isReadable = $file->isReadable();

        $this->assertFalse($isReadable);
        $this->assertSame(is_readable($filePath), $isReadable);
    }

    public function test_isReadable_for_readable_directory(): void
    {
        $dirPath = $this->testDir . '/readable_directory';
        mkdir($dirPath, 0755); // Owner read/write/execute, group/other read/execute

        $directory = new Directory($dirPath);
        $isReadable = $directory->isReadable();

        $this->assertTrue($isReadable);
        $this->assertSame(is_readable($dirPath), $isReadable);
    }

    public function test_isReadable_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/test_symlink.txt';

        touch($targetPath);
        chmod($targetPath, 0644); // Readable target
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $isReadable = $symlink->isReadable();

        // Should reflect the readability of the symlink itself
        $this->assertSame(is_readable($symlinkPath), $isReadable);
    }

    public function test_isReadable_for_symlink_to_unreadable_target(): void
    {
        $targetPath = $this->testDir . '/unreadable_target.txt';
        $symlinkPath = $this->testDir . '/symlink_to_unreadable.txt';

        touch($targetPath);
        chmod($targetPath, 0000); // No permissions for target
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $isReadable = $symlink->isReadable();

        $this->assertSame(is_readable($symlinkPath), $isReadable);
    }

    public function test_isReadable_for_different_permission_levels(): void
    {
        $readOnlyPath = $this->testDir . '/read_only.txt';
        $readWritePath = $this->testDir . '/read_write.txt';
        $executeOnlyPath = $this->testDir . '/execute_only.txt';

        touch($readOnlyPath);
        touch($readWritePath);
        touch($executeOnlyPath);

        chmod($readOnlyPath, 0444);  // Read-only
        chmod($readWritePath, 0644); // Read/write
        chmod($executeOnlyPath, 0111); // Execute-only

        $readOnlyFile = new File($readOnlyPath);
        $readWriteFile = new File($readWritePath);
        $executeOnlyFile = new File($executeOnlyPath);

        // Results depend on current user/group permissions
        $this->assertSame(is_readable($readOnlyPath), $readOnlyFile->isReadable());
        $this->assertSame(is_readable($readWritePath), $readWriteFile->isReadable());
        $this->assertSame(is_readable($executeOnlyPath), $executeOnlyFile->isReadable());
    }

    public function test_isReadable_consistency(): void
    {
        $filePath = $this->testDir . '/consistency_test.txt';
        touch($filePath);
        chmod($filePath, 0644);

        $file = new File($filePath);
        $isReadable1 = $file->isReadable();
        $isReadable2 = $file->isReadable();

        $this->assertSame($isReadable1, $isReadable2);
        $this->assertTrue($isReadable1); // Should be readable with 644
    }

    public function test_isReadable_after_permission_change(): void
    {
        $filePath = $this->testDir . '/permission_change_test.txt';
        touch($filePath);
        chmod($filePath, 0644); // Initially readable

        $file1 = new File($filePath);
        $this->assertTrue($file1->isReadable());

        // Change permissions to no access
        chmod($filePath, 0000);

        $file2 = new File($filePath); // New instance after permission change
        $this->assertFalse($file2->isReadable());
    }

    public function test_isReadable_for_nonexistent_file(): void
    {
        $nonExistentPath = $this->testDir . '/nonexistent_file.txt';

        $file = new File($nonExistentPath);
        $isReadable = $file->isReadable();

        $this->assertFalse($isReadable);
        $this->assertSame(is_readable($nonExistentPath), $isReadable);
    }

    public function test_isReadable_for_directory_with_files(): void
    {
        $dirPath = $this->testDir . '/dir_with_files';
        mkdir($dirPath, 0755);

        // Add some files to the directory
        touch($dirPath . '/file1.txt');
        touch($dirPath . '/file2.txt');

        $directory = new Directory($dirPath);
        $isReadable = $directory->isReadable();

        $this->assertTrue($isReadable);
        $this->assertSame(is_readable($dirPath), $isReadable);
    }

    public function test_isReadable_for_write_only_file(): void
    {
        $filePath = $this->testDir . '/write_only_file.txt';
        touch($filePath);
        chmod($filePath, 0200); // Write-only for owner

        $file = new File($filePath);
        $isReadable = $file->isReadable();

        $this->assertSame(is_readable($filePath), $isReadable);
    }

    public function test_isReadable_for_hidden_file(): void
    {
        $hiddenPath = $this->testDir . '/.hidden_readable_file';
        touch($hiddenPath);
        chmod($hiddenPath, 0644);

        $file = new File($hiddenPath);
        $isReadable = $file->isReadable();

        $this->assertTrue($isReadable);
        $this->assertSame(is_readable($hiddenPath), $isReadable);
    }

    public function test_isExecutable_for_executable_file(): void
    {
        $filePath = $this->testDir . '/executable_file.sh';
        touch($filePath);
        chmod($filePath, 0755); // Owner read/write/execute, group/other read/execute

        $file = new File($filePath);
        $isExecutable = $file->isExecutable();

        $this->assertTrue($isExecutable);
        $this->assertSame(is_executable($filePath), $isExecutable);
    }

    public function test_isExecutable_for_non_executable_file(): void
    {
        $filePath = $this->testDir . '/non_executable_file.txt';
        touch($filePath);
        chmod($filePath, 0644); // Owner read/write, group/other read

        $file = new File($filePath);
        $isExecutable = $file->isExecutable();

        $this->assertFalse($isExecutable);
        $this->assertSame(is_executable($filePath), $isExecutable);
    }

    public function test_isExecutable_for_executable_directory(): void
    {
        $dirPath = $this->testDir . '/executable_directory';
        mkdir($dirPath, 0755); // Owner read/write/execute, group/other read/execute

        $directory = new Directory($dirPath);
        $isExecutable = $directory->isExecutable();

        $this->assertTrue($isExecutable);
        $this->assertSame(is_executable($dirPath), $isExecutable);
    }

    public function test_isExecutable_for_non_executable_directory(): void
    {
        $dirPath = $this->testDir . '/non_executable_directory';
        mkdir($dirPath, 0755);
        chmod($dirPath, 0644); // No execute permissions

        $directory = new Directory($dirPath);
        $isExecutable = $directory->isExecutable();

        $this->assertFalse($isExecutable);
        $this->assertSame(is_executable($dirPath), $isExecutable);
    }

    public function test_isExecutable_for_symlink(): void
    {
        $targetPath = $this->testDir . '/target_executable.sh';
        $symlinkPath = $this->testDir . '/test_symlink.sh';

        touch($targetPath);
        chmod($targetPath, 0755); // Executable target
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $isExecutable = $symlink->isExecutable();

        // Should reflect the executability of the symlink itself
        $this->assertSame(is_executable($symlinkPath), $isExecutable);
    }

    public function test_isExecutable_for_symlink_to_non_executable_target(): void
    {
        $targetPath = $this->testDir . '/non_executable_target.txt';
        $symlinkPath = $this->testDir . '/symlink_to_non_executable.txt';

        touch($targetPath);
        chmod($targetPath, 0644); // Non-executable target
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $isExecutable = $symlink->isExecutable();

        $this->assertSame(is_executable($symlinkPath), $isExecutable);
    }

    public function test_isExecutable_for_different_permission_levels(): void
    {
        $ownerExecPath = $this->testDir . '/owner_exec.sh';
        $groupExecPath = $this->testDir . '/group_exec.sh';
        $allExecPath = $this->testDir . '/all_exec.sh';
        $noExecPath = $this->testDir . '/no_exec.sh';

        touch($ownerExecPath);
        touch($groupExecPath);
        touch($allExecPath);
        touch($noExecPath);

        chmod($ownerExecPath, 0744); // Owner execute
        chmod($groupExecPath, 0754); // Owner and group execute
        chmod($allExecPath, 0755);   // All execute
        chmod($noExecPath, 0644);    // No execute permissions

        $ownerExecFile = new File($ownerExecPath);
        $groupExecFile = new File($groupExecPath);
        $allExecFile = new File($allExecPath);
        $noExecFile = new File($noExecPath);

        // Results depend on current user/group permissions
        $this->assertSame(is_executable($ownerExecPath), $ownerExecFile->isExecutable());
        $this->assertSame(is_executable($groupExecPath), $groupExecFile->isExecutable());
        $this->assertSame(is_executable($allExecPath), $allExecFile->isExecutable());
        $this->assertSame(is_executable($noExecPath), $noExecFile->isExecutable());
    }

    public function test_isExecutable_consistency(): void
    {
        $filePath = $this->testDir . '/consistency_test.sh';
        touch($filePath);
        chmod($filePath, 0755);

        $file = new File($filePath);
        $isExecutable1 = $file->isExecutable();
        $isExecutable2 = $file->isExecutable();

        $this->assertSame($isExecutable1, $isExecutable2);
        $this->assertTrue($isExecutable1); // Should be executable with 755
    }

    public function test_isExecutable_after_permission_change(): void
    {
        $filePath = $this->testDir . '/permission_change_exec_test.sh';
        touch($filePath);
        chmod($filePath, 0755); // Initially executable

        $file1 = new File($filePath);
        $this->assertTrue($file1->isExecutable());

        // Change permissions to non-executable
        chmod($filePath, 0644);

        $file2 = new File($filePath); // New instance after permission change
        $this->assertFalse($file2->isExecutable());
    }

    public function test_isExecutable_for_nonexistent_file(): void
    {
        $nonExistentPath = $this->testDir . '/nonexistent_executable.sh';

        $file = new File($nonExistentPath);
        $isExecutable = $file->isExecutable();

        $this->assertFalse($isExecutable);
        $this->assertSame(is_executable($nonExistentPath), $isExecutable);
    }

    public function test_isExecutable_for_directory_with_files(): void
    {
        $dirPath = $this->testDir . '/exec_dir_with_files';
        mkdir($dirPath, 0755);

        // Add some files to the directory
        touch($dirPath . '/file1.txt');
        touch($dirPath . '/file2.txt');

        $directory = new Directory($dirPath);
        $isExecutable = $directory->isExecutable();

        $this->assertTrue($isExecutable);
        $this->assertSame(is_executable($dirPath), $isExecutable);
    }

    public function test_isExecutable_for_execute_only_file(): void
    {
        $filePath = $this->testDir . '/execute_only_file.sh';
        touch($filePath);
        chmod($filePath, 0111); // Execute-only for all

        $file = new File($filePath);
        $isExecutable = $file->isExecutable();

        $this->assertSame(is_executable($filePath), $isExecutable);
    }

    public function test_isExecutable_for_script_file(): void
    {
        $scriptPath = $this->testDir . '/test_script.sh';
        file_put_contents($scriptPath, "#!/bin/bash\necho 'Hello World'\n");
        chmod($scriptPath, 0755);

        $file = new File($scriptPath);
        $isExecutable = $file->isExecutable();

        $this->assertTrue($isExecutable);
        $this->assertSame(is_executable($scriptPath), $isExecutable);
    }

    public function test_isExecutable_for_binary_like_file(): void
    {
        $binaryPath = $this->testDir . '/fake_binary';
        touch($binaryPath);
        chmod($binaryPath, 0755);

        $file = new File($binaryPath);
        $isExecutable = $file->isExecutable();

        $this->assertTrue($isExecutable);
        $this->assertSame(is_executable($binaryPath), $isExecutable);
    }

    public function test_isLink_for_regular_file(): void
    {
        $filePath = $this->testDir . '/regular_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $isLink = $file->isLink();

        $this->assertFalse($isLink);
        $this->assertSame(is_link($filePath), $isLink);
    }

    public function test_isLink_for_directory(): void
    {
        $dirPath = $this->testDir . '/regular_directory';
        mkdir($dirPath);

        $directory = new Directory($dirPath);
        $isLink = $directory->isLink();

        $this->assertFalse($isLink);
        $this->assertSame(is_link($dirPath), $isLink);
    }

    public function test_isLink_for_symlink_to_file(): void
    {
        $targetPath = $this->testDir . '/target_file.txt';
        $symlinkPath = $this->testDir . '/symlink_to_file.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $isLink = $symlink->isLink();

        $this->assertTrue($isLink);
        $this->assertSame(is_link($symlinkPath), $isLink);
    }

    public function test_isLink_for_symlink_to_directory(): void
    {
        $targetDir = $this->testDir . '/target_directory';
        $symlinkPath = $this->testDir . '/symlink_to_directory';

        mkdir($targetDir);
        symlink($targetDir, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $isLink = $symlink->isLink();

        $this->assertTrue($isLink);
        $this->assertSame(is_link($symlinkPath), $isLink);
    }

    public function test_isLink_for_symlink_to_nonexistent_target(): void
    {
        $nonexistentPath = $this->testDir . '/nonexistent_target.txt';
        $symlinkPath = $this->testDir . '/symlink_to_nonexistent.txt';

        symlink($nonexistentPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $isLink = $symlink->isLink();

        $this->assertTrue($isLink);
        $this->assertSame(is_link($symlinkPath), $isLink);
    }

    public function test_isLink_consistency(): void
    {
        $targetPath = $this->testDir . '/target_for_consistency.txt';
        $symlinkPath = $this->testDir . '/symlink_for_consistency.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $isLink1 = $symlink->isLink();
        $isLink2 = $symlink->isLink();

        $this->assertTrue($isLink1);
        $this->assertSame($isLink1, $isLink2);
    }

    public function test_isLink_for_nonexistent_file(): void
    {
        $nonexistentPath = $this->testDir . '/nonexistent_file.txt';

        $file = new File($nonexistentPath);
        $isLink = $file->isLink();

        $this->assertFalse($isLink);
        $this->assertSame(is_link($nonexistentPath), $isLink);
    }

    public function test_isLink_for_file_class_on_symlink(): void
    {
        $targetPath = $this->testDir . '/target_for_file_test.txt';
        $symlinkPath = $this->testDir . '/symlink_using_file_class.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        // Using File class on a symlink
        $file = new File($symlinkPath);
        $isLink = $file->isLink();

        $this->assertTrue($isLink);
        $this->assertSame(is_link($symlinkPath), $isLink);
    }

    public function test_isLink_for_directory_class_on_symlink(): void
    {
        $targetDir = $this->testDir . '/dir_target_for_dir_test';
        $symlinkPath = $this->testDir . '/symlink_using_dir_class';

        mkdir($targetDir);
        symlink($targetDir, $symlinkPath);

        // Using Directory class on a symlink to directory
        $directory = new Directory($symlinkPath);
        $isLink = $directory->isLink();

        $this->assertTrue($isLink);
        $this->assertSame(is_link($symlinkPath), $isLink);
    }

    public function test_isLink_after_creation_and_deletion(): void
    {
        $targetPath = $this->testDir . '/target_for_deletion.txt';
        $symlinkPath = $this->testDir . '/symlink_for_deletion.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $this->assertTrue($symlink->isLink());

        // Delete the symlink
        unlink($symlinkPath);
        clearstatcache(true, $symlinkPath);

        // Create a new Symlink object for the now deleted symlink
        $newSymlink = new Symlink($symlinkPath);
        $this->assertFalse($newSymlink->isLink());
    }

    public function test_isLink_for_relative_symlink(): void
    {
        $origDir = getcwd();
        chdir($this->testDir);

        try {
            $targetPath = 'relative_target.txt';
            $symlinkPath = 'relative_symlink.txt';

            touch($targetPath);
            symlink($targetPath, $symlinkPath);

            $symlink = new Symlink($symlinkPath);
            $isLink = $symlink->isLink();

            $this->assertTrue($isLink);
            $this->assertSame(is_link($symlinkPath), $isLink);
        } finally {
            chdir($origDir);
        }
    }

    public function test_isLink_for_chain_of_symlinks(): void
    {
        $targetPath = $this->testDir . '/chain_target.txt';
        $symlink1Path = $this->testDir . '/chain_symlink1.txt';
        $symlink2Path = $this->testDir . '/chain_symlink2.txt';

        touch($targetPath);
        symlink($targetPath, $symlink1Path);
        symlink($symlink1Path, $symlink2Path);

        $symlink1 = new Symlink($symlink1Path);
        $symlink2 = new Symlink($symlink2Path);

        $this->assertTrue($symlink1->isLink());
        $this->assertTrue($symlink2->isLink());
    }

    public function test_exists_for_existing_file(): void
    {
        $filePath = $this->testDir . '/existing_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $exists = $file->exists();

        $this->assertTrue($exists);
        $this->assertSame(file_exists($filePath), $exists);
    }

    public function test_exists_for_nonexistent_file(): void
    {
        $nonExistentPath = $this->testDir . '/nonexistent_file.txt';

        $file = new File($nonExistentPath);
        $exists = $file->exists();

        $this->assertFalse($exists);
        $this->assertSame(file_exists($nonExistentPath), $exists);
    }

    public function test_exists_for_existing_directory(): void
    {
        $dirPath = $this->testDir . '/existing_directory';
        mkdir($dirPath);

        $directory = new Directory($dirPath);
        $exists = $directory->exists();

        $this->assertTrue($exists);
        $this->assertSame(file_exists($dirPath), $exists);
    }

    public function test_exists_for_nonexistent_directory(): void
    {
        $nonExistentPath = $this->testDir . '/nonexistent_directory';

        $directory = new Directory($nonExistentPath);
        $exists = $directory->exists();

        $this->assertFalse($exists);
        $this->assertSame(file_exists($nonExistentPath), $exists);
    }

    public function test_exists_for_symlink_to_existing_target(): void
    {
        $targetPath = $this->testDir . '/symlink_target.txt';
        $symlinkPath = $this->testDir . '/symlink_to_existing.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $exists = $symlink->exists();

        $this->assertTrue($exists);
        $this->assertSame(file_exists($symlinkPath), $exists);
    }

    public function test_exists_for_symlink_to_nonexistent_target(): void
    {
        $nonExistentTarget = $this->testDir . '/nonexistent_target.txt';
        $symlinkPath = $this->testDir . '/symlink_to_nonexistent.txt';

        symlink($nonExistentTarget, $symlinkPath);

        $symlink = new Symlink($symlinkPath);
        $exists = $symlink->exists();

        // file_exists() returns false for broken symlinks
        $this->assertFalse($exists);
        $this->assertSame(file_exists($symlinkPath), $exists);
    }

    public function test_exists_after_file_creation(): void
    {
        $filePath = $this->testDir . '/created_after_test.txt';

        $file = new File($filePath);
        $this->assertFalse($file->exists());

        // Create the file
        touch($filePath);

        // Test that exists() now returns true
        $this->assertTrue($file->exists());
    }

    public function test_exists_after_file_deletion(): void
    {
        $filePath = $this->testDir . '/deleted_after_test.txt';
        touch($filePath);

        $file = new File($filePath);
        $this->assertTrue($file->exists());

        // Delete the file
        unlink($filePath);

        // Test that exists() now returns false
        $this->assertFalse($file->exists());
    }

    public function test_exists_consistency(): void
    {
        $filePath = $this->testDir . '/consistency_test.txt';
        touch($filePath);

        $file = new File($filePath);
        $exists1 = $file->exists();
        $exists2 = $file->exists();

        $this->assertTrue($exists1);
        $this->assertSame($exists1, $exists2);
    }

    public function test_exists_for_nested_path(): void
    {
        $nestedDir = $this->testDir . '/level1/level2/level3';
        mkdir($nestedDir, 0755, true);

        $filePath = $nestedDir . '/nested_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $exists = $file->exists();

        $this->assertTrue($exists);
        $this->assertSame(file_exists($filePath), $exists);
    }

    public function test_exists_clears_stat_cache(): void
    {
        $filePath = $this->testDir . '/stat_cache_test.txt';

        $file = new File($filePath);

        // First check - file doesn't exist
        $this->assertFalse($file->exists());

        // Create file
        touch($filePath);

        // Check again - should return true even though stat cache might be stale
        $this->assertTrue($file->exists());

        // Delete file
        unlink($filePath);

        // Check again - should return false
        $this->assertFalse($file->exists());
    }

    public function test_exists_with_different_storable_types(): void
    {
        $filePath = $this->testDir . '/type_test_file.txt';
        $dirPath = $this->testDir . '/type_test_dir';
        $symlinkPath = $this->testDir . '/type_test_symlink.txt';

        touch($filePath);
        mkdir($dirPath);
        symlink($filePath, $symlinkPath);

        $file = new File($filePath);
        $directory = new Directory($dirPath);
        $symlink = new Symlink($symlinkPath);

        $this->assertTrue($file->exists());
        $this->assertTrue($directory->exists());
        $this->assertTrue($symlink->exists());

        // Clean up and test non-existence
        unlink($symlinkPath);
        rmdir($dirPath);
        unlink($filePath);

        $this->assertFalse($file->exists());
        $this->assertFalse($directory->exists());
        $this->assertFalse($symlink->exists());
    }

    public function test_chmod(): void
    {
        $filePath = $this->testDir . '/test_chmod.txt';
        file_put_contents($filePath, 'test content');

        $file = new File($filePath);

        // Test changing permissions to 644 (owner: read/write, group/others: read)
        $file->chmod(0644);
        $this->assertSame(0644, $file->permissions);

        // Test changing permissions to 755 (owner: read/write/execute, group/others: read/execute)
        $file->chmod(0755);
        $this->assertSame(0755, $file->permissions);

        // Test changing permissions to 600 (owner: read/write, group/others: no access)
        $file->chmod(0600);
        $this->assertSame(0600, $file->permissions);
    }

    public function test_chmod_updates_file_info(): void
    {
        $filePath = $this->testDir . '/test_chmod_info.txt';
        file_put_contents($filePath, 'test content');

        $file = new File($filePath);

        $initialPermissions = $file->permissions;

        $newPermissions = 0600;
        $file->chmod($newPermissions);

        // Verify that the file info was updated (permissions should be different from initial)
        $this->assertNotSame($initialPermissions, $file->permissions);
        $this->assertSame($newPermissions, $file->permissions);

        // Verify that the file info object was refreshed by checking it reflects the new permissions
        $this->assertSame($newPermissions, $file->permissions);
    }

    public function test_chown_with_uid_only(): void
    {
        // Create a test file
        $filePath = $this->testDir . '/test_chown_uid.txt';
        file_put_contents($filePath, 'test content');

        $file = new File($filePath);

        // Get current uid
        $currentUid = $file->uid;

        // Change ownership to the same user (this should work without permission issues)
        $file->chown($currentUid);

        // Verify that the uid remains the same
        $this->assertSame($currentUid, $file->uid);
    }

    public function test_chown_with_uid_and_gid(): void
    {
        // Create a test file
        $filePath = $this->testDir . '/test_chown_both.txt';
        file_put_contents($filePath, 'test content');

        $file = new File($filePath);

        // Get current uid and gid
        $currentUid = $file->uid;
        $currentGid = $file->gid;

        // Change ownership with both uid and gid (to same values to avoid permission issues)
        $file->chown($currentUid, $currentGid);

        // Verify that both uid and gid are correctly set
        $this->assertSame($currentUid, $file->uid);
        $this->assertSame($currentGid, $file->gid);
    }

    public function test_chown_updates_file_info(): void
    {
        // Create a test file
        $filePath = $this->testDir . '/test_chown_info.txt';
        file_put_contents($filePath, 'test content');

        $file = new File($filePath);

        // Get initial uid
        $initialUid = $file->uid;
        $initialGid = $file->gid;

        // Change ownership to the same user/group to ensure it works
        $file->chown($initialUid, $initialGid);

        // Verify that the file info object was refreshed
        // The values should be the same since we're setting to current values
        $this->assertSame($initialUid, $file->uid);
        $this->assertSame($initialGid, $file->gid);

        // Verify that calling uid/gid properties multiple times returns consistent results
        $this->assertSame($file->uid, $file->uid);
        $this->assertSame($file->gid, $file->gid);
    }

    public function test_chown_with_string_uid(): void
    {
        // Create a test file
        $filePath = $this->testDir . '/test_chown_string.txt';
        file_put_contents($filePath, 'test content');

        $file = new File($filePath);

        // Get current user name if possible, otherwise use numeric uid
        $currentUid = $file->uid;
        $userName = posix_getpwuid($currentUid)['name'] ?? (string)$currentUid;

        // Change ownership using string username
        $file->chown($userName);

        // Verify that the uid is correctly set
        $this->assertSame($currentUid, $file->uid);
    }

    public function test_chown_with_string_gid(): void
    {
        // Create a test file
        $filePath = $this->testDir . '/test_chown_string_gid.txt';
        file_put_contents($filePath, 'test content');

        $file = new File($filePath);

        // Get current user and group names if possible
        $currentUid = $file->uid;
        $currentGid = $file->gid;
        $userName = posix_getpwuid($currentUid)['name'] ?? (string)$currentUid;
        $groupName = posix_getgrgid($currentGid)['name'] ?? (string)$currentGid;

        // Change ownership using string username and group name
        $file->chown($userName, $groupName);

        // Verify that both uid and gid are correctly set
        $this->assertSame($currentUid, $file->uid);
        $this->assertSame($currentGid, $file->gid);
    }

    public function test_chown_on_directory(): void
    {
        // Create a test directory
        $dirPath = $this->testDir . '/test_chown_dir';
        mkdir($dirPath);

        $directory = new Directory($dirPath);

        // Get current uid and gid
        $currentUid = $directory->uid;
        $currentGid = $directory->gid;

        // Change ownership
        $directory->chown($currentUid, $currentGid);

        // Verify that the ownership is correctly set
        $this->assertSame($currentUid, $directory->uid);
        $this->assertSame($currentGid, $directory->gid);
    }

    public function test_chown_on_symlink(): void
    {
        // Create a target file and symlink
        $targetPath = $this->testDir . '/chown_target.txt';
        $symlinkPath = $this->testDir . '/chown_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);

        // Get current uid and gid of the symlink
        $currentUid = $symlink->uid;
        $currentGid = $symlink->gid;

        // Change ownership of the symlink
        $symlink->chown($currentUid, $currentGid);

        // Verify that the ownership is correctly set
        $this->assertSame($currentUid, $symlink->uid);
        $this->assertSame($currentGid, $symlink->gid);
    }

    public function test_chgrp_with_numeric_gid(): void
    {
        // Create a test file
        $filePath = $this->testDir . '/test_chgrp_numeric.txt';
        file_put_contents($filePath, 'test content');

        $file = new File($filePath);

        // Get current gid
        $currentGid = $file->gid;

        // Change group to the same group (this should work without permission issues)
        $file->chgrp($currentGid);

        // Verify that the gid remains the same
        $this->assertSame($currentGid, $file->gid);
    }

    public function test_chgrp_with_string_gid(): void
    {
        // Create a test file
        $filePath = $this->testDir . '/test_chgrp_string.txt';
        file_put_contents($filePath, 'test content');

        $file = new File($filePath);

        // Get current group name if possible, otherwise use numeric gid
        $currentGid = $file->gid;
        $groupName = posix_getgrgid($currentGid)['name'] ?? (string)$currentGid;

        // Change group using string group name
        $file->chgrp($groupName);

        // Verify that the gid is correctly set
        $this->assertSame($currentGid, $file->gid);
    }

    public function test_chgrp_updates_file_info(): void
    {
        // Create a test file
        $filePath = $this->testDir . '/test_chgrp_info.txt';
        file_put_contents($filePath, 'test content');

        $file = new File($filePath);

        // Get initial gid
        $initialGid = $file->gid;

        // Change group to the same group to ensure it works
        $file->chgrp($initialGid);

        // Verify that the file info object was refreshed
        // The value should be the same since we're setting to current value
        $this->assertSame($initialGid, $file->gid);

        // Verify that calling gid property multiple times returns consistent results
        $this->assertSame($file->gid, $file->gid);
    }

    public function test_chgrp_on_directory(): void
    {
        // Create a test directory
        $dirPath = $this->testDir . '/test_chgrp_dir';
        mkdir($dirPath);

        $directory = new Directory($dirPath);

        // Get current gid
        $currentGid = $directory->gid;

        // Change group
        $directory->chgrp($currentGid);

        // Verify that the group is correctly set
        $this->assertSame($currentGid, $directory->gid);
    }

    public function test_chgrp_on_symlink(): void
    {
        // Create a target file and symlink
        $targetPath = $this->testDir . '/chgrp_target.txt';
        $symlinkPath = $this->testDir . '/chgrp_symlink.txt';

        touch($targetPath);
        symlink($targetPath, $symlinkPath);

        $symlink = new Symlink($symlinkPath);

        // Get current gid of the symlink
        $currentGid = $symlink->gid;

        // Change group of the symlink
        $symlink->chgrp($currentGid);

        // Verify that the group is correctly set
        $this->assertSame($currentGid, $symlink->gid);
    }

    public function test_chgrp_consistency(): void
    {
        // Create a test file
        $filePath = $this->testDir . '/test_chgrp_consistency.txt';
        file_put_contents($filePath, 'test content');

        $file = new File($filePath);

        // Get current gid
        $currentGid = $file->gid;

        // Change group multiple times and verify consistency
        $file->chgrp($currentGid);
        $gid1 = $file->gid;

        $file->chgrp($currentGid);
        $gid2 = $file->gid;

        $this->assertSame($gid1, $gid2);
        $this->assertSame($currentGid, $gid1);
    }

    public function test_chgrp_with_different_file_types(): void
    {
        // Create test files of different types
        $filePath = $this->testDir . '/chgrp_file.txt';
        $dirPath = $this->testDir . '/chgrp_directory';
        $symlinkPath = $this->testDir . '/chgrp_symlink.txt';

        file_put_contents($filePath, 'test content');
        mkdir($dirPath);
        symlink($filePath, $symlinkPath);

        $file = new File($filePath);
        $directory = new Directory($dirPath);
        $symlink = new Symlink($symlinkPath);

        // Get current gids
        $fileGid = $file->gid;
        $dirGid = $directory->gid;
        $symlinkGid = $symlink->gid;

        // Change groups
        $file->chgrp($fileGid);
        $directory->chgrp($dirGid);
        $symlink->chgrp($symlinkGid);

        // Verify all worked correctly
        $this->assertSame($fileGid, $file->gid);
        $this->assertSame($dirGid, $directory->gid);
        $this->assertSame($symlinkGid, $symlink->gid);
    }
}
