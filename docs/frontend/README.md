# Frontend

**Stack:** Next.js 16 · React 19 · Tailwind CSS 4 · TypeScript

## Scripts

```bash
npm run dev          # Servidor de desarrollo
npm run build        # Build de producción
npm run lint         # ESLint
npm test             # Tests (Jest)
npm run test:watch   # Tests en modo watch
npm run test:coverage # Tests con cobertura
```

## Estructura

```
frontend/src/
├── app/             # App Router (rutas)
│   ├── layout.tsx
│   ├── page.tsx
│   └── globals.css
└── public/          # Assets estáticos
```

## Testing

- **Framework:** Jest + React Testing Library
- **Config:** `jest.config.ts` (usa `next/jest`)
- **Setup:** `jest.setup.ts`

## Notas

- App Router (Next.js 16)
- Tailwind CSS 4 (PostCSS)
- TypeScript estricto
