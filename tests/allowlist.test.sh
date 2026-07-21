#!/usr/bin/env bash
# Exercises the content-path allowlist (export/.claude/hooks/guard-content-paths.sh),
# the single source of truth the CI path-allowlist job and the local editor share.
# Plain bash so it runs with no framework: `bash tests/allowlist.test.sh`.
set -uo pipefail

script="$(cd "$(dirname "$0")/.." && pwd)/export/.claude/hooks/guard-content-paths.sh"
pass=0
fail=0

check() {
  local expected="$1" path="$2"
  printf '%s' "$path" | bash "$script" >/dev/null 2>&1
  local actual=$?
  # Normalise any non-zero exit to 1 for comparison.
  [ "$actual" -ne 0 ] && actual=1
  if [ "$actual" -eq "$expected" ]; then
    pass=$((pass + 1))
  else
    fail=$((fail + 1))
    echo "FAIL: '$path' expected exit $expected, got $actual"
  fi
}

# Allowed: editable content and assets.
for p in \
  "content/collections/pages/home.md" \
  "content/collections/blog/nested/post.md" \
  "content/globals/site.yaml" \
  "content/taxonomies/tags/news.md" \
  "content/navigation/main.yaml" \
  "content/trees/collections/pages.yaml" \
  "content/assets/photo.jpg.meta.yaml" \
  "public/assets/img/photo.jpg" \
  "content/editor-notes.md"; do
  check 0 "$p"
done

# Denied: code, config, build files, collection config, and the agent's own
# brief + generated catalogue (it doesn't rewrite its own rules).
for p in \
  "content/AGENTS.md" \
  "content/agent-reference.md" \
  "content/collections/pages.yaml" \
  "content/collections/blog.yaml" \
  "app/Console/Commands/ValidateContent.php" \
  "resources/views/layout.antlers.html" \
  "resources/fieldsets/page_builder.yaml" \
  "config/agentic.php" \
  ".github/workflows/content-guardrails.yml" \
  ".claude/settings.json" \
  "composer.json" \
  "package.json" \
  "vite.config.js" \
  "README.md"; do
  check 1 "$p"
done

# The PreToolUse adapter (guard-content-edits.sh) unwraps the hook JSON and
# feeds the path through the same allowlist: exit 0 allowed, exit 2 blocked.
adapter="$(cd "$(dirname "$0")/.." && pwd)/export/.claude/hooks/guard-content-edits.sh"
export CLAUDE_PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)/export"

check_adapter() {
  local expected="$1" path="$2"
  printf '{"tool_input":{"file_path":"%s"}}' "$path" | bash "$adapter" >/dev/null 2>&1
  local actual=$?
  if [ "$actual" -eq "$expected" ]; then
    pass=$((pass + 1))
  else
    fail=$((fail + 1))
    echo "FAIL (adapter): '$path' expected exit $expected, got $actual"
  fi
}

check_adapter 0 "content/collections/pages/about.md"
check_adapter 2 "app/Something.php"
check_adapter 2 "content/AGENTS.md"

echo "allowlist: $pass passed, $fail failed"
[ "$fail" -eq 0 ]
