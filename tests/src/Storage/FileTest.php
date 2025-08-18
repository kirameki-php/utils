<?php declare(strict_types=1);

namespace Tests\Kirameki\Storage;

use Kirameki\Core\Exceptions\ErrorException;
use Kirameki\Storage\FileType;
use RuntimeException;
use Kirameki\Storage\File;
use Kirameki\Stream\FileStream;
use Kirameki\Time\Instant;
use function chmod;
use function dump;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function hash_file;
use function mkdir;
use function stat;
use function str_repeat;
use function strlen;
use function touch;

final class FileTest extends TestCase
{
    public function test_open_default_mode(): void
    {
        $filePath = $this->testDir . '/test_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $stream = $file->open();

        $this->assertInstanceOf(FileStream::class, $stream);
        $this->assertTrue($stream->close());
    }

    public function test_open_read_mode(): void
    {
        $filePath = $this->testDir . '/read_test.txt';
        $content = 'Test content for reading';
        file_put_contents($filePath, $content);

        $file = new File($filePath);
        $stream = $file->open('r');

        $readContent = $stream->read(100);
        $this->assertSame($content, $readContent);
        $this->assertTrue($stream->close());
    }

    public function test_open_write_mode(): void
    {
        $filePath = $this->testDir . '/write_test.txt';

        $file = new File($filePath);
        $stream = $file->open('w');

        $testContent = 'Written through stream';
        $stream->write($testContent);
        $this->assertTrue($stream->close());
        $this->assertSame($testContent, file_get_contents($filePath));
    }

    public function test_open_multiple_streams(): void
    {
        $filePath = $this->testDir . '/multi_stream.txt';
        $content = 'Content for multiple streams';
        file_put_contents($filePath, $content);

        $file = new File($filePath);

        // Open multiple streams
        $stream1 = $file->open('r');
        $stream2 = $file->open('r');

        $this->assertNotSame($stream1, $stream2);

        // Both should be able to read the same content
        $content1 = $stream1->read(100);
        $content2 = $stream2->read(100);

        $this->assertSame($content, $content1);
        $this->assertSame($content, $content2);

        $this->assertTrue($stream1->close());
        $this->assertTrue($stream2->close());
    }

    public function test_read_simple_content(): void
    {
        $filePath = $this->testDir . '/simple_read.txt';
        $content = 'Simple test content';
        file_put_contents($filePath, $content);

        $file = new File($filePath);
        $result = $file->read();

        $this->assertSame($content, $result);
    }

    public function test_read_empty_file(): void
    {
        $filePath = $this->testDir . '/empty_file.txt';
        touch($filePath);

        $file = new File($filePath);
        $result = $file->read();

        $this->assertSame('', $result);
    }

    public function test_read_multiline_content(): void
    {
        $filePath = $this->testDir . '/multiline.txt';
        $content = "Line 1\nLine 2\r\nLine 3\n\nLine 5";
        file_put_contents($filePath, $content);

        $file = new File($filePath);
        $result = $file->read();

        $this->assertSame($content, $result);
    }

    public function test_read_throws_exception_for_nonexistent_file(): void
    {
        $filePath = $this->testDir . '/nonexistent.txt';

        $file = new File($filePath);

        $this->expectErrorMessage("file_get_contents({$filePath}): Failed to open stream: No such file or directory");

        $file->read();
    }

    public function test_read_throws_exception_for_directory(): void
    {
        $dirPath = $this->testDir . '/directory';
        mkdir($dirPath);

        $file = new File($dirPath);
        $blockSize = (stat($file->pathname)['blksize'] ?? 0) * 2;
        $this->expectErrorMessage("file_get_contents(): Read of {$blockSize} bytes failed with errno=21 Is a directory");

        $file->read();
    }

    public function test_read_after_write(): void
    {
        $filePath = $this->testDir . '/write_then_read.txt';
        $content = 'Content written then read';

        $file = new File($filePath);
        $file->write($content);
        $result = $file->read();

        $this->assertSame($content, $result);
    }

    public function test_write_new_file(): void
    {
        $filePath = $this->testDir . '/new_write_file.txt';
        $content = 'Content written to new file';

        $file = new File($filePath);
        $file->write($content);

        $this->assertTrue($file->exists());
        $this->assertSame($content, file_get_contents($filePath));
    }

