<?php

return [
    // Fieldset the catalog + block-partial check read as the source of truth.
    'page_builder_fieldset' => 'page_builder',

    // Field inside that fieldset; null uses the fieldset handle, as in the starter.
    'page_builder_field' => null,

    // Optional fully qualified blueprint handles, e.g. collections.pages.page,
    // collections.posts.article, globals.company, taxonomies.tags, navigation.main.
    // When non-empty, catalog these blueprints instead of the page-builder fieldset.
    // This selects documentation sources, NOT permissions or validation scope.
    'catalog_blueprints' => [],

    // resources/views/<blocks_view_path>/<set-handle>.antlers.html (or .blade.php).
    // Set to null to skip this rendering convention on an existing site.
    'blocks_view_path' => 'blocks',

    // Containers whose files are committed to the repo and can be existence-checked.
    'committed_asset_containers' => ['assets'],

    // Generated agent catalog.
    'catalog_path' => 'content/agent-reference.md',

    // Content-agent brain.
    'agents_file' => 'content/AGENTS.md',

    // Developer-facing editor onboarding prompt (stamped by agentic:setup).
    'onboarding_file' => 'ONBOARDING.md',

    // Git topology the agent + docs assume.
    'branches' => [
        'work' => 'staging',
        'release' => 'main',
    ],
];
