# Agentic

**Let your client edit their Statamic site by talking to an AI agent, without letting it break
anything.**

Agentic is a Statamic starter kit. You build the site on a replicator page builder, the block-based
setup many Statamic sites already use. Your client describes changes in plain language to Claude
Code, the agent edits the content, and a preview URL updates. When they say "publish", you get one
pull request to approve.

## What that looks like

> **Client:** "Here's an overview doc for our six services. Set up a page for each one, in our tone."

The agent looks up which content blocks the site actually has, builds the six pages out of them,
writes the copy in the client's own voice (it keeps a notes file on their tone and preferences),
checks its work, and pushes to the preview site. The client reviews all six at once, says "looks
good, publish it", and one pull request lands on your desk.

For a one-line tweak, the Control Panel is already fine and Agentic isn't trying to replace it.
Where it earns its keep is volume and structure: redoing a whole page, drafting a batch of posts, a
site-wide tone pass, "here are three new team members, add them to the team page in our usual style".

## Why it's safe to hand over

![A man in a 90s office, on the phone, looking worried](art/client-on-phone.jpg)

*Your client, ten minutes after you gave them repo access and their agent broke the website.
Agentic exists so this call never happens.*

- **The agent can only touch content.** Code, config, templates and CI are blocked, both on the
  editor's machine and on GitHub's side. It's default-deny, not a polite request.
- **Broken content can't go live.** Every change is validated against your blueprints, using the
  same rules a Control Panel save enforces, before it's committed and again on every push.
- **The agent knows your site.** A catalogue of every available block and field is generated from
  your own fieldsets, so it can't invent a block or field that doesn't exist.
- **You own production.** The client's agent only pushes to the preview branch. Nothing reaches the
  live site except through a pull request you merge.

## Getting started

    statamic new my-site sanderjn/statamic-agentic

…or in an existing Statamic project:

    php please starter-kit:install sanderjn/statamic-agentic

Then follow **`SETUP.md`** in your new site: one setup command, a repo with two branches, your host,
and one email to hand over to the editor.

- Statamic v6, PHP 8.3+, single locale (multilingual is on the roadmap).
- Deployment is your choice: Ploi, Forge, Vapor, SSG. Agentic sets up the repo, branches and CI, not
  the host.
- Your editor needs a GitHub account and their own paid Claude Code account. Flag that cost up front.

## For developers

The kit has exactly one opinion: pages are built from blocks, the sets of a single replicator
fieldset (`resources/fieldsets/page_builder.yaml`). Add a set, add a matching
`resources/views/blocks/<handle>.antlers.html` (or `.blade.php`), regenerate the catalogue. The
agent's reference, the validation, the Control Panel and the front-end rendering all derive from
that one file: no switch statement, no second registry. Everything else is yours. The kit ships one example block and a
minimal layout; design the blocks, templates and front end however you like and the agentic layer
rides along.

**Antlers or Blade.** The agent only edits content, so the kit doesn't care how you render it. The
shipped views are Antlers; if you work in Blade, replace the five small views in `resources/views/`
and loop the page builder like this:

```blade
@foreach ($page_builder as $set)
    @include('blocks.' . $set->type)
@endforeach
```

Each block partial then reads its fields from `$set` (e.g. `{!! $set->body !!}`).

The edit guard also applies to your own Claude Code sessions. Set `AGENTIC_DEVELOPER=1` (via `env`
in an untracked `.claude/settings.local.json`) to lift it while you build.

**[How it works →](https://github.com/sanderjn/statamic-agentic/blob/main/docs/how-it-works.md)**
What the agent reads, how the guardrails and the validator work, and how changes go live.

## License

MIT: use it, strip it down, rebuild it however you like. If you improve it, or learn something worth
sharing from running it for a client, open an issue or PR.
