<?php

namespace Tests\Support;

use App\Console\Commands\AgenticSetup;
use App\Console\Commands\BuildAgentCatalog;
use App\Console\Commands\ValidateContent;
use Illuminate\Support\ServiceProvider;

/**
 * A real Statamic site auto-discovers these via app/Console/Kernel; testbench
 * does not, so register them explicitly for the test harness.
 */
class KitTestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ValidateContent::class,
                BuildAgentCatalog::class,
                AgenticSetup::class,
            ]);
        }
    }
}
