import Link from 'next/link';

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
        className="mx-auto flex max-w-[1200px] items-center justify-between rounded-full bg-tmc-ink px-6 py-3 text-tmc-canvas"
      >
        <Link href="/" className="flex items-center gap-2">
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
              className="text-sm font-medium text-tmc-canvas/80 transition-colors hover:text-tmc-canvas"
            >
              {anchor.label}
            </a>
          ))}
        </div>

        <Link
          href="/login"
          className="rounded-full bg-tmc-canvas px-4 py-2 text-sm font-medium text-tmc-ink transition-colors hover:bg-tmc-neutral-2"
        >
          Ingresar
        </Link>
      </nav>
    </header>
  );
}
