# Add Agentic to an existing site

**Keep your site. Let your coding agent handle the integration.** Give it this guide inside your
existing project, review its changes, and approve the hand-over. You can also follow the steps yourself.

This path adds content tools without replacing your frontend, templates, content model, or asset
pipeline. It supports **Statamic v6, PHP 8.3+, single locale**. Don't run the full starter-kit installer
unless you want its scaffold too.

## Give this to your agent

```text
Read this guide and integrate Agentic into this existing Statamic site.
Inspect the project first, work on a new branch, and follow the checklist below.
Preserve existing content, schemas, design, and application configuration.
Ask me to approve the editing scope and publishing rules. Verify the integration
and show me the diff before hand-over. Don't push, deploy, or change remote
permissions unless I explicitly approve it.
```

**Already using Statamic MCP?** Your agent can use read-only blueprint and collection inspection
through an existing connection—for example, the community
[MCP Server for Statamic](https://statamic.com/addons/cboxdk/statamic-mcp). It's optional: reading
the project files is enough. MCP doesn't replace Agentic's validation or guardrails, and this guide
doesn't require installing it or granting write access.

## Integration checklist

### 1. Inspect, then add only what's needed

Start from a committed or backed-up site with Composer dependencies installed. Preserve other work.
Get the kit outside the site directory:

```sh
git clone --depth 1 https://github.com/sanderjn/statamic-agentic.git ../statamic-agentic
```

Copy these files from its `export/` directory to the same paths in the site:

- `app/Console/Commands/BuildAgentCatalog.php`
- `app/Console/Commands/ValidateContent.php`
- `config/agentic.php`

**Merge existing files; never overwrite them blindly.** Don't copy the kit's Composer/frontend
manifests, content, schemas, views, providers, asset classes, or Statamic config. Verify both commands
appear in `php artisan list`; register them through the site's command discovery if needed.

### 2. Point the catalogue at the site's blueprints

Set actual blueprint handles in `config/agentic.php`. For example:

```php
'catalog_blueprints' => ['collections.news.article', 'globals.company'],
'blocks_view_path' => null,
```

`collections.news.article` means `resources/blueprints/collections/news/article.yaml`. Select each
blueprint needed; imports and nested fields are resolved automatically. Missing blueprints fail
generation without replacing the previous catalogue.

- **Catalogue sources aren't permissions.** Validation still checks all content.
- **Null skips only the block-partial filename convention.** Keep a view path if the site uses it.
- **Assets:** list only repo/CI-accessible containers in `committed_asset_containers`.
- **Prefer the starter catalogue?** Leave `catalog_blueprints` empty. Use `page_builder_fieldset`
  and, if the field has a different handle, `page_builder_field`.

### 3. Adapt the brief and guardrails

Use the kit's `export/` files as templates, not drop-in replacements:

- **Editing brief:** adapt `content/AGENTS.md` and optionally `content/editor-notes.md`. Replace
  assumptions about `pages`, the `page` blueprint, and `main` navigation. Document allowed content,
  examples, custom fields, entry creation/deletion, IDs, slugs, trees, and menu updates.
- **Editor integration:** merge `CLAUDE.md`, `.claude/settings.json`, and `.claude/hooks/` for Claude
  Code; supply equivalent enforcement for another agent. The supplied local hook needs PHP.
- **CI:** merge `.github/workflows/content-guardrails.yml` into existing checks. Keep the site's
  build/deploy process and replace maintainer placeholders. Match branch names across triggers,
  conditions, permissions, and instructions; `agentic:setup` doesn't synchronize every literal reference.
- **Developer decisions:** confirm editable paths, local versus remote validation, preview URL,
  branches, and publication approval. Content editors must not edit the brief, catalogue, code, policy,
  secrets, accounts, or form submissions. A failed local runtime isn't permission to bypass validation.
- **Production gate:** require checks and authorized review before deployment. Post-push CI doesn't
  stop an independent auto-deploy; author-email exemptions and `[BOT]` prefixes aren't trusted identities.

If assets or catalogue/brief paths differ from the defaults, update the path policy, instructions,
and CI to match. Keep policy changes developer-only. If entry creation or a custom field's storage
format isn't understood, ask the developer—don't guess.

### 4. Verify, then hand over

```sh
php artisan statamic:stache:refresh
php artisan content:catalog
php artisan content:validate
```

Review the generated reference. After committing it, CI should regenerate it and run
`git diff --exit-code -- content/agent-reference.md` to catch drift (adjust for a custom path).

Before hand-over, confirm in a test/preview environment:

- A valid edit renders correctly; invalid required values/options fail validation. Test custom and
  nested fields explicitly: catalogue support isn't a guarantee of complete validation coverage.
- The local guard rejects template edits and protects the brief/catalogue.
- Required checks and review gate production deployment.
- The final diff contains only deliberate integration changes—not a replaced site.

**You own the result.** The agent does the work; the developer reviews it. This is a copied-code
integration, not a managed addon, so future kit updates need review too. The default starter workflow
stays unchanged.
