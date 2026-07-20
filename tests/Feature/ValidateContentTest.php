<?php

namespace Tests\Feature;

use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Nav;
use Statamic\Facades\Site;
use Tests\TestCase;

class ValidateContentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Collection::make('pages')->save();
    }

    private function makeEntry(array $data, string $slug = 'home'): void
    {
        Entry::make()
            ->collection('pages')
            ->slug($slug)
            ->data($data)
            ->save();
    }

    public function test_valid_content_passes(): void
    {
        $this->makeEntry([
            'title' => 'Home',
            'page_builder' => [
                ['id' => 'a', 'type' => 'rich_text', 'body' => '<p>Welcome</p>'],
            ],
        ]);

        $this->artisan('content:validate')
            ->expectsOutputToContain('All content is valid.')
            ->assertSuccessful();
    }

    public function test_unknown_block_type_is_caught(): void
    {
        $this->makeEntry([
            'title' => 'Home',
            'page_builder' => [
                ['id' => 'a', 'type' => 'not_a_real_block'],
            ],
        ]);

        $this->artisan('content:validate')
            ->expectsOutputToContain("unknown type 'not_a_real_block'")
            ->assertFailed();
    }

    public function test_unknown_field_in_block_is_caught(): void
    {
        $this->makeEntry([
            'title' => 'Home',
            'page_builder' => [
                ['id' => 'a', 'type' => 'rich_text', 'body' => '<p>Hi</p>', 'bogus' => 'x'],
            ],
        ]);

        $this->artisan('content:validate')
            ->expectsOutputToContain("unknown field 'bogus'")
            ->assertFailed();
    }

    public function test_missing_required_title_is_caught(): void
    {
        $this->makeEntry([
            'page_builder' => [],
        ]);

        $this->artisan('content:validate')
            ->expectsOutputToContain('required')
            ->assertFailed();
    }

    private function makeNav(array $tree): void
    {
        $nav = Nav::make('main');
        $nav->save();
        $nav->makeTree(Site::default()->handle(), $tree)->save();
    }

    public function test_menu_linking_to_existing_pages_passes(): void
    {
        $this->makeEntry(['title' => 'Home', 'page_builder' => []]);
        $this->makeNav([['entry' => Entry::all()->first()->id()]]);

        $this->artisan('content:validate')
            ->expectsOutputToContain('All content is valid.')
            ->assertSuccessful();
    }

    public function test_menu_item_to_missing_page_is_caught(): void
    {
        $this->makeEntry(['title' => 'Home', 'page_builder' => []]);
        $this->makeNav([['entry' => 'ghost-page-that-was-deleted']]);

        $this->artisan('content:validate')
            ->expectsOutputToContain('no longer exists')
            ->assertFailed();
    }
}
