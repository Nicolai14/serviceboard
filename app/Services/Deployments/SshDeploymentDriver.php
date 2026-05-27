<?php

namespace App\Services\Deployments;

use App\Contracts\DeploymentDriverContract;
use App\Enums\DeploymentType;
use App\Models\Deployment;
use App\Services\SSHService;
use InvalidArgumentException;

/**
 * Runs a deployment over SSH on the target server. Each deployment type maps
 * to a shell command built from its stored config; stdout and the exit code
 * are streamed into the deployment log.
 */
class SshDeploymentDriver implements DeploymentDriverContract
{
    public function __construct(private readonly SSHService $ssh) {}

    public function run(Deployment $deployment): bool
    {
        $command = $this->buildCommand($deployment);

        $deployment->appendLog('$ ' . $command);

        $result = $this->ssh->runScript($deployment->server, $command);

        if (trim($result['output']) !== '') {
            $deployment->appendLog(rtrim($result['output']));
        }
        $deployment->appendLog("[exit code: {$result['exit_code']}]");

        return $result['exit_code'] === 0;
    }

    /**
     * @return array{command: string}
     */
    public function dryRun(Deployment $deployment): array
    {
        return ['command' => $this->buildCommand($deployment)];
    }

    public function supports(string $type): bool
    {
        return in_array($type, [
            DeploymentType::Git->value,
            DeploymentType::Script->value,
            DeploymentType::DockerCompose->value,
        ], true);
    }

    private function buildCommand(Deployment $deployment): string
    {
        $config = $deployment->config ?? [];

        return match ($deployment->type) {
            DeploymentType::Git->value           => $this->gitCommand($config),
            DeploymentType::Script->value        => $this->scriptCommand($config),
            DeploymentType::DockerCompose->value => $this->dockerComposeCommand($config),
            default => throw new InvalidArgumentException("Unsupported deployment type: {$deployment->type}"),
        };
    }

    /**
     * @param array<string, mixed> $config
     */
    private function gitCommand(array $config): string
    {
        $dir    = escapeshellarg((string) ($config['directory'] ?? '.'));
        $branch = escapeshellarg('origin/' . ($config['branch'] ?? 'main'));

        return "cd {$dir} && git fetch --all --prune && git reset --hard {$branch} && git log -1 --oneline";
    }

    /**
     * @param array<string, mixed> $config
     */
    private function scriptCommand(array $config): string
    {
        $script    = (string) ($config['script'] ?? '');
        $directory = (string) ($config['directory'] ?? '');

        $prefix = $directory !== '' ? 'cd ' . escapeshellarg($directory) . ' && ' : '';

        return $prefix . $script;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function dockerComposeCommand(array $config): string
    {
        $dir  = escapeshellarg((string) ($config['directory'] ?? '.'));
        $file = escapeshellarg((string) ($config['compose_file'] ?? 'docker-compose.yml'));

        $command = "cd {$dir} && ";

        if (! empty($config['pull_images'])) {
            $command .= "docker compose -f {$file} pull && ";
        }

        return $command . "docker compose -f {$file} up -d --build";
    }
}
