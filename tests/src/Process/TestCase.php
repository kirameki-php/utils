<?php declare(strict_types=1);

namespace Tests\Kirameki\Process;

use Kirameki\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return string
     */
    protected function getScriptsDir(): string
    {
        return __DIR__ . '/scripts';
    }
}
