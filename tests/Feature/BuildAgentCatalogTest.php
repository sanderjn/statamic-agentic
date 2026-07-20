<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BuildAgentCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('agentic.catalog_path', 'agent-reference-test.md');
    }

    protected function tearDown(): void
    {
        File::delete(base_path('agent-reference-test.md'));

        parent::tearDown();
    }

    public function test_catalog_lists_the_shipped_block_and_its_fields(): void
    {
        $this->artisan('content:catalog')->assertSuccessful();

        $catalog = File::get(base_path('agent-reference-test.md'));

        $this->assertStringContainsString('`rich_text`', $catalog);
        $this->assertStringContainsString('`body` — bard', $catalog);
    }

    public function test_catalog_is_stable_across_runs(): void
    {
        $this->artisan('content:catalog')->assertSuccessful();
        $first = File::get(base_path('agent-reference-test.md'));

        $this->artisan('content:catalog')->assertSuccessful();
        $second = File::get(base_path('agent-reference-test.md'));

        $this->assertSame($first, $second);
    }

    public function test_generated_catalog_matches_the_committed_file(): void
    {
        // The CI freshness check fails if the shipped catalog drifts from what
        // the generator produces; assert they already agree.
        $this->artisan('content:catalog')->assertSuccessful();

        $this->assertSame(
            File::get($this->kitRoot.'/export/content/agent-reference.md'),
            File::get(base_path('agent-reference-test.md')),
        );
    }
}
