<?php

namespace App\Enums;

enum WorkspaceType: string
{
    case Personal = 'personal';
    case Business = 'business';

    public function label(): string
    {
        return match($this) {
            self::Personal => 'Privat',
            self::Business => 'Geschäftlich',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Personal => '🏠',
            self::Business => '💼',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Personal => '#3b82f6',
            self::Business => '#f59e0b',
        };
    }

    public function accentClasses(): string
    {
        return match($this) {
            self::Personal => 'bg-blue-500/10 border-blue-500/20 text-blue-400',
            self::Business => 'bg-amber-500/10 border-amber-500/20 text-amber-400',
        };
    }
}
