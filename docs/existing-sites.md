# Add Agentic to an existing Statamic site

This is a **manual, developer-led integration**, not an installer. No AI is needed for setup or
catalogue generation: PHP reads your existing blueprints and fieldsets. Your editor uses the
generated reference later.

Use this path to keep your own frontend, templates, content model, and asset pipeline. The full
starter-kit installer exports those areas too; don't use it just to add these commands to an
established site.

## 1. Start on a branch

Use a working Statamic v6 site with PHP 8.3+, a single locale, and its Composer dependencies
installed. Commit or back up your site first, then create an integration branch.

Get a copy of the kit outside your site's directory:

```sh
git clone --depth 1 https://github.com/sanderjn/statamic-agentic.git ../statamic-agentic
```

The kit's shippable files are in `../statamic-agentic/export/`. Review each destination before
copying; if it already exists, merge the changes manually. This recipe does not replace your
application files automatically.

## 2. Copy only the content tools

Copy these files from `export/`, preserving their relative paths in your site:

| File | Purpose |
| --- | --- |
| `app/Console/Commands/BuildAgentCatalog.php` | Generate the content reference |
| `app/Console/Commands/ValidateContent.php` | Validate the site's content |
| `config/agentic.php` | Choose catalogue sources and rendering checks |

These commands use Statamic and Laravel, already installed in your site. Don't copy the kit's
root `composer.json` or install its test dependencies. Standard Laravel command discovery finds
commands in `app/Console/Commands`; if your application customizes command discovery, register
the two classes through your existing mechanism. Verify discovery with `php artisan list`.

**Leave these untouched:** your content, blueprints, fieldsets, views, CSS, JavaScript,
`package.json`, Vite config, `AppServiceProvider.php`, and Statamic asset/git config. The asset
classes, responsive-image tag, and compression command are not needed by these two content tools.

## 3. Select existing blueprints

In `config/agentic.php`, set `catalog_blueprints` to the fully qualified handles you want documented:

```php
'catalog_blueprints' => [
    'collections.news.article',
    'globals.company',
    'taxonomies.topics',
    'navigation.footer',
],

// Your templates don't use the starter's blocks/<set-handle> convention.
'blocks_view_path' => null,
```

Use handles that actually exist in your site. For example,
`resources/blueprints/collections/news/article.yaml` is `collections.news.article`. Add each
blueprint you want included; selecting one does not automatically include other blueprints or
the targets of relationships. The command fails without replacing the previous catalogue if a
selected blueprint is missing. It resolves imported fieldsets using Statamic's own field APIs.

The reference includes field types, required markers, option keys, instructions, configured
relationship targets, and nested Replicator/Bard sets and Group/Grid fields. It doesn't infer
custom-field storage formats, routing, publishing policy, or who is allowed to edit what.

**This list is documentation scope, not an access-control list.** It neither restricts editable
paths nor narrows validation: `content:validate` continues to check the whole site's content.
Keep permissions in the path policy and your protected review/deployment gates. Never add user
accounts, secrets, form submissions, or application configuration to the content allowlist.

`blocks_view_path: null` skips only the starter's partial-filename check. Blueprint rules,
existing structural checks, asset checks, and navigation-reference checks still run. It does
not make arbitrary custom fieldtypes or nested storage formats fully supported: review their
rules and test representative content before handing over.

Keep `committed_asset_containers` limited to asset containers whose files are available in the
repository/CI environment. Remote-only containers should not be in that list. If your local asset
path differs from `public/assets/`, update the path policy and editing brief to match it.

### If you want the original page-builder catalogue

Leave `catalog_blueprints` as `[]`. The existing starter mode remains the default. A fieldset and
field no longer need to share a handle:

```php
'page_builder_fieldset' => 'layout',
'page_builder_field' => 'sections',
'blocks_view_path' => 'components/sections',
```

