<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Version;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function test_parse_valid_version(): void
    {
        $v = Version::parse('1.2.3');
        $this->assertSame(1, $v->major);
        $this->assertSame(2, $v->minor);
        $this->assertSame(3, $v->patch);
        $this->assertSame('1.2.3', (string)$v);
    }

    public function test_parse_invalid_version_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid version format. Expected 'major.minor.patch'. Got '1.2'.");
        Version::parse('1.2');
    }

    public function test_try_parse(): void
    {
        $v = Version::tryParse('2.0.1');
        $this->assertInstanceOf(Version::class, $v);
        $this->assertSame(2, $v->major);
        $this->assertSame(0, $v->minor);
        $this->assertSame(1, $v->patch);
        $this->assertNull(Version::tryParse('bad.version'));
    }

    public function test_zero(): void
    {
        $v = Version::zero();
        $this->assertSame(0, $v->major);
        $this->assertSame(0, $v->minor);
        $this->assertSame(0, $v->patch);
    }

    public function test_compare_to(): void
    {
        $v1 = Version::parse('1.2.3');
        $v2 = Version::parse('1.2.4');
        $this->assertLessThan(0, $v1->compareTo($v2));
        $this->assertGreaterThan(0, $v2->compareTo($v1));
        $this->assertSame(0, $v1->compareTo(Version::parse('1.2.3')));
    }

    public function test_is_major_update(): void
    {
        $from = Version::parse('1.2.3');
        $to = Version::parse('2.0.0');
        $this->assertTrue($to->isMajorUpdate($from));
        $this->assertFalse($from->isMajorUpdate($to));
    }

    public function test_is_minor_update(): void
    {
        $from = Version::parse('1.2.3');
        $to = Version::parse('1.3.0');
        $this->assertTrue($to->isMinorUpdate($from));
        $this->assertFalse($from->isMinorUpdate($to));
    }

    public function test_is_patch_update(): void
    {
        $from = Version::parse('1.2.3');
        $to = Version::parse('1.2.4');
        $this->assertTrue($to->isPatchUpdate($from));
        $this->assertFalse($from->isPatchUpdate($to));
    }

    public function test_comparison_trait_methods(): void
    {
        $v1 = Version::parse('1.2.3');
        $v2 = Version::parse('1.2.4');
        $v3 = Version::parse('1.2.3');

        $this->assertTrue($v1->isEqualTo($v3));
        $this->assertFalse($v1->isEqualTo($v2));
        $this->assertTrue($v1->isNotEqualTo($v2));
        $this->assertFalse($v1->isNotEqualTo($v3));
        $this->assertTrue($v1->isLessThan($v2));
        $this->assertTrue($v1->isLessThanOrEqualTo($v2));
        $this->assertTrue($v1->isLessThanOrEqualTo($v3));
        $this->assertFalse($v2->isLessThan($v1));
        $this->assertTrue($v2->isGreaterThan($v1));
        $this->assertTrue($v2->isGreaterThanOrEqualTo($v1));
        $this->assertTrue($v2->isGreaterThanOrEqualTo($v2));
        $this->assertFalse($v1->isGreaterThan($v2));
    }
}
