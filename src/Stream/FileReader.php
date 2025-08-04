<?php declare(strict_types=1);

namespace Kirameki\Stream;

class FileReader extends ResourceStreamable implements StreamReadable, StreamSeekable
{
    use CanRead;
    use CanSeek;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        parent::__construct($this->open($path, 'rb'));
    }
}
