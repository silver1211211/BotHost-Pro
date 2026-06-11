<?php

namespace App\Console\Commands;

use App\Services\DockerRuntimeService;
use Illuminate\Console\Command;

class RuntimeCleanup extends Command
{
    protected $signature = 'runtime:cleanup';
    protected $description = 'Remove orphan Docker runtime containers.';

    public function handle(DockerRuntimeService $runtime): int
    {
        $result = $runtime->cleanupOrphans();

        if (! ($result['ok'] ?? false)) {
            $this->error($result['error'] ?? 'Runtime cleanup failed.');
            return self::FAILURE;
        }

        $this->info('Removed orphan runtime containers: '.$result['removed']);
        return self::SUCCESS;
    }
}
