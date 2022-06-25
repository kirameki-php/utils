<?php declare(strict_types=1);

namespace Tests\Kirameki\Utils;

use Kirameki\Utils\Str;
use stdClass;
use Webmozart\Assert\InvalidArgumentException;

class StrTest extends TestCase
{
    public function test_after(): void
    {
        // match first
        self::assertEquals('est', Str::after('test', 't'));

        // match last
        self::assertEquals('', Str::after('test1', '1'));

        // match empty string
        self::assertEquals('test', Str::after('test', ''));

        // no match
        self::assertEquals('', Str::after('test', 'test2'));

        // multi byte
        self::assertEquals('うえ', Str::after('ああいうえ', 'い'));

        // grapheme
        self::assertEquals('def', Str::after('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿def', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
    }

    public function test_afterIndex(): void
    {
        self::assertEquals('', Str::afterIndex('abcde', 6));
        self::assertEquals('', Str::afterIndex('abcde', 5));
        self::assertEquals('e', Str::afterIndex('abcde', 4));
        self::assertEquals('a', Str::afterIndex('a', 0));
        self::assertEquals('a', Str::afterIndex('a', -0));
        self::assertEquals('e', Str::afterIndex('abcde', -1));
        self::assertEquals('abcde', Str::afterIndex('abcde', -5));
        self::assertEquals('bcde', Str::afterIndex('abcde', -4));

        // grapheme
        self::assertEquals('def', Str::afterIndex('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿def', 4));
    }

    public function test_afterLast(): void
    {
        // match first (single occurrence)
        self::assertEquals('bc', Str::afterLast('abc', 'a'));

        // match first (multiple occurrence)
        self::assertEquals('1', Str::afterLast('test1', 't'));

        // match last
        self::assertEquals('', Str::afterLast('test1', '1'));

        // should match the last string
        self::assertEquals('Foo', Str::afterLast('----Foo', '---'));

        // match empty string
        self::assertEquals('test', Str::afterLast('test', ''));

        // no match
        self::assertEquals('', Str::afterLast('test', 'test2'));

        // multi byte
        self::assertEquals('え', Str::afterLast('ああいういえ', 'い'));

        // grapheme
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿f', Str::afterLast('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', 'e'));
    }

    public function test_before(): void
    {
        // match first (single occurrence)
        self::assertEquals('a', Str::before('abc', 'b'));

        // match first (multiple occurrence)
        self::assertEquals('a', Str::before('abc-abc', 'b'));

        // match last
        self::assertEquals('test', Str::before('test1', '1'));

        // match empty string
        self::assertEquals('test', Str::before('test', ''));

        // no match
        self::assertEquals('test', Str::before('test', 'test2'));

        // multi byte
        self::assertEquals('ああ', Str::before('ああいういえ', 'い'));

        // grapheme
        self::assertEquals('abc', Str::before('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
        self::assertEquals('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::before('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', 'e'));
    }

    public function test_beforeIndex(): void
    {
        self::assertEquals('abcde', Str::beforeIndex('abcde', 6));
        self::assertEquals('abcde', Str::beforeIndex('abcde', 5));
        self::assertEquals('abcd', Str::beforeIndex('abcde', 4));
        self::assertEquals('', Str::beforeIndex('a', 0));
        self::assertEquals('', Str::beforeIndex('a', -0));
        self::assertEquals('abcd', Str::beforeIndex('abcde', -1));
        self::assertEquals('', Str::beforeIndex('abcde', -5));
        self::assertEquals('a', Str::beforeIndex('abcde', -4));

        // grapheme
        self::assertEquals('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::beforeIndex('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', 4));
    }

    public function test_beforeLast(): void
    {
        // match first (single occurrence)
        self::assertEquals('a', Str::beforeLast('abc', 'b'));

        // match first (multiple occurrence)
        self::assertEquals('abc-a', Str::beforeLast('abc-abc', 'b'));

        // match last
        self::assertEquals('test', Str::beforeLast('test1', '1'));

        // match empty string
        self::assertEquals('test', Str::beforeLast('test', ''));

        // no match
        self::assertEquals('test', Str::beforeLast('test', 'test2'));

        // multi byte
        self::assertEquals('ああいう', Str::beforeLast('ああいういえ', 'い'));

        // grapheme
        self::assertEquals('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e', Str::beforeLast('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
    }

    public function test_between(): void
    {
        // empty
        self::assertEquals('test(1)', Str::between('test(1)', '', ''));
        self::assertEquals('1)', Str::between('test(1)', '(', ''));
        self::assertEquals('test(1', Str::between('test(1)', '', ')'));

        // basic
        self::assertEquals('1', Str::between('test(1)', '(', ')'));

        // edge
        self::assertEquals('', Str::between('()', '(', ')'));
        self::assertEquals('1', Str::between('(1)', '(', ')'));

        // start only
        self::assertEquals('', Str::between('test(', '(', ')'));

        // end only
        self::assertEquals('', Str::between('test)', '(', ')'));

        // nested
        self::assertEquals('test(1)', Str::between('(test(1))', '(', ')'));

        // multichar
        self::assertEquals('_', Str::between('ab_ba', 'ab', 'ba'));

        // utf8
        self::assertEquals('い', Str::between('あいう', 'あ', 'う'));
    }

    public function test_camelCase(): void
    {
        self::assertEquals('test', Str::camelCase('test'));
        self::assertEquals('test', Str::camelCase('Test'));
        self::assertEquals('testTest', Str::camelCase('test-test'));
        self::assertEquals('testTest', Str::camelCase('test_test'));
        self::assertEquals('testTest', Str::camelCase('test test'));
        self::assertEquals('testTestTest', Str::camelCase('test test test'));
        self::assertEquals('testTest', Str::camelCase(' test  test  '));
        self::assertEquals('testTestTest', Str::camelCase("--test_test-test__"));
    }

    public function test_capitalize(): void
    {
        self::assertEquals('Test', Str::capitalize('test'));
        self::assertEquals('Test abc', Str::capitalize('test abc'));
        self::assertEquals(' test abc', Str::capitalize(' test abc'));
        self::assertEquals('Àbc', Str::capitalize('àbc'));
        self::assertEquals('ゅ', Str::capitalize('ゅ'));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::capitalize('🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
    }

    public function test_concat(): void
    {
        self::assertEquals('', Str::concat());
        self::assertEquals('test', Str::concat('test'));
        self::assertEquals('testa ', Str::concat('test', 'a', '', ' '));
        self::assertEquals('ゅゅ', Str::concat('ゅ', 'ゅ'));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿🐌', Str::concat('🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🐌'));
    }

    public function test_contains(): void
    {
        self::assertTrue(Str::contains('abcde', 'ab'));
        self::assertFalse(Str::contains('abcde', 'ac'));
        self::assertTrue(Str::contains('abcde', ''));
        self::assertTrue(Str::contains('', ''));
    }

    public function test_containsAll(): void
    {
        self::assertTrue(Str::containsAll('', ['']));
        self::assertTrue(Str::containsAll('abcde', ['']));

        self::assertFalse(Str::containsAll('abcde', ['a', 'z']));
        self::assertFalse(Str::containsAll('abcde', ['z', 'a']));
        self::assertTrue(Str::containsAll('abcde', ['a']));
        self::assertTrue(Str::containsAll('abcde', ['a', 'b']));
        self::assertTrue(Str::containsAll('abcde', ['c', 'b']));

        self::assertFalse(Str::containsAll('abcde', ['z']));
        self::assertFalse(Str::containsAll('abcde', ['y', 'z']));
    }

    public function test_containsAll_empty_needles(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an array to contain at least 1 elements. Got: 0');
        Str::containsAll('abcde', []);
    }

    public function test_containsAny(): void
    {
        self::assertTrue(Str::containsAny('', ['']));
        self::assertTrue(Str::containsAny('abcde', ['']));

        self::assertTrue(Str::containsAny('abcde', ['a', 'z']));
        self::assertTrue(Str::containsAny('abcde', ['z', 'a']));
        self::assertTrue(Str::containsAny('abcde', ['a']));

        self::assertFalse(Str::containsAny('abcde', ['z']));
        self::assertFalse(Str::containsAny('abcde', ['y', 'z']));
    }

    public function test_containsAny_empty_needles(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an array to contain at least 1 elements. Got: 0');
        Str::containsAny('abcde', []);
    }

    public function test_containsPattern(): void
    {
        self::assertTrue(Str::containsPattern('abc', '/b/'));
        self::assertTrue(Str::containsPattern('abc', '/ab/'));
        self::assertTrue(Str::containsPattern('abc', '/abc/'));
        self::assertTrue(Str::containsPattern('ABC', '/abc/i'));
        self::assertTrue(Str::containsPattern('aaaz', '/a{3}/'));
        self::assertTrue(Str::containsPattern('ABC1', '/[A-z\d]+/'));
        self::assertTrue(Str::containsPattern('ABC1]', '/\d]$/'));
        self::assertFalse(Str::containsPattern('AB1C', '/\d]$/'));
    }

    public function test_cut(): void
    {
        // empty
        self::assertEquals('', Str::cut('', 0));

        // basic
        self::assertEquals('a', Str::cut('a', 1));
        self::assertEquals('a', Str::cut('abc', 1));

        // utf-8
        self::assertEquals('', Str::cut('あいう', 1));
        self::assertEquals('あ', Str::cut('あいう', 3));

        // cut and replaced with ellipsis
        self::assertEquals('a...', Str::cut('abc', 1, '...'));
        self::assertEquals('...', Str::cut('あいう', 1, '...'));
        self::assertEquals('あ...', Str::cut('あいう', 3, '...'));

        // cut and replaced with custom ellipsis
        self::assertEquals('a$', Str::cut('abc', 1, '$'));
    }

    public function test_delete(): void
    {
        self::assertEquals('', Str::delete('aaa', 'a'));
        self::assertEquals('a  a', Str::delete('aaa aa a', 'aa'));
        self::assertEquals('', Str::delete('', ''));
        self::assertEquals('no match', Str::delete('no match', 'hctam on'));
    }

    public function test_doesNotStartWith(): void
    {
        self::assertFalse(Str::doesNotStartWith('', ''));
        self::assertFalse(Str::doesNotStartWith('bb', ''));
        self::assertFalse(Str::doesNotStartWith('bb', 'b'));
        self::assertTrue(Str::doesNotStartWith('bb', 'ab'));
        self::assertFalse(Str::doesNotStartWith('あ-い-う', 'あ'));
        self::assertTrue(Str::doesNotStartWith('あ-い-う', 'え'));
        self::assertFalse(Str::doesNotStartWith('👨‍👨‍👧‍👦', '👨‍'));
        self::assertFalse(Str::doesNotStartWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
        self::assertTrue(Str::doesNotStartWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦'));
        self::assertFalse(Str::doesNotStartWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a'));
        self::assertTrue(Str::doesNotStartWith('ba', 'a'));
        self::assertTrue(Str::doesNotStartWith('', 'a'));
        self::assertTrue(Str::doesNotStartWith('abc', ['d', 'e']));
        self::assertFalse(Str::doesNotStartWith('abc', ['d', 'a']));
        self::assertTrue(Str::doesNotStartWith("\nあ", 'あ'));
    }

    public function test_doesNotEndWith(): void
    {
        self::assertFalse(Str::doesNotEndWith('abc', 'c'));
        self::assertTrue(Str::doesNotEndWith('abc', 'b'));
        self::assertFalse(Str::doesNotEndWith('abc', ['c']));
        self::assertFalse(Str::doesNotEndWith('abc', ['a', 'b', 'c']));
        self::assertTrue(Str::doesNotEndWith('abc', ['a', 'b']));
        self::assertFalse(Str::doesNotEndWith('aabbcc', 'cc'));
        self::assertFalse(Str::doesNotEndWith('aabbcc' . PHP_EOL, PHP_EOL));
        self::assertFalse(Str::doesNotEndWith('abc0', '0'));
        self::assertFalse(Str::doesNotEndWith('abcfalse', 'false'));
        self::assertFalse(Str::doesNotEndWith('a', ''));
        self::assertFalse(Str::doesNotEndWith('', ''));
        self::assertFalse(Str::doesNotEndWith('あいう', 'う'));
        self::assertTrue(Str::doesNotEndWith("あ\n", 'あ'));
    }

    public function test_endsWith(): void
    {
        self::assertTrue(Str::endsWith('abc', 'c'));
        self::assertFalse(Str::endsWith('abc', 'b'));
        self::assertTrue(Str::endsWith('abc', ['c']));
        self::assertTrue(Str::endsWith('abc', ['a', 'b', 'c']));
        self::assertFalse(Str::endsWith('abc', ['a', 'b']));
        self::assertTrue(Str::endsWith('aabbcc', 'cc'));
        self::assertTrue(Str::endsWith('aabbcc' . PHP_EOL, PHP_EOL));
        self::assertTrue(Str::endsWith('abc0', '0'));
        self::assertTrue(Str::endsWith('abcfalse', 'false'));
        self::assertTrue(Str::endsWith('a', ''));
        self::assertTrue(Str::endsWith('', ''));
        self::assertTrue(Str::endsWith('あいう', 'う'));
        self::assertFalse(Str::endsWith("あ\n", 'あ'));
    }

    public function test_firstIndexOf(): void
    {
        // empty string
        self::assertFalse(Str::firstIndexOf('', 'a'));

        // empty search
        self::assertEquals(0, Str::firstIndexOf('ab', ''));

        // find at 0
        self::assertEquals(0, Str::firstIndexOf('a', 'a'));

        // multiple matches
        self::assertEquals(1, Str::firstIndexOf('abb', 'b'));

        // offset (within bound)
        self::assertEquals(1, Str::firstIndexOf('abb', 'b', 1));
        self::assertEquals(5, Str::firstIndexOf('aaaaaa', 'a', 5));

        // offset (out of bound)
        self::assertEquals(false, Str::firstIndexOf('abb', 'b', 4));

        // offset (negative)
        self::assertEquals(2, Str::firstIndexOf('abb', 'b', -1));

        // offset (negative)
        self::assertEquals(false, Str::firstIndexOf('abb', 'b', -100));

        // offset utf-8
        self::assertEquals(0, Str::firstIndexOf('👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦'));
        self::assertFalse(Str::firstIndexOf('👨‍👨‍👧‍👦', '👨'));
        self::assertEquals(1, Str::firstIndexOf('あいう', 'い', 1));
        self::assertEquals(1, Str::firstIndexOf('🏴󠁧󠁢󠁳󠁣󠁴󠁿👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 1));
        self::assertFalse(Str::firstIndexOf('🏴󠁧󠁢󠁳󠁣󠁴󠁿👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 2));
    }

    public function test_insert(): void
    {
        self::assertEquals('xyzabc', Str::insert('abc', 'xyz', 0));
        self::assertEquals('axyzbc', Str::insert('abc', 'xyz', 1));
        self::assertEquals('abxyzc', Str::insert('abc', 'xyz', -1));
        self::assertEquals('abcxyz', Str::insert('abc', 'xyz', 3));
        self::assertEquals('あxyzい', Str::insert('あい', 'xyz', 1));
        self::assertEquals('あxyzい', Str::insert('あい', 'xyz', -1));
    }

    public function test_isBlank(): void
    {
        self::assertTrue(Str::isBlank(null));
        self::assertTrue(Str::isBlank(''));
        self::assertFalse(Str::isBlank('0'));
        self::assertFalse(Str::isBlank(' '));
    }

    public function test_isNotBlank(): void
    {
        self::assertFalse(Str::isNotBlank(null));
        self::assertFalse(Str::isNotBlank(''));
        self::assertTrue(Str::isNotBlank('0'));
        self::assertTrue(Str::isNotBlank(' '));
    }

    public function test_kebabCase(): void
    {
        self::assertEquals('test', Str::kebabCase('test'));
        self::assertEquals('test', Str::kebabCase('Test'));
        self::assertEquals('ttt', Str::kebabCase('TTT'));
        self::assertEquals('tt-test', Str::kebabCase('TTTest'));
        self::assertEquals('test-test', Str::kebabCase('testTest'));
        self::assertEquals('test-t-test', Str::kebabCase('testTTest'));
        self::assertEquals('test-test', Str::kebabCase('test-test'));
        self::assertEquals('test-test', Str::kebabCase('test_test'));
        self::assertEquals('test-test', Str::kebabCase('test test'));
        self::assertEquals('test-test-test', Str::kebabCase('test test test'));
        self::assertEquals('-test--test--', Str::kebabCase(' test  test  '));
        self::assertEquals('--test-test-test--', Str::kebabCase("--test_test-test__"));
    }

    public function test_lastIndexOf(): void
    {
        // empty string
        self::assertFalse(Str::lastIndexOf('', 'a'));

        // empty search
        self::assertEquals(2, Str::lastIndexOf('ab', ''));

        // find at 0
        self::assertEquals(0, Str::lastIndexOf('a', 'a'));

        // multiple matches
        self::assertEquals(2, Str::lastIndexOf('abb', 'b'));

        // offset (within bound)
        self::assertEquals(2, Str::lastIndexOf('abb', 'b', 1));
        self::assertEquals(5, Str::lastIndexOf('aaaaaa', 'a', 5));

        // offset (out of bound)
        self::assertEquals(false, Str::lastIndexOf('abb', 'b', 4));

        // offset (negative)
        self::assertEquals(3, Str::lastIndexOf('abbb', 'b', -1));

        // offset (negative)
        self::assertEquals(false, Str::lastIndexOf('abb', 'b', -100));

        // offset utf-8
        self::assertEquals(0, Str::lastIndexOf('👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦'));
        self::assertFalse(Str::lastIndexOf('👨‍👨‍👧‍👦', '👨'));
        self::assertEquals(1, Str::lastIndexOf('あいう', 'い', 1));
        self::assertEquals(1, Str::lastIndexOf('🏴󠁧󠁢󠁳󠁣󠁴󠁿👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 1));
        self::assertFalse(Str::lastIndexOf('🏴󠁧󠁢󠁳󠁣󠁴󠁿👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 2));
    }

    public function test_lcFirst(): void
    {
        self::assertEquals('', Str::lcFirst(''));
        self::assertEquals('test', Str::lcFirst('Test'));
        self::assertEquals('t T', Str::lcFirst('T T'));
        self::assertEquals(' T ', Str::lcFirst(' T '));
        self::assertEquals('é', Str::lcFirst('É'));
        self::assertEquals('🔡', Str::lcFirst('🔡'));
    }

    public function test_length(): void
    {
        self::assertEquals(0, Str::length(''));
        self::assertEquals(4, Str::length('Test'));
        self::assertEquals(9, Str::length(' T e s t '));
        self::assertEquals(2, Str::length('あい'));
        self::assertEquals(4, Str::length('あいzう'));
    }

    public function test_match(): void
    {
        self::assertEquals(['a'], Str::match('abcabc', '/a/'));
        self::assertEquals(['abc', 'p1' => 'a', 'a'], Str::match('abcabc', '/(?<p1>a)bc/'));
        self::assertEquals([], Str::match('abcabc', '/bcd/'));
        self::assertEquals(['cd'], Str::match('abcdxabc', '/c[^x]*/'));
        self::assertEquals([], Str::match('abcabcx', '/^abcx/'));
        self::assertEquals(['cx'], Str::match('abcabcx', '/cx$/'));
    }

    public function test_match_without_slashes(): void
    {
        $this->expectWarning();
        $this->expectWarningMessage('preg_match(): Delimiter must not be alphanumeric or backslash');
        Str::match('abcabc', 'a');
    }

    public function test_matchAll(): void
    {
        self::assertEquals([['a', 'a']], Str::matchAll('abcabc', '/a/'));
        self::assertEquals([['abc', 'abc'], 'p1' => ['a', 'a'], ['a', 'a']], Str::matchAll('abcabc', '/(?<p1>a)bc/'));
        self::assertEquals([[]], Str::matchAll('abcabc', '/bcd/'));
        self::assertEquals([['cd', 'c']], Str::matchAll('abcdxabc', '/c[^x]*/'));
        self::assertEquals([[]], Str::matchAll('abcabcx', '/^abcx/'));
        self::assertEquals([['cx']], Str::matchAll('abcabcx', '/cx$/'));
    }

    public function test_matchAll_without_slashes(): void
    {
        $this->expectWarning();
        $this->expectWarningMessage('preg_match_all(): Delimiter must not be alphanumeric or backslash');
        Str::matchAll('abcabc', 'a');
    }

    public function test_notContains(): void
    {
        self::assertTrue(Str::notContains('abcde', 'ac'));
        self::assertFalse(Str::notContains('abcde', 'ab'));
        self::assertFalse(Str::notContains('a', ''));
        self::assertTrue(Str::notContains('', 'a'));
    }

    public function test_pad(): void
    {
        // empty string
        self::assertEquals('', Str::pad('', -1, '_'));

        // pad string
        self::assertEquals('abc', Str::pad('abc', 3, ''));

        // defaults to pad right
        self::assertEquals('a', Str::pad('a', -1, '_'));
        self::assertEquals('a', Str::pad('a', 0, '_'));
        self::assertEquals('a_', Str::pad('a', 2, '_'));
        self::assertEquals('__', Str::pad('_', 2, '_'));
        self::assertEquals('ab', Str::pad('ab', 1, '_'));
    }

    public function test_pad_invalid_pad(): void
    {
        $this->expectExceptionMessage('Invalid padding type: 3');
        self::assertEquals('ab', Str::pad('ab', 1, '_', 3));
    }

    public function test_padBoth(): void
    {
        self::assertEquals('a', Str::padBoth('a', -1, '_'));
        self::assertEquals('a', Str::padBoth('a', 0, '_'));
        self::assertEquals('a_', Str::padBoth('a', 2, '_'));
        self::assertEquals('__', Str::padBoth('_', 2, '_'));
        self::assertEquals('_a_', Str::padBoth('a', 3, '_'));
        self::assertEquals('__a__', Str::padBoth('a', 5, '_'));
        self::assertEquals('__a___', Str::padBoth('a', 6, '_'));
        self::assertEquals('12hello123', Str::padBoth('hello', 10, '123'));
        self::assertEquals('いあい', Str::padBoth('あ', 3, 'い'));
    }

    public function test_padLeft(): void
    {
        self::assertEquals('a', Str::padLeft('a', -1, '_'));
        self::assertEquals('a', Str::padLeft('a', 0, '_'));
        self::assertEquals('_a', Str::padLeft('a', 2, '_'));
        self::assertEquals('__', Str::padLeft('_', 2, '_'));
        self::assertEquals('ab', Str::padLeft('ab', 1, '_'));
    }

    public function test_padRight(): void
    {
        self::assertEquals('a', Str::padRight('a', -1, '_'));
        self::assertEquals('a', Str::padRight('a', 0, '_'));
        self::assertEquals('a_', Str::padRight('a', 2, '_'));
        self::assertEquals('__', Str::padRight('_', 2, '_'));
        self::assertEquals('ab', Str::padRight('ab', 1, '_'));
    }

    public function test_pascalCase(): void
    {
        self::assertEquals('A', Str::pascalCase('a'));
        self::assertEquals('TestMe', Str::pascalCase('test_me'));
        self::assertEquals('TestMe', Str::pascalCase('test-me'));
        self::assertEquals('TestMe', Str::pascalCase('test me'));
        self::assertEquals('TestMe', Str::pascalCase('testMe'));
        self::assertEquals('TestMe', Str::pascalCase('TestMe'));
        self::assertEquals('TestMe', Str::pascalCase(' test_me '));
        self::assertEquals('TestMeNow!', Str::pascalCase('test_me now-!'));
    }

    public function test_repeat(): void
    {
        self::assertEquals('aaa', Str::repeat('a', 3));
        self::assertEquals('', Str::repeat('a', 0));
    }

    public function test_repeat_negative_times(): void
    {
        $this->expectError();
        $this->expectErrorMessage('str_repeat(): Argument #2 ($times) must be greater than or equal to 0');
        /** @noinspection PhpExpressionResultUnusedInspection */
        Str::repeat('a', -1);
    }

    public function test_replace(): void
    {
        self::assertEquals('', Str::replace('', '', ''));
        self::assertEquals('b', Str::replace('b', '', 'a'));
        self::assertEquals('aa', Str::replace('bb', 'b', 'a'));
        self::assertEquals('', Str::replace('b', 'b', ''));
        self::assertEquals('あえいえう', Str::replace('あ-い-う', '-', 'え'));
        self::assertEquals('__🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::replace('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a', '_'));
    }

    public function test_replaceFirst(): void
    {
        self::assertEquals('', Str::replaceFirst('', '', ''));
        self::assertEquals('bb', Str::replaceFirst('bb', '', 'a'));
        self::assertEquals('abb', Str::replaceFirst('bbb', 'b', 'a'));
        self::assertEquals('b', Str::replaceFirst('bb', 'b', ''));
        self::assertEquals('あえい-う', Str::replaceFirst('あ-い-う', '-', 'え'));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿 a', Str::replaceFirst('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 'a'));
        self::assertEquals('_🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::replaceFirst('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a', '_'));
    }

    public function test_replaceLast(): void
    {
        self::assertEquals('', Str::replaceLast('', '', ''));
        self::assertEquals('bb', Str::replaceLast('bb', '', 'a'));
        self::assertEquals('bba', Str::replaceLast('bbb', 'b', 'a'));
        self::assertEquals('b', Str::replaceLast('bb', 'b', ''));
        self::assertEquals('あ-いえう', Str::replaceLast('あ-い-う', '-', 'え'));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿 a', Str::replaceLast('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 'a'));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿a_🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::replaceLast('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a', '_'));
    }

    public function test_replaceMatch(): void
    {
        self::assertEquals('', Str::replaceMatch('', '', ''));
        self::assertEquals('abb', Str::replaceMatch('abc', '/c/', 'b'));
        self::assertEquals('abbb', Str::replaceMatch('abcc', '/c/', 'b'));
        self::assertEquals('あいい', Str::replaceMatch('あいう', '/う/', 'い'));
        self::assertEquals('x', Str::replaceMatch('abcde', '/[A-Za-z]+/', 'x'));
        self::assertEquals('a-b', Str::replaceMatch('a🏴󠁧󠁢󠁳󠁣󠁴󠁿b', '/🏴󠁧󠁢󠁳󠁣󠁴󠁿/', '-'));
    }

    public function test_reverse(): void
    {
        self::assertEquals('', Str::reverse(''));
        self::assertEquals('ba', Str::reverse('ab'));
        self::assertEquals('ういあ', Str::reverse('あいう'));
        self::assertEquals('cbあ🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::reverse('🏴󠁧󠁢󠁳󠁣󠁴󠁿あbc'));
    }

    public function test_startsWith(): void
    {
        self::assertTrue(Str::startsWith('', ''));
        self::assertTrue(Str::startsWith('bb', ''));
        self::assertTrue(Str::startsWith('bb', 'b'));
        self::assertTrue(Str::startsWith('あ-い-う', 'あ'));
        self::assertTrue(Str::startsWith('👨‍👨‍👧‍👦', '👨‍'));
        self::assertTrue(Str::startsWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
        self::assertFalse(Str::startsWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦'));
        self::assertTrue(Str::startsWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a'));
        self::assertFalse(Str::startsWith('ba', 'a'));
        self::assertFalse(Str::startsWith('', 'a'));
    }

    public function test_snakeCase(): void
    {
        // empty
        self::assertEquals('', Str::snakeCase(''));

        // no-change
        self::assertEquals('abc', Str::snakeCase('abc'));

        // case
        self::assertEquals('the_test_for_case', Str::snakeCase('the test for case'));
        self::assertEquals('the_test_for_case', Str::snakeCase('the-test-for-case'));
        self::assertEquals('the_test_for_case', Str::snakeCase('theTestForCase'));
        self::assertEquals('ttt', Str::snakeCase('TTT'));
        self::assertEquals('tt_t', Str::snakeCase('TtT'));
        self::assertEquals('tt_t', Str::snakeCase('TtT'));
        self::assertEquals('the__test', Str::snakeCase('the  test'));
        self::assertEquals('__test', Str::snakeCase('  test'));
        self::assertEquals("test\nabc", Str::snakeCase("test\nabc"));
        self::assertEquals('__test_test_test__', Str::snakeCase("--test_test-test__"));
    }

    public function test_split(): void
    {
        // empty
        self::assertEquals(['', ''], Str::split(' ', ' '));

        // no match
        self::assertEquals(['abc'], Str::split('abc', '_'));

        // match
        self::assertEquals(['a', 'c', 'd'], Str::split('abcbd', 'b'));

        // match utf-8
        self::assertEquals(['あ', 'う'], Str::split('あいう', 'い'));

        // match with limit
        self::assertEquals(['a', 'cbd'], Str::split('abcbd', 'b', 2));

        // split with empty string
        self::assertEquals(['', 'a', 'b', 'c', ''], Str::split('abc', ''));

        // match emoji
        self::assertEquals(['👨‍👨‍👧', ''], Str::split('👨‍👨‍👧‍👦', '‍👦'));

        // multiple separators
        self::assertEquals(['', '', 'c'], Str::split('abc', ['a', 'b']));
    }

    public function test_split_with_invalid_separator_in_array(): void
    {
        $this->expectErrorMessage('Argument #1 ($str) must be of type string, stdClass given');
        self::assertEquals(['', '', 'c'], Str::split('abc', [new stdClass()]));
    }

    public function test_substring(): void
    {
        // empty
        self::assertEquals('', Str::substring('', 0));
        self::assertEquals('', Str::substring('', 0, 1));

        // ascii
        self::assertEquals('abc', Str::substring('abc', 0));
        self::assertEquals('bc', Str::substring('abc', 1));
        self::assertEquals('c', Str::substring('abc', -1));
        self::assertEquals('a', Str::substring('abc', 0, 1));
        self::assertEquals('b', Str::substring('abc', 1, 1));
        self::assertEquals('b', Str::substring('abc', -2, 1));
        self::assertEquals('bc', Str::substring('abc', -2, 2));
        self::assertEquals('ab', Str::substring('abc', -9999, 2));

        // utf-8
        self::assertEquals('あいう', Str::substring('あいう', 0));
        self::assertEquals('いう', Str::substring('あいう', 1));
        self::assertEquals('う', Str::substring('あいう', -1));
        self::assertEquals('い', Str::substring('あいう', -2, 1));
        self::assertEquals('いう', Str::substring('あいう', -2, 2));
        self::assertEquals('あい', Str::substring('あいう', -9999, 2));

        // grapheme
        self::assertEquals('👨‍👨‍👧‍👦', Str::substring('👨‍👨‍👧‍👦', 0));
        self::assertEquals('', Str::substring('👨‍👨‍👧‍👦', 1));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::substring('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', 1));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::substring('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', -1, 1));
    }

    public function test_substring_invalid_input(): void
    {
        $this->expectExceptionMessage('Error converting input string to UTF-16: U_INVALID_CHAR_FOUND');
        self::assertEquals('', Str::substring(substr('あ', 1), 0, 2));
    }

    public function test_toLower(): void
    {
        // empty (nothing happens)
        self::assertEquals('', Str::toLower(''));

        // basic
        self::assertEquals('abc', Str::toLower('ABC'));

        // utf-8 chars (nothing happens)
        self::assertEquals('あいう', Str::toLower('あいう'));

        // utf-8 special chars
        self::assertEquals('çği̇öşü', Str::toLower('ÇĞİÖŞÜ'));

        // grapheme (nothing happens)
        self::assertEquals('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::toLower('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
    }

    public function test_toUpper(): void
    {
        // empty (nothing happens)
        self::assertEquals('', Str::toUpper(''));

        // basic
        self::assertEquals('ABC', Str::toUpper('abc'));

        // utf-8 chars (nothing happens)
        self::assertEquals('あいう', Str::toUpper('あいう'));

        // utf-8 special chars
        self::assertEquals('ÇĞİÖŞÜ', Str::toUpper('çği̇öşü'));

        // grapheme (nothing happens)
        self::assertEquals('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::toLower('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
    }

    public function test_trim(): void
    {
        // empty (nothing happens)
        self::assertEquals('', Str::trim(''));

        // left only
        self::assertEquals('a', Str::trim("\ta"));

        // right only
        self::assertEquals('a', Str::trim("a\t"));

        // new line on both ends
        self::assertEquals('abc', Str::trim("\nabc\n"));

        // tab and mixed line on both ends
        self::assertEquals('abc', Str::trim("\t\nabc\n\t"));

        // grapheme (nothing happens)
        self::assertEquals('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::trim('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿'));

        // custom
        self::assertEquals('b', Str::trim('aba', 'a'));

        // custom overrides delimiter
        self::assertEquals("\nb\n", Str::trim("a\nb\na", 'a'));

        // custom multiple
        self::assertEquals('b', Str::trim("_ab_a_", 'a_'));
    }

    public function test_trimEnd(): void
    {
        // empty (nothing happens)
        self::assertEquals('', Str::trimEnd(''));

        // left only
        self::assertEquals("\ta", Str::trimEnd("\ta"));

        // right only
        self::assertEquals('a', Str::trimEnd("a\t"));

        // new line on both ends
        self::assertEquals("\nabc", Str::trimEnd("\nabc\n"));

        // tab and mixed line on both ends
        self::assertEquals('abc', Str::trimEnd("abc\n\t"));

        // grapheme (nothing happens)
        self::assertEquals('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::trimEnd('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿'));

        // custom
        self::assertEquals('ab', Str::trimEnd('aba', 'a'));

        // custom overrides delimiter
        self::assertEquals("ab\n", Str::trimEnd("ab\na", 'a'));

        // custom multiple
        self::assertEquals('_ab', Str::trimEnd("_ab_a_", 'a_'));
    }

    public function test_trimStart(): void
    {
        // empty (nothing happens)
        self::assertEquals('', Str::trimStart(''));

        // left only
        self::assertEquals("a", Str::trimStart("\ta"));

        // right only
        self::assertEquals("a\t", Str::trimStart("a\t"));

        // new line on both ends
        self::assertEquals("abc\n", Str::trimStart("\nabc\n"));

        // tab and mixed line on both ends
        self::assertEquals('abc', Str::trimStart("\n\tabc"));

        // grapheme (nothing happens)
        self::assertEquals('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', Str::trimStart('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿'));

        // custom
        self::assertEquals('ba', Str::trimStart('aba', 'a'));

        // custom overrides delimiter
        self::assertEquals("\nba", Str::trimStart("a\nba", 'a'));

        // custom multiple
        self::assertEquals('b_a_', Str::trimStart("_ab_a_", 'a_'));
    }

    public function test_ucFirst(): void
    {
        self::assertEquals('', Str::ucFirst(''));
        self::assertEquals('Test', Str::ucFirst('test'));
        self::assertEquals('T t', Str::ucFirst('t t'));
        self::assertEquals('T T', Str::ucFirst('T T'));
        self::assertEquals(' t ', Str::ucFirst(' t '));
        self::assertEquals('É', Str::ucFirst('é'));
        self::assertEquals('🔡', Str::ucFirst('🔡'));
    }

    public function test_wordWrap(): void
    {
        self::assertEquals('', Str::wordWrap(''));

        // nowrap
        self::assertEquals('aaa bbb', Str::wordWrap('aaa bbb'));

        // default width is 80
        $repeated = Str::repeat('a', 80);
        self::assertEquals($repeated."\na", Str::wordWrap($repeated. 'a'));

        // change width
        self::assertEquals("wrap\naround", Str::wordWrap('wrap around', 1, overflow: true));

        // allow overflow
        self::assertEquals("wra\np\naro\nund", Str::wordWrap('wrap around', 3));

        // change
        self::assertEquals("wrap<br>around", Str::wordWrap('wrap around', 6, '<br>'));
    }
}