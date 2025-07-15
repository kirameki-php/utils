<?php declare(strict_types=1);

namespace Tests\Kirameki\Text;

use Kirameki\Testing\TestCase;
use Kirameki\Text\StrObject;

class StrObjectTest extends TestCase
{
    protected function obj(string $string): StrObject
    {
        return new StrObject($string);
    }

    public function test_from(): void
    {
        $sb = $this->obj('a');
        $this->assertInstanceOf(StrObject::class, $sb);
        $this->assertSame('a', $sb->toString());
    }

    public function test___toString(): void
    {
        $sb = $this->obj('a');
        $this->assertSame('a', (string) $sb);
    }

    public function test_jsonSerialize(): void
    {
        $sb = $this->obj('a');
        $this->assertSame('a', $sb->jsonSerialize());
    }

    public function test_append(): void
    {
        $after = $this->obj('a')->append('1');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('a1', $after->toString());
    }

    public function test_appendFormat(): void
    {
        $after = $this->obj('a')->appendFormat('%s %s', 'b', 0);
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('ab 0', $after->toString());
    }

    public function test_basename(): void
    {
        $sb = $this->obj('/test/path/of.php');
        $after = $sb->basename();
        $afterSuffix = $sb->basename('.php');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertInstanceOf(StrObject::class, $afterSuffix);
        $this->assertSame('of.php', $after->toString());
        $this->assertSame('of', $afterSuffix->toString());
    }

    public function test_before(): void
    {
        $after = $this->obj('abc')->substringBefore('b');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('a', $after->toString());

        $after = $this->obj('abc')->substringBefore('d');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('abc', $after->toString());
    }

    public function test_beforeLast(): void
    {
        $after = $this->obj('abbc')->substringBeforeLast('b');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('ab', $after->toString());

        $after = $this->obj('abbc')->substringBeforeLast('d');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('abbc', $after->toString());
    }

    public function test_between(): void
    {
        $after = $this->obj('abcd')->between('a', 'c');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('b', $after->toString());
    }

    public function test_betweenFurthest(): void
    {
        $after = $this->obj('aa bb cc')->betweenFurthest('a', 'c');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('a bb c', $after->toString());
    }

    public function test_betweenLast(): void
    {
        $after = $this->obj('aa bb cc')->betweenLast('a', 'c');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame(' bb c', $after->toString());
    }

    public function test_capitalize(): void
    {
        $after = $this->obj('foo bar')->capitalize();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('Foo bar', $after->toString());
        $after = $this->obj('é')->capitalize();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('é', $after->toString());
    }

    public function test_chunk(): void
    {
        $after = $this->obj('foo bar')->chunk(2, 2);
        $this->assertSame(['fo', 'o ', 'bar'], $after);
    }

    public function test_contains(): void
    {
        $sb = $this->obj('foo bar');
        $this->assertFalse($sb->contains('baz'));
        $this->assertTrue($sb->contains('foo'));
        $this->assertTrue($sb->contains(''));
        $this->assertFalse($sb->contains('  '));
    }

    public function test_containsAll(): void
    {
        $sb = $this->obj('foo bar');
        $this->assertFalse($sb->containsAll(['foo', 'bar', 'baz']));
        $this->assertTrue($sb->containsAll(['foo', 'bar']));
        $this->assertTrue($sb->containsAll(['', '']));
    }

    public function test_containsAny(): void
    {
        $sb = $this->obj('foo bar');
        $this->assertTrue($sb->containsAny(['foo', 'bar', 'baz']));
        $this->assertFalse($sb->containsAny(['baz', '_']));
    }

    public function test_containsPattern(): void
    {
        $sb = $this->obj('foo bar');
        $this->assertTrue($sb->containsPattern('/[a-z]+/'));
        $this->assertFalse($sb->containsPattern('/[0-9]+/'));
    }

    public function test_count(): void
    {
        $sb = $this->obj('foo bar');
        $this->assertSame(1, $sb->count('foo'));

        $sb = $this->obj('あああ');
        $this->assertSame(1, $sb->count('ああ'));
        $this->assertSame(2, $sb->count('ああ', true));
    }

    public function test_decapitalize(): void
    {
        $after = $this->obj('FOO Bar')->decapitalize();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('fOO Bar', $after->toString());
    }

