<?php

namespace Tests\Unit\Enums;

use App\Enums\WorkspaceType;
use PHPUnit\Framework\TestCase;

class WorkspaceTypeTest extends TestCase
{
    public function test_personal_label(): void
    {
        $this->assertSame('Privat', WorkspaceType::Personal->label());
    }

    public function test_business_label(): void
    {
        $this->assertSame('Geschäftlich', WorkspaceType::Business->label());
    }

    public function test_personal_icon(): void
    {
        $this->assertSame('🏠', WorkspaceType::Personal->icon());
    }

    public function test_business_icon(): void
    {
        $this->assertSame('💼', WorkspaceType::Business->icon());
    }

    public function test_personal_color(): void
    {
        $this->assertSame('#3b82f6', WorkspaceType::Personal->color());
    }

    public function test_business_color(): void
    {
        $this->assertSame('#f59e0b', WorkspaceType::Business->color());
    }

    public function test_personal_accent_classes_is_non_empty_string(): void
    {
        $classes = WorkspaceType::Personal->accentClasses();

        $this->assertIsString($classes);
        $this->assertNotEmpty($classes);
    }

    public function test_business_accent_classes_is_non_empty_string(): void
    {
        $classes = WorkspaceType::Business->accentClasses();

        $this->assertIsString($classes);
        $this->assertNotEmpty($classes);
    }

    public function test_personal_and_business_accent_classes_differ(): void
    {
        $this->assertNotSame(
            WorkspaceType::Personal->accentClasses(),
            WorkspaceType::Business->accentClasses(),
        );
    }
}