    public function test_write_overwrites_existing_file(): void
    {
        $filePath = $this->testDir . '/overwrite_test.txt';
        $originalContent = 'Original content';
        $newContent = 'New content that overwrites';

        file_put_contents($filePath, $originalContent);

        $file = new File($filePath);
        $file->write($newContent);

        $this->assertSame($newContent, file_get_contents($filePath));
        $this->assertNotSame($originalContent, file_get_contents($filePath));
    }

    public function test_write_empty_content(): void
    {
        $filePath = $this->testDir . '/empty_write.txt';

        $file = new File($filePath);
        $file->write('');

        $this->assertTrue($file->exists());
        $this->assertSame('', file_get_contents($filePath));
    }

    public function test_write_unicode_content(): void
    {
        $filePath = $this->testDir . '/unicode_write.txt';
        $content = "Unicode: àáâãäåæçèéêë 中文 العربية русский 🚀✨";

        $file = new File($filePath);
        $file->write($content);

        $this->assertSame($content, file_get_contents($filePath));
    }


    public function test_replace_existing_file(): void
    {
        $filePath = $this->testDir . '/replace_test.txt';
        $originalContent = 'Original content for replacement';
        $newContent = 'Replaced content';

        file_put_contents($filePath, $originalContent);

        $file = new File($filePath);
        $file->replace($newContent);

        $this->assertSame($newContent, file_get_contents($filePath));
    }

