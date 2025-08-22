<?php declare(strict_types=1);

namespace Kirameki\Process;

final class ExitCode
{
    public const int SUCCESS = 0;
    public const int GENERAL_ERROR = 1;
    public const int INVALID_USAGE = 2;
    public const int TIMED_OUT = 124;
    public const int TIMEOUT_COMMAND_FAILED = 125;
    public const int COMMAND_NOT_EXECUTABLE = 126;
    public const int COMMAND_NOT_FOUND = 127;
    public const int INVALID_ARGUMENT = 128;
    public const int SIGHUP = 129;
    public const int SIGINT = 130;
    public const int SIGKILL = 137;
    public const int SIGSEGV = 139;
    public const int SIGTERM = 143;
    public const int STATUS_OUT_OF_RANGE = 255;
}
