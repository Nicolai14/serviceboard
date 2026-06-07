<?php

namespace App\Enums;

enum WorkflowNodeType: string
{
    case App      = 'app';
    case Server   = 'server';
    case Docker   = 'docker';
    case Software = 'software';
    case Domain   = 'domain';
    case Service  = 'service';
    case Database = 'database';

    public function label(): string
    {
        return match ($this) {
            self::App      => 'App',
            self::Server   => 'Server',
            self::Docker   => 'Docker Container',
            self::Software => 'Software',
            self::Domain   => 'Domain',
            self::Service  => 'Dienst',
            self::Database => 'Datenbank',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::App      => '📱',
            self::Server   => '🖥️',
            self::Docker   => '🐳',
            self::Software => '📦',
            self::Domain   => '🌐',
            self::Service  => '☁️',
            self::Database => '🗄️',
        };
    }

    /**
     * Hex colour used for the node accent and connection handles.
     */
    public function color(): string
    {
        return match ($this) {
            self::App      => '#3b82f6', // blue
            self::Server   => '#8b5cf6', // violet
            self::Docker   => '#0ea5e9', // sky
            self::Software => '#f59e0b', // amber
            self::Domain   => '#f97316', // orange
            self::Service  => '#10b981', // emerald
            self::Database => '#ec4899', // pink
        };
    }

    /**
     * @return array<int, array{value: string, label: string, icon: string, color: string}>
     */
    public static function palette(): array
    {
        return array_map(fn (self $t) => [
            'value' => $t->value,
            'label' => $t->label(),
            'icon'  => $t->icon(),
            'color' => $t->color(),
        ], self::cases());
    }
}
