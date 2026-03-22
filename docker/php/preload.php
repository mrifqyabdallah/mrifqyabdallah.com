<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';

$classesToPreload = [
    // Core/HTTP
    \Illuminate\Http\Request::class,
    \Illuminate\Http\Response::class,
    \Illuminate\Routing\Router::class,
    \Illuminate\Foundation\Application::class,

    // Database/Eloquent
    \Illuminate\Database\Eloquent\Model::class,
    \Illuminate\Database\Eloquent\Builder::class,
    \Illuminate\Database\Query\Builder::class,
    \Illuminate\Database\Eloquent\Relations\Relation::class,
    \Illuminate\Database\Eloquent\Collection::class,

    // Container/Support
    \Illuminate\Container\Container::class,
    \Illuminate\Support\Collection::class,
    \Illuminate\Support\Str::class,
    \Illuminate\Support\Arr::class,
    \Illuminate\Support\Facades\Facade::class,

    // Common middleware
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,

    // Auth related
    \Illuminate\Auth\AuthManager::class,
    \Illuminate\Auth\SessionGuard::class,
    \Illuminate\Auth\EloquentUserProvider::class,

    // Queue related
    \Illuminate\Queue\Worker::class,
    \Illuminate\Queue\QueueManager::class,
    \Illuminate\Queue\Jobs\Job::class,

    // Inertia
    \Inertia\Inertia::class,
    \Inertia\Response::class,
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
preloadDirectory(__DIR__.'/app/Http/Controllers', '/\.php$/');
preloadDirectory(__DIR__.'/app/Models', '/\.php$/');
preloadDirectory(__DIR__.'/app/Jobs', '/\.php$/');
preloadDirectory(__DIR__.'/app/Providers', '/\.php$/');

// Free memory
gc_collect_cycles();
