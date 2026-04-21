<?php

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Container\Container;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Inertia\Inertia;
use Inertia\Response;

require '/app/vendor/autoload.php';
$app = require '/app/bootstrap/app.php';

$classesToPreload = [
    // Core/HTTP
    Request::class,
    Illuminate\Http\Response::class,
    Router::class,
    Application::class,

    // Database/Eloquent
    Model::class,
    Builder::class,
    Illuminate\Database\Query\Builder::class,
    Relation::class,
    Illuminate\Database\Eloquent\Collection::class,

    // Container/Support
    Container::class,
    Collection::class,
    Str::class,
    Arr::class,
    Facade::class,

    // Common middleware
    EncryptCookies::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
    SubstituteBindings::class,

    // Auth related
    AuthManager::class,
    SessionGuard::class,
    EloquentUserProvider::class,

    // Queue related
    Worker::class,
    QueueManager::class,
    Job::class,

    // Inertia
    Inertia::class,
    Response::class,
];

function preloadClass($class)
{
    if (class_exists($class, false)) {
        // Already loaded
        return;
    }

    if (! class_exists($class)) {
        return;
    }

    $rc = new ReflectionClass($class);

    // Preload methods and their static variables
    foreach ($rc->getMethods() as $method) {
        if ($method->isPublic() && ! $method->isAbstract()) {
            $method->getStaticVariables();
        }
    }

    // Preload parent classes
    $parent = $rc->getParentClass();
    if ($parent) {
        preloadClass($parent->getName());
    }

    // Preload interfaces
    foreach ($rc->getInterfaces() as $interface) {
        preloadClass($interface->getName());
    }
}

function preloadDirectory($dir, $pattern)
{
    if (! is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match($pattern, $file->getPathname())) {
            require_once $file->getPathname();
        }
    }
}

// Preload framework classes
foreach ($classesToPreload as $class) {
    preloadClass($class);
}

// Preload application directories
preloadDirectory('/app/app/Http/Controllers', '/\.php$/');
preloadDirectory('/app/app/Models', '/\.php$/');
preloadDirectory('/app/app/Jobs', '/\.php$/');
preloadDirectory('/app/app/Providers', '/\.php$/');

// Free memory
gc_collect_cycles();
