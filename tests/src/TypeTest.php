<?php declare(strict_types=1);

namespace Tests\Kirameki\Utils;

use DateTime;
use Kirameki\Utils\Type;
use stdClass;
use Tests\Kirameki\Utils\References\IntEnum;
use Tests\Kirameki\Utils\References\NonBackedEnum;
use Tests\Kirameki\Utils\References\StringEnum;

class TypeTest extends TestCase
{
    public function test_of(): void
    {
        self::assertSame('null', Type::of(null));

        self::assertSame('bool', Type::of(true));
        self::assertSame('bool', Type::of(false));

        self::assertSame('int', Type::of(0));
        self::assertSame('int', Type::of(1));

        self::assertSame('float', Type::of(0.0));
        self::assertSame('float', Type::of(-0.0));
        self::assertSame('float', Type::of(1.0));
        self::assertSame('float', Type::of(INF));
        self::assertSame('float', Type::of(NAN));

        self::assertSame('string', Type::of(''));
        self::assertSame('string', Type::of(' '));
        self::assertSame('string', Type::of('0'));
        self::assertSame('string', Type::of('a'));
        self::assertSame('string', Type::of('あ'));
        self::assertSame('string', Type::of('󠁧󠁢󠁳󠁣󠁴󠁿󠁧󠁢󠁳󠁣󠁴󠁿🏴󠁧󠁢󠁳󠁣󠁴󠁿'));

        self::assertSame('array', Type::of([]));
        self::assertSame('array', Type::of([1]));
        self::assertSame('array', Type::of(['a' => 1]));

        self::assertSame('enum', Type::of(IntEnum::One));
        self::assertSame('enum', Type::of(StringEnum::One));
        self::assertSame('enum', Type::of(NonBackedEnum::One));

        self::assertSame('closure', Type::of(static fn() => true));
        self::assertSame('closure', Type::of(substr(...)));

        self::assertSame('object', Type::of(new stdClass()));
        self::assertSame('object', Type::of(new DateTime()));

        self::assertSame('resource', Type::of(tmpfile()));
    }
}
