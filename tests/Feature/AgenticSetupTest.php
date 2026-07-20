<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AgenticSetupTest extends TestCase
{
    private string $agentsPath;

    private string $ciPath;

    protected function setUp(): void
    {
        parent::setUp();

        // The command stamps base_path('content/AGENTS.md') and the CI workflow;
        // seed both from the shipped templates.
        $this->agentsPath = base_path('content/AGENTS.md');
        $this->ciPath = base_path('.github/workflows/content-guardrails.yml');

        File::ensureDirectoryExists(dirname($this->agentsPath));
        File::ensureDirectoryExists(dirname($this->ciPath));
        File::copy($this->kitRoot.'/export/content/AGENTS.md', $this->agentsPath);
        File::copy($this->kitRoot.'/export/.github/workflows/content-guardrails.yml', $this->ciPath);
    }

    protected function tearDown(): void
    {
        File::delete($this->agentsPath);
        File::delete($this->ciPath);

        parent::tearDown();
    }

    private function stamp(string $name, string $maintainer, string $emails): void
    {
        $this->artisan('agentic:setup', [
            '--site-name' => $name,
            '--site-description' => 'A test site.',
            '--maintainer' => $maintainer,
            '--maintainer-emails' => $emails,
            '--work-branch' => 'staging',
            '--release-branch' => 'main',
        ])->assertSuccessful();
    }

    public function test_it_stamps_the_docs_and_ci(): void
    {
        $this->stamp('Acme Co', 'octocat', 'dev@acme.test');

        $agents = File::get($this->agentsPath);
        $ci = File::get($this->ciPath);

        $this->assertStringContainsString('<!-- agentic:site_name -->Acme Co<!-- /agentic:site_name -->', $agents);
        $this->assertStringContainsString('MAINTAINER_EMAILS: "dev@acme.test"', $ci);
        $this->assertStringContainsString('MAINTAINERS: "octocat"', $ci);
    }

    public function test_it_is_idempotent(): void
    {
        $this->stamp('First Name', 'octocat', 'dev@acme.test');
        $this->stamp('Second Name', 'hubot', 'ops@acme.test');

        $agents = File::get($this->agentsPath);
        $ci = File::get($this->ciPath);

        // Re-running replaces the value rather than duplicating markers.
        $this->assertStringContainsString('<!-- agentic:site_name -->Second Name<!-- /agentic:site_name -->', $agents);
        $this->assertStringNotContainsString('First Name', $agents);
        $this->assertSame(1, substr_count($agents, '<!-- agentic:site_name -->'));
        $this->assertStringContainsString('MAINTAINER_EMAILS: "ops@acme.test"', $ci);
        $this->assertStringContainsString('MAINTAINERS: "hubot"', $ci);
    }
}
