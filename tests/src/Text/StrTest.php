<?php declare(strict_types=1);

namespace Tests\Kirameki\Text;

use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Testing\TestCase;
use Kirameki\Text\Exceptions\NoMatchException;
use Kirameki\Text\Exceptions\ParseException;
use Kirameki\Text\Str;
use Kirameki\Text\StrObject;
use function array_shift;
use function str_repeat;
use function strlen;
use const PHP_EOL;
use const STR_PAD_LEFT;

class StrTest extends TestCase
{
    protected static Str $ref;

    protected function setUp(): void
    {
        parent::setUp();
        self::$ref = new Str();
    }

    public function test_of(): void
    {
        $this->assertSame('', self::$ref::of('')->toString());
        $this->assertSame('abc', self::$ref::of('abc')->toString());
        $this->assertInstanceOf(StrObject::class, self::$ref::of(''));
    }

    public function test_between(): void
    {
        $this->assertSame('1', self::$ref::between('test(1)', '(', ')'), 'basic');
        $this->assertSame('', self::$ref::between('()', '(', ')'), 'match edge: nothing in between');
        $this->assertSame('1', self::$ref::between('(1)', '(', ')'), 'match edge: char in between');
        $this->assertSame('test)', self::$ref::between('test)', '(', ')'), 'missing from');
        $this->assertSame('test(', self::$ref::between('test(', '(', ')'), 'missing to');
        $this->assertSame('test(1', self::$ref::between('(test(1))', '(', ')'), 'nested');
        $this->assertSame('1', self::$ref::between('(1) to (2)', '(', ')'), 'multi occurrence');
        $this->assertSame('_ab_', self::$ref::between('ab_ab_ba_ba', 'ab', 'ba'), 'multi char');
        $this->assertSame('い', self::$ref::between('あいういう', 'あ', 'う'), 'utf8');
        $this->assertSame('😃', self::$ref::between('👋🏿😃👋🏿😃👋🏿', '👋🏿', '👋🏿'), 'substring is grapheme');
        $this->assertSame('', self::$ref::between('👋🏿', '👋', '🏿'), 'grapheme between codepoints');
    }

    public function test_between_empty_from(): void
    {
        $this->expectExceptionMessage('$from must not be empty.');
        self::$ref::between('test)', '', ')');
    }

    public function test_between_empty_to(): void
    {
        $this->expectExceptionMessage('$to must not be empty.');
        self::$ref::between('test)', '(', '');
    }

    public function test_between_empty_from_and_to(): void
    {
        $this->expectExceptionMessage('$from must not be empty.');
        self::$ref::between('test)', '', '');
    }

    public function test_betweenFurthest(): void
    {
        $this->assertSame('1', self::$ref::betweenFurthest('test(1)', '(', ')'), 'basic');
        $this->assertSame('', self::$ref::betweenFurthest('()', '(', ')'), 'match edge: nothing in between');
        $this->assertSame('1', self::$ref::betweenFurthest('(1)', '(', ')'), 'match edge: char in between');
        $this->assertSame('test)', self::$ref::betweenFurthest('test)', '(', ')'), 'missing from');
        $this->assertSame('test(', self::$ref::betweenFurthest('test(', '(', ')'), 'missing to');
        $this->assertSame('test(1)', self::$ref::betweenFurthest('(test(1))', '(', ')'), 'nested');
        $this->assertSame('1) to (2', self::$ref::betweenFurthest('(1) to (2)', '(', ')'), 'multi occurrence');
        $this->assertSame('_', self::$ref::betweenFurthest('ab_ba', 'ab', 'ba'), 'multi char');
        $this->assertSame('い', self::$ref::betweenFurthest('あいう', 'あ', 'う'), 'utf8');
        $this->assertSame('😃', self::$ref::betweenFurthest('👋🏿😃👋🏿😃', '👋🏿', '👋🏿'), 'grapheme');
        $this->assertSame('', self::$ref::betweenFurthest('👋🏿', '👋', '🏿'), 'grapheme between codepoints');
    }

    public function test_betweenFurthest_empty_from(): void
    {
        $this->expectExceptionMessage('$from must not be empty.');
        self::$ref::betweenFurthest('test)', '', ')');
    }

    public function test_betweenFurthest_empty_to(): void
    {
        $this->expectExceptionMessage('$to must not be empty.');
        self::$ref::betweenFurthest('test)', '(', '');
    }

    public function test_betweenFurthest_empty_from_and_to(): void
    {
        $this->expectExceptionMessage('$from must not be empty.');
        self::$ref::betweenFurthest('test)', '', '');
    }

    public function test_betweenLast(): void
    {
        $this->assertSame('1', self::$ref::betweenLast('test(1)', '(', ')'), 'basic');
        $this->assertSame('', self::$ref::betweenLast('()', '(', ')'), 'match edge: nothing in between');
        $this->assertSame('1', self::$ref::betweenLast('(1)', '(', ')'), 'match edge: char in between');
        $this->assertSame('test)', self::$ref::betweenLast('test)', '(', ')'), 'missing from');
        $this->assertSame('test(', self::$ref::betweenLast('test(', '(', ')'), 'missing to');
        $this->assertSame('1)', self::$ref::betweenLast('(test(1))', '(', ')'), 'nested');
        $this->assertSame('2', self::$ref::betweenLast('(1) to (2)', '(', ')'), 'multi occurrence');
        $this->assertSame('_ba_', self::$ref::betweenLast('ab_ab_ba_ba', 'ab', 'ba'), 'multi char');
        $this->assertSame('いうい', self::$ref::betweenLast('あいういう', 'あ', 'う'), 'utf8');
        $this->assertSame('🥹', self::$ref::betweenLast('👋🏿😃👋🏿🥹👋', '👋🏿', '👋'), 'grapheme');
        $this->assertSame('', self::$ref::betweenLast('👋🏿', '👋', '🏿'), 'grapheme between codepoints');
    }

    public function test_betweenLast_empty_from(): void
    {
        $this->expectExceptionMessage('$from must not be empty.');
        self::$ref::betweenLast('test)', '', ')');
    }

    public function test_betweenLast_empty_to(): void
    {
        $this->expectExceptionMessage('$to must not be empty.');
        self::$ref::betweenLast('test)', '(', '');
    }

    public function test_betweenLast_empty_from_and_to(): void
    {
        $this->expectExceptionMessage('$from must not be empty.');
        self::$ref::betweenLast('test)', '', '');
    }

    public function test_capitalize(): void
    {
        $this->assertSame('', self::$ref::capitalize(''), 'empty');
        $this->assertSame('TT', self::$ref::capitalize('TT'), 'all uppercase');
        $this->assertSame('Test', self::$ref::capitalize('test'), 'lowercase');
        $this->assertSame('Test abc', self::$ref::capitalize('test abc'), 'lowercase with spaces');
        $this->assertSame(' test abc', self::$ref::capitalize(' test abc'), 'lowercase with spaces and leading space');
        $this->assertSame('àbc', self::$ref::capitalize('àbc'), 'lowercase with accent');
        $this->assertSame('é', self::$ref::capitalize('é'), 'lowercase with accent');
        $this->assertSame('ゅ', self::$ref::capitalize('ゅ'), 'lowercase with hiragana');
        $this->assertSame('🏴󠁧󠁢󠁳󠁣󠁴󠁿', self::$ref::capitalize('🏴󠁧󠁢󠁳󠁣󠁴󠁿'), 'lowercase with emoji');
    }

    public function test_chunk(): void
    {
        $this->assertSame([], self::$ref::chunk('', 5), 'empty');
        $this->assertSame(['ab'], self::$ref::chunk('ab', 5), 'oversize');
        $this->assertSame(['ab'], self::$ref::chunk('ab', 2), 'exact');
        $this->assertSame(['ab', 'c'], self::$ref::chunk('abc', 2), 'fragment');
        $this->assertSame(['あ', 'い', 'う'], self::$ref::chunk('あいう', 3), 'utf8');
        $this->assertSame(['ab', 'cd', 'efg'], self::$ref::chunk('abcdefg', 2, 2), 'limit');

        $chunked = self::$ref::chunk('あ', 2);
        $this->assertSame(2, strlen($chunked[0]), 'invalid');
        $this->assertSame(1, strlen($chunked[1]), 'invalid');
    }

    public function test_chunk_with_invalid_size(): void
    {
        $this->expectExceptionMessage('Expected: $size >= 1. Got: 0.');
        self::$ref::chunk('abc', 0);
    }

    public function test_chunk_with_invalid_limit(): void
    {
        $this->expectExceptionMessage('Expected: $limit >= 0. Got: -1.');
        self::$ref::chunk('abc', 2, -1);
    }

    public function test_concat(): void
    {
        $this->assertSame('', self::$ref::concat('', '', ''), 'empty');
        $this->assertSame(' ', self::$ref::concat('', ' '), 'blank');
        $this->assertSame('', self::$ref::concat(), 'no arg');
        $this->assertSame('a', self::$ref::concat('a'), 'one arg');
        $this->assertSame('abc', self::$ref::concat('a', 'b', 'c'), 'basic');
        $this->assertSame('あい', self::$ref::concat('あ', 'い'), 'mb string');
        $this->assertSame('👋🏿', self::$ref::concat('👋', '🏿'), 'mb string');
    }

    public function test_contains(): void
    {
        $this->assertTrue(self::$ref::contains('abcde', ''), 'empty needle');
        $this->assertTrue(self::$ref::contains('', ''), 'empty haystack and needle');
        $this->assertTrue(self::$ref::contains('abcde', 'ab'), 'partial first');
        $this->assertTrue(self::$ref::contains('abcde', 'cd'), 'partial mid');
        $this->assertTrue(self::$ref::contains('abcde', 'de'), 'partial last');
        $this->assertFalse(self::$ref::contains('abc', ' a'), 'space pad left');
        $this->assertFalse(self::$ref::contains('abc', 'a '), 'space pad right');
        $this->assertTrue(self::$ref::contains('abc', 'abc'), 'full');
        $this->assertFalse(self::$ref::contains('ab', 'abc'), 'needle is longer');
        $this->assertTrue(self::$ref::contains('👨‍👨‍👧‍👧‍', '👨'), 'grapheme partial');
        $this->assertFalse(self::$ref::contains('👨‍👨‍👧‍👧‍abc', '👨‍👨‍👧‍👧‍ abc'), 'grapheme');
    }

