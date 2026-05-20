<?php

namespace App\Services;

use App\Exceptions\SSHException;
use App\Models\DockerContainer;
use App\Models\Server;
use Illuminate\Support\Collection;

class DockerService
{
    /**
     * Single SSH session: runs `docker ps` and `docker stats`, returns merged result.
     *
     * Uses template format for compatibility with all Docker versions (18+).
     * Stats are only available for running containers; stopped ones get null metrics.
     */
    private const DOCKER_SCRIPT = <<<'SH'
echo "=BEGIN_PS="
docker ps -a --format '{"id":"{{.ID}}","name":"{{.Names}}","image":"{{.Image}}","state":"{{.State}}","status":"{{.Status}}","ports":"{{.Ports}}"}' 2>/dev/null || echo '{"error":"docker_not_found"}'
echo "=BEGIN_STATS="
docker stats --no-stream --format '{"name":"{{.Name}}","cpu":"{{.CPUPerc}}","mem":"{{.MemUsage}}","mem_perc":"{{.MemPerc}}"}' 2>/dev/null
echo "=END="
SH;

    public function __construct(private readonly SSHService $sshService) {}

    /**
     * Collect container data from a server and upsert into docker_containers.
     * Returns the number of containers found.
     */
    public function sync(Server $server): int
    {
        $output = $this->sshService->runCommand($server, self::DOCKER_SCRIPT);

        ['ps' => $psLines, 'stats' => $statsLines] = $this->splitSections($output);

        $containers = $this->parsePs($psLines);
        $stats      = $this->parseStats($statsLines);

        // Merge stats into container records (keyed by name).
        foreach ($containers as &$c) {
            $s = $stats[$c['name']] ?? null;
            $c['cpu_percent']     = $s ? $this->parseCpu($s['cpu'])        : null;
            $c['memory_usage_mb'] = $s ? $this->parseMemoryValue($s['mem']) : null;
            $c['memory_limit_mb'] = $s ? $this->parseMemoryLimit($s['mem']) : null;
            $c['memory_percent']  = $s ? $this->parseCpu($s['mem_perc'])    : null;
        }
        unset($c);

        $now            = now();
        $activeIds      = [];

        foreach ($containers as $c) {
            $record = DockerContainer::updateOrCreate(
                ['server_id' => $server->id, 'container_id' => $c['id']],
                [
                    'name'             => $c['name'],
                    'image'            => $c['image'],
                    'state'            => $c['state'],
                    'status_text'      => $c['status'],
                    'cpu_percent'      => $c['cpu_percent'],
                    'memory_usage_mb'  => $c['memory_usage_mb'],
                    'memory_limit_mb'  => $c['memory_limit_mb'],
                    'memory_percent'   => $c['memory_percent'],
                    'ports'            => $this->parsePorts($c['ports']),
                    'synced_at'        => $now,
                ]
            );
            $activeIds[] = $record->id;
        }

        // Remove containers that were deleted on the host since last sync.
        DockerContainer::where('server_id', $server->id)
            ->whereNotIn('id', $activeIds)
            ->delete();

        return count($containers);
    }

    /**
     * Return all containers for a server as a JSON-ready array.
     */
    public function getContainersForServer(Server $server): Collection
    {
        return $server->dockerContainers()->orderByRaw("state = 'running' DESC")->orderBy('name')->get();
    }

    // -------------------------------------------------------------------------
    // Parsing helpers

    private function splitSections(string $output): array
    {
        $ps    = [];
        $stats = [];

        $section = null;
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if ($line === '=BEGIN_PS=')    { $section = 'ps';    continue; }
            if ($line === '=BEGIN_STATS=') { $section = 'stats'; continue; }
            if ($line === '=END=')         { break; }
            if ($line === '')              { continue; }

            match ($section) {
                'ps'    => $ps[]    = $line,
                'stats' => $stats[] = $line,
                default => null,
            };
        }

        return ['ps' => $ps, 'stats' => $stats];
    }

    private function parsePs(array $lines): array
    {
        $containers = [];
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if (!$data || isset($data['error'])) {
                continue;
            }
            $containers[] = [
                'id'     => $data['id']     ?? '',
                'name'   => ltrim($data['name']   ?? '', '/'),
                'image'  => $data['image']  ?? '',
                'state'  => $data['state']  ?? 'unknown',
                'status' => $data['status'] ?? '',
                'ports'  => $data['ports']  ?? '',
            ];
        }
        return $containers;
    }

    /**
     * Returns stats keyed by container name.
     */
    private function parseStats(array $lines): array
    {
        $stats = [];
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if (!$data) {
                continue;
            }
            $name = ltrim($data['name'] ?? '', '/');
            if ($name) {
                $stats[$name] = $data;
            }
        }
        return $stats;
    }

    /**
     * Parses "0.05%" → 0.05
     */
    private function parseCpu(string $value): float
    {
        return (float) str_replace('%', '', trim($value));
    }

    /**
     * Parses the USED part of "52.3MiB / 10GiB" → float MB
     */
    private function parseMemoryValue(string $mem): float
    {
        $parts = explode(' / ', $mem);
        return $this->convertToMb(trim($parts[0] ?? '0'));
    }

    /**
     * Parses the LIMIT part of "52.3MiB / 10GiB" → float MB
     */
    private function parseMemoryLimit(string $mem): float
    {
        $parts = explode(' / ', $mem);
        return $this->convertToMb(trim($parts[1] ?? '0'));
    }

    private function convertToMb(string $value): float
    {
        // Units ordered longest-first to avoid prefix conflicts.
        $units = [
            'TiB' => 1_048_576.0,
            'GiB' => 1_024.0,
            'MiB' => 1.0,
            'KiB' => 1.0 / 1_024,
            'TB'  => 1_000_000.0,
            'GB'  => 1_000.0,
            'MB'  => 1.0,
            'KB'  => 0.001,
            'B'   => 1.0 / 1_048_576,
        ];

        foreach ($units as $unit => $factor) {
            if (str_ends_with($value, $unit)) {
                return (float) rtrim(substr($value, 0, -strlen($unit))) * $factor;
            }
        }

        return (float) $value;
    }

    /**
     * Parses Docker port string into structured array.
     * Input examples:
     *   "0.0.0.0:80->80/tcp, 0.0.0.0:443->443/tcp"
     *   ":::8080->8080/tcp"
     *   "" (no ports)
     */
    private function parsePorts(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $ports = [];
        foreach (explode(', ', $raw) as $segment) {
            $segment = trim($segment);
            if (!str_contains($segment, '->')) {
                continue;
            }

            // e.g. "0.0.0.0:8080->80/tcp"
            [$hostPart, $containerPart] = explode('->', $segment, 2);
            [$containerPort, $proto]    = str_contains($containerPart, '/')
                ? explode('/', $containerPart, 2)
                : [$containerPart, 'tcp'];

            // Host part: "0.0.0.0:8080" or ":::8080" or just "8080"
            $hostPort = str_contains($hostPart, ':')
                ? substr($hostPart, strrpos($hostPart, ':') + 1)
                : $hostPart;

            $ports[] = [
                'host'      => $hostPort,
                'container' => $containerPort,
                'proto'     => $proto,
            ];
        }

        return $ports;
    }
}
