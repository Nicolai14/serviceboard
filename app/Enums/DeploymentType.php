<?php

namespace App\Enums;

enum DeploymentType: string
{
    case Git           = 'git';
    case Script        = 'script';
    case DockerCompose = 'docker_compose';

    public function label(): string
    {
        return match($this) {
            self::Git           => 'Git Pull',
            self::Script        => 'Shell Script',
            self::DockerCompose => 'Docker Compose',
        };
    }

    public function configSchema(): array
    {
        return match($this) {
            self::Git => [
                'repository' => 'string',
                'branch'     => 'string',
                'directory'  => 'string',
            ],
            self::Script => [
                'script'    => 'text',
                'directory' => 'string',
            ],
            self::DockerCompose => [
                'compose_file' => 'string',
                'directory'    => 'string',
                'pull_images'  => 'bool',
            ],
        };
    }
}
