<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        putenv('DB_MYSQL_DRIVER=sqlite');
        putenv('WP_DB_DRIVER=sqlite');
        putenv('DB_DATABASE=:memory:');
        putenv('WP_DB_DATABASE=:memory:');

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if ($app->environment('testing')) {
            $sqliteConfig = [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ];

            $app['config']->set('database.default', 'mysql');
            $app['config']->set('database.connections.mysql', $sqliteConfig);
            $app['config']->set('database.connections.wp', $sqliteConfig);
        }

        return $app;
    }
}
