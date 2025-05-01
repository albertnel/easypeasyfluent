<?php

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

if (!function_exists('runBackgroundJob')) {
    /**
     * Run a background job script.
     *
     * @param string $class The class name to execute.
     * @param string $method The method name to execute.
     * @param array $params Parameters to pass to the method.
     * @return void
     */
    function runBackgroundJob($class, $method, $params = [])
    {
        // Build the command to execute the script
        $command = sprintf(
            'php %s/scripts/run-job.php %s %s %s',
            base_path(),
            escapeshellarg($class),
            escapeshellarg($method),
            implode(' ', array_map('escapeshellarg', $params))
        );

        // Use Symfony Process to execute the command
        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows: Run the process in the background
            $process = new Process(["cmd", "/c", "start /B " . $command]);
        } else {
            // Unix: Run the process in the background
            $process = new Process([$command . " > /dev/null 2>&1 &"]);
        }

        try {
            $process->run();
        } catch (ProcessFailedException $e) {
            // Log the error if the process fails
            $errorMessage = 'Failed to run background job: ' . $e->getMessage();
            Log::error($errorMessage);

            // Log to a specific file
            $errorLogPath = storage_path('logs/background_jobs_errors.log');
            file_put_contents($errorLogPath, '[' . now() . '] ' . $errorMessage . PHP_EOL, FILE_APPEND);
        } catch (Exception $e) {
            // Catch any other exceptions and log them
            $errorMessage = 'Unexpected error in background job: ' . $e->getMessage();
            Log::error($errorMessage);

            // Log to a specific file
            $errorLogPath = storage_path('logs/background_jobs_errors.log');
            file_put_contents($errorLogPath, '[' . now() . '] ' . $errorMessage . PHP_EOL, FILE_APPEND);
        }
    }
}