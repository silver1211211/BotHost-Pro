<?php

namespace App\Services;

use App\Models\RuntimeReloadLog;

class RuntimeReloadReportBuilder
{
    public function toArray(RuntimeReloadLog $log): array
    {
        return [
            'id' => $log->id,
            'trigger_type' => $log->trigger_type,
            'status' => $log->status,
            'mode' => $log->mode,
            'current_step' => $log->current_step,
            'steps_total' => $log->steps_total,
            'steps_completed' => $log->steps_completed,
            'helpers_compiled' => $log->helpers_compiled,
            'containers_affected' => $log->containers_affected,
            'containers_ok' => $log->containers_ok,
            'containers_failed' => $log->containers_failed,
            'started_at' => $log->started_at?->toIso8601String(),
            'completed_at' => $log->completed_at?->toIso8601String(),
            'duration_ms' => $log->duration_ms,
            'output' => $log->parsedOutput(),
            'error' => $log->error,
            'created_at' => $log->created_at?->toIso8601String(),
            'updated_at' => $log->updated_at?->toIso8601String(),
        ];
    }

    public function toText(RuntimeReloadLog $log): string
    {
        $counts = $log->summaryCounts();
        $output = $log->parsedOutput();

        return implode(PHP_EOL, [
            'BotHost Pro Runtime Reload Report',
            'Log ID: '.$log->id,
            'Status: '.$log->status,
            'Trigger Type: '.$log->trigger_type,
            'Mode: '.($log->mode ?: 'None'),
            'Started: '.($log->started_at?->toDateTimeString() ?: 'None'),
            'Completed: '.($log->completed_at?->toDateTimeString() ?: 'None'),
            'Duration: '.($log->duration_ms ?? 0).'ms',
            'Current Step: '.($log->current_step ?: 'None'),
            '',
            'Summary:',
            '* Helpers compiled: '.($log->helpers_compiled ?? $counts['helpers_compiled']),
            '* Containers affected: '.($log->containers_affected ?? 0),
            '* Containers ok: '.($log->containers_ok ?? 0),
            '* Containers failed: '.($log->containers_failed ?? 0),
            '',
            'Output:',
            $this->readableOutput($output),
            '',
            'Error:',
            $log->error ?: 'None',
            '',
        ]);
    }

    private function readableOutput(array $output): string
    {
        if ($output === []) {
            return 'None';
        }

        $lines = [];
        foreach ($output as $key => $value) {
            if (is_array($value)) {
                $lines[] = $key.':';
                $rows = array_is_list($value) ? $value : [$value];
                if ($rows === []) {
                    $lines[] = '  None';
                    continue;
                }

                foreach ($rows as $row) {
                    $lines[] = '  - '.$this->flattenRow(is_array($row) ? $row : ['value' => $row]);
                }
                continue;
            }

            $lines[] = $key.': '.$this->scalar($value);
        }

        return implode(PHP_EOL, $lines);
    }

    private function flattenRow(array $row): string
    {
        return collect($row)
            ->map(fn ($value, string $key) => $key.'='.$this->scalar($value))
            ->implode(', ');
    }

    private function scalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '';
    }
}
