---
description: Verify both stacks build cleanly (Next.js + Laravel).
---

Verify the project builds cleanly.

1. Frontend: run `npm run build` in `frontend/`. Any TypeScript or build errors are failures.
2. Backend: run `docker compose exec backend php artisan route:list` and `composer validate` (in `backend/`) to catch route/config/autoload errors.
3. Report results; on failure give the exact error and a fix.