    public function test_containsAll(): void
    {
        $this->assertTrue(self::$ref::containsAll('', []), 'empty substrings with blank');
        $this->assertTrue(self::$ref::containsAll('abc', []), 'empty substrings');
        $this->assertTrue(self::$ref::containsAll('', ['']), 'blank match blank');
        $this->assertTrue(self::$ref::containsAll('abcde', ['']), 'blank match string');
        $this->assertFalse(self::$ref::containsAll('abcde', ['a', 'z']), 'partial match first');
        $this->assertFalse(self::$ref::containsAll('abcde', ['z', 'a']), 'partial match last');
        $this->assertTrue(self::$ref::containsAll('abcde', ['a']), 'match single');
        $this->assertFalse(self::$ref::containsAll('abcde', ['z']), 'no match single');
        $this->assertTrue(self::$ref::containsAll('abcde', ['a', 'b']), 'match all first');
        $this->assertTrue(self::$ref::containsAll('abcde', ['c', 'b']), 'match all reversed');
        $this->assertFalse(self::$ref::containsAll('abcde', ['y', 'z']), 'no match all');
        $this->assertTrue(self::$ref::containsAll('👨‍👨‍👧‍👧‍', ['👨', '👧']), 'grapheme partial');
    }

    public function test_containsAny(): void
    {
        $this->assertTrue(self::$ref::containsAny('', []), 'blank and empty substrings');
        $this->assertTrue(self::$ref::containsAny('abcde', []), 'empty substrings');
        $this->assertTrue(self::$ref::containsAny('', ['']), 'blank match blank');
        $this->assertTrue(self::$ref::containsAny('abcde', ['']), 'blank matchs anything');
        $this->assertTrue(self::$ref::containsAny('abcde', ['a', 'z']), 'one match of many (first one matched)');
        $this->assertTrue(self::$ref::containsAny('abcde', ['z', 'a']), 'one match of many (last one matched)');
        $this->assertTrue(self::$ref::containsAny('abcde', ['a']), 'match single');
        $this->assertFalse(self::$ref::containsAny('abcde', ['z']), 'no match single');
        $this->assertFalse(self::$ref::containsAny('abcde', ['y', 'z']), 'no match all');
        $this->assertTrue(self::$ref::containsAny('👨‍👨‍👧‍👧‍', ['👨', '🐌']), 'grapheme partial');
        $this->assertFalse(self::$ref::containsAny('👨‍👨‍👧‍👧‍', ['👀', '🐌']), 'grapheme no match');
    }

    public function test_containsNone(): void
    {
        $this->assertTrue(self::$ref::containsNone('', []), 'blank and empty substrings');
        $this->assertTrue(self::$ref::containsNone('abcde', []), 'empty substrings');
        $this->assertFalse(self::$ref::containsNone('', ['']), 'blank match blank');
        $this->assertFalse(self::$ref::containsNone('abcde', ['']), 'blank matchs anything');
        $this->assertFalse(self::$ref::containsNone('abcde', ['a', 'z']), 'one match of many (first one matched)');
        $this->assertFalse(self::$ref::containsNone('abcde', ['z', 'a']), 'one match of many (last one matched)');
        $this->assertFalse(self::$ref::containsNone('abcde', ['a']), 'match single');
        $this->assertTrue(self::$ref::containsNone('abcde', ['z']), 'no match single');
        $this->assertTrue(self::$ref::containsNone('abcde', ['y', 'z']), 'no match all');
        $this->assertFalse(self::$ref::containsNone('👨‍👨‍👧‍👧‍', ['👀', '👨']), 'grapheme partial');
        $this->assertTrue(self::$ref::containsNone('👨‍👨‍👧‍👧‍', ['👀', '🐌']), 'grapheme no match');
    }

    public function test_containsPattern(): void
    {
        $this->assertTrue(self::$ref::containsPattern('abc', '/b/'));
        $this->assertTrue(self::$ref::containsPattern('abc', '/ab/'));
        $this->assertTrue(self::$ref::containsPattern('abc', '/abc/'));
        $this->assertTrue(self::$ref::containsPattern('ABC', '/abc/i'));
        $this->assertTrue(self::$ref::containsPattern('aaaz', '/a{3}/'));
        $this->assertTrue(self::$ref::containsPattern('ABC1', '/[A-z\d]+/'));
        $this->assertTrue(self::$ref::containsPattern('ABC1]', '/\d]$/'));
        $this->assertFalse(self::$ref::containsPattern('AB1C', '/\d]$/'));
        $this->assertTrue(self::$ref::containsPattern('👨‍👨‍👧‍👧‍', '/👨/'));
    }

    public function test_containsPattern_warning_as_error(): void
    {
        $this->expectWarningMessage('preg_match(): Unknown modifier \'a\'');
        $this->assertFalse(self::$ref::containsPattern('', '/a/a'));
    }

    public function test_count(): void
    {
        $this->assertSame(0, self::$ref::count('', 'aaa'), 'empty string');
        $this->assertSame(1, self::$ref::count('abc', 'abc'), 'exact match');
        $this->assertSame(0, self::$ref::count('ab', 'abc'), 'no match');
        $this->assertSame(1, self::$ref::count('This is a cat', ' is '), 'single match');
        $this->assertSame(2, self::$ref::count('This is a cat', 'is'), 'multi match');
        $this->assertSame(2, self::$ref::count('abababa', 'aba'), 'no overlapping');
        $this->assertSame(2, self::$ref::count('あいあ', 'あ'), 'utf8');
        $this->assertSame(1, self::$ref::count('あああ', 'ああ'), 'utf8 no overlapping');
        $this->assertSame(0, self::$ref::count('ア', 'ｱ'), 'check half-width is not counted.');
        $this->assertSame(1, self::$ref::count('👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦'), 'grapheme');
        $this->assertSame(2, self::$ref::count('👨‍👨‍👧‍👦', '👨'), 'grapheme subset will match');
        $this->assertSame(3, self::$ref::count('abababa', 'aba', true), 'overlapping');
        $this->assertSame(2, self::$ref::count('あああ', 'ああ', true), 'utf8 overlapping');
        $this->assertSame(2, self::$ref::count('👨‍👨‍👧‍👦👨‍👨‍👧‍👦👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦👨‍👨‍👧‍👦', true), 'grapheme overlapping');
    }

    public function test_count_with_empty_search(): void
    {
        $this->expectExceptionMessage('$substring must not be empty.');
        $this->assertFalse(self::$ref::count('a', ''));
    }

    public function test_decapitalize(): void
    {
        $this->assertSame('', self::$ref::decapitalize(''));
        $this->assertSame('test', self::$ref::decapitalize('Test'));
        $this->assertSame('t T', self::$ref::decapitalize('T T'));
        $this->assertSame(' T ', self::$ref::decapitalize(' T '));
        $this->assertSame('Éé', self::$ref::decapitalize('Éé'));
        $this->assertSame('🔡', self::$ref::decapitalize('🔡'));
    }

    public function test_doesNotContain(): void
    {
        $this->assertTrue(self::$ref::doesNotContain('abcde', 'ac'));
        $this->assertFalse(self::$ref::doesNotContain('abcde', 'ab'));
        $this->assertFalse(self::$ref::doesNotContain('a', ''));
        $this->assertTrue(self::$ref::doesNotContain('', 'a'));
        $this->assertFalse(self::$ref::doesNotContain('👨‍👨‍👧‍👧‍', '👨'));
    }

    public function test_doesNotEndWith(): void
    {
        $this->assertFalse(self::$ref::doesNotEndWith('abc', 'c'));
        $this->assertTrue(self::$ref::doesNotEndWith('abc', 'b'));
        $this->assertFalse(self::$ref::doesNotEndWith('aabbcc', 'cc'));
        $this->assertFalse(self::$ref::doesNotEndWith('aabbcc' . PHP_EOL, PHP_EOL));
        $this->assertFalse(self::$ref::doesNotEndWith('abc0', '0'));
        $this->assertFalse(self::$ref::doesNotEndWith('abcfalse', 'false'));
        $this->assertFalse(self::$ref::doesNotEndWith('a', ''));
        $this->assertFalse(self::$ref::doesNotEndWith('', ''));
        $this->assertFalse(self::$ref::doesNotEndWith('あいう', 'う'));
        $this->assertTrue(self::$ref::doesNotEndWith("あ\n", 'あ'));
        $this->assertFalse(self::$ref::doesNotEndWith('👋🏻', '🏻'));
    }