    public function test_replace_throws_exception_for_nonexistent_file(): void
    {
        $filePath = $this->testDir . '/nonexistent_replace.txt';

        $file = new File($filePath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("SplFileInfo::getPerms(): stat failed for {$filePath}");

        $file->replace('New content');
    }

    public function test_replace_with_empty_content(): void
    {
        $filePath = $this->testDir . '/replace_empty.txt';
        $originalContent = 'Content to be replaced with empty';

        file_put_contents($filePath, $originalContent);

        $file = new File($filePath);
        $file->replace('');

        $this->assertSame('', file_get_contents($filePath));
    }

    public function test_replace_preserves_file_permissions(): void
    {
        $filePath = $this->testDir . '/replace_permissions.txt';
        $originalContent = 'Original content';
        $newContent = 'New content';

        file_put_contents($filePath, $originalContent);
        chmod($filePath, 0644);
        $originalPerms = fileperms($filePath);

        $file = new File($filePath);
        $file->replace($newContent);

        $this->assertSame($newContent, file_get_contents($filePath));
        $this->assertSame($originalPerms, fileperms($filePath));
    }

    public function test_delete_existing_file(): void
    {
        $filePath = $this->testDir . '/delete_test.txt';
        $content = 'Content to be deleted';

        file_put_contents($filePath, $content);
        $this->assertTrue(file_exists($filePath));

        $file = new File($filePath);
        $file->delete();

        $this->assertFalse($file->exists());
        $this->assertFalse(file_exists($filePath));
    }

    public function test_delete_nonexistent_file_throws_exception(): void
    {
        $filePath = $this->testDir . '/nonexistent_delete.txt';

        $file = new File($filePath);

        $this->expectErrorMessage("unlink({$filePath}): No such file or directory");

        $file->delete();
    }

    public function test_touch_creates_new_file(): void
    {
        $filePath = $this->testDir . '/touch_new.txt';

        $this->assertFalse(file_exists($filePath));

        $file = new File($filePath);
        $file->touch();

        $this->assertTrue($file->exists());
        $this->assertTrue(file_exists($filePath));
        $this->assertSame('', file_get_contents($filePath)); // Should be empty
    }

    public function test_touch_with_specific_timestamp(): void
    {
        $filePath = $this->testDir . '/touch_timestamp.txt';
        $mtime = strtotime('2023-01-01 12:00:00');
        $atime = strtotime('2023-01-02 12:00:00');

        $file = new File($filePath);
        $file->touch(new Instant($mtime), new Instant($atime));

        $this->assertTrue($file->exists());
        $this->assertSame($mtime, filemtime($filePath));
        $this->assertSame($atime, fileatime($filePath));
    }

    public function test_touch_preserves_content(): void
    {
        $filePath = $this->testDir . '/touch_preserve.txt';
        $content = 'Content that should be preserved';

        file_put_contents($filePath, $content);

        $file = new File($filePath);
        $file->touch();

        $this->assertSame($content, file_get_contents($filePath));
    }

    public function test_touch_cannot_create_from_nested_directory(): void
    {
        $filePath = $this->testDir . '/nested/dir/touch_nested.txt';

        $file = new File($filePath);

        $this->expectErrorMessage("touch(): Unable to create file {$filePath} because No such file or directory");

        $file->touch();
    }

    public function test_write_then_read_cycle(): void
    {
        $filePath = $this->testDir . '/write_read_cycle.txt';
        $content = 'Content for write-read cycle test';

        $file = new File($filePath);

        // Write content
        $file->write($content);
        $this->assertTrue($file->exists());

        // Read it back
        $readContent = $file->read();
        $this->assertSame($content, $readContent);
    }

    public function test_write_replace_read_cycle(): void
    {
        $filePath = $this->testDir . '/write_replace_read.txt';
        $originalContent = 'Original content';
        $replacedContent = 'Replaced content';

        $file = new File($filePath);

        // Write original content
        $file->write($originalContent);
        $this->assertSame($originalContent, $file->read());

        // Replace with new content
        $file->replace($replacedContent);
        $this->assertSame($replacedContent, $file->read());
    }

    public function test_touch_write_delete_cycle(): void
    {
        $filePath = $this->testDir . '/touch_write_delete.txt';
        $content = 'Content for full cycle test';

        $file = new File($filePath);

        // Touch to create
        $file->touch();
        $this->assertTrue($file->exists());
        $this->assertSame('', $file->read());

        // Write content
        $file->write($content);
        $this->assertSame($content, $file->read());

        // Delete
        $file->delete();
        $this->assertFalse($file->exists());
    }

    public function test_file_copyTo_creates_exact_copy(): void
    {
        $originalPath = $this->testDir . '/original_file.txt';
        $destinationPath = $this->testDir . '/copied_file.txt';
        $content = 'Test content for File::copyTo()';

        file_put_contents($originalPath, $content);
        chmod($originalPath, 0644);

        $originalFile = new File($originalPath);
        $copiedFile = $originalFile->copyTo($destinationPath);

        // Verify return value is a new File instance
        $this->assertInstanceOf(File::class, $copiedFile);
        $this->assertSame($destinationPath, $copiedFile->pathname);
        $this->assertNotSame($originalFile, $copiedFile);

        // Verify both files exist
        $this->assertTrue(file_exists($originalPath));
        $this->assertTrue(file_exists($destinationPath));

        // Verify content is identical
        $this->assertSame($content, $originalFile->read());
        $this->assertSame($content, $copiedFile->read());
        $this->assertSame($originalFile->bytes, $copiedFile->bytes);
    }

    public function test_file_copyTo_preserves_file_content_and_size(): void
    {
        $originalPath = $this->testDir . '/binary_test.dat';
        $destinationPath = $this->testDir . '/binary_copy.dat';

        // Create a file with binary content including null bytes
        $binaryContent = "\x00\x01\x02\xFF\xFE\xFD" . str_repeat('A', 1000) . "\x00\x00";
        file_put_contents($originalPath, $binaryContent);

        $originalFile = new File($originalPath);
        $copiedFile = $originalFile->copyTo($destinationPath);

        // Verify exact content match
        $this->assertSame($binaryContent, $copiedFile->read());
        $this->assertSame(strlen($binaryContent), $copiedFile->bytes);
        $this->assertSame($originalFile->bytes, $copiedFile->bytes);

        // Verify files are byte-for-byte identical
        $this->assertSame(
            hash_file('sha256', $originalPath),
            hash_file('sha256', $destinationPath)
        );
    }

    public function test_file_copyTo_handles_large_files(): void
    {
        $originalPath = $this->testDir . '/large_file.txt';
        $destinationPath = $this->testDir . '/large_copy.txt';

        // Create a larger file (10KB)
        $largeContent = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 200);
        file_put_contents($originalPath, $largeContent);

        $originalFile = new File($originalPath);
        $copiedFile = $originalFile->copyTo($destinationPath);

        $this->assertInstanceOf(File::class, $copiedFile);
        $this->assertSame(strlen($largeContent), $copiedFile->bytes);
        $this->assertSame($originalFile->bytes, $copiedFile->bytes);

        // Verify content integrity
        $this->assertSame($largeContent, $copiedFile->read());
    }

