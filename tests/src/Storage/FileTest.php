<?php declare(strict_types=1);

namespace Tests\Kirameki\Storage;

use RuntimeException;
use Kirameki\Storage\File;
use Kirameki\Stream\FileStream;
use Kirameki\Time\Instant;
use function file_put_contents;
use function filemtime;
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

        $this->expectErrorMessage("file_get_contents(): Read of 8192 bytes failed with errno=21 Is a directory");

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

    public function test_write_multiline_content(): void
    {
        $filePath = $this->testDir . '/multiline_write.txt';
        $content = "Line 1\nLine 2\r\nLine 3\n\nLine 5 with spaces   \n\tTabbed line";

        $file = new File($filePath);
        $file->write($content);

        $this->assertSame($content, file_get_contents($filePath));
    }

    public function test_write_binary_content(): void
    {
        $filePath = $this->testDir . '/binary_write.dat';
        $binaryContent = pack('C*', 0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A);

        $file = new File($filePath);
        $file->write($binaryContent);

        $this->assertSame($binaryContent, file_get_contents($filePath));
    }

    public function test_write_unicode_content(): void
    {
        $filePath = $this->testDir . '/unicode_write.txt';
        $content = "Unicode: àáâãäåæçèéêë 中文 العربية русский 🚀✨";

        $file = new File($filePath);
        $file->write($content);

        $this->assertSame($content, file_get_contents($filePath));
    }

    public function test_write_large_content(): void
    {
        $filePath = $this->testDir . '/large_write.txt';
        $largeContent = str_repeat('Large content line ' . PHP_EOL, 10000);

        $file = new File($filePath);
        $file->write($largeContent);

        $this->assertSame($largeContent, file_get_contents($filePath));
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
}
