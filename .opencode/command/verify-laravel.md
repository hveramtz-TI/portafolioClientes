---
description: Run the Laravel (PHPUnit) test suite in Docker and verify it passes.
---

Run the Laravel PHPUnit suite (backend runs in Docker) and verify it passes.

1. Check Docker services: `docker compose ps --services` must list `backend`. If not, run `docker compose up -d` and wait for it to be healthy.
2. Run `docker compose exec backend php artisan test` (append `$ARGUMENTS`, e.g. a test path or `--filter=<name>`).
3. PostgreSQL variant: `./test-pg.sh $ARGUMENTS`.
4. Report suites/tests/failures; on failure, root cause and a fix proposal.
