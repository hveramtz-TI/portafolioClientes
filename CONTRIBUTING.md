# Contributing Guidelines

## Branching Strategy

### Main Branches
- `main` - Production-ready code (stable)
- `development` - Integration branch for features

### Feature Branches
- `feature/[nombre]` - New features
- `bugfix/[nombre]` - Bug fixes
- `hotfix/[nombre]` - Emergency fixes for production

### Branch Naming Convention
```
feature/add-tipo-cliente
feature/contact-synchronization
bugfix/fix-phone-normalization
hotfix/critical-security-fix
```

## Workflow

### 1. Create Feature Branch
```bash
git checkout development
git pull origin development
git checkout -b feature/[nombre]
```

### 2. Development
- Make your changes
- Write tests
- Ensure coverage > 80%
- Run `npm test` locally
- Run `npm run lint` locally

### 3. Create Pull Request
- Push your branch: `git push origin feature/[nombre]`
- Create PR targeting `development`
- Fill PR template with:
  - Description of changes
  - Testing performed
  - Screenshots (if UI changes)

### 4. Automated Validation
PR will automatically run:
- ✅ ESLint (no warnings)
- ✅ TypeScript compilation
- ✅ All tests passing
- ✅ Coverage > 80%
- ✅ Build successful

### 5. Auto-Merge
If all validations pass:
- PR is automatically merged to `development`
- Branch is deleted automatically

## PR Requirements

### Before Creating PR
- [ ] Code follows project conventions
- [ ] Tests written and passing
- [ ] Coverage > 80%
- [ ] No ESLint warnings
- [ ] TypeScript compiles without errors
- [ ] Build succeeds
- [ ] Documentation updated (if needed)

### PR Checklist
- [ ] Descriptive title
- [ ] Clear description of changes
- [ ] Related issues linked
- [ ] Breaking changes documented
- [ ] Migration notes (if DB changes)

## Code Review

### Reviewers
- At least 1 approval required (when team grows)
- Auto-merge enabled for passing PRs

### Review Focus
- Code quality and SOLID principles
- Test coverage
- Performance implications
- Security considerations
- Documentation

## Release Process

### Development → Main
1. Create PR from `development` to `main`
2. Manual review required
3. All checks must pass
4. Merge with merge commit (not squash)
5. Tag release: `v1.x.x`

### Hotfixes
1. Create `hotfix/[nombre]` from `main`
2. Fix issue
3. Create PR to `main` (urgent review)
4. Merge and tag
5. Cherry-pick to `development`

## Commit Messages

Follow Conventional Commits:
```
feat: add tipo field to Cliente
fix: correct phone normalization logic
docs: update README with setup instructions
refactor: extract navigation logic
test: add tests for contact sync
chore: update dependencies
```

## Testing Requirements

### Coverage Thresholds
- Line coverage: > 80%
- Branch coverage: > 80%
- Function coverage: > 80%

### Test Types
- Unit tests for business logic
- Integration tests for repositories
- E2E tests for critical flows (future)

### Running Tests
```bash
# All tests
npm test

# With coverage
npm test -- --coverage

# Specific file
npm test -- domain/validators.test.ts

# Watch mode
npm test -- --watch
```

## Environment Setup

### Prerequisites
- Node.js 20+
- npm 10+
- Expo CLI

### Installation
```bash
cd portafolioClientesApp
npm install
```

### Development Server
```bash
# Web
npm run web

# iOS
npm run ios

# Android
npm run android
```

## Questions?

- Check existing issues first
- Create new issue for bugs/features
- Use discussions for questions

---

*Last updated: 2026-07-27*
*Pipeline test: v2*
