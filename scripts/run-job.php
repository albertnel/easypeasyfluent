<?php

use App\Models\BackgroundJob;
use Carbon\Carbon;

// Autoload classes using Composer
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    echo "Error: Composer dependencies are not installed. Please run 'composer install'.\n";
    exit(1);
}
require_once $autoloadPath;

// Bootstrap the Laravel application
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Parse CLI arguments. If argument count is less than 3, show usage message.
if ($argc < 3) {
    echo "Usage: php run-job.php ClassName methodName \"param1,param2\"\n";
    exit(1);
}

$className = $argv[1];
$methodName = $argv[2];
$params = isset($argv[3]) ? array_map('trim', explode(',', trim($argv[3], '"'))) : [];
$delay = isset($argv[4]) ? (int)$argv[4] : null;
$priority = isset($argv[5]) ? (int)$argv[5] : 10;

// Log the class name, method name, and parameters
writeLogMessage(
    "INFO: Received job request - Class: $className, Method: $methodName, Params: " . json_encode($params),
    storage_path('logs/background_jobs.log')
);

// Sanitize inputs
$className = filter_var($className, FILTER_SANITIZE_STRING);
$methodName = filter_var($methodName, FILTER_SANITIZE_SPECIAL_CHARS);

// Validate class name format
if (!preg_match('/^[A-Za-z0-9\\\\]+$/', $className)) {
    writeLogMessage('ERROR: Invalid class name format: ' . $className, storage_path('logs/background_jobs_errors.log'));
    throw new Exception("Invalid class name format: '$className'.");
}

// Validate method name format
if (!preg_match('/^[A-Za-z0-9_]+$/', $methodName)) {
    writeLogMessage('ERROR: Invalid method name format: ' . $methodName, storage_path('logs/background_jobs_errors.log'));
    throw new Exception("Invalid method name format: '$methodName'.");
}

// Validate delay is an integer if provided
if ($delay !== null && !is_int($delay)) {
    writeLogMessage('ERROR: Delay must be an integer.', storage_path('logs/background_jobs_errors.log'));
    throw new Exception("Invalid delay value. Delay must be an integer.");
}

// Validate priority is an integer if provided
if ($priority !== null && !is_int($priority)) {
    writeLogMessage('ERROR: Priority must be an integer.', storage_path('logs/background_jobs_errors.log'));
    throw new Exception("Invalid priority value. Priority must be an integer.");
}

// Load allowed jobs from the configuration file
$allowedJobs = config('background-jobs.allowed_jobs');
$retryInterval = config('background-jobs.retry_interval', 5);
$maxRetries = config('background-jobs.max_retries', 5);

try {
    // Validate the class name
    if (!isset($allowedJobs[$className])) {
        writeLogMessage('ERROR: Class ' . $className . ' is not allowed.', storage_path('logs/background_jobs_errors.log'));
        throw new Exception("Class '$className' is not allowed.");
    }

    // Check if the class exists
    if (!class_exists($className)) {
        writeLogMessage('ERROR: Class ' . $className . ' not found.', storage_path('logs/background_jobs_errors.log'));
        throw new Exception("Class '$className' not found.");
    }

    // Validate the method name
    if (!in_array($methodName, $allowedJobs[$className])) {
        writeLogMessage('ERROR: Method ' . $methodName . ' is not allowed for class ' . $className, storage_path('logs/background_jobs_errors.log'));
        throw new Exception("Method '$methodName' is not allowed for class '$className'.");
    }

    // Check if the job already exists
    $job = BackgroundJob::where('class', $className)
        ->where('method', $methodName)
        ->where('parameters', '"' . implode(',', $params) . '"')
        ->where('priority', $priority)
        ->orderBy('priority', 'desc') // Higher priority first
        ->orderBy('created_at', 'desc')
        ->first();

    if (!$job || $job->status === 'success') {
        // If no job exists or the last job was successful, create a new job
        $jobData = [
            'class' => $className,
            'method' => $methodName,
            'parameters' => implode(',', $params),
            'priority' => $priority,
        ];

        if ($delay !== null) {
            // If delay is specified, set status to "pending" and calculate next_retry_at
            $jobData['status'] = 'pending';
            $jobData['next_retry_at'] = Carbon::now()->addSeconds($delay);
        } else {
            // Otherwise, set status to "running"
            $jobData['status'] = 'running';
        }

        $job = BackgroundJob::create($jobData);

        if ($jobData['status'] === 'pending') {
            writeLogMessage("INFO: Job scheduled as pending. Exiting script.", storage_path('logs/background_jobs.log'));
            echo "Job scheduled as pending. Exiting script.\n";
            exit(0);
        }
    } elseif ($job->status === 'pending') {
        // If the job is pending, check if it's time to run it
        if ($job->next_retry_at && $job->next_retry_at->isPast()) {
            $job->update(['status' => 'running']);
        } else {
            writeLogMessage("INFO: Job is still pending. Exiting script.", storage_path('logs/background_jobs.log'));
            echo "Job is still pending. Exiting script.\n";
            exit(0);
        }
    } elseif ($job->status === 'running') {
        // Job is already running
        writeLogMessage("ERROR: Job is already running.", storage_path('logs/background_jobs_errors.log'));
        throw new Exception("Job is already running.");
    }
    elseif ($job->status === 'failed' && $job->retry_count < $maxRetries) {
        // Retry a failed job
        if ($job->next_retry_at && $job->next_retry_at->isFuture()) {
            throw new Exception("Job is scheduled to retry at {$job->next_retry_at}.");
        }

        $job->update([
            'status' => 'running',
            'retry_count' => $job->retry_count + 1,
        ]);
    } else {
        throw new Exception("Job cannot be retried. Maximum retries reached or job is already running.");
    }

    // Instantiate the class
    $instance = new $className();

    // Check if the method exists
    if (!method_exists($instance, $methodName)) {
        writeLogMessage('ERROR: Method ' . $methodName . ' not found.', storage_path('logs/background_jobs_errors.log'));
        throw new Exception("Method '$methodName' not found in class '$className'.");
    }

    // Execute the method with parameters
    $result = call_user_func_array([$instance, $methodName], $params);

    // Mark the job as successful
    $job->update(['status' => 'success']);

    // Log success
    writeLogMessage("SUCCESS: Executed $className::$methodName with params [" . implode(', ', $params) . "]", storage_path('logs/background_jobs.log'));
    echo "Job executed successfully.\n";

} catch (Exception $e) {
    // Mark the job as failed and schedule a retry
    if (isset($job)) {
        $job->update([
            'status' => 'failed',
            'next_retry_at' => Carbon::now()->addMinutes($retryInterval),
        ]);
    }

    // Log failure
    writeLogMessage("FAILURE: " . $e->getMessage(), storage_path('logs/background_jobs_errors.log'));
    echo "Job execution failed: " . $e->getMessage() . "\n";
}