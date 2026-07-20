<?php

namespace Tests;

use Illuminate\Support\Facades\File;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;
use Statamic\Providers\StatamicServiceProvider;
use Statamic\Statamic;
use Tests\Support\KitTestServiceProvider;

/**
 * Boots Statamic against the kit's real shipped blueprints, fieldsets, and
 * views (from export/), with content stores pointed at a throwaway temp dir so
 * tests can create entries without touching the repo.
 */
abstract class TestCase extends BaseTestCase
{
    protected string $kitRoot;

    protected string $tempContent;

    protected function setUp(): void
    {
        $this->kitRoot = dirname(__DIR__);
        $this->tempContent = sys_get_temp_dir().'/agentic-tests-'.getmypid();

        parent::setUp();

        $this->withoutVite();

        // The commands read the kit's real blueprints and fieldsets.
        Blueprint::setDirectory($this->kitRoot.'/export/resources/blueprints');
        Fieldset::setDirectory($this->kitRoot.'/export/resources/fieldsets');

        // ValidateContent::blockPartialProblems checks resource_path('views/...'),
        // so mirror the shipped block partials where it looks.
        $viewBlocks = resource_path('views/blocks');
        File::ensureDirectoryExists($viewBlocks);
        File::copyDirectory($this->kitRoot.'/export/resources/views/blocks', $viewBlocks);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempContent);
        File::deleteDirectory(resource_path('views/blocks'));

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            StatamicServiceProvider::class,
            ServiceProvider::class,
            KitTestServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return ['Statamic' => Statamic::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $kit = dirname(__DIR__);
        $content = sys_get_temp_dir().'/agentic-tests-'.getmypid();

        $app['config']->set('agentic', require $kit.'/export/config/agentic.php');

        $app['config']->set('statamic.users.repository', 'file');
        $app['config']->set('statamic.stache.watcher', false);

        $stores = [
            'collections' => '/collections',
            'entries' => '/collections',
            'taxonomies' => '/taxonomies',
            'terms' => '/taxonomies',
            'navigation' => '/navigation',
            'globals' => '/globals',
            'global-variables' => '/globals',
            'asset-containers' => '/assets',
            'collection-trees' => '/trees/collections',
            'nav-trees' => '/trees/navigation',
        ];

        foreach ($stores as $store => $suffix) {
            $app['config']->set("statamic.stache.stores.$store.directory", $content.$suffix);
        }
    }
}
