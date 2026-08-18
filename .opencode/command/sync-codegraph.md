---
description: Sync the CodeGraph index after code changes.
---

Sync the CodeGraph knowledge graph so the AI sees the latest code.

1. From the repo root run `codegraph sync`, then `codegraph status` to confirm "Index is up to date".
2. If sync reports stale files or errors, report them. Do not run `codegraph index` unless the user explicitly asks.
3. Pass through `$ARGUMENTS` (e.g. `--quiet`) if provided.
