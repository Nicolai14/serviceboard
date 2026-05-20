<?php

namespace Tests\Unit\Models;

use App\Models\DnsRecord;
use PHPUnit\Framework\TestCase;

class DnsRecordTest extends TestCase
{
    public function test_ttl_label_returns_auto_for_ttl_1(): void
    {
        $record = new DnsRecord(['ttl' => 1]);
        $this->assertSame('Auto', $record->ttl_label);
    }

    public function test_ttl_label_returns_seconds_for_other_values(): void
    {
        $record = new DnsRecord(['ttl' => 300]);
        $this->assertSame('300s', $record->ttl_label);

        $record = new DnsRecord(['ttl' => 3600]);
        $this->assertSame('3600s', $record->ttl_label);
    }
}
