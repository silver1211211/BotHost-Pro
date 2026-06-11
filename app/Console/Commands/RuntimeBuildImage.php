<?php

namespace App\Console\Commands;

use App\Services\DockerRuntimeService;
use Illuminate\Console\Command;

class RuntimeBuildImage extends Command
{
    protected $signature = 'runtime:build-image';
    protected $description = 'Build the Docker image used by isolated bot runtimes.';

    public function handle(DockerRuntimeService $runtime): int
    {
        $this->info('Building Docker runtime image...');
        $result = $runtime->buildImage();

        if (! ($result['ok'] ?? false)) {
            $this->error($result['error'] ?? 'Docker image build failed.');
            return self::FAILURE;
        }

        $this->info('Docker runtime image built successfully.');
        return self::SUCCESS;
    }
}