    public function test_dirname(): void
    {
        $sb = $this->obj('/test/path/of.php');
        $after1 = $sb->dirname();
        $after2 = $sb->dirname(2);

        $this->assertInstanceOf(StrObject::class, $after1);
        $this->assertInstanceOf(StrObject::class, $after2);
        $this->assertSame('/test/path', $after1->toString());
        $this->assertSame('/test', $after2->toString());
    }

    public function test_doesNotContain(): void
    {
        $sb = $this->obj('foo bar');
        $this->assertTrue($sb->doesNotContain('baz'));
        $this->assertFalse($sb->doesNotContain('foo'));
        $this->assertFalse($sb->doesNotContain(''));
        $this->assertTrue($sb->doesNotContain('  '));
    }

    public function test_doesNotEndWith(): void
    {
        $sb = $this->obj('/test/path/of.php');
        $this->assertTrue($sb->doesNotEndWith('/test'));
        $this->assertFalse($sb->doesNotEndWith('.php'));
    }

    public function test_doesNotStartWith(): void
    {
        $sb = $this->obj('/test/path/of.php');
        $this->assertFalse($sb->doesNotStartWith('/test'));
        $this->assertTrue($sb->doesNotStartWith('.php'));
    }

    public function test_dropFirst(): void
    {
        $after = $this->obj('abc')->dropFirst(1);
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('bc', $after->toString());
    }

    public function test_dropLast(): void
    {
        $after = $this->obj('abc')->dropLast(1);
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('ab', $after->toString());
    }

    public function test_endsWith(): void
    {
        $sb = $this->obj('/test/path/of.php');
        $this->assertFalse($sb->endsWith('/test'));
        $this->assertTrue($sb->endsWith('.php'));
    }

    public function test_endsWithAny(): void
    {
        $sb = $this->obj('/test/path/of.php');
        $this->assertTrue($sb->endsWithAny(['.php']));
        $this->assertTrue($sb->endsWithAny(['path', '.php']));
        $this->assertFalse($sb->endsWithAny(['/test']));
        $this->assertFalse($sb->endsWithAny(['/test', 'path']));
    }

    public function test_endsWithNone(): void
    {
        $sb = $this->obj('/test/path/of.php');
        $this->assertTrue($sb->endsWithNone(['/test']));
        $this->assertTrue($sb->endsWithNone(['/test', 'path']));
        $this->assertFalse($sb->endsWithNone(['.php']));
        $this->assertFalse($sb->endsWithNone(['path', '.php']));
    }

    public function test_equals(): void
    {
        $sb = $this->obj('foo bar');
        $this->assertTrue($sb->equals('foo bar'));
        $this->assertFalse($sb->equals('foo'));
    }

    public function test_equalsAny(): void
    {
        $sb = $this->obj('foo bar');
        $this->assertTrue($sb->equalsAny(['foo bar']));
        $this->assertFalse($sb->equalsAny(['foo']));
    }

    public function test_indexOfFirst(): void
    {
        $sb = $this->obj('aabbcc');
        $this->assertSame(2, $sb->indexOfFirst('b'));
        $this->assertSame(3, $sb->indexOfFirst('b', 3));
    }

    public function test_indexOfLast(): void
    {
        $this->assertSame(3, $this->obj('aabbcc')->indexOfLast('b'));
    }

