<?php

namespace App\Contracts;

use App\Models\Deployment;

interface DeploymentDriverContract
{
    public function run(Deployment $deployment): bool;

    public function dryRun(Deployment $deployment): array;

    public function supports(string $type): bool;
}
