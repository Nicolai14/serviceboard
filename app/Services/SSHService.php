<?php

namespace App\Services;

use App\Exceptions\SSHException;
use App\Models\Server;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class SSHService
{
    private const CONNECT_TIMEOUT = 10;
    private const TCP_TIMEOUT     = 3;

    /**
     * Single shell script run in one SSH session.
     * Outputs: CPU=N MEM_USED=N MEM_TOTAL=N DISK_USED=N DISK_TOTAL=N LOAD=N UPTIME=N
     */
    private const METRICS_SCRIPT = <<<'SH'
L1=$(grep '^cpu ' /proc/stat 2>/dev/null)
sleep 0.3
L2=$(grep '^cpu ' /proc/stat 2>/dev/null)
u1=$(echo $L1|awk '{print $2}') n1=$(echo $L1|awk '{print $3}') s1=$(echo $L1|awk '{print $4}') i1=$(echo $L1|awk '{print $5}') w1=$(echo $L1|awk '{print $6}')
u2=$(echo $L2|awk '{print $2}') n2=$(echo $L2|awk '{print $3}') s2=$(echo $L2|awk '{print $4}') i2=$(echo $L2|awk '{print $5}') w2=$(echo $L2|awk '{print $6}')
DI=$((i2-i1)); DT=$(((u2+n2+s2+i2+w2)-(u1+n1+s1+i1+w1)))
CPU=$([ "$DT" -gt 0 ] 2>/dev/null && echo $((100-DI*100/DT)) || echo 0)
MEM=$(free -b 2>/dev/null | awk 'NR==2{print $3,$2}')
DSK=$(df -B1 / 2>/dev/null | awk 'NR==2{print $3,$2}')
LOAD=$(awk '{print $1}' /proc/loadavg 2>/dev/null || echo 0)
UPT=$(awk '{print int($1)}' /proc/uptime 2>/dev/null || echo 0)
printf "CPU=%s MEM_USED=%s MEM_TOTAL=%s DISK_USED=%s DISK_TOTAL=%s LOAD=%s UPTIME=%s\n" \
  "${CPU:-0}" $(echo ${MEM:-0 0}) $(echo ${DSK:-0 0}) "${LOAD:-0}" "${UPT:-0}"
SH;

    /**
     * TCP port check — no SSH credentials required.
     */
    public function isReachable(Server $server): bool
    {
        $socket = @fsockopen(
            $server->ip_address ?: $server->hostname,
            $server->ssh_port,
            $errno,
            $errstr,
            self::TCP_TIMEOUT
        );

        if ($socket !== false) {
            fclose($socket);
            return true;
        }

        return false;
    }

    /**
     * Full SSH handshake + auth test. Returns result array for JSON responses.
     */
    public function testConnection(Server $server): array
    {
        $start = microtime(true);

        if (!$this->isReachable($server)) {
            return [
                'success'    => false,
                'step'       => 'tcp',
                'message'    => "Port {$server->ssh_port} auf {$server->hostname} ist nicht erreichbar.",
                'latency_ms' => null,
            ];
        }

        if (!$server->hasSSHCredentials()) {
            return [
                'success'    => false,
                'step'       => 'credentials',
                'message'    => 'Keine SSH-Zugangsdaten konfiguriert.',
                'latency_ms' => null,
            ];
        }

        try {
            $ssh = $this->connect($server);
            $ssh->exec('echo ok');

            return [
                'success'    => true,
                'step'       => 'auth',
                'message'    => "SSH-Verbindung erfolgreich ({$server->ssh_user}@{$server->hostname}).",
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ];
        } catch (SSHException $e) {
            return [
                'success'    => false,
                'step'       => 'auth',
                'message'    => $e->getMessage(),
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ];
        }
    }

    /**
     * Run an arbitrary shell command via SSH and return stdout.
     * Throws SSHException if the server has no credentials.
     */
    public function runCommand(Server $server, string $command): string
    {
        $ssh = $this->connect($server);
        return (string) $ssh->exec($command);
    }

    /**
     * Run a command and return both combined output and the shell exit code.
     *
     * @return array{output: string, exit_code: int}
     */
    public function runScript(Server $server, string $command): array
    {
        $ssh    = $this->connect($server);
        $output = (string) $ssh->exec($command);

        return [
            'output'    => $output,
            'exit_code' => $ssh->getExitStatus() ?: 0,
        ];
    }

    /**
     * SSH connect, run metrics script, parse and return data array.
     *
     * @return array{cpu_usage: float, memory_usage: float, memory_total: float,
     *               disk_usage: float, disk_total: float,
     *               load_average: float, uptime_seconds: int}
     */
    public function collectMetrics(Server $server): array
    {
        $ssh    = $this->connect($server);
        $output = $ssh->exec(self::METRICS_SCRIPT);

        if ($ssh->getExitStatus() !== 0 && empty(trim($output))) {
            throw SSHException::commandFailed('metrics_script');
        }

        return $this->parseMetrics(trim($output));
    }

    // -------------------------------------------------------------------------

    private function connect(Server $server): SSH2
    {
        $ssh = new SSH2($server->ip_address ?: $server->hostname, $server->ssh_port, self::CONNECT_TIMEOUT);

        $authenticated = match ($server->ssh_auth_method) {
            'key'      => $this->loginWithKey($ssh, $server),
            'password' => $ssh->login($server->ssh_user, $server->ssh_password ?? ''),
        };

        if (!$authenticated) {
            throw SSHException::authFailed($server->hostname, $server->ssh_auth_method);
        }

        return $ssh;
    }

    private function loginWithKey(SSH2 $ssh, Server $server): bool
    {
        if (empty($server->ssh_private_key)) {
            throw SSHException::noCredentials($server->hostname);
        }

        $key = PublicKeyLoader::load($server->ssh_private_key);

        return $ssh->login($server->ssh_user, $key);
    }

    /**
     * Parses "KEY=value KEY=value …" output into a typed array.
     */
    private function parseMetrics(string $output): array
    {
        $data = [];
        preg_match_all('/(\w+)=([^\s]+)/', $output, $matches);

        foreach ($matches[1] as $i => $key) {
            $data[$key] = $matches[2][$i];
        }

        $required = ['CPU', 'MEM_USED', 'MEM_TOTAL', 'DISK_USED', 'DISK_TOTAL', 'LOAD', 'UPTIME'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                throw SSHException::parseError($output);
            }
        }

        return [
            'cpu_usage'      => (float) $data['CPU'],
            'memory_usage'   => (float) $data['MEM_USED'] / 1_048_576,   // bytes → MB
            'memory_total'   => (float) $data['MEM_TOTAL'] / 1_048_576,
            'disk_usage'     => (float) $data['DISK_USED'] / 1_073_741_824,  // bytes → GB
            'disk_total'     => (float) $data['DISK_TOTAL'] / 1_073_741_824,
            'load_average'   => (float) $data['LOAD'],
            'uptime_seconds' => (int)   $data['UPTIME'],
        ];
    }
}
