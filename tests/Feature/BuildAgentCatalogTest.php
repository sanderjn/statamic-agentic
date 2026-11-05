<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;
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

    public function test_selected_blueprints_resolve_imports_nested_fields_and_relationship_targets(): void
    {
        $directory = $this->tempContent.'/blueprints';
        File::ensureDirectoryExists($directory.'/collections/news');
        Blueprint::setDirectory($directory);
        File::put($directory.'/collections/news/article.yaml', <<<'YAML'
title: News article
tabs:
  main:
    fields:
      - import: seo
      - handle: headline
        field:
          type: text
          validate: [required]
          instructions: Use the approved headline.
      - handle: audience
        field:
          type: select
          options:
            trade: Trade partners
            public: General public
      - handle: related
        field:
          type: entries
          collections: [services, projects]
      - handle: topics
        field:
          type: terms
          taxonomies: [topics]
      - handle: cover
        field:
          type: assets
          container: media
      - handle: details
        field:
          type: group
          fields:
            - handle: rows
              field:
                type: grid
                fields:
                  - handle: label
                    field:
                      type: text
      - handle: story
        field:
          type: bard
          sets:
            content:
              sets:
                callout:
                  display: Callout
                  instructions: Use for important notices only.
                  fields:
                    - handle: priority
                      field:
                        type: select
                        options:
                          - key: urgent
                            value: Act now
                          - key: normal
                            value: For information
      - handle: sections
        field:
          type: replicator
          sets:
            quote:
              fields:
                - handle: attribution
                  field:
                    type: text
YAML);

        foreach (['globals.company', 'taxonomies.topics', 'navigation.footer'] as $handle) {
            Blueprint::makeFromFields(['summary' => ['type' => 'textarea']])
                ->setNamespace(substr($handle, 0, strrpos($handle, '.')))
                ->setHandle(substr($handle, strrpos($handle, '.') + 1))
                ->save();
        }
        Blueprint::makeFromFields(['private_note' => ['type' => 'text']])
            ->setNamespace('globals')->setHandle('internal')->save();

        config()->set('agentic.catalog_blueprints', [
            'collections.news.article', 'globals.company', 'taxonomies.topics', 'navigation.footer',
        ]);
        config()->set('agentic.page_builder_fieldset', 'not_used_on_this_site');

        $this->artisan('content:catalog')->assertSuccessful();
        $catalog = File::get(base_path('agent-reference-test.md'));

        foreach ([
            'News article (`collections.news.article`)',
            '`globals.company`', '`taxonomies.topics`', '`navigation.footer`',
            '`seo_title` — text', 'Optional. Overrides the page title',
            '`headline` — text (required)', 'Use the approved headline.',
            '`audience` — select; options: trade, public',
            '`related` — entries; collections: services, projects',
            '`topics` — terms; taxonomies: topics', '`cover` — assets; container: media',
            '`details` — group', '`rows` — grid', '`label` — text',
            'items of type `callout` (Callout)', 'Use for important notices only.',
            '`priority` — select; options: urgent, normal',
            'items of type `quote`', '`attribution` — text',
        ] as $expected) {
            $this->assertStringContainsString($expected, $catalog);
        }
        $this->assertStringNotContainsString('private_note', $catalog);
        $this->assertStringNotContainsString('General public', $catalog);

        $this->artisan('content:catalog')->assertSuccessful();
        $this->assertSame($catalog, File::get(base_path('agent-reference-test.md')));
    }

    public function test_missing_blueprint_does_not_replace_an_existing_catalog(): void
    {
        File::put(base_path('agent-reference-test.md'), 'Existing reference');
        config()->set('agentic.catalog_blueprints', ['collections.pages.page', 'globals.does_not_exist']);

        $this->artisan('content:catalog')
            ->expectsOutputToContain('globals.does_not_exist blueprint not found')
            ->assertFailed();

        $this->assertSame('Existing reference', File::get(base_path('agent-reference-test.md')));
    }

    public function test_page_builder_field_can_differ_from_its_fieldset_handle(): void
    {
        Fieldset::setDirectory($this->tempContent.'/fieldsets');
        Fieldset::make('layout')->setContents(['fields' => [
            ['handle' => 'sections', 'field' => ['type' => 'replicator', 'sets' => [
                'banner' => ['fields' => [
                    ['handle' => 'heading', 'field' => ['type' => 'text']],
                ]],
            ]]],
        ]])->save();
        config()->set('agentic.page_builder_fieldset', 'layout');
        config()->set('agentic.page_builder_field', 'sections');

        $this->artisan('content:catalog')->assertSuccessful();

        $catalog = File::get(base_path('agent-reference-test.md'));
        $this->assertStringContainsString('`banner`', $catalog);
        $this->assertStringContainsString('`heading` — text', $catalog);
    }
}
