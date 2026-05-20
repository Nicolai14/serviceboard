<?php

namespace Tests\Unit\Services;

use App\Exceptions\SSHException;
use App\Services\SSHService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SSHServiceTest extends TestCase
{
    private ReflectionMethod $parseMetrics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parseMetrics = new ReflectionMethod(SSHService::class, 'parseMetrics');
        $this->parseMetrics->setAccessible(true);
    }

    private function invoke(string $output): array
    {
        return $this->parseMetrics->invoke(new SSHService(), $output);
    }

    public function test_valid_output_parses_to_correct_typed_array(): void
    {
        $output = 'CPU=42 MEM_USED=1073741824 MEM_TOTAL=2147483648 DISK_USED=10737418240 DISK_TOTAL=107374182400 LOAD=1.25 UPTIME=86400';

        $result = $this->invoke($output);

        $this->assertSame(42.0, $result['cpu_usage']);
        $this->assertSame(1024.0, $result['memory_usage']);
        $this->assertSame(2048.0, $result['memory_total']);
        $this->assertEqualsWithDelta(10.0, $result['disk_usage'], 0.001);
        $this->assertEqualsWithDelta(100.0, $result['disk_total'], 0.001);
        $this->assertSame(1.25, $result['load_average']);
        $this->assertSame(86400, $result['uptime_seconds']);
    }

    public function test_memory_is_converted_from_bytes_to_megabytes(): void
    {
        $output = 'CPU=0 MEM_USED=1073741824 MEM_TOTAL=2147483648 DISK_USED=0 DISK_TOTAL=1073741824 LOAD=0 UPTIME=0';

        $result = $this->invoke($output);

        // 1 073 741 824 bytes / 1 048 576 = 1024 MB
        $this->assertSame(1024.0, $result['memory_usage']);
        $this->assertSame(2048.0, $result['memory_total']);
    }

    public function test_disk_is_converted_from_bytes_to_gigabytes(): void
    {
        $output = 'CPU=0 MEM_USED=0 MEM_TOTAL=1048576 DISK_USED=10737418240 DISK_TOTAL=107374182400 LOAD=0 UPTIME=0';

        $result = $this->invoke($output);

        // 10 737 418 240 bytes / 1 073 741 824 = 10 GB
        $this->assertEqualsWithDelta(10.0, $result['disk_usage'], 0.001);
        $this->assertEqualsWithDelta(100.0, $result['disk_total'], 0.001);
    }

    public function test_uptime_seconds_is_cast_to_integer(): void
    {
        $output = 'CPU=5 MEM_USED=1048576 MEM_TOTAL=2097152 DISK_USED=0 DISK_TOTAL=1073741824 LOAD=0.5 UPTIME=86400';

        $result = $this->invoke($output);

        $this->assertIsInt($result['uptime_seconds']);
    }

    public function test_missing_cpu_field_throws_ssh_exception(): void
    {
        $output = 'MEM_USED=1073741824 MEM_TOTAL=2147483648 DISK_USED=10737418240 DISK_TOTAL=107374182400 LOAD=1.25 UPTIME=86400';

        $this->expectException(SSHException::class);

        $this->invoke($output);
    }

    public function test_missing_mem_used_field_throws_ssh_exception(): void
    {
        $output = 'CPU=10 MEM_TOTAL=2147483648 DISK_USED=10737418240 DISK_TOTAL=107374182400 LOAD=1.25 UPTIME=86400';

        $this->expectException(SSHException::class);

        $this->invoke($output);
    }

    public function test_empty_output_throws_ssh_exception(): void
    {
        $this->expectException(SSHException::class);

        $this->invoke('');
    }

    public function test_completely_invalid_output_throws_ssh_exception(): void
    {
        $this->expectException(SSHException::class);

        $this->invoke('something went wrong on the server');
    }
}
