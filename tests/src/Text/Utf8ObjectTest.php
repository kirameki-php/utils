<?php declare(strict_types=1);

namespace Tests\Kirameki\Text;

use Kirameki\Core\Testing\TestCase;
use Kirameki\Text\Utf8Object;

class Utf8ObjectTest extends TestCase
{
    protected function obj(string $string): Utf8Object
    {
        return new Utf8Object($string);
    }

    public function test_byteLength(): void
    {
        $this->assertSame(0, $this->obj('')->byteLength(), 'empty');
        $this->assertSame(3, $this->obj('123')->byteLength(), 'ascii');
        $this->assertSame(9, $this->obj('あいう')->byteLength(), 'utf8');
        $this->assertSame(28, $this->obj('🏴󠁧󠁢󠁳󠁣󠁴󠁿')->byteLength(), 'grapheme');
    }

    public function test_cut(): void
    {
        $after = $this->obj('あいう')->cut(7, '...');
        $this->assertInstanceOf(Utf8Object::class, $after);
        $this->assertSame('あい...', $after->toString());
    }

    public function test_interpolate(): void
    {
        $buffer = $this->obj(' <a> ')->interpolate(['a' => 1], '<', '>');
        $this->assertSame(' 1 ', $buffer->toString());
        $this->assertInstanceOf(Utf8Object::class, $buffer);
    }

    public function test_isBlank(): void
    {
        $this->assertTrue($this->obj('')->isBlank());
        $this->assertFalse($this->obj('a')->isBlank());
        $this->assertFalse($this->obj("\n")->isBlank());
    }

    public function test_isNotBlank(): void
    {
        $this->assertFalse($this->obj('')->isNotBlank());
        $this->assertTrue($this->obj('a')->isNotBlank());
        $this->assertTrue($this->obj("\n")->isNotBlank());
    }

    public function test_length(): void
    {
        $this->assertSame(3, $this->obj('あいう')->length());
    }

}
