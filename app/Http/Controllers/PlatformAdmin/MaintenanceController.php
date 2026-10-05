<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class MaintenanceController extends Controller
{
    public function index(): View
    {
        return view('platform-admin.maintenance.index', [
            'log' => $this->latestLog(),
        ]);
    }

    public function gitPull(): RedirectResponse
    {
        return $this->run(['git', 'pull', 'origin', 'main'], 'Git pull completed.');
    }

    public function optimizeClear(): RedirectResponse
    {
        return $this->run([PHP_BINARY, 'artisan', 'optimize:clear'], 'Laravel caches cleared.');
    }

    public function clearLogs(): RedirectResponse
    {
        $files = glob(storage_path('logs/*.log')) ?: [];
        $cleared = 0;

        foreach ($files as $file) {
            if (is_file($file) && is_writable($file)) {
                file_put_contents($file, '');
                $cleared++;
            }
        }

        return back()->with('success', "Cleared {$cleared} Laravel log file(s).");
    }

    private function run(array $command, string $message): RedirectResponse
    {
        $process = new Process($command, base_path());
        $process->setTimeout(120);
        $process->run();

        return back()->with($process->isSuccessful() ? 'success' : 'failure', $message)
            ->with('command_output', trim($process->getOutput()."\n".$process->getErrorOutput()));
    }

    private function latestLog(): string
    {
        $files = glob(storage_path('logs/*.log')) ?: [];
        rsort($files);
        $file = $files[0] ?? storage_path('logs/laravel.log');

        if (! is_file($file)) {
            return 'No Laravel log file found.';
        }

        $contents = file_get_contents($file) ?: '';

        return substr($contents, -50000);
    }
}
