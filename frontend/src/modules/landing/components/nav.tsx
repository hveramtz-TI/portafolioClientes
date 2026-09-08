import Link from 'next/link';
import { CtaButton } from './cta-button';

const anchors = [
  { href: '#que-hace', label: 'Qué hace' },
  { href: '#editorial', label: 'Por qué TMC' },
  { href: '#comenzar', label: 'Comenzar' },
];

export function LandingNav() {
  return (
    <header className="sticky top-4 z-50 px-4">
      <nav
        aria-label="Navegación principal"
        className="mx-auto flex max-w-[1200px] items-center justify-between rounded-full bg-tmc-surface px-6 py-3 text-tmc-ink"
      >
        <Link
          href="/"
          className="flex items-center gap-2 rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-tmc-accent"
        >
          <span className="text-base font-medium" style={{ fontWeight: 500 }}>
            TMC
          </span>
          <span className="sr-only">That&apos;s My Client</span>
        </Link>

        <div className="hidden items-center gap-6 md:flex">
          {anchors.map((anchor) => (
            <a
              key={anchor.href}
              href={anchor.href}
              className="rounded-sm text-sm font-medium text-tmc-ink/75 transition-colors hover:text-tmc-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-tmc-accent"
            >
              {anchor.label}
            </a>
          ))}
        </div>

        <CtaButton href="/login" variant="light" size="sm">
          Ingresar
        </CtaButton>
      </nav>
    </header>
  );
}
