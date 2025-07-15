<?php declare(strict_types=1);

namespace Kirameki\Backoff;

/**
 * Used in @see ExponentialBackoff
 */
enum JitterStrategy
{
    case None;
    case Full;
    case Equal;
    case Decorrelated;
}
