<?php

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

if (!function_exists('writeLogMessage')) {
    /**
     * Write a log message to a specified file.
     *
     * @param string $message The log message.
     * @param string $logFile The path to the log file.
     * @return void
     */
    function writeLogMessage($message, $logFile)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}

if (!function_exists('runBackgroundJob')) {
    /**
     * Run a background job script.
     *
     * @param string $class The class name to execute.
     * @param string $method The method name to execute.
     * @param array $params Parameters to pass to the method.
     * @return void
     */
    function runBackgroundJob($class, $method, $params = [], $delay = null, $priority = 10)
    {
        $phpBinary = config('background-jobs.php_binary');
        if (!$phpBinary) {
            $errorMessage = 'PHP binary path is not configured.';
            writeLogMessage('ERROR: ' . $errorMessage, storage_path('logs/background_jobs_errors.log'));
            throw new RuntimeException($errorMessage);
        } // Use the configured path or fallback to PHP_BINARY

        $paramString = implode(',', $params);
        $command = [
            $phpBinary, // Use the dynamically found PHP binary path
            base_path('scripts/run-job.php'),
            $class,
            $method,
            $paramString,
            $delay ? $delay : '',
            $priority
        ];

        writeLogMessage('RUNNING: ' . implode(' ', $command), storage_path('logs/background_jobs.log'));

        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            // Windows
            $command = array_merge(['start', '/B'], $command);
            $process = new Process($command);
        } else {
            // Linux/Unix
            $commandString = implode(' ', array_map('escapeshellarg', $command)) . ' > /dev/null 2>&1 &';
            $process = Process::fromShellCommandline($commandString);
        }

        try {
            $process->run();

            // Check if the process was successful
            if (!$process->isSuccessful()) {
                writeLogMessage('FAILED: ' . $process->getErrorOutput(), storage_path('logs/background_jobs.log'));
                throw new ProcessFailedException($process);
            }

            // Log the output
            $output = $process->getOutput();
            writeLogMessage('COMPLETED: ' . implode(' ', $command), storage_path('logs/background_jobs.log'));
        } catch (ProcessFailedException $e) {
            writeLogMessage('FAILED: Process failed. ' . $e->getMessage(), storage_path('logs/background_jobs_errors.log'));
        } catch (Exception $e) {
            writeLogMessage('FAILED: Unexpected error. ' . $e->getMessage(), storage_path('logs/background_jobs_errors.log'));
        }
    }
}