    public function test_insertAt(): void
    {
        $after = $this->obj('aaa')->insertAt('b', 1);
        $this->assertSame('abaa', $after->toString());
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

    public function test_interpolate(): void
    {
        $buffer = $this->obj(' <a> ')->interpolate(['a' => 1], '<', '>');
        $this->assertSame(' 1 ', $buffer->toString());
        $this->assertInstanceOf(StrObject::class, $buffer);
    }

    public function test_length(): void
    {
        $this->assertSame(9, $this->obj('あいう')->length());
    }

    public function test_matchAll(): void
    {
        $buffer = $this->obj('a1b2c3');
        $matches = $buffer->matchAll('/[a-z]+/');
        $this->assertSame([['a', 'b', 'c']], $matches);
    }

    public function test_matchFirst(): void
    {
        $buffer = $this->obj('a1b2c3');
        $match = $buffer->matchFirst('/[a-z]+/');
        $this->assertSame('a', $match);
    }

    public function test_matchFirstOrNull(): void
    {
        $buffer = $this->obj('abc');
        $match = $buffer->matchFirstOrNull('/[0-9]+/');
        $this->assertNull($match);
    }

    public function test_matchLast(): void
    {
        $buffer = $this->obj('a1b2c3');
        $match = $buffer->matchLast('/[a-z]+/');
        $this->assertSame('c', $match);
    }

    public function test_matchLastOrNull(): void
    {
        $buffer = $this->obj('abc');
        $match = $buffer->matchLastOrNull('/[0-9]+/');
        $this->assertNull($match);
    }

    public function test_padBoth(): void
    {
        $after = $this->obj('a')->padBoth(3, 'b');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('bab', $after->toString());
    }

    public function test_padEnd(): void
    {
        $after = $this->obj('a')->padEnd(3, 'b');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('abb', $after->toString());
    }

    public function test_padStart(): void
    {
        $after = $this->obj('a')->padStart(3, 'b');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('bba', $after->toString());
    }

    public function test_pipe(): void
    {
        $count = 0;
        $tapped = $this->obj('a')->pipe(function(StrObject $b) use (&$count) {
            $count++;
            return $b->append('b');
        });
        self::assertSame(1, $count);
        self::assertInstanceOf(StrObject::class, $tapped);
        self::assertSame('ab', $tapped->toString());
    }

    public function test_prepend(): void
    {
        $after = $this->obj('a')->prepend('1', '2');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('12a', $after->toString());
    }

    public function test_range(): void
    {
        $after = $this->obj('abc')->range(1, 2);
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('b', $after->toString());
    }

    public function test_remove(): void
    {
        $after = $this->obj('foooooo bar')->remove('oo', 2);
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('foo bar', $after->toString());
    }

    public function test_removeFirst(): void
    {
        $after = $this->obj('foo foo')->removeFirst('foo');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame(' foo', $after->toString());
    }

    public function test_removeLast(): void
    {
        $after = $this->obj('foo foo')->removeLast('foo');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('foo ', $after->toString());
    }

    public function test_repeat(): void
    {
        $after = $this->obj('a')->repeat(3);
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('aaa', $after->toString());
    }

    public function test_replace(): void
    {
        $after = $this->obj('foo bar foo')->replace('foo', 'baz');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('baz bar baz', $after->toString());
    }

    public function test_replaceFirst(): void
    {
        $after = $this->obj('foo bar foo')->replaceFirst('foo', 'baz');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('baz bar foo', $after->toString());
    }

    public function test_replaceLast(): void
    {
        $after = $this->obj('foo bar foo')->replaceLast('foo', 'baz');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('foo bar baz', $after->toString());
    }

    public function test_replaceMatch(): void
    {
        $after = $this->obj('foo bar foo')->replaceMatch('/[a-z]+/', 'baz');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('baz baz baz', $after->toString());
    }

    public function test_replaceMatchWithCallback(): void
    {
        $after = $this->obj('foo bar')->replaceMatchWithCallback('/[a-z]+/', fn(array $m) => strtoupper($m[0]));
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('FOO BAR', $after->toString());
    }

    public function test_reverse(): void
    {
        $after = $this->obj('abc')->reverse();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('cba', $after->toString());
    }

    public function test_split(): void
    {
        $after = $this->obj('a b c')->split(' ');
        $this->assertSame(['a', 'b', 'c'], $after);
    }

    public function test_splitMatch(): void
    {
        $after = $this->obj('a1b2c3')->splitMatch('/[0-9]+/');
        $this->assertSame(['a', 'b', 'c', ''], $after);
    }

    public function test_startsWith(): void
    {
        $sb = $this->obj('/test/path/of.php');
        $this->assertTrue($sb->startsWith('/test'));
        $this->assertFalse($sb->startsWith('path'));
    }

    public function test_startsWithAny(): void
    {
        $sb = $this->obj('/test/path/of.php');
        $this->assertTrue($sb->startsWithAny(['/test']));
        $this->assertTrue($sb->startsWithAny(['/test', '.php']));
        $this->assertFalse($sb->startsWithAny(['path', '.php']));
        $this->assertFalse($sb->startsWithAny(['.php']));
    }

    public function test_startsWithNone(): void
    {
        $sb = $this->obj('/test/path/of.php');
        $this->assertFalse($sb->startsWithNone(['/test']));
        $this->assertTrue($sb->startsWithNone(['.php']));
        $this->assertTrue($sb->startsWithNone(['path', '.php']));
    }

    public function test_substring(): void
    {
        $after = $this->obj('abcd')->substring(1, 2);
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('bc', $after->toString());
    }

    public function test_surround(): void
    {
        $after = $this->obj('a')->surround('1', '2');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('1a2', $after->toString());
    }

    public function test_takeAfter(): void
    {
        $after = $this->obj('buffer')->substringAfter('f');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('fer', $after->toString());

        $after = $this->obj('abc')->substringAfter('d');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('abc', $after->toString());
    }

    public function test_takeAfterLast(): void
    {
        $after = $this->obj('abc abc')->substringAfterLast('b');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('c', $after->toString());

        $after = $this->obj('buffer')->substringAfterLast('f');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('er', $after->toString());

        $after = $this->obj('abc')->substringAfterLast('d');
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('abc', $after->toString());

    }

    public function test_takeFirst(): void
    {
        $after = $this->obj('abc')->takeFirst(1);
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('a', $after->toString());
    }

    public function test_takeLast(): void
    {
        $after = $this->obj('abc')->takeLast(1);
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('c', $after->toString());
    }

    public function test_tap(): void
    {
        $count = 0;
        $tapped = $this->obj('a')->tap(function(StrObject $b) use (&$count) {
            $count++;
            return 'x';
        });
        self::assertSame(1, $count);
        self::assertInstanceOf(StrObject::class, $tapped);
        self::assertSame('a', $tapped->toString());
    }

    public function test_toBool(): void
    {
        $this->assertTrue($this->obj('true')->toBool());
        $this->assertFalse($this->obj('false')->toBool());
    }

    public function test_toBoolOrNull(): void
    {
        $this->assertTrue($this->obj('true')->toBoolOrNull());
        $this->assertNull($this->obj('T')->toBoolOrNull());
        $this->assertFalse($this->obj('false')->toBoolOrNull());
        $this->assertNull($this->obj('')->toBoolOrNull());
    }

    public function test_toCamelCase(): void
    {
        $after = $this->obj('foo bar')->toCamelCase();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('fooBar', $after->toString());
    }

    public function test_toFloat(): void
    {
        $this->assertSame(12.3, $this->obj('12.3')->toFloat());
    }

    public function test_toFloatOrNull(): void
    {
        $this->assertSame(12.3, $this->obj('12.3')->toFloatOrNull());
        $this->assertNull($this->obj('12.3a')->toFloatOrNull());
    }

    public function test_toInt(): void
    {
        $this->assertSame(123, $this->obj('123')->toInt());
    }

    public function test_toIntOrNull(): void
    {
        $this->assertSame(123, $this->obj('123')->toIntOrNull());
        $this->assertNull($this->obj('123a')->toIntOrNull());
    }

    public function test_toKebabCase(): void
    {
        $after = $this->obj('foo barBaz')->toKebabCase();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('foo-bar-baz', $after->toString());
    }

    public function test_toLowerCase(): void
    {
        $after = $this->obj('FOO BAR')->toLowerCase();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('foo bar', $after->toString());
    }

    public function test_toPascalCase(): void
    {
        $after = $this->obj('foo bar')->toPascalCase();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('FooBar', $after->toString());
    }

    public function test_toSnakeCase(): void
    {
        $after = $this->obj('foo barBaz')->toSnakeCase();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('foo_bar_baz', $after->toString());
    }

    public function test_toString(): void
    {
        $this->assertSame('a', $this->obj('a')->toString());
    }

    public function test_toUpperCase(): void
    {
        $after = $this->obj('foo bar')->toUpperCase();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('FOO BAR', $after->toString());
    }

    public function test_trim(): void
    {
        $after = $this->obj(" \n\r\vfoo \n\r\v")->trim();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame('foo', $after->toString());
    }

    public function test_trimEnd(): void
    {
        $after = $this->obj(" \n\r\vfoo \n\r\v")->trimEnd();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame(" \n\r\vfoo", $after->toString());
    }

    public function test_trimStart(): void
    {
        $after = $this->obj(" \n\r\vfoo \n\r\v")->trimStart();
        $this->assertInstanceOf(StrObject::class, $after);
        $this->assertSame("foo \n\r\v", $after->toString());
    }
}
