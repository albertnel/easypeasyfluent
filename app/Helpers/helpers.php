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
        // Find the PHP binary path dynamically
        /*$phpBinary = trim(shell_exec('which php'));
        if (!$phpBinary) {
            Log::error('PHP binary not found.');
            throw new RuntimeException('PHP binary not found.');
        }*/
        $phpBinary = '/Users/albertnel/Library/Application Support/Herd/bin//php';

        $paramString = implode(',', $params);
        $command = [
            $phpBinary, // Use the dynamically found PHP binary path
            base_path('scripts/run-job.php'),
            $class,
            $method,
            $paramString,
        ];

        // Log the command for debugging
        Log::debug('Running background job command: ' . implode(' ', $command));

        $process = new Process($command);

        try {
            $process->run();

            // Check if the process was successful
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Log the output for debugging
            $output = $process->getOutput();
            Log::info('Background job output: ' . $output);
        } catch (ProcessFailedException $e) {
            $errorMessage = 'Failed to run background job: ' . $e->getMessage();
            Log::error($errorMessage);
            $errorLogPath = storage_path('logs/background_jobs_errors.log');
            file_put_contents($errorLogPath, '[' . now() . '] ' . $errorMessage . PHP_EOL, FILE_APPEND);
        } catch (Exception $e) {
            $errorMessage = 'Unexpected error in background job: ' . $e->getMessage();
            Log::error($errorMessage);
            $errorLogPath = storage_path('logs/background_jobs_errors.log');
            file_put_contents($errorLogPath, '[' . now() . '] ' . $errorMessage . PHP_EOL, FILE_APPEND);
        }
    }
}