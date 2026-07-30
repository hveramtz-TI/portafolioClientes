# Project Agents — portafolioClientes (Expo)

## Code Quality (MANDATORY)

Before writing or modifying code, apply the `code-audit` skill principles:

- **SOLID**: Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion.
- **DRY**: No duplicated logic. Extract shared functions.
- **KISS**: Prefer the simplest solution that works. No over-engineering.
- **YAGNI**: No speculative code. Build only what is needed now.
- **Naming**: Intention-revealing, pronounceable, searchable identifiers.
- **Small functions**: One responsibility per function. Extract when it grows.
- **Error handling**: Never swallow errors silently. Log or rethrow with context.
- **Composition over inheritance**: Prefer composition for code reuse.
- **Testability**: Inject dependencies, prefer pure functions, avoid global state.

Run a mental audit against these principles before committing. If code violates any, fix it before moving on.

## Documentation Lookup (MANDATORY)

When unsure about an API, library behavior, configuration, or framework feature:

1. **Use Context7 MCP first**: call `context7_resolve-library-id` to get the library ID, then `context7_query-docs` for the specific concept. This gives up-to-date, version-accurate documentation.
2. **Fallback**: use `webfetch` to read the official docs (e.g., docs.expo.dev, reactnative.dev, nodejs.org).

Do NOT guess APIs, props, or configuration options. Verify against docs before using them.

## Expo / React Native Specifics

- This is an Expo project. Follow Expo conventions and SDK patterns.
- Use Expo Router for navigation unless explicitly told otherwise.
- Prefer Expo managed workflow APIs over bare React Native when both exist.
- Check `app.json` / `app.config.js` for project configuration before making assumptions.
