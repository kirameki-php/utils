<?php declare(strict_types=1);

namespace Kirameki\Dumper\Configs;

enum DebugInfo
{
    case Ignore;
    case Overwrite;
    case Append;
}
