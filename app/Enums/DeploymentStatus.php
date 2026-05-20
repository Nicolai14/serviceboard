<?php

namespace App\Enums;

enum DeploymentStatus: string
{
    case Pending   = 'pending';
    case Running   = 'running';
    case Success   = 'success';
    case Failed    = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending',
            self::Running   => 'Running',
            self::Success   => 'Success',
            self::Failed    => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Success, self::Failed, self::Cancelled]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Running]);
    }

    public function color(): string
    {
        return match($this) {
            self::Pending   => 'yellow',
            self::Running   => 'blue',
            self::Success   => 'green',
            self::Failed    => 'red',
            self::Cancelled => 'zinc',
        };
    }
}