That example checks for `resources/views/components/sections/<set-handle>.antlers.html` (also
accepting an underscore-prefixed Antlers partial or a `.blade.php` partial). Both catalogue
generation and the partial check use the selected field. Leaving `page_builder_field` null uses
the fieldset handle, exactly as before. Blueprint catalogue mode does not automatically change
the rendering check; explicitly choose a path or null.

## 4. Write the site's editing brief

Copy `export/content/AGENTS.md` and, if desired, `export/content/editor-notes.md` as starting points.
**Customize the brief before letting an editor use it.** The shipped brief assumes the starter's
`pages` collection, `page` blueprint, page tree, and `main` navigation. Do not keep those instructions
if they don't describe your site.

Document these decisions explicitly:

- Which collections, globals, terms, menus, and assets the editor may change.
- Existing example entries to follow, supported field storage formats, and custom-field guidance.
- Whether creating/deleting entries is allowed; which blueprint, slug/date conventions, IDs,
  collection trees, and navigation steps apply. If creation isn't documented, tell the editor to
  ask rather than guess.
- Whether local PHP validation is available or checks run remotely. A broken local installation
  should be fixed, not treated as permission to bypass validation.
- Preview and release branches, preview URL, and the exact save/review/publish procedure.

The catalogue supplies schema facts; the developer supplies permissions and intent. Neither setup
nor discovery needs an AI model to infer these decisions.

## 5. Integrate the editor and checks

For Claude Code, merge the relevant parts of `export/.claude/settings.json`,
`export/.claude/hooks/`, and `export/CLAUDE.md` into your existing configuration. Preserve your own
hooks and instructions; don't replace them wholesale. The optional `/setup` command in
`export/.claude/commands/setup.md` helps editors record their writing preferences.

Review the path allowlist against your site. The supplied local edit hook uses PHP, so install PHP
on editor machines that need this hook; a no-runtime editor setup does not provide the same local
enforcement. For another agent, point it at the brief and supply that tool's enforcement integration;
reading instructions alone is not an access-control boundary.

For GitHub, adapt or merge `export/.github/workflows/content-guardrails.yml`, rather than replacing
your CI. Set branch names consistently in triggers, conditions, the editing brief, permissions,
and onboarding. Stamp maintainers manually, or also copy `AgenticSetup.php` and `ONBOARDING.md`
and run `php artisan agentic:setup` after preserving its marker sections. Setup stamps marked
text and maintainer values; it does **not** synchronize every literal branch reference.

Keep your existing frontend build and deployment process. The content-specific checks to add are:

```sh
php artisan statamic:stache:refresh
php artisan content:catalog
git diff --exit-code -- content/agent-reference.md
php artisan content:validate
```

Run the catalogue freshness check in CI after committing a newly generated reference. If you
change `catalog_path`, update the freshness command, brief, and protected path policy too. Those
paths are not synchronized automatically.

Require successful checks and authorized review before production deployment. A post-push check
does not block a push or stop an independent auto-deploy. The shipped workflow's maintainer-author
email exemption and `[BOT]` validation skip are conventions, not proof of a trusted identity;
don't rely on either as an authorization boundary. Use protected, trusted review and deployment
gates appropriate to your repository. Keep edits to policy and workflow files developer-only.

## 6. Verify before hand-over

Generate and review the reference, then validate the existing content. Resolve reported problems
before allowing content edits. In your preview/test environment:

1. Make a valid edit to each selected content type; confirm validation and rendering succeed.
2. Remove a required value or use an invalid option in a representative field; confirm the checks
   fail, then restore the valid content. Test custom and nested fields explicitly.
3. Confirm the local hook rejects a template edit and the brief/catalogue remain protected.
4. Confirm required checks and review prevent unapproved production deployment.
5. Review the integration diff: your frontend, providers, asset pipeline, schemas, and original
   content should be unchanged except for deliberate integration edits and documentation.

Keep the integration in version control. This is a copied-code integration, not a managed addon:
future kit updates must be reviewed and merged. The normal starter installation, default
catalogue, and shared-preview publishing flow do not change when this feature is unused.
