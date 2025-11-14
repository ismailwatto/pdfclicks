<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ProgressTracking
{
    protected int $currentProgress = 0;

    protected string $currentStatus = '';

    protected $progressCallback = null;

    protected function setProgressCallback(callable $callback): void
    {
        $this->progressCallback = $callback;
    }

    protected function updateProgress(int $progress, string $status = ''): void
    {
        $this->currentProgress = max(0, min(100, $progress));
        $this->currentStatus = $status;

        if ($this->progressCallback) {
            call_user_func($this->progressCallback, $this->currentProgress, $this->currentStatus);
        }

        // Log progress for debugging
        Log::debug("Processing progress: {$this->currentProgress}% - {$this->currentStatus}");
    }

    protected function getProgress(): array
    {
        return [
            'progress' => $this->currentProgress,
            'status' => $this->currentStatus,
        ];
    }

    protected function resetProgress(): void
    {
        $this->currentProgress = 0;
        $this->currentStatus = '';
    }

    protected function completeProgress(string $message = 'Processing completed'): void
    {
        $this->updateProgress(100, $message);
    }

    protected function calculateStepProgress(int $currentStep, int $totalSteps, int $baseProgress = 0, int $maxProgress = 100): int
    {
        if ($totalSteps <= 0) {
            return $baseProgress;
        }

        $stepSize = ($maxProgress - $baseProgress) / $totalSteps;

        return $baseProgress + (int) ($currentStep * $stepSize);
    }

    protected function updateStepProgress(int $currentStep, int $totalSteps, string $action, int $baseProgress = 0, int $maxProgress = 100): void
    {
        $progress = $this->calculateStepProgress($currentStep, $totalSteps, $baseProgress, $maxProgress);
        $status = "{$action} ({$currentStep}/{$totalSteps})";
        $this->updateProgress($progress, $status);
    }
}
