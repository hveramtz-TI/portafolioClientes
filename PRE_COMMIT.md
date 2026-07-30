# Pre-Commit Hook

Este proyecto tiene un pre-commit hook configurado que valida la calidad del código antes de cada commit.

## Qué valida

El pre-commit hook ejecuta las mismas validaciones que el pipeline de GitHub Actions:

1. **ESLint** - Verifica que no haya errores de linting
2. **TypeScript** - Verifica que el código compile sin errores
3. **Tests** - Ejecuta todos los tests
4. **Coverage** - Verifica que la cobertura sea >80%

## Ubicación

El hook está en `.git/hooks/pre-commit` y se ejecuta automáticamente antes de cada commit.

## Cómo funciona

```bash
# Al hacer git commit, el hook se ejecuta automáticamente
git commit -m "feat: add new feature"

# Si todas las validaciones pasan:
✅ ESLint passed
✅ TypeScript check passed
✅ Tests passed
✅ Coverage is above 80%: 95.15%
🎉 All pre-commit checks passed!

# Si alguna validación falla, el commit se rechaza
❌ Coverage is below 80%: 75%
Please add more tests to meet the coverage requirement.
```

## Coverage

La cobertura se mide para:
- ✅ `src/domain/` - Lógica de negocio
- ✅ `src/application/` - Casos de uso
- ✅ `src/infrastructure/` - Implementaciones

Se excluye del coverage:
- ❌ `src/presentation/` - UI (React components)
- ❌ `src/app/` - Rutas de Expo Router
- ❌ `src/constants/` - Constantes
- ❌ `src/hooks/` - Custom hooks

## Tests

Actualmente hay **123 tests** con **95.15% de coverage**.

### Estructura de tests

```
__tests__/
├── domain/
│   ├── result.test.ts
│   └── validators.test.ts
├── application/
│   └── usecases/
│       ├── cliente.test.ts
│       └── perfil.test.ts
├── infrastructure/
│   ├── connection.test.ts
│   ├── migrations.test.ts
│   ├── seeders.test.ts
│   ├── sqlite-cliente-repository.test.ts
│   └── sqlite-perfil-repository.test.ts
└── presentation/
    ├── navigation-items.test.ts
    └── sidebar-path.test.ts
```

## Ejecutar manualmente

```bash
# Ejecutar pre-commit manualmente
.git/hooks/pre-commit

# O ejecutar las validaciones por separado
cd portafolioClientesApp
npm run lint
npm run test:coverage
npx tsc --noEmit
```

## Agregar nuevos tests

Cuando agregues nueva lógica de negocio:

1. **Domain**: Agrega tests en `__tests__/domain/`
2. **Application**: Agrega tests en `__tests__/application/usecases/`
3. **Infrastructure**: Agrega tests en `__tests__/infrastructure/`

Ejemplo:

```typescript
// __tests__/domain/my-new-validator.test.ts
import { describe, it, expect } from '@jest/globals';
import { myNewValidator } from '@/domain/validators';

describe('myNewValidator', () => {
  it('should validate correctly', () => {
    expect(myNewValidator('valid')).toBe(true);
    expect(myNewValidator('invalid')).toBe(false);
  });
});
```

## Bypass (no recomendado)

Si necesitas hacer commit sin pasar las validaciones (no recomendado):

```bash
git commit --no-verify -m "hotfix: emergency fix"
```

**Advertencia**: Si haces esto, el pipeline de GitHub probablemente fallará.

## Troubleshooting

### "bc: orden no encontrada"

Si ves este error, es porque `bc` no está instalado. El hook usa `awk` como alternativa, así que no debería ser un problema.

### "Coverage is below 80%"

Agrega más tests para la lógica que agregaste. Enfócate en:
- Casos de uso (application/)
- Validadores (domain/)
- Repositorios (infrastructure/)

### "ESLint failed"

Ejecuta `npm run lint:fix` para arreglar errores automáticamente.
