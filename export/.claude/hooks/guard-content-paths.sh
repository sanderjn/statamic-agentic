#!/usr/bin/env bash
# Single source of truth for the content-path allowlist. Reads a path on stdin;
# exits 0 for editable content/asset paths, non-zero otherwise. The CI
# path-allowlist job pipes every changed file through this script, and the kit's
# test suite exercises it directly, so the rule lives in exactly one place. The
# local Claude Code PreToolUse hook (guard-content-edits.sh) also pipes each
# edited path through here for live enforcement on the editor's machine.
set -euo pipefail
read -r file || true
case "$file" in
  content/collections/*/*|content/globals/*|content/taxonomies/*|content/navigation/*|content/trees/*|content/assets/*|public/assets/*|content/editor-notes.md)
    exit 0 ;;
  content/AGENTS.md|content/agent-reference.md)
    echo "Blocked: $file is the agent's own brief/catalogue — you don't rewrite your own rules. Save preferences in content/editor-notes.md instead." >&2
    exit 1 ;;
  content/collections/*.yaml)
    echo "Blocked: $file is collection config (routes/structure), not content." >&2
    exit 1 ;;
  *)
    echo "Blocked: $file is outside the content allowlist — that's a developer job." >&2
    exit 1 ;;
esac
