<?php declare(strict_types=1);

namespace Kirameki\Stream;

use function assert;
use function tmpfile;

class TmpFileStream extends ResourceStreamable
{
    use CanRead;
    use CanWrite;
    use CanSeek;
    use CanLock;

    public function __construct()
    {
        $tmp = tmpfile();
        assert($tmp !== false);
        parent::__construct($tmp);
    }
}