    public function test_file_copyTo_updates_destination_file_info(): void
    {
        $originalPath = $this->testDir . '/source_file_info.txt';
        $destinationPath = $this->testDir . '/dest_file_info.txt';
        $content = 'Content for file info test';

        file_put_contents($originalPath, $content);

        $originalFile = new File($originalPath);
        $copiedFile = $originalFile->copyTo($destinationPath);

        // Verify the copied file has correct file info
        $this->assertSame('dest_file_info.txt', $copiedFile->filename);
        $this->assertSame('dest_file_info', $copiedFile->name);
        $this->assertSame('txt', $copiedFile->extension);
        $this->assertSame($this->testDir, $copiedFile->directory->pathname);
        $this->assertTrue($copiedFile->exists());
        $this->assertSame(FileType::File, $copiedFile->type);
    }

    public function test_file_copyTo_to_different_directory(): void
    {
        $originalPath = $this->testDir . '/source.txt';
        $targetDir = $this->testDir . '/target_directory';
        $destinationPath = $targetDir . '/destination.txt';

        file_put_contents($originalPath, 'Moving to different directory');
        mkdir($targetDir);

        $originalFile = new File($originalPath);
        $copiedFile = $originalFile->copyTo($destinationPath);

        $this->assertInstanceOf(File::class, $copiedFile);
        $this->assertSame($destinationPath, $copiedFile->pathname);
        $this->assertSame($targetDir, $copiedFile->directory->pathname);
        $this->assertTrue(file_exists($destinationPath));
        $this->assertSame('Moving to different directory', $copiedFile->read());
    }

    public function test_file_copyTo_overwrites_existing_file(): void
    {
        $originalPath = $this->testDir . '/original.txt';
        $destinationPath = $this->testDir . '/existing.txt';

        file_put_contents($originalPath, 'New content');
        file_put_contents($destinationPath, 'Old content that will be overwritten');

        $originalFile = new File($originalPath);
        $copiedFile = $originalFile->copyTo($destinationPath);

        // Verify the destination file was overwritten
        $this->assertSame('New content', $copiedFile->read());
        $this->assertSame('New content', file_get_contents($destinationPath));

        // Verify original file is unchanged
        $this->assertSame('New content', $originalFile->read());
    }

    public function test_file_copyTo_with_special_characters_in_name(): void
    {
        $originalPath = $this->testDir . '/file with spaces & symbols!.txt';
        $destinationPath = $this->testDir . '/copied file with spaces & symbols!.txt';
        $content = 'Content with special filename';

        file_put_contents($originalPath, $content);

        $originalFile = new File($originalPath);
        $copiedFile = $originalFile->copyTo($destinationPath);

        $this->assertInstanceOf(File::class, $copiedFile);
        $this->assertSame($content, $copiedFile->read());
        $this->assertSame('copied file with spaces & symbols!.txt', $copiedFile->filename);
        $this->assertTrue(file_exists($destinationPath));
    }

    public function test_file_copyTo_preserves_original_after_copy(): void
    {
        $originalPath = $this->testDir . '/preserve_test.txt';
        $destinationPath = $this->testDir . '/preserve_copy.txt';
        $originalContent = 'Original content';

        file_put_contents($originalPath, $originalContent);

        $originalFile = new File($originalPath);
        $originalSize = $originalFile->bytes;
        $originalFilename = $originalFile->filename;

        $copiedFile = $originalFile->copyTo($destinationPath);

        // Verify original file is completely unchanged
        $this->assertSame($originalContent, $originalFile->read());
        $this->assertSame($originalSize, $originalFile->bytes);
        $this->assertSame($originalFilename, $originalFile->filename);
        $this->assertTrue($originalFile->exists());

        // Verify copy was created successfully
        $this->assertSame($originalContent, $copiedFile->read());
        $this->assertSame($originalSize, $copiedFile->bytes);
        $this->assertTrue($copiedFile->exists());
    }
}
