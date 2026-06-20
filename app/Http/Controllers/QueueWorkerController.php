<?php

namespace App\Http\Controllers;

class QueueWorkerController extends Controller
{
    private string $pidFile;

    public function __construct()
    {
        $this->pidFile = storage_path('app/queue-worker.pid');
    }

    public function status()
    {
        return response()->json(['running' => $this->isRunning()]);
    }

    public function start()
    {
        if ($this->isRunning()) {
            return response()->json(['running' => true]);
        }

        $artisan = base_path('artisan');
        $log = storage_path('logs/queue-worker.log');
        exec("nohup php {$artisan} queue:work --queue=wordpress,default --tries=3 --timeout=310 >> {$log} 2>&1 & echo \$!", $output);

        $pid = intval($output[0] ?? 0);
        if ($pid <= 0) {
            return response()->json(['running' => false, 'message' => 'Failed to start worker'], 500);
        }

        file_put_contents($this->pidFile, $pid);

        return response()->json(['running' => true]);
    }

    public function stop()
    {
        if (!$this->isRunning()) {
            return response()->json(['running' => false]);
        }

        $pid = intval(file_get_contents($this->pidFile));
        if ($pid > 0) {
            exec("kill {$pid}");
        }

        @unlink($this->pidFile);

        return response()->json(['running' => false]);
    }

    private function isRunning(): bool
    {
        if (!file_exists($this->pidFile)) {
            return false;
        }

        $pid = intval(file_get_contents($this->pidFile));
        if ($pid <= 0) {
            return false;
        }

        exec("ps -p {$pid}", $output, $exitCode);

        if ($exitCode !== 0) {
            @unlink($this->pidFile);
            return false;
        }

        return true;
    }
}
