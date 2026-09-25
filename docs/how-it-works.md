# How Agentic works

Statamic content is **flat files**: YAML and Markdown under `content/`. An LLM is good at rewriting
plain text, so an agent can edit that content directly. Two things make it trustworthy:

1. The agent knows exactly what it's allowed to do.
2. Nothing it produces can reach production without passing automated checks.

## What the agent reads

- **`content/AGENTS.md`**: the agent's brief, in plain language. You are a content editor, not a
  developer; only edit content, never code; here is what you may and may not touch; run
  `content:validate` before you commit; here's how "publish" works. The editor never sees YAML, a
  branch, or a blueprint.
- **`content/agent-reference.md`**: an **auto-generated** catalogue of every page-builder block and
  its fields, types, and allowed options. The agent reads it before adding content, so it never
  invents a block or field that doesn't exist. Regenerate it with `php artisan content:catalog`
  whenever you add or change a block. It's a build step, not automatic, but it can't drift
  silently: CI regenerates the catalogue on every push and fails if the committed copy is stale.
- **`content/editor-notes.md`**: the editor's **own** space for tone of voice, writing do's and
  don'ts, recurring page structures, and sign-off. In the first session the agent offers to fill it
  in (a short interview, or `/setup` in Claude Code), then reads it before every edit. Unlike the
  brief and the catalogue, the agent may edit this file: it holds the client's preferences, not the
  rules. The agent can never rewrite its own brief.

The shipped root `CLAUDE.md` imports `content/AGENTS.md`, so Claude Code picks up the brief
automatically. With other agents, point them at `content/AGENTS.md` yourself.

## What catches mistakes

Statamic does **not** validate flat-file content when it loads: a malformed edit renders wrong or
blank, silently. That's the gap Agentic closes.

**`php artisan content:validate`** first parses every content file and reports YAML errors
(duplicate keys and the like) per file. Then it walks every entry, term, global and navigation
against its blueprint and fails on anything the Control Panel would never allow: a missing required
field, a value past its length limit, an unknown block type, an invalid select option, a reference
to a missing image, or a block with no matching template. It runs the blueprint's real validation
rules, exactly as a Control Panel save would. The agent runs it before every commit; CI runs it
again.

## What stops it going off the rails

Guardrails at two levels, one for a smooth experience and one that's the actual guarantee.

**Local (experience).** A Claude Code **PreToolUse hook** runs every edit through the *same*
path-allowlist script CI uses. If the agent tries to write outside content (`app/`, `config/`,
`resources/`, dependencies, CI), the edit is blocked and the reason goes back to the agent, so it
corrects itself instead of looking for a workaround. `settings.json` also allow-lists the handful of
commands the brief needs (validate, catalogue, the git steps), so the editor isn't flooded with
permission prompts.

**CI (the guarantee).** `.github/workflows/content-guardrails.yml` enforces on GitHub's side:

- Every commit on `staging` may touch **only** content and assets, *unless* a maintainer authored
  it. It's default-deny: the agent's edits are held to the content allowlist whatever git identity
  they carry.
- Only a **maintainer** can land changes on `main`.
- Every push runs `content:validate`, a catalogue-freshness check, a build, and a code-style check.

So even if the agent goes rogue or someone hand-edits a file, a code-touching or schema-invalid
change can't land cleanly on `staging` and can't reach production.

If editors also use the Control Panel with Statamic's git automation, every save is its own commit.
Those commits are prefixed `[BOT]` and skip the full validate-and-build job, so a busy editing day
doesn't use up your GitHub Actions minutes. The path allowlist still checks every commit, and the
next agent or developer push validates all content again.

The guardrail machinery itself (the commands, the validator, the path allowlist) is covered by the
kit's own test suite: see `tests/` and `.github/workflows/ci.yml`.

## How changes go live

- **Day to day:** the agent commits and pushes to `staging`, which redeploys to a preview URL. Many
  edits, no ceremony. The editor reviews on the preview site.
- **Publishing:** when the editor says "publish" or "make it live", the agent opens **one** pull
  request from `staging` to `main`. You review and merge it. Production only ever changes through a
  reviewed PR.

A typical exchange, in full: the editor asks for six service pages from an overview doc. The agent
switches to `staging` and pulls, checks `content/agent-reference.md` for the available blocks and
their fields, and builds the six pages from those blocks: it copies an existing page as a starting
point, wires each page into the page tree and writes the copy in the site's voice. It runs
`content:validate`, commits, pushes to `staging` and shares the preview link. Later the editor says
"looks good, publish it" and the agent opens one PR.

## Adding a block

Blocks are defined in one place, the `page_builder` fieldset, and everything else derives from it:

1. Add a `set` to `resources/fieldsets/page_builder.yaml`.
2. Add `resources/views/blocks/<set-handle>.antlers.html`, or `.blade.php` if you work in Blade.
   **The filename must equal the set handle.**
3. Run `php artisan content:catalog` and `php artisan content:validate`.

That's the whole contract. The agent's catalogue, the validation rules, the Control Panel and the
front-end rendering all follow from the fieldset. The kit ships one example block (`rich_text`) to
show the pattern: delete it and add your own.

## Handing over to your editor

The default setup: the editor installs Claude Code and nothing else. Their agent edits the
flat-file content and pushes to `staging`, your deploy builds and publishes it, and the editor
reviews on the preview URL. Because you own the deployment, everything technical (the front-end
build, the content-index refresh) stays on your side.

You *can* give the editor a local setup instead, for instant validation or a localhost preview that
doesn't wait on a deploy, but that puts more on their machine.

Either way, hand-over is two prerequisites: the client's own GitHub account with push access, and
Claude Code signed in on their own paid account. Then one email: the stamped prompt from
`ONBOARDING.md`. Their agent installs `gh`, walks them through the GitHub sign-in, clones the repo,
sets a git identity that stays off the maintainer list, and verifies push access. `SETUP.md` walks
through it tier by tier.

## Working on the site yourself

You work on `staging` and release through the same PR flow as the agent. Merge those PRs with a
merge commit, not squash, so `staging` and `main` don't diverge (SETUP.md step 5 has the details).
