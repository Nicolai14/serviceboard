<?php

namespace Database\Factories;

use App\Enums\WorkspaceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workspace>
 */
class WorkspaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name'    => 'Privat',
            'type'    => WorkspaceType::Personal,
            'color'   => '#3b82f6',
            'settings' => null,
        ];
    }

    public function personal(): static
    {
        return $this->state([
            'name'  => 'Privat',
            'type'  => WorkspaceType::Personal,
            'color' => '#3b82f6',
        ]);
    }

    public function business(): static
    {
        return $this->state([
            'name'  => 'Geschäftlich',
            'type'  => WorkspaceType::Business,
            'color' => '#f59e0b',
        ]);
    }
}
