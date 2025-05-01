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
$params = isset($argv[3]) ? array_map('trim', explode(',', $argv[3])) : [];

// Log file path
$logFile = __DIR__ . '/job_logs.txt';

// Function to log messages
function logMessage($message, $logFile)
{
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Check if the class exists
    if (!class_exists($className)) {
        throw new Exception("Class '$className' not found.");
    }

    // Instantiate the class
    $instance = new $className();

    // Check if the method exists
    if (!method_exists($instance, $methodName)) {
        throw new Exception("Method '$methodName' not found in class '$className'.");
    }

    // Execute the method with parameters
    $result = call_user_func_array([$instance, $methodName], $params);

    // Log success
    logMessage("SUCCESS: Executed $className::$methodName with params [" . implode(', ', $params) . "]", $logFile);
    echo "Job executed successfully.\n";

} catch (Exception $e) {
    // Log failure
    logMessage("FAILURE: " . $e->getMessage(), $logFile);
    echo "Job execution failed: " . $e->getMessage() . "\n";
}