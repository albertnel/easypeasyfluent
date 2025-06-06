# EasyPeasyFluent Background Job Runner

## Table of Contents
1. [Introduction](#introduction)
2. [Setup and Installation](#setup-and-installation)
3. [Usage](#usage)
    - [Using the `runBackgroundJob` Function](#using-the-runbackgroundjob-function)
4. [Features](#features)
    - [Retry Attempts](#retry-attempts)
    - [Delays](#delays)
    - [Job Priorities](#job-priorities)
    - [Dashboard](#dashboard)
    - [Security](#security)
    - [Testing and Logs](#testing-and-logs)
5. [Bonus Points](#bonus-points)


## Introduction

Hi! Thank you for this very fun assignment. I enjoyed working on it a lot and was an interesting thought experiment when we consider doing something outside the typical workflow like using Laravel's built-in queueing system.

I created this project to do the following, as requested:

* I created a file `scripts/run-job.php` to instantiate the class provided and run the specified class method with the supplied parameters in the background of the current operation system.
* Windows and Linux-based operating systems are both supported.
* Create a helper task `runBackgroundJob` that calls the `scripts/run-job.php` script in a clean, accessible way.

## Setup and Installation

1. Open `config/background-jobs.php` and you'll see the following configurations:
    - `php_binary`: I used Herd to set the project up, and the FPM process and the command line uses different PHP binaries, so please provide the full path to your local PHP binary here that you get from `which php`.
    - `allowed_jobs`: Here you whitelist all the classes and associated class methods are are allowed to be run with this background job code.
    - `retry_interval`: Specify how many minutes before a failed job can be retried again.
    - `max_retries`: The maximum number of retries before a job is marked as failed.

2. Follow the steps below to install the package:
    - Run `composer install`.
    - Publish the configuration file using `php artisan vendor:publish --tag=background-jobs-config`.
    - Ensure the `scripts/run-job.php` file is executable by running `chmod +x scripts/run-job.php`.
    - Run `php artisan migrate` to create the required database tables.

## Usage

### Using the `runBackgroundJob` Function

Running the `runBackgroundJob` function is extremely simple. It's available on a global scope and you simply call it from wherever you need to queue a asynchronous background job.

```php
runBackgroundJob(
    string $className,
    string $methodName,
    array $parameters = [],
    array $delay = []
);
```

#### Parameters:
- **$className**: The fully qualified class name of the job to be executed.
- **$methodName**: The method within the class to be called.
- **$parameters**: (Optional) An array of parameters to pass to the method.
- **$delay**: (Optional) Delay execution of the job in second.
- **$priority**: (Optional) Priority of the job, integer.

#### Example:

```php
runBackgroundJob(
    App\Jobs\ExampleJob::class,
    'execute',
    ['param1', 'param2'],
    60
);
```

Below, an example I used the whole time with a class I created to test this:

```php
runBackgroundJob(
    'App\Services\UserSeederService',
    'seedUsers',
    ['10'],
    60
);
```

You can also run the script directly from the terminal. Example:

```bash
<php-binary-path> '<easypeasyfluent-project-path>/scripts/run-job.php' 'App\Services\UserSeederService' seedUsers 2 60
```

## Features

### Retry Attempts

From `config/background-jobs.php` you'll find a globally accesible config value:
```php
config('background-jobs.retry_interval');
```

This values specifies, in minutes, when to schedule the next retry of a failed job. Minutes was chosen to adhere to the smallest timeframe you can schedule cron jobs on, in case background tasks was eventually moved to a cron schedule.

### Delays
Delay of execution is supported as an optional 4th parameter when calling the helper function `runBackgroundJob()`. This delays execution to only happen when the time is past `next_retry_at` datetime as stored in the `background_jobs` database table for the particular record.

### Job Priorities

Job priority is introduced as an optional 5th parameter of the global helper function `runBackgroundJob()`.

This specifies the order of priority to place a new job in and on successive retrievals of multiple jobs, all jobs will be sorted by priority first so that they run according to priority and not simply by created_at date.

Example to create a new job and set it to priority 2.

```php
runBackgroundJob(
    'App\Services\UserSeederService',
    'seedUsers',
    ['10'],
    60,
    2
);
```

### Dashboard

A dashboard has been created where you can see all the background jobs and their statuses.

I created two files for this.

1. Livewire component: `app/Livewire/BackgroundJobsDashboard.php` 
2. Liveware and blade template: `resources/views/livewire/background-jobs-dashboard.blade.php`

I opted to just do this with Livewire as it's nice and easy, however I am perfectly comfortable to have done it with Vue.js or React.js as well.

To view it, please register for a new account by going to `/register`. After registration you'll be logged in. When logged in you'll see a link on the left sidebar called Background Jobs.

### Security

A few important security features were created and implemented.

1. Only whitelisted classes and class methods are allowed to be queue for this background job process.
2. All inputs are properly sanitized.
3. There are class and method validations done on both the class name and method name.

### Testing and Logs

To test, I created a controller that simply seeds some users to the database. If you run the project via Herd, you can access it as follows:

http://easypeasyfluent.test/seed-users?count=21

Of course, just replace the easypeasyfluent.test domain with your local one.

The following logs have been created:

- `storage/logs/background_jobs.log` - contains information about the starting, running and completion of jobs.
- `storage/logs/background_jobs_errors.log` - contains all errors encountered during execution of the background job.

I've also added sample populated logs I generated during my testing. It can be found in the folder `sample_logs`.

## Bonus Points
- ✅ I'm happy to report that I implemented the call of the background process using Symfony/Process.
- ✅ I implemented try/catch with exception handling (specific and finally general type exceptions) in every place where execution starts and might throw an error.
- ✅ Job delays
- ✅ Job priorities
- ✅ Basic dashboard