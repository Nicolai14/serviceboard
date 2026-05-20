<?php

namespace App\Exceptions;

use RuntimeException;

class SSHException extends RuntimeException
{
    public static function notReachable(string $host, int $port): self
    {
        return new self("Host {$host}:{$port} is not reachable (TCP timeout)");
    }

    public static function authFailed(string $host, string $method): self
    {
        return new self("SSH {$method} authentication failed for {$host}");
    }

    public static function noCredentials(string $host): self
    {
        return new self("No SSH credentials configured for {$host}");
    }

    public static function commandFailed(string $command): self
    {
        return new self("SSH command failed: {$command}");
    }

    public static function parseError(string $raw): self
    {
        return new self("Failed to parse metrics output: {$raw}");
    }
}