    public function test_doesNotStartWith(): void
    {
        $this->assertFalse(self::$ref::doesNotStartWith('', ''));
        $this->assertFalse(self::$ref::doesNotStartWith('bb', ''));
        $this->assertFalse(self::$ref::doesNotStartWith('bb', 'b'));
        $this->assertTrue(self::$ref::doesNotStartWith('bb', 'ab'));
        $this->assertFalse(self::$ref::doesNotStartWith('あ-い-う', 'あ'));
        $this->assertTrue(self::$ref::doesNotStartWith('あ-い-う', 'え'));
        $this->assertFalse(self::$ref::doesNotStartWith('👨‍👨‍👧‍👦', '👨‍'));
        $this->assertFalse(self::$ref::doesNotStartWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
        $this->assertTrue(self::$ref::doesNotStartWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦'));
        $this->assertFalse(self::$ref::doesNotStartWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a'));
        $this->assertTrue(self::$ref::doesNotStartWith('ba', 'a'));
        $this->assertTrue(self::$ref::doesNotStartWith('', 'a'));
        $this->assertTrue(self::$ref::doesNotStartWith("\nあ", 'あ'));
    }

    public function test_dropFirst(): void
    {
        $this->assertSame('', self::$ref::dropFirst('', 1), 'empty');
        $this->assertSame('a', self::$ref::dropFirst('a', 0), 'zero amount');
        $this->assertSame('e', self::$ref::dropFirst('abcde', 4), 'mid amount');
        $this->assertSame('', self::$ref::dropFirst('abc', 3), 'exact amount');
        $this->assertSame('', self::$ref::dropFirst('abc', 4), 'over overflow');
        $this->assertSame('👦', self::$ref::dropFirst('👨‍👨‍👧‍👦', 21), 'grapheme');
        $this->assertSame('🏿', self::$ref::dropFirst('👋🏿', 4), 'grapheme cluster (positive)');
    }

    public function test_dropFirst_negative_amount(): void
    {
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -4.');
        self::$ref::dropFirst('abc', -4);
    }

    public function test_dropLast(): void
    {
        $this->assertSame('', self::$ref::dropLast('', 1), 'empty');
        $this->assertSame('a', self::$ref::dropLast('a', 0), 'zero length');
        $this->assertSame('ab', self::$ref::dropLast('abc', 1), 'mid amount');
        $this->assertSame('', self::$ref::dropLast('abc', 3), 'exact amount');
        $this->assertSame('', self::$ref::dropLast('abc', 4), 'overflow');
        $this->assertSame('👨‍👨‍👧‍', self::$ref::dropLast('👨‍👨‍👧‍👦', 4), 'grapheme');
        $this->assertSame('👋', self::$ref::dropLast('👋🏿', 4), 'grapheme cluster (positive)');
    }

    public function test_dropLast_negative_amount(): void
    {
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -4.');
        self::$ref::dropLast('abc', -4);
    }

    public function test_endsWith(): void
    {
        $this->assertTrue(self::$ref::endsWith('abc', 'c'), 'single hit');
        $this->assertFalse(self::$ref::endsWith('abc', 'b'), 'single miss');
        $this->assertTrue(self::$ref::endsWith('aabbcc', 'cc'), 'multiple occurrence string');
        $this->assertTrue(self::$ref::endsWith('aabbcc' . PHP_EOL, PHP_EOL), 'newline');
        $this->assertTrue(self::$ref::endsWith('abc0', '0'), 'zero');
        $this->assertTrue(self::$ref::endsWith('abcfalse', 'false'), 'false');
        $this->assertTrue(self::$ref::endsWith('a', ''), 'empty needle');
        $this->assertTrue(self::$ref::endsWith('', ''), 'empty haystack and needle');
        $this->assertTrue(self::$ref::endsWith('あいう', 'う'), 'utf8');
        $this->assertFalse(self::$ref::endsWith("あ\n", 'あ'), 'utf8 newline');
        $this->assertTrue(self::$ref::endsWith('👋🏻', '🏻'), 'grapheme');
    }

    public function test_endsWithAny(): void
    {
        $this->assertTrue(self::$ref::endsWithAny('abc', ['c']), 'array hit');
        $this->assertTrue(self::$ref::endsWithAny('abc', ['a', 'b', 'c']), 'array hit with misses');
        $this->assertFalse(self::$ref::endsWithAny('abc', ['a', 'b']), 'array miss');
        $this->assertTrue(self::$ref::endsWithAny('👋🏿', ['🏿', 'a']), 'array miss');
    }

    public function test_endsWithNone(): void
    {
        $this->assertFalse(self::$ref::endsWithNone('abc', ['c']));
        $this->assertFalse(self::$ref::endsWithNone('abc', ['a', 'b', 'c']));
        $this->assertTrue(self::$ref::endsWithNone('abc', ['a', 'b']));
        $this->assertfalse(self::$ref::endsWithNone('👋🏿', ['🏿', 'a']));
    }

    public function test_equals(): void
    {
        $this->assertTrue(self::$ref::equals('', ''), 'empty');
        $this->assertTrue(self::$ref::equals('abc', 'abc'), 'basic');
        $this->assertFalse(self::$ref::equals('abc', 'ABC'), 'case sensitive');
        $this->assertFalse(self::$ref::equals('abc', 'ab'), 'shorter');
        $this->assertFalse(self::$ref::equals('abc', 'abcd'), 'longer');
        $this->assertFalse(self::$ref::equals('abc', 'abc '), 'space');
    }

    public function test_equalsAny(): void
    {
        $this->assertTrue(self::$ref::equalsAny('abc', ['abc']), 'basic');
        $this->assertTrue(self::$ref::equalsAny('abc', ['abc', 'abc']), 'all hit');
        $this->assertTrue(self::$ref::equalsAny('abc', ['abc', 'def']), 'basic with miss');
        $this->assertFalse(self::$ref::equalsAny('abc', ['ABC']), 'case sensitive');
        $this->assertFalse(self::$ref::equalsAny('abc', ['ab']), 'shorter');
        $this->assertFalse(self::$ref::equalsAny('abc', ['abcd']), 'longer');
        $this->assertFalse(self::$ref::equalsAny('abc', ['abc ']), 'space');
    }

    public function test_indexOfFirst(): void
    {
        $this->assertNull(self::$ref::indexOfFirst('', 'a'), 'empty string');
        $this->assertSame(0, self::$ref::indexOfFirst('ab', ''), 'empty search');
        $this->assertSame(0, self::$ref::indexOfFirst('a', 'a'), 'find at 0');
        $this->assertSame(1, self::$ref::indexOfFirst('abb', 'b'), 'multiple matches');
        $this->assertSame(1, self::$ref::indexOfFirst('abb', 'b', 1), 'offset (within bound)');
        $this->assertSame(5, self::$ref::indexOfFirst('aaaaaa', 'a', 5), 'offset (within bound)');
        $this->assertNull(self::$ref::indexOfFirst('abb', 'b', 4), 'offset (out of bound)');
        $this->assertSame(2, self::$ref::indexOfFirst('abb', 'b', -1), 'offset (negative)');
        $this->assertNull(self::$ref::indexOfFirst('abb', 'b', -100), 'offset (negative)');
        $this->assertSame(0, self::$ref::indexOfFirst('👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦'), 'grapheme hit');
        $this->assertSame(0, self::$ref::indexOfFirst('👨‍👨‍👧‍👦', '👨'), 'grapheme hit subset');
        $this->assertSame(3, self::$ref::indexOfFirst('あいう', 'い', 1), 'utf8');
        $this->assertSame(28, self::$ref::indexOfFirst('🏴󠁧󠁢󠁳󠁣󠁴󠁿👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 1), 'grapheme hit with offset');
    }

    public function test_indexOfLast(): void
    {
        $this->assertNull(self::$ref::indexOfLast('', 'a'), 'empty string');
        $this->assertSame(2, self::$ref::indexOfLast('ab', ''), 'empty search');
        $this->assertSame(0, self::$ref::indexOfLast('a', 'a'), 'find at 0');
        $this->assertSame(2, self::$ref::indexOfLast('abb', 'b'), 'multiple matches');
        $this->assertSame(2, self::$ref::indexOfLast('abb', 'b', 1), 'offset (within bound)');
        $this->assertSame(5, self::$ref::indexOfLast('aaaaaa', 'a', 5), 'offset (within bound)');
        $this->assertNull(self::$ref::indexOfLast('abb', 'b', 4), 'offset (out of bound)');
        $this->assertSame(3, self::$ref::indexOfLast('abbb', 'b', -1), 'offset (negative)');
        $this->assertNull(self::$ref::indexOfLast('abb', 'b', -100), 'offset (negative)');
        $this->assertSame(0, self::$ref::indexOfLast('👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦'), 'utf-8');
        $this->assertSame(7, self::$ref::indexOfLast('👨‍👨‍👧‍👦', '👨'), 'utf-8');
        $this->assertSame(3, self::$ref::indexOfLast('あいう', 'い', 1), 'offset utf-8');
        $this->assertSame(28, self::$ref::indexOfLast('🏴󠁧󠁢󠁳󠁣󠁴󠁿👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 1), 'offset utf-8');
        $this->assertSame(null, self::$ref::indexOfLast('🏴󠁧󠁢󠁳󠁣󠁴󠁿👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 29), 'offset utf-8');
    }

    public function test_insertAt(): void
    {
        $this->assertSame('xyzabc', self::$ref::insertAt('abc', 'xyz', 0), 'at zero');
        $this->assertSame('axyzbc', self::$ref::insertAt('abc', 'xyz', 1), 'basic');
        $this->assertSame('xyzabc', self::$ref::insertAt('abc', 'xyz', -1), 'negative');
        $this->assertSame('abcxyz', self::$ref::insertAt('abc', 'xyz', 3), 'edge');
        $this->assertSame('abcxyz', self::$ref::insertAt('abc', 'xyz', 4), 'overflow');
        $this->assertSame('あxyzい', self::$ref::insertAt('あい', 'xyz', 3), 'utf8');
        $this->assertSame('xyzあい', self::$ref::insertAt('あい', 'xyz', -1), 'utf8 negative');
        $this->assertSame('👨x👨', self::$ref::insertAt('👨👨', 'x', 4), 'grapheme');
    }

    public function test_interpolate(): void
    {
        $this->assertSame('', self::$ref::interpolate('', ['a' => 1]), 'empty string');
        $this->assertSame('abc', self::$ref::interpolate('abc', []), 'no placeholder');
        $this->assertSame('{a}', self::$ref::interpolate('{a}', []), 'no match');
        $this->assertSame('1{b}', self::$ref::interpolate('{a}{b}', ['a' => 1]), 'one match');
        $this->assertSame('1 hi', self::$ref::interpolate('{a} hi', ['a' => 1]), 'replace edge');
        $this->assertSame('1 1', self::$ref::interpolate('{a} {a}', ['a' => 1]), 'replace twice');
        $this->assertSame('{b} 1', self::$ref::interpolate('{a} {b}', ['a' => '{b}', 'b' => 1]), 'replace multiple');
        $this->assertSame('{a1}', self::$ref::interpolate('{a{a}}', ['a' => 1]), 'nested v1');
        $this->assertSame('{1}', self::$ref::interpolate('{{a}}', ['a' => 1]), 'nested v2');
        $this->assertSame('\\{a}', self::$ref::interpolate('\\{a}', ['a' => 1]), 'escape start');
        $this->assertSame('{a\\}', self::$ref::interpolate('{a\\}', ['a' => 1]), 'escape end');
        $this->assertSame('\\1', self::$ref::interpolate('\\\\{a}', ['a' => 1]), 'don\'t escape double escape char');
        $this->assertSame('\\\\{a}', self::$ref::interpolate('\\\\\\{a}', ['a' => 1]), 'escape mixed with no escape');
        $this->assertSame('{a!}', self::$ref::interpolate('{a!}', ['a!' => 1]), 'only match ascii placeholder');
        $this->assertSame(' 1 ', self::$ref::interpolate(' {_a_b} ', ['_a_b' => 1]), 'allow under score');
        $this->assertSame('1', self::$ref::interpolate('<a>', ['a' => 1], '<', '>'), 'different delimiters');
        $this->assertSame('1.23', self::$ref::interpolate('{a:%.2f}', ['a' => 1.2345]), 'with formatting');
        $this->assertSame('005', self::$ref::interpolate('{a:%1$03d}', ['a' => 5]), 'with formatting');
        $this->assertSame('...5', self::$ref::interpolate('{a:%\'.4d}', ['a' => 5]), 'with formatting');
        $this->assertSame('{a:}', self::$ref::interpolate('{a:}', ['a' => 5]), 'empty formatting');
    }

    public function test_interpolate_non_list(): void
    {
        $this->expectExceptionMessage('Expected $replace to be a map. List given.');
        $this->expectException(InvalidArgumentException::class);
        self::$ref::interpolate('', [1, 2]);
    }

    public function test_interpolate_empty_delimiterStart(): void
    {
        $this->expectExceptionMessage('$delimiterStart and $delimiterEnd must not be empty.');
        $this->expectException(InvalidArgumentException::class);
        self::$ref::interpolate('', [1, 2], '');
    }

    public function test_interpolate_empty_delimiterEnd(): void
    {
        $this->expectExceptionMessage('$delimiterStart and $delimiterEnd must not be empty.');
        $this->expectException(InvalidArgumentException::class);
        self::$ref::interpolate('', [1, 2], '{', '');
    }

    public function test_isBlank(): void
    {
        $this->assertTrue(self::$ref::isBlank(''));
        $this->assertFalse(self::$ref::isBlank('0'));
        $this->assertFalse(self::$ref::isBlank(' '));
    }

    public function test_isNotBlank(): void
    {
        $this->assertFalse(self::$ref::isNotBlank(''));
        $this->assertTrue(self::$ref::isNotBlank('0'));
        $this->assertTrue(self::$ref::isNotBlank(' '));
    }

    public function test_length(): void
    {
        $this->assertSame(0, self::$ref::length(''), 'empty');
        $this->assertSame(4, self::$ref::length('Test'), 'ascii');
        $this->assertSame(9, self::$ref::length(' T e s t '), 'ascii');
        $this->assertSame(6, self::$ref::length('あい'), 'utf8');
        $this->assertSame(10, self::$ref::length('あいzう'), 'utf8');
        $this->assertSame(25, self::$ref::length('👨‍👨‍👧‍👦'), 'emoji');
    }

    public function test_matchAll(): void
    {
        $this->assertSame([['a', 'a']], self::$ref::matchAll('abcabc', '/a/'));
        $this->assertSame([['abc', 'abc'], 'p1' => ['a', 'a'], ['a', 'a']], self::$ref::matchAll('abcabc', '/(?<p1>a)bc/'));
        $this->assertSame([[]], self::$ref::matchAll('abcabc', '/bcd/'));
        $this->assertSame([['cd', 'c']], self::$ref::matchAll('abcdxabc', '/c[^x]*/'));
        $this->assertSame([[]], self::$ref::matchAll('abcabcx', '/^abcx/'));
        $this->assertSame([['cx']], self::$ref::matchAll('abcabcx', '/cx$/'));
    }

    public function test_matchAll_without_slashes(): void
    {
        $this->expectWarningMessage('preg_match_all(): Delimiter must not be alphanumeric, backslash, or NUL');
        self::$ref::matchAll('abcabc', 'a');
    }

    public function test_matchFirst(): void
    {
        $this->assertSame('a', self::$ref::matchFirst('abcabc', '/a/'));
        $this->assertSame('abc', self::$ref::matchFirst('abcabc', '/(?<p1>a)bc/'));
        $this->assertSame('cd', self::$ref::matchFirst('abcdxabc', '/c[^x]*/'));
        $this->assertSame('cx', self::$ref::matchFirst('abcabcx', '/cx$/'));
    }

    public function test_matchFirst_no_match(): void
    {
        $this->expectException(NoMatchException::class);
        $this->expectExceptionMessage('"aaa" does not match /z/');
        self::$ref::matchFirst('aaa', '/z/');
    }

    public function test_matchFirst_without_slashes(): void
    {
        $this->expectWarningMessage('preg_match(): Delimiter must not be alphanumeric, backslash, or NUL');
        self::$ref::matchFirst('abcabc', 'a');
    }

    public function test_matchFirstOrNull(): void
    {
        $this->assertSame('a', self::$ref::matchFirstOrNull('abcabc', '/a/'));
        $this->assertSame('abc', self::$ref::matchFirstOrNull('abcabc', '/(?<p1>a)bc/'));
        $this->assertSame(null, self::$ref::matchFirstOrNull('abcabc', '/bcd/'));
        $this->assertSame('cd', self::$ref::matchFirstOrNull('abcdxabc', '/c[^x]*/'));
        $this->assertSame(null, self::$ref::matchFirstOrNull('abcabcx', '/^abcx/'));
        $this->assertSame('cx', self::$ref::matchFirstOrNull('abcabcx', '/cx$/'));
    }

    public function test_matchFirstOrNull_without_slashes(): void
    {
        $this->expectWarningMessage('preg_match(): Delimiter must not be alphanumeric, backslash, or NUL');
        self::$ref::matchFirstOrNull('abcabc', 'a');
    }

    public function test_matchLast(): void
    {
        $this->assertSame('34', self::$ref::matchLast('12a34a', '/\d+/'));
        $this->assertSame('13', self::$ref::matchLast('1213', '/(?<p1>1)\d/'));
        $this->assertSame('c', self::$ref::matchLast('abcdxabc', '/c[^x]*/'));
        $this->assertSame('cx', self::$ref::matchLast('abcabcx', '/cx$/'));
    }

    public function test_matchLast_no_match(): void
    {
        $this->expectException(NoMatchException::class);
        $this->expectExceptionMessage('"aaa" does not match /z/');
        self::$ref::matchLast('aaa', '/z/');
    }

    public function test_matchLast_without_slashes(): void
    {
        $this->expectWarningMessage('preg_match_all(): Delimiter must not be alphanumeric, backslash, or NUL');
        self::$ref::matchLast('abcabc', 'a');
    }

    public function test_matchLastOrNull(): void
    {
        $this->assertSame('34', self::$ref::matchLastOrNull('12a34a', '/\d+/'));
        $this->assertSame('13', self::$ref::matchLastOrNull('1213', '/(?<p1>1)\d/'));
        $this->assertSame(null, self::$ref::matchLastOrNull('abcabc', '/bcd/'));
        $this->assertSame('c', self::$ref::matchLastOrNull('abcdxabc', '/c[^x]*/'));
        $this->assertSame(null, self::$ref::matchLastOrNull('abcabcx', '/^abcx/'));
        $this->assertSame('cx', self::$ref::matchLastOrNull('abcabcx', '/cx$/'));
    }

    public function test_matchLastOrNull_without_slashes(): void
    {
        $this->expectWarningMessage('preg_match_all(): Delimiter must not be alphanumeric, backslash, or NUL');
        self::$ref::matchLastOrNull('abcabc', 'a');
    }

    public function test_pad(): void
    {
        $this->assertSame('', self::$ref::pad('', -1, '_'), 'empty string');
        $this->assertSame('abc', self::$ref::pad('abc', 3, ''), 'pad string');
        $this->assertSame('a', self::$ref::pad('a', -1, '_'), 'defaults to pad right');
        $this->assertSame('a', self::$ref::pad('a', 0, '_'), 'zero length');
        $this->assertSame('a_', self::$ref::pad('a', 2, '_'), 'pad right');
        $this->assertSame('__', self::$ref::pad('_', 2, '_'), 'pad same char as given');
        $this->assertSame('ab', self::$ref::pad('ab', 1, '_'), 'length < string size');
        $this->assertSame('abcd', self::$ref::pad('a', 4, 'bcde'), 'overflow padding');
        $this->assertSame('あ_', self::$ref::pad('あ', 4, '_'), 'multi byte');
        $this->assertSame('👋🏿_', self::$ref::pad('👋🏿', 9, '_'), 'grapheme');
        $this->assertSame('_👋🏿', self::$ref::pad('👋🏿', 9, '_', STR_PAD_LEFT), 'Set type');
    }

    public function test_pad_invalid_pad(): void
    {
        $this->expectExceptionMessage('Unknown padding type: 3.');
        $this->expectException(InvalidArgumentException::class);
        $this->assertSame('ab', self::$ref::pad('ab', 1, '_', 3));
    }

    public function test_padBoth(): void
    {
        $this->assertSame('a', self::$ref::padBoth('a', -1, '_'));
        $this->assertSame('a', self::$ref::padBoth('a', 0, '_'));
        $this->assertSame('a_', self::$ref::padBoth('a', 2, '_'));
        $this->assertSame('__', self::$ref::padBoth('_', 2, '_'));
        $this->assertSame('_a_', self::$ref::padBoth('a', 3, '_'));
        $this->assertSame('__a__', self::$ref::padBoth('a', 5, '_'));
        $this->assertSame('__a___', self::$ref::padBoth('a', 6, '_'));
        $this->assertSame('12hello123', self::$ref::padBoth('hello', 10, '123'));
        $this->assertSame('あ', self::$ref::padEnd('あ', 3, '_'), 'multi byte no pad');
        $this->assertSame('あ_', self::$ref::padBoth('あ', 4, '_'), 'multi byte');
        $this->assertSame('👋🏿_', self::$ref::padBoth('👋🏿', 9, '_'), 'grapheme');
    }

    public function test_padEnd(): void
    {
        $this->assertSame('a', self::$ref::padEnd('a', -1, '_'));
        $this->assertSame('a', self::$ref::padEnd('a', 0, '_'));
        $this->assertSame('a_', self::$ref::padEnd('a', 2, '_'));
        $this->assertSame('__', self::$ref::padEnd('_', 2, '_'));
        $this->assertSame('ab', self::$ref::padEnd('ab', 1, '_'));
        $this->assertSame('あ', self::$ref::padEnd('あ', 3, '_'), 'multi byte no pad');
        $this->assertSame('あ_', self::$ref::padEnd('あ', 4, '_'), 'multi byte');
        $this->assertSame('👋🏿_', self::$ref::padEnd('👋🏿', 9, '_'), 'grapheme');
    }

    public function test_padStart(): void
    {
        $this->assertSame('a', self::$ref::padStart('a', -1, '_'));
        $this->assertSame('a', self::$ref::padStart('a', 0, '_'));
        $this->assertSame('_a', self::$ref::padStart('a', 2, '_'));
        $this->assertSame('__', self::$ref::padStart('_', 2, '_'));
        $this->assertSame('ab', self::$ref::padStart('ab', 1, '_'));
        $this->assertSame('あ', self::$ref::padStart('あ', 3, '_'), 'multi byte no pad');
        $this->assertSame('_あ', self::$ref::padStart('あ', 4, '_'), 'multi byte');
        $this->assertSame('_👋🏿', self::$ref::padStart('👋🏿', 9, '_'), 'grapheme');
    }

    public function test_range(): void
    {
        $this->assertSame('', self::$ref::range('', 0, 1), 'empty string');
        $this->assertSame('', self::$ref::range('abc', 0, 0), 'zero length');
        $this->assertSame('', self::$ref::range('1234', 1, 1));
        $this->assertSame('ab', self::$ref::range('abc', 0, -1), 'negative length');
        $this->assertSame('34', self::$ref::range('12345', -3, -1));
        $this->assertSame('23', self::$ref::range('1234', 1, 3));
        $this->assertSame('123', self::$ref::range('1234', -10, -1));
        $this->assertSame('', self::$ref::range('1234', -10, -9));
        $this->assertSame('', self::$ref::range('1234', -10, -10));
    }

    public function test_range_negative_length(): void
    {
        $this->expectExceptionMessage('$end: -2 cannot be > $start: -1.');
        $this->expectException(InvalidArgumentException::class);
        self::$ref::range('abc', -1, -2);
    }

    public function test_remove(): void
    {
        $this->assertSame('', self::$ref::remove('', ''), 'empty');
        $this->assertSame('', self::$ref::remove('aaa', 'a'), 'delete everything');
        $this->assertSame('a  a', self::$ref::remove('aaa aa a', 'aa'), 'no traceback check');
        $this->assertSame('no match', self::$ref::remove('no match', 'hctam on'), 'out of order chars');
        $this->assertSame('👋👋', self::$ref::remove('👋🏿👋🏿', '🏿'), 'dont delete grapheme code point');
        $this->assertSame('aa', self::$ref::remove('aa', 'a', 0), 'limit to 0');
        $this->assertSame('a', self::$ref::remove('aaa', 'a', 2), 'limit to 2');

        $count = 0;
        $this->assertSame('aaa', self::$ref::remove('aaa', 'a', 0, $count), 'count none');
        $this->assertSame(0, $count);

        $this->assertSame('a', self::$ref::remove('aaa', 'a', 2, $count), 'count several');
        $this->assertSame(2, $count);

        $this->assertSame('', self::$ref::remove('aaa', 'a', null, $count), 'count unlimited');
        $this->assertSame(3, $count);
    }

    public function test_remove_with_negative_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $limit >= 0. Got: -1.');
        self::$ref::remove('', '', -1);
    }

    public function test_removeFirst(): void
    {
        $this->assertSame('', self::$ref::removeFirst('', ''), 'empty');
        $this->assertSame('', self::$ref::removeFirst('', 'abc'), 'empty string');
        $this->assertSame('abc', self::$ref::removeFirst('abc', ''), 'empty substring');
        $this->assertSame('bac', self::$ref::removeFirst('abac', 'a'), 'delete first');
        $this->assertSame('👋👋🏿', self::$ref::removeFirst('👋🏿👋🏿', '🏿'), 'dont delete grapheme code point');
    }

    public function test_removeLast(): void
    {
        $this->assertSame('', self::$ref::removeLast('', ''), 'empty');
        $this->assertSame('', self::$ref::removeLast('', 'abc'), 'empty string');
        $this->assertSame('abc', self::$ref::removeLast('abc', ''), 'empty substring');
        $this->assertSame('abc', self::$ref::removeLast('abac', 'a'), 'delete last');
        $this->assertSame('👋🏿👋', self::$ref::removeLast('👋🏿👋🏿', '🏿'), 'dont delete grapheme code point');
    }

    public function test_repeat(): void
    {
        $this->assertSame('aaa', self::$ref::repeat('a', 3), 'ascii');
        $this->assertSame('あああ', self::$ref::repeat('あ', 3), 'multi byte');
        $this->assertSame('👋🏿👋🏿👋🏿', self::$ref::repeat('👋🏿', 3), 'grapheme');
        $this->assertSame('', self::$ref::repeat('a', 0), 'zero');
    }

    public function test_repeat_negative_times(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $times >= 0. Got: -1.');
        self::$ref::repeat('a', -1);
    }

    public function test_replace(): void
    {
        $this->assertSame('', self::$ref::replace('', '', ''), 'empty string');
        $this->assertSame('b', self::$ref::replace('b', '', 'a'), 'empty search');
        $this->assertSame('aa', self::$ref::replace('bb', 'b', 'a'), 'basic');
        $this->assertSame('', self::$ref::replace('b', 'b', ''), 'empty replacement');
        $this->assertSame('あえいえう', self::$ref::replace('あ-い-う', '-', 'え'), 'mbstring');
        $this->assertSame('__🏴󠁧󠁢󠁳󠁣󠁴󠁿', self::$ref::replace('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a', '_'), 'multiple codepoints');
        $this->assertSame('abc', self::$ref::replace('ab\c', '\\', ''), 'escape char');
        $this->assertSame('abc', self::$ref::replace('abc.*', '.*', ''), 'regex chars');
        $this->assertSame('a', self::$ref::replace('[]/\\!?', '[]/\\!?', 'a'), 'regex chars');

        $count = 0;
        $this->assertSame('a', self::$ref::replace('aaa', 'a', '', 2, $count), 'with limit and count');
        $this->assertSame(2, $count, 'with limit and count');

        $count = 0;
        $this->assertSame('', self::$ref::replace('', '', '', null, $count), '0 count for no match');
        $this->assertSame(0, $count, '0 count for no match');

        $this->assertSame('🏿', self::$ref::replace('👋🏿', '👋', ''), 'grapheme');
    }

    public function test_replace_with_negative_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $limit >= 0. Got: -1.');
        self::$ref::replace('', 'a', 'a', -1);
    }

    public function test_replaceFirst(): void
    {
        $this->assertSame('', self::$ref::replaceFirst('', '', ''), 'empty string');
        $this->assertSame('bb', self::$ref::replaceFirst('bb', '', 'a'), 'empty search');
        $this->assertSame('abb', self::$ref::replaceFirst('bbb', 'b', 'a'), 'basic');
        $this->assertSame('b', self::$ref::replaceFirst('bb', 'b', ''), 'empty replacement');
        $this->assertSame('あえい-う', self::$ref::replaceFirst('あ-い-う', '-', 'え'), 'mbstring');
        $this->assertSame('🏴󠁧󠁢󠁳󠁣󠁴󠁿 a', self::$ref::replaceFirst('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 'a'), 'multiple codepoints');
        $this->assertSame('_🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', self::$ref::replaceFirst('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a', '_'));
        $this->assertSame('🏿', self::$ref::replaceFirst('👋🏿', '👋', ''), 'grapheme');

        $replaced = false;
        self::$ref::replaceFirst('bbb', 'b', 'a', $replaced);
        $this->assertTrue($replaced, 'validate flag');

        $replaced = true;
        self::$ref::replaceFirst('b', 'z', '', $replaced);
        $this->assertFalse($replaced, 'flag is overridden with false');
    }

    public function test_replaceLast(): void
    {
        $this->assertSame('', self::$ref::replaceLast('', '', ''), 'empty string');
        $this->assertSame('bb', self::$ref::replaceLast('bb', '', 'a'), 'empty search');
        $this->assertSame('bba', self::$ref::replaceLast('bbb', 'b', 'a'), 'basic');
        $this->assertSame('b', self::$ref::replaceLast('bb', 'b', ''), 'empty replacement');
        $this->assertSame('あ-いえう', self::$ref::replaceLast('あ-い-う', '-', 'え'), 'mbstring');
        $this->assertSame('🏴󠁧󠁢󠁳󠁣󠁴󠁿 a', self::$ref::replaceLast('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦', 'a'), 'multiple codepoints');
        $this->assertSame('🏴󠁧󠁢󠁳󠁣󠁴󠁿a_🏴󠁧󠁢󠁳󠁣󠁴󠁿', self::$ref::replaceLast('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a', '_'));
        $this->assertSame('🏿', self::$ref::replaceLast('👋🏿', '👋', ''), 'grapheme');

        $replaced = false;
        self::$ref::replaceLast('bbb', 'b', 'a', $replaced);
        $this->assertTrue($replaced, 'validate flag');

        $replaced = true;
        self::$ref::replaceLast('b', 'z', '', $replaced);
        $this->assertFalse($replaced, 'flag is overridden with false');
    }

    public function test_replaceMatch(): void
    {
        $this->assertSame('', self::$ref::replaceMatch('', '', ''));
        $this->assertSame('abb', self::$ref::replaceMatch('abc', '/c/', 'b'));
        $this->assertSame('abbb', self::$ref::replaceMatch('abcc', '/c/', 'b'));
        $this->assertSame('あいい', self::$ref::replaceMatch('あいう', '/う/', 'い'));
        $this->assertSame('x', self::$ref::replaceMatch('abcde', '/[A-Za-z]+/', 'x'));
        $this->assertSame('a👋-b', self::$ref::replaceMatch('a👋-b', '/🏿/', '-'), 'grapheme');

        $count = 0;
        $this->assertSame('', self::$ref::replaceMatch('', '', '', null, $count), 'check count: no match');
        $this->assertSame(0, $count, 'check count: no match');

        $count = 0;
        $this->assertSame('', self::$ref::replaceMatch('aaa', '/a/', '', null, $count), 'unlimited match');
        $this->assertSame(3, $count, 'unlimited match');

        $count = 1;
        $this->assertSame('', self::$ref::replaceMatch('aaa', '/a/', '', null, $count), 'counter is reset');
        $this->assertSame(3, $count, 'counter is reset');

        $this->assertSame('a', self::$ref::replaceMatch('aaa', '/a/', '', 2), 'limit to 2');
    }

    public function test_replaceMatch_with_negative_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $limit >= 0. Got: -1.');
        self::$ref::replaceMatch('', '/a/', 'a', -1);
    }

    public function test_replaceMatchWithCallback(): void
    {
        $this->assertSame('', Str::replaceMatchWithCallback('', '/./', fn() => 'b'));
        $this->assertSame('bbb', Str::replaceMatchWithCallback('abc', '/[ac]/', fn() => 'b'));

        $list = ['a', 'b', 'c'];
        $this->assertSame('a b c', self::$ref::replaceMatchWithCallback('? ? ?', '/\?/', function (array $m) use (&$list) {
            return array_shift($list) ?? '';
        }), 'with callback');
    }

    public function test_replaceMatchWithCallback_with_negative_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $limit >= 0. Got: -1.');
        self::$ref::replaceMatchWithCallback('', '/a/', fn() => 'a', -1);
    }

    public function test_reverse(): void
    {
        $this->assertSame('', self::$ref::reverse(''));
        $this->assertSame('ba', self::$ref::reverse('ab'));
        $this->assertSame("\x82\x81\xE3", self::$ref::reverse('あ'));
    }

    public function test_split(): void
    {
        $this->assertSame(['', ''], self::$ref::split(' ', ' '), 'empty');
        $this->assertSame(['abc'], self::$ref::split('abc', '_'), 'no match');
        $this->assertSame(['a', 'c', 'd'], self::$ref::split('abcbd', 'b'), 'match');
        $this->assertSame(['あ', 'う'], self::$ref::split('あいう', 'い'), 'match utf-8');
        $this->assertSame(['a', 'cbd'], self::$ref::split('abcbd', 'b', 2), 'match with limit');
        $this->assertSame(['a', 'b', 'c'], self::$ref::split('abc', ''), 'match with limit');
        $this->assertSame(['👨‍👨‍👧', ''], self::$ref::split('👨‍👨‍👧‍👦', '‍👦'), 'match emoji');
    }

    public function test_split_with_negative_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $limit >= 0. Got: -1.');
        self::$ref::split('a', 'b', -1);
    }

    public function test_splitMatch(): void
    {
        $this->assertSame(['abc'], self::$ref::splitMatch('abc', '/_/'), 'no match');
        $this->assertSame(['', '1', '', ''], self::$ref::splitMatch('a1bc', '/[a-z]/'), 'no match');
        $this->assertSame(['a', 'c', 'd'], self::$ref::splitMatch('abcbd', '/b/'), 'match');
        $this->assertSame(['あ', 'う'], self::$ref::splitMatch('あいう', '/い/'), 'match utf-8');
        $this->assertSame(['a', 'cbd'], self::$ref::splitMatch('abcbd', '/b/', 2), 'match with limit');
        $this->assertSame(['', 'a', 'b', 'c', ''], self::$ref::splitMatch('abc', '//'), 'match with limit');
        $this->assertSame(['', '🏿'], self::$ref::splitMatch('👋🏿', '/👋/'), 'match emoji');
    }

    public function test_splitMatch_negative_limit(): void
    {
        $this->expectExceptionMessage('Expected: $limit >= 0. Got: -1.');
        $this->expectException(InvalidArgumentException::class);
        self::$ref::splitMatch('a', '/b/', -1);
    }

    public function test_splitMatch_invalid_empty_pattern(): void
    {
        $this->expectWarningMessage('preg_split(): Empty regular expression');
        self::$ref::splitMatch('a', '', 1);
    }

    public function test_splitMatch_invalid_non_pattern(): void
    {
        $this->expectWarningMessage('preg_split(): Delimiter must not be alphanumeric, backslash, or NUL');
        self::$ref::splitMatch('a', 'a', 1);
    }

    public function test_splitMatch_invalid_no_matching_delimiter(): void
    {
        $this->expectWarningMessage('preg_split(): No ending matching delimiter \']\' found');
        self::$ref::splitMatch('a', '[', 1);
    }

    public function test_startsWith(): void
    {
        $this->assertTrue(self::$ref::startsWith('', ''));
        $this->assertTrue(self::$ref::startsWith('bb', ''));
        $this->assertTrue(self::$ref::startsWith('bb', 'b'));
        $this->assertTrue(self::$ref::startsWith('あ-い-う', 'あ'));
        $this->assertTrue(self::$ref::startsWith('👨‍👨‍👧‍👦', '👨‍'));
        $this->assertTrue(self::$ref::startsWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
        $this->assertFalse(self::$ref::startsWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿 👨‍👨‍👧‍👦', '👨‍👨‍👧‍👦'));
        $this->assertTrue(self::$ref::startsWith('🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿a🏴󠁧󠁢󠁳󠁣󠁴󠁿', '🏴󠁧󠁢󠁳󠁣󠁴󠁿a'));
        $this->assertFalse(self::$ref::startsWith('ba', 'a'));
        $this->assertFalse(self::$ref::startsWith('', 'a'));
    }

    public function test_startsWithAny(): void
    {
        $this->assertFalse(self::$ref::startsWithAny('abc', ['d', 'e']));
        $this->assertTrue(self::$ref::startsWithAny('abc', ['d', 'a']));
        $this->assertTrue(self::$ref::startsWithAny('👋🏿', ['👋', 'a']));
    }

    public function test_startsWithNone(): void
    {
        $this->assertTrue(self::$ref::startsWithNone('abc', ['d', 'e']));
        $this->assertFalse(self::$ref::startsWithNone('abc', ['d', 'a']));
        $this->assertFalse(self::$ref::startsWithNone('👋🏿', ['👋', 'a']));
    }

    public function test_substring(): void
    {
        $this->assertSame('', self::$ref::substring('', 0));
        $this->assertSame('', self::$ref::substring('', 0, 1));
        $this->assertSame('abc', self::$ref::substring('abc', 0));
        $this->assertSame('bc', self::$ref::substring('abc', 1));
        $this->assertSame('c', self::$ref::substring('abc', -1));
        $this->assertSame('a', self::$ref::substring('abc', 0, 1));
        $this->assertSame('b', self::$ref::substring('abc', 1, 1));
        $this->assertSame('b', self::$ref::substring('abc', -2, 1));
        $this->assertSame('bc', self::$ref::substring('abc', -2, 2));
        $this->assertSame('ab', self::$ref::substring('abc', -9999, 2));
        $this->assertSame('ab', self::$ref::substring('abc', 0, -1));
        $this->assertSame('a', self::$ref::substring('abc', 0, -2));
        $this->assertSame('', self::$ref::substring('abc', 0, -3));
        $this->assertSame('', self::$ref::substring('abc', 2, -1));
        $this->assertSame("\x81\x82", self::$ref::substring('あ', 1), 'utf-8');
        $this->assertSame('🏿', self::$ref::substring('👋🏿', 4), 'grapheme');
    }

    public function test_substringAfter(): void
    {
        $this->assertSame('est', self::$ref::substringAfter('test', 't'), 'match first');
        $this->assertSame('', self::$ref::substringAfter('test1', '1'), 'match last');
        $this->assertSame('test', self::$ref::substringAfter('test', ''), 'match empty string');
        $this->assertSame('test', self::$ref::substringAfter('test', 'a'), 'no match');
        $this->assertSame('うえ', self::$ref::substringAfter('ああいうえ', 'い'), 'multi byte');
        $this->assertSame('def', self::$ref::substringAfter('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿def', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'), 'grapheme');
        $this->assertSame('🏿', self::$ref::substringAfter('👋🏿', '👋'), 'grapheme cluster');
    }

    public function test_substringAfterLast(): void
    {
        $this->assertSame('bc', self::$ref::substringAfterLast('abc', 'a'), 'match first (single occurrence)');
        $this->assertSame('1', self::$ref::substringAfterLast('test1', 't'), 'match first (multiple occurrence)');
        $this->assertSame('', self::$ref::substringAfterLast('test1', '1'), 'match last');
        $this->assertSame('Foo', self::$ref::substringAfterLast('----Foo', '---'), 'should match the last string');
        $this->assertSame('test', self::$ref::substringAfterLast('test', ''), 'match empty string');
        $this->assertSame('test', self::$ref::substringAfterLast('test', 'a'), 'no match');
        $this->assertSame('え', self::$ref::substringAfterLast('ああいういえ', 'い'), 'multi byte');
        $this->assertSame('🏴󠁧󠁢󠁳󠁣󠁴󠁿f', self::$ref::substringAfterLast('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', 'e'), 'grapheme');
        $this->assertSame('🏿', self::$ref::substringAfterLast('👋🏿', '👋'), 'grapheme cluster');
    }

    public function test_substringBefore(): void
    {
        $this->assertSame('a', self::$ref::substringBefore('abc', 'b'), 'match first (single occurrence)');
        $this->assertSame('a', self::$ref::substringBefore('abc-abc', 'b'), 'match first (multiple occurrence)');
        $this->assertSame('test', self::$ref::substringBefore('test1', '1'), 'match last');
        $this->assertSame('test', self::$ref::substringBefore('test123', '12'), 'match multiple chars');
        $this->assertSame('test', self::$ref::substringBefore('test', ''), 'match empty string');
        $this->assertSame('test', self::$ref::substringBefore('test', 'a'), 'no match');
        $this->assertSame('ああ', self::$ref::substringBefore('ああいういえ', 'い'), 'multi byte');
        $this->assertSame('abc', self::$ref::substringBefore('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'), 'grapheme substring');
        $this->assertSame('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿', self::$ref::substringBefore('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', 'e'), 'grapheme string');
        $this->assertSame('👋', self::$ref::substringBefore('👋🏿', '🏿'), 'substring is grapheme codepoint');
    }

    public function test_substringBeforeLast(): void
    {
        $this->assertSame('a', self::$ref::substringBeforeLast('abc', 'b'), 'match first (single occurrence)');
        $this->assertSame('abc-a', self::$ref::substringBeforeLast('abc-abc', 'b'), 'match first (multiple occurrence)');
        $this->assertSame('test', self::$ref::substringBeforeLast('test1', '1'), 'match last');
        $this->assertSame('test', self::$ref::substringBeforeLast('test', ''), 'match empty string');
        $this->assertSame('test', self::$ref::substringBeforeLast('test', 'a'), 'no match');
        $this->assertSame('ああいう', self::$ref::substringBeforeLast('ああいういえ', 'い'), 'multi byte');
        $this->assertSame('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e', self::$ref::substringBeforeLast('abc🏴󠁧󠁢󠁳󠁣󠁴󠁿d🏴󠁧󠁢󠁳󠁣󠁴󠁿e🏴󠁧󠁢󠁳󠁣󠁴󠁿f', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'), 'substring is grapheme');
        $this->assertSame('👋', self::$ref::substringBeforeLast('👋🏿', '🏿'), 'substring is grapheme codepoint');
    }

    public function test_surround(): void
    {
        $this->assertSame('', self::$ref::surround('', '', ''), 'blanks');
        $this->assertSame('[a]', self::$ref::surround('a', '[', ']'), 'simple case');
        $this->assertSame('１a２', self::$ref::surround('a', '１', '２'), 'multibyte');
        $this->assertSame('👨‍👨‍👧‍a🏴󠁧󠁢󠁳󠁣󠁴󠁿', self::$ref::surround('a', '👨‍👨‍👧‍', '🏴󠁧󠁢󠁳󠁣󠁴󠁿'), 'grapheme');
    }

    public function test_takeFirst(): void
    {
        $this->assertSame('', self::$ref::takeFirst('', 1), 'empty string');
        $this->assertSame('', self::$ref::takeFirst('a', 0), 'zero amount');
        $this->assertSame('abcd', self::$ref::takeFirst('abcde', 4), 'mid amount');
        $this->assertSame('abc', self::$ref::takeFirst('abc', 3), 'exact length');
        $this->assertSame('👋', self::$ref::takeFirst('👋🏿', 4), 'grapheme');
    }

    public function test_takeFirst_out_of_range_negative(): void
    {
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -4.');
        self::$ref::takeFirst('abc', -4);
    }

    public function test_takeLast(): void
    {
        $this->assertSame('', self::$ref::takeLast('', 1), 'empty string');
        $this->assertSame('a', self::$ref::takeLast('a', 0), 'zero amount');
        $this->assertSame('bcde', self::$ref::takeLast('abcde', 4), 'mid amount');
        $this->assertSame('abc', self::$ref::takeLast('abc', 3), 'exact length');
        $this->assertSame('abc', self::$ref::takeLast('abc', 4), 'over length');
        $this->assertSame('🏿', self::$ref::takeLast('👋🏿', 4), 'grapheme');
    }

    public function test_takeLast_out_of_range_negative(): void
    {
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -4.');
        self::$ref::takeLast('abc', -4);
    }

    public function test_toBool(): void
    {
        $this->assertTrue(self::$ref::toBool('true'), 'true as string');
        $this->assertTrue(self::$ref::toBool('TRUE'), 'TRUE as string');
        $this->assertFalse(self::$ref::toBool('false'), 'false as string');
        $this->assertFalse(self::$ref::toBool('FALSE'), 'FALSE as string');
        $this->assertTrue(self::$ref::toBool('1'), 'empty as string');
    }

    public function test_toBool_empty(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"" is not a valid boolean string.');
        // empty as string
        self::$ref::toBool('');
    }

    public function test_toBool_with_negative(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"-2" is not a valid boolean string.');
        // invalid boolean (number)
        self::$ref::toBool('-2');
    }

    public function test_toBool_with_yes(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"yes" is not a valid boolean string.');
        // truthy will fail
        self::$ref::toBool('yes');
    }

    public function test_toBoolOrNull(): void
    {
        $this->assertTrue(self::$ref::toBoolOrNull('true'), 'true as string');
        $this->assertTrue(self::$ref::toBoolOrNull('TRUE'), 'TRUE as string');
        $this->assertFalse(self::$ref::toBoolOrNull('false'), 'false as string');
        $this->assertFalse(self::$ref::toBoolOrNull('FALSE'), 'FALSE as string');
        $this->assertTrue(self::$ref::toBoolOrNull('1'), 'empty as string');
        $this->assertNull(self::$ref::toBoolOrNull(''), 'empty as string');
        $this->assertNull(self::$ref::toBoolOrNull('-2'), 'invalid boolean (number)');
        $this->assertNull(self::$ref::toBoolOrNull('yes'), 'truthy will fail');
    }

    public function test_toCamelCase(): void
    {
        $this->assertSame('test', self::$ref::toCamelCase('test'));
        $this->assertSame('test', self::$ref::toCamelCase('Test'));
        $this->assertSame('testTest', self::$ref::toCamelCase('test-test'));
        $this->assertSame('testTest', self::$ref::toCamelCase('test_test'));
        $this->assertSame('testTest', self::$ref::toCamelCase('test test'));
        $this->assertSame('testTestTest', self::$ref::toCamelCase('test test test'));
        $this->assertSame('testTest', self::$ref::toCamelCase(' test  test  '));
        $this->assertSame('testTestTest', self::$ref::toCamelCase("--test_test-test__"));
    }

    public function test_toFloat(): void
    {
        $this->assertSame(1.0, self::$ref::toFloat('1'), 'positive int');
        $this->assertSame(-1.0, self::$ref::toFloat('-1'), 'negative int');
        $this->assertSame(1.23, self::$ref::toFloat('1.23'), 'positive float');
        $this->assertSame(-1.23, self::$ref::toFloat('-1.23'), 'negative float');
        $this->assertSame(0.0, self::$ref::toFloat('0'), 'zero int');
        $this->assertSame(0.0, self::$ref::toFloat('0.0'), 'zero float');
        $this->assertSame(0.0, self::$ref::toFloat('-0'), 'negative zero int');
        $this->assertSame(0.0, self::$ref::toFloat('-0.0'), 'negative zero float');
        $this->assertSame(0.123, self::$ref::toFloat('0.123'), 'start from zero');
        $this->assertSame(123.456, self::$ref::toFloat('123.456'), 'multiple digits');
        $this->assertSame(1230.0, self::$ref::toFloat('1.23e3'), 'scientific notation with e');
        $this->assertSame(1230.0, self::$ref::toFloat('1.23E3'), 'scientific notation with E');
        $this->assertSame(-1230.0, self::$ref::toFloat('-1.23e3'), 'scientific notation as negative');
        $this->assertSame(1.234, self::$ref::toFloatOrNull('123.4E-2'), 'scientific notation irregular');
        $this->assertSame(1230.0, self::$ref::toFloat('1.23e+3'), 'with +e');
        $this->assertSame(1230.0, self::$ref::toFloat('1.23E+3'), 'with +E');
        $this->assertSame(0.012, self::$ref::toFloat('1.2e-2'), 'with -e');
        $this->assertSame(0.012, self::$ref::toFloat('1.2E-2'), 'with -E');
        $this->assertNan(self::$ref::toFloat('NAN'), 'NAN');
        $this->assertNan(self::$ref::toFloat('-NAN'), 'Negative NAN');
        $this->assertNan(self::$ref::toFloat('NaN'), 'NaN from Javascript');
        $this->assertNan(self::$ref::toFloat('-NaN'), 'Negative NaN');
        $this->assertInfinite(self::$ref::toFloat('INF'), 'upper case INF');
        $this->assertInfinite(self::$ref::toFloat('Infinity'), 'INF from Javascript');
    }

    public function test_toFloat_overflow_e_notation(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Float precision lost for "1e20"');
        self::$ref::toFloat('1e20');
    }

    public function test_toFloat_empty_string(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"" is not a valid float.');
        self::$ref::toFloat('');
    }

    public function test_toFloat_invalid_string(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"1a" is not a valid float.');
        self::$ref::toFloat('1a');
    }

    public function test_toFloat_dot_start(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('".1" is not a valid float.');
        self::$ref::toFloat('.1');
    }

    public function test_toFloat_zero_start(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"00.1" is not a valid float.');
        self::$ref::toFloat('00.1');
    }

    public function test_toFloat_overflow_number(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Float precision lost for "1.11111111111111"');
        self::$ref::toFloat('1.' . str_repeat('1', 14));
    }

    public function test_toFloatOrNull(): void
    {
        $this->assertSame(1.0, self::$ref::toFloatOrNull('1'), 'positive int');
        $this->assertSame(-1.0, self::$ref::toFloatOrNull('-1'), 'negative int');
        $this->assertSame(1.23, self::$ref::toFloatOrNull('1.23'), 'positive float');
        $this->assertSame(-1.23, self::$ref::toFloatOrNull('-1.23'), 'negative float');
        $this->assertSame(0.0, self::$ref::toFloatOrNull('0'), 'zero int');
        $this->assertSame(0.0, self::$ref::toFloatOrNull('0.0'), 'zero float');
        $this->assertSame(0.0, self::$ref::toFloatOrNull('-0'), 'negative zero int');
        $this->assertSame(0.0, self::$ref::toFloatOrNull('-0.0'), 'negative zero float');
        $this->assertSame(0.123, self::$ref::toFloatOrNull('0.123'), 'start from zero');
        $this->assertSame(123.456, self::$ref::toFloatOrNull('123.456'), 'multiple digits');
        $this->assertSame(1230.0, self::$ref::toFloatOrNull('1.23e3'), 'scientific notation with e');
        $this->assertSame(1230.0, self::$ref::toFloatOrNull('1.23E3'), 'scientific notation with E');
        $this->assertSame(-1230.0, self::$ref::toFloatOrNull('-1.23e3'), 'scientific notation as negative');
        $this->assertSame(1230.0, self::$ref::toFloatOrNull('1.23e+3'), 'with +e');
        $this->assertSame(1230.0, self::$ref::toFloatOrNull('1.23E+3'), 'with +E');
        $this->assertSame(0.012, self::$ref::toFloatOrNull('1.2e-2'), 'with -e');
        $this->assertSame(0.012, self::$ref::toFloatOrNull('1.2E-2'), 'with -E');
        $this->assertSame(1.234, self::$ref::toFloatOrNull('123.4E-2'), 'scientific notation irregular');
        $this->assertNull(self::$ref::toFloatOrNull('1e+20'), 'overflowing +e notation');
        $this->assertNull(self::$ref::toFloatOrNull('1e-20'), 'overflowing -e notation');
        $this->assertNull(self::$ref::toFloatOrNull('nan'), 'Lowercase nan is not NAN');
        $this->assertNan(self::$ref::toFloatOrNull('NAN'), 'NAN');
        $this->assertNan(self::$ref::toFloatOrNull('-NAN'), 'Negative NAN');
        $this->assertNan(self::$ref::toFloatOrNull('NaN'), 'NaN from Javascript');
        $this->assertNan(self::$ref::toFloatOrNull('-NaN'), 'Negative NaN');
        $this->assertNull(self::$ref::toFloatOrNull('inf'), 'Lowercase inf is not INF');
        $this->assertInfinite(self::$ref::toFloatOrNull('INF'), 'upper case INF');
        $this->assertInfinite(self::$ref::toFloatOrNull('Infinity'), 'INF from Javascript');
        $this->assertNull(self::$ref::toFloatOrNull(''), 'empty');
        $this->assertNull(self::$ref::toFloatOrNull('a1'), 'invalid string');
        $this->assertNull(self::$ref::toFloatOrNull('01.1'), 'zero start');
        $this->assertNull(self::$ref::toFloatOrNull('.1'), 'dot start');
        $this->assertNull(self::$ref::toFloatOrNull('1.' . str_repeat('1', 100)), 'overflow');
    }

    public function test_toInt(): void
    {
        $this->assertSame(123, self::$ref::toIntOrNull('123'));
    }

    public function test_toInt_blank(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"" is not a valid integer.');
        self::$ref::toInt('');
    }

    public function test_toInt_float(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"1.0" is not a valid integer.');
        self::$ref::toInt('1.0');
    }

    public function test_toInt_with_e_notation(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"1.23E+3" is not a valid integer.');
        self::$ref::toInt('1.23E+3');
    }

    public function test_toInt_float_with_e_notation(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"1.0e-2" is not a valid integer.');
        self::$ref::toInt('1.0e-2');
    }

    public function test_toInt_zero_start(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"01" is not a valid integer.');
        self::$ref::toInt('01');
    }

    public function test_toInt_not_compatible(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"a1" is not a valid integer.');
        self::$ref::toInt('a1');
    }

    public function test_toInt_positive_overflow(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"11111111111111111111" is not a valid integer.');
        self::$ref::toInt(str_repeat('1', 20));
    }

    public function test_toInt_negative_overflow(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"-11111111111111111111" is not a valid integer.');
        self::$ref::toInt('-' . str_repeat('1', 20));
    }

    public function test_toIntOrNull(): void
    {
        $this->assertSame(123, self::$ref::toIntOrNull('123'));
        $this->assertNull(self::$ref::toIntOrNull(str_repeat('1', 20)), 'overflow positive');
        $this->assertNull(self::$ref::toIntOrNull('-' . str_repeat('1', 20)), 'overflow positive');
        $this->assertNull(self::$ref::toIntOrNull(''), 'blank');
        $this->assertNull(self::$ref::toIntOrNull('1.0'), 'float value');
        $this->assertNull(self::$ref::toIntOrNull('1.0e-2'), 'float value with e notation');
        $this->assertNull(self::$ref::toIntOrNull('a1'), 'invalid string');
        $this->assertNull(self::$ref::toIntOrNull('01'), 'zero start');
    }

    public function test_toKebabCase(): void
    {
        $this->assertSame('test', self::$ref::toKebabCase('test'));
        $this->assertSame('test', self::$ref::toKebabCase('Test'));
        $this->assertSame('ttt', self::$ref::toKebabCase('TTT'));
        $this->assertSame('tt-test', self::$ref::toKebabCase('TTTest'));
        $this->assertSame('test-test', self::$ref::toKebabCase('testTest'));
        $this->assertSame('test-t-test', self::$ref::toKebabCase('testTTest'));
        $this->assertSame('test-test', self::$ref::toKebabCase('test-test'));
        $this->assertSame('test-test', self::$ref::toKebabCase('test_test'));
        $this->assertSame('test-test', self::$ref::toKebabCase('test test'));
        $this->assertSame('test-test-test', self::$ref::toKebabCase('test test test'));
        $this->assertSame('-test--test--', self::$ref::toKebabCase(' test  test  '));
        $this->assertSame('--test-test-test--', self::$ref::toKebabCase("--test_test-test__"));
    }

    public function test_toLowerCase(): void
    {
        $this->assertSame('', self::$ref::toLowerCase(''), 'empty (nothing happens)');
        $this->assertSame('abc', self::$ref::toLowerCase('ABC'), 'basic');
        $this->assertSame('あいう', self::$ref::toLowerCase('あいう'), 'utf-8 chars (nothing happens)');
        $this->assertSame('ÇĞİÖŞÜ', self::$ref::toLowerCase('ÇĞİÖŞÜ'), 'utf-8 special chars');
        $this->assertSame('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', self::$ref::toLowerCase('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿'), 'grapheme (nothing happens)');
    }

    public function test_toPascalCase(): void
    {
        $this->assertSame('A', self::$ref::toPascalCase('a'));
        $this->assertSame('TestMe', self::$ref::toPascalCase('test_me'));
        $this->assertSame('TestMe', self::$ref::toPascalCase('test-me'));
        $this->assertSame('TestMe', self::$ref::toPascalCase('test me'));
        $this->assertSame('TestMe', self::$ref::toPascalCase('testMe'));
        $this->assertSame('TestMe', self::$ref::toPascalCase('TestMe'));
        $this->assertSame('TestMe', self::$ref::toPascalCase(' test_me '));
        $this->assertSame('TestMeNow!', self::$ref::toPascalCase('test_me now-!'));
    }

    public function test_toSnakeCase(): void
    {
        $this->assertSame('', self::$ref::toSnakeCase(''), 'empty');
        $this->assertSame('abc', self::$ref::toSnakeCase('abc'), 'no-change');
        $this->assertSame('the_test_for_case', self::$ref::toSnakeCase('the test for case'));
        $this->assertSame('the_test_for_case', self::$ref::toSnakeCase('the-test-for-case'));
        $this->assertSame('the_test_for_case', self::$ref::toSnakeCase('theTestForCase'));
        $this->assertSame('ttt', self::$ref::toSnakeCase('TTT'));
        $this->assertSame('tt_t', self::$ref::toSnakeCase('TtT'));
        $this->assertSame('tt_t', self::$ref::toSnakeCase('TtT'));
        $this->assertSame('the__test', self::$ref::toSnakeCase('the  test'));
        $this->assertSame('__test', self::$ref::toSnakeCase('  test'));
        $this->assertSame("test\nabc", self::$ref::toSnakeCase("test\nabc"));
        $this->assertSame('__test_test_test__', self::$ref::toSnakeCase("--test_test-test__"));
    }

    public function test_toUpperCase(): void
    {
        $this->assertSame('', self::$ref::toUpperCase(''), 'empty (nothing happens)');
        $this->assertSame('ABC', self::$ref::toUpperCase('abc'), 'basic');
        $this->assertSame('あいう', self::$ref::toUpperCase('あいう'), 'utf-8 chars (nothing happens)');
        $this->assertSame('çğ', self::$ref::toUpperCase('çğ'), 'utf-8 special chars');
        $this->assertSame('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', self::$ref::toUpperCase('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿'), 'grapheme (nothing happens)');
    }

    public function test_trim(): void
    {
        $this->assertSame('', self::$ref::trim(''), 'empty (nothing happens)');
        $this->assertSame('a', self::$ref::trim("\ta"), 'left only');
        $this->assertSame('a', self::$ref::trim("a\t"), 'right only');
        $this->assertSame('abc', self::$ref::trim("\nabc\n"), 'new line on both ends');
        $this->assertSame('abc', self::$ref::trim("\t\nabc\n\t"), 'tab and mixed line on both ends');
        $this->assertSame('abc', self::$ref::trim("\t\nabc\n\t"), 'tab and mixed line on both ends');
        $this->assertSame('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', self::$ref::trim('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿'), 'grapheme (nothing happens)');
        $this->assertSame('b', self::$ref::trim('aba', 'a'), 'custom');
        $this->assertSame('a', self::$ref::trim('a', ''), 'custom empty');
        $this->assertSame("\nb\n", self::$ref::trim("a\nb\na", 'a'), 'custom overrides delimiter');
        $this->assertSame('b', self::$ref::trim("_ab_a_", 'a_'), 'custom multiple');

        $trim = "\u{2000}\u{2001}abc\u{2002}\u{2003}";
        $this->assertSame($trim, self::$ref::trim($trim), 'multibyte spaces (https://3v4l.org/s16FF)');
    }

    public function test_trimEnd(): void
    {
        $this->assertSame('', self::$ref::trimEnd(''), 'empty (nothing happens)');
        $this->assertSame("\ta", self::$ref::trimEnd("\ta"), 'left only');
        $this->assertSame('a', self::$ref::trimEnd("a\t"), 'right only');
        $this->assertSame("\nabc", self::$ref::trimEnd("\nabc\n"), 'new line on both ends');
        $this->assertSame('abc', self::$ref::trimEnd("abc\n\t"), 'tab and mixed line on both ends');
        $this->assertSame('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', self::$ref::trimEnd('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿'), 'grapheme (nothing happens)');
        $this->assertSame('ab', self::$ref::trimEnd('aba', 'a'), 'custom');
        $this->assertSame('a', self::$ref::trimEnd('a', ''), 'custom empty');
        $this->assertSame("ab\n", self::$ref::trimEnd("ab\na", 'a'), 'custom overrides delimiter');
        $this->assertSame('_ab', self::$ref::trimEnd("_ab_a_", 'a_'), 'custom multiple');

        $trim = " abc\n\t\u{0009}\u{2028}\u{2029}";
        $this->assertSame($trim, self::$ref::trimEnd($trim . "\v "), 'multibyte spaces (https://3v4l.org/s16FF)');
    }

    public function test_trimStart(): void
    {
        $this->assertSame('', self::$ref::trimStart(''), 'empty (nothing happens)');
        $this->assertSame("a", self::$ref::trimStart("\ta"), 'left only');
        $this->assertSame("a\t", self::$ref::trimStart("a\t"), 'right only');
        $this->assertSame("abc\n", self::$ref::trimStart("\nabc\n"), 'new line on both ends');
        $this->assertSame('abc', self::$ref::trimStart("\n\tabc"), 'tab and new line');
        $this->assertSame('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿', self::$ref::trimStart('👨‍👨‍👧‍👦🏴󠁧󠁢󠁳󠁣󠁴󠁿'), 'grapheme (nothing happens)');
        $this->assertSame('ba', self::$ref::trimStart('aba', 'a'), 'custom');
        $this->assertSame('a', self::$ref::trimStart('a', ''), 'custom empty');
        $this->assertSame("\nba", self::$ref::trimStart("a\nba", 'a'), 'custom overrides delimiter');
        $this->assertSame('b_a_', self::$ref::trimStart("_ab_a_", 'a_'), 'custom multiple');
        $trim = "\u{2028}\u{2029}\v abc ";
        $this->assertSame($trim, self::$ref::trimStart(" \n\t\u{0009}" . $trim), 'multibyte spaces (https://3v4l.org/s16FF)');
    }

    public function test_withPrefix(): void
    {
        $this->assertSame('foo', self::$ref::withPrefix('', 'foo'), 'empty string always adds');
        $this->assertSame('foo', self::$ref::withPrefix('foo', ''), 'empty start does nothing');
        $this->assertSame('foo', self::$ref::withPrefix('foo', 'f'), 'has match');
        $this->assertSame('_foo', self::$ref::withPrefix('foo', '_'), 'no match');
        $this->assertSame('___foo', self::$ref::withPrefix('_foo', '__'), 'partial matching doesn\'t count');
        $this->assertSame('__foo', self::$ref::withPrefix('__foo', '_'), 'repeats handled properly');
        $this->assertSame('\s foo', self::$ref::withPrefix(' foo', "\s"), 'try escape chars');
        $this->assertSame("\n foo", self::$ref::withPrefix(' foo', "\n"), 'new line');
        $this->assertSame('/foo', self::$ref::withPrefix('foo', '/'), 'slashes');
        $this->assertSame('あい', self::$ref::withPrefix('あい', 'あ'), 'utf8 match');
        $this->assertSame('うえあい', self::$ref::withPrefix('あい', 'うえ'), 'utf8 no match');
        $this->assertSame('👨‍👨‍👧‍👧', self::$ref::withPrefix('👨‍👨‍👧‍👧', '👨'), 'grapheme (treats combined grapheme as 1 whole character)');
    }

    public function test_withSuffix(): void
    {
        $this->assertSame('foo', self::$ref::withSuffix('', 'foo'), 'empty string always adds');
        $this->assertSame('foo', self::$ref::withSuffix('foo', ''), 'empty start does nothing');
        $this->assertSame('foo', self::$ref::withSuffix('foo', 'oo'), 'has match');
        $this->assertSame('foo bar', self::$ref::withSuffix('foo', ' bar'), 'no match');
        $this->assertSame('foo___', self::$ref::withSuffix('foo_', '__'), 'partial matching doesn\'t count');
        $this->assertSame('foo__', self::$ref::withSuffix('foo__', '_'), 'repeats handled properly');
        $this->assertSame('foo \s', self::$ref::withSuffix('foo ', "\s"), 'try escape chars');
        $this->assertSame("foo \n", self::$ref::withSuffix('foo ', "\n"), 'new line');
        $this->assertSame('foo/', self::$ref::withSuffix('foo', '/'), 'slashes');
        $this->assertSame('あい', self::$ref::withSuffix('あい', 'い'), 'utf8 match');
        $this->assertSame('あいうえ', self::$ref::withSuffix('あい', 'うえ'), 'utf8 no match');
        $this->assertSame('👨‍👨‍👧‍👧‍', self::$ref::withSuffix('👨‍👨‍👧‍👧‍', '👧‍'), 'grapheme (treats combined grapheme as 1 whole character)');
    }
}
