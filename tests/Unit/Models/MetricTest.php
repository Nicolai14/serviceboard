<?php

namespace Tests\Unit\Models;

use App\Models\Metric;
use PHPUnit\Framework\TestCase;

class MetricTest extends TestCase
{
    public function test_memory_percent_calculation(): void
    {
        $metric = new Metric([
            'memory_usage' => 4096.0,
            'memory_total' => 8192.0,
        ]);

        $this->assertSame(50.0, $metric->memory_percent);
    }

    public function test_memory_percent_returns_zero_when_total_is_zero(): void
    {
        $metric = new Metric([
            'memory_usage' => 1000.0,
            'memory_total' => 0.0,
        ]);

        $this->assertSame(0.0, $metric->memory_percent);
    }

    public function test_disk_percent_calculation(): void
    {
        $metric = new Metric([
            'disk_usage' => 25600.0,
            'disk_total' => 102400.0,
        ]);

        $this->assertSame(25.0, $metric->disk_percent);
    }

    public function test_disk_percent_returns_zero_when_total_is_zero(): void
    {
        $metric = new Metric([
            'disk_usage' => 5000.0,
            'disk_total' => 0.0,
        ]);

        $this->assertSame(0.0, $metric->disk_percent);
    }

    public function test_cpu_percent_attribute_formats_correctly(): void
    {
        $metric = new Metric(['cpu_usage' => 73.456]);
        $this->assertSame('73.5%', $metric->cpu_percent);
    }
}
