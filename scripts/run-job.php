<?php

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

// Sanitize inputs
$className = filter_var($className, FILTER_SANITIZE_SPECIAL_CHARS);
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

// Load allowed jobs from the configuration file
$allowedJobs = config('background-jobs.allowed_jobs');

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

    // Instantiate the class
    $instance = new $className();

    // Check if the method exists
    if (!method_exists($instance, $methodName)) {
        writeLogMessage('ERROR: Method ' . $methodName . ' not found.', storage_path('logs/background_jobs_errors.log'));
        throw new Exception("Method '$methodName' not found in class '$className'.");
    }

    // Execute the method with parameters
    $result = call_user_func_array([$instance, $methodName], $params);

    // Log success
    writeLogMessage("SUCCESS: Executed $className::$methodName with params [" . implode(', ', $params) . "]", storage_path('logs/background_jobs.log'));
    echo "Job executed successfully.\n";

} catch (Exception $e) {
    // Log failure
    writeLogMessage("FAILURE: " . $e->getMessage(), storage_path('logs/background_jobs_errors.log'));
    echo "Job execution failed: " . $e->getMessage() . "\n";
}