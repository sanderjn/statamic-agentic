#!/usr/bin/env bash
# PreToolUse adapter: extracts the edited file's path from the hook JSON on
# stdin and applies the content-path allowlist (guard-content-paths.sh).
# Exit 2 blocks the edit and feeds the reason back to the agent.
# Developers: set AGENTIC_DEVELOPER=1 (e.g. via "env" in
# .claude/settings.local.json) to lift this local guard on your own machine —
# maintainer commits are exempt from the CI allowlist anyway.
set -uo pipefail
[ "${AGENTIC_DEVELOPER:-0}" = "1" ] && exit 0
root="${CLAUDE_PROJECT_DIR:-$PWD}"
file="$(php -r '$i=json_decode(stream_get_contents(STDIN),true);echo $i["tool_input"]["file_path"] ?? "";' 2>/dev/null)"
[ -z "$file" ] && exit 0
case "$file" in "$root"/*) file="${file#"$root"/}" ;; esac
if msg="$(printf '%s' "$file" | bash "$root/.claude/hooks/guard-content-paths.sh" 2>&1)"; then
  exit 0
fi
echo "$msg" >&2
exit 2